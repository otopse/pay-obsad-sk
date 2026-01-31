<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo 'OK';
    exit;
}

$notifyToken = $config->get('NOTIFY_TOKEN');
if ($notifyToken !== null && $notifyToken !== '') {
    $headerToken = $_SERVER['HTTP_X_NOTIFY_TOKEN'] ?? $_SERVER['REDIRECT_HTTP_X_NOTIFY_TOKEN'] ?? '';
    $queryToken = $_GET['token'] ?? $_POST['token'] ?? '';
    if ($headerToken !== $notifyToken && $queryToken !== $notifyToken) {
        $log->error('pay-notify forbidden', ['result' => 'invalid_token']);
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

$mode = $paymentService->getPaymentMode();

if ($mode === 'fake') {
    $log->info('pay-notify fake', ['result' => 'no-op']);
    echo 'OK';
    exit;
}

$rawBody = (string) file_get_contents('php://input');
$payloadHash = hash('sha256', $rawBody);
$headers = [];
foreach ($_SERVER as $k => $v) {
    if (str_starts_with($k, 'HTTP_')) {
        $headers[$k] = $v;
    }
}

$data = [];
if ($rawBody !== '') {
    $decoded = json_decode($rawBody, true);
    $data = is_array($decoded) ? $decoded : [];
}
$publicId = $data['order_id'] ?? $data['public_id'] ?? null;
$providerRef = $data['transaction_id'] ?? $data['provider_ref'] ?? null;

$payment = null;
if ($publicId !== null && $publicId !== '') {
    $payment = $paymentService->findByPublicId((string) $publicId);
}
if ($payment === null && $providerRef !== null && $providerRef !== '') {
    $payment = $paymentService->findByProviderRef((string) $providerRef);
}

if ($payment === null) {
    $log->info('pay-notify payment not found', ['public_id' => $publicId, 'provider_ref' => $providerRef, 'result' => 'not_found']);
    echo 'OK';
    exit;
}

$finalStatuses = ['paid', 'cancelled'];
if (in_array($payment->status, $finalStatuses, true)) {
    $log->info('pay-notify duplicate notify', ['public_id' => $payment->publicId, 'reason' => 'final_status', 'result' => 'duplicate']);
    echo 'OK';
    exit;
}

try {
    if ($paymentService->notifyHashExists($payloadHash)) {
        $log->info('pay-notify duplicate notify', ['public_id' => $payment->publicId, 'reason' => 'payload_hash', 'result' => 'duplicate']);
        echo 'OK';
        exit;
    }
} catch (\Throwable $e) {
    $log->warning('pay-notify notify_log check failed', ['message' => $e->getMessage()]);
}

$ecardVerify = new \App\EcardVerify($config, $log);
if (!$ecardVerify->verifyEcardSignature($rawBody, $headers)) {
    $log->error('pay-notify signature invalid', ['public_id' => $payment->publicId, 'result' => 'invalid_signature']);
    echo 'OK';
    exit;
}

$result = $paymentService->getGateway()->handleNotify($rawBody, $headers);
if (!$result->success || $result->publicId === null) {
    $log->error('pay-notify verify failed', ['public_id' => $payment->publicId, 'result' => 'invalid']);
    echo 'OK';
    exit;
}

$status = $result->status ?? 'paid';
if ($status === 'paid') {
    $paymentService->markPaid($result->publicId, $result->providerRef, $rawBody);
} elseif ($status === 'failed') {
    $paymentService->markFailed($result->publicId, $result->providerRef, $rawBody);
} else {
    $paymentService->markCancelled($result->publicId);
}

try {
    $paymentService->recordNotify($result->publicId, 'ipn', $payloadHash, $rawBody);
} catch (\Throwable $e) {
    $log->warning('pay-notify recordNotify failed', ['public_id' => $result->publicId, 'message' => $e->getMessage()]);
}

$log->info('pay-notify updated', ['public_id' => $result->publicId, 'status' => $status, 'result' => $status === 'paid' ? 'paid' : ($status === 'failed' ? 'error' : 'cancel')]);
echo 'OK';
