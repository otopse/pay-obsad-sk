<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
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

$rawBody = (string) file_get_contents('php://input');
$headers = [];
foreach ($_SERVER as $k => $v) {
    if (str_starts_with($k, 'HTTP_')) {
        $headers[$k] = $v;
    }
}

$result = $paymentService->getProvider()->handleNotify($rawBody, $headers);

if (!$result->success || $result->publicId === null) {
    $log->error('pay-notify verify failed', ['result' => 'invalid']);
    http_response_code(400);
    echo 'Invalid notify';
    exit;
}

$payment = $paymentService->findByPublicId($result->publicId);
if ($payment === null) {
    $log->error('pay-notify payment not found', ['public_id' => $result->publicId, 'result' => 'not_found']);
    http_response_code(404);
    echo 'Not found';
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

$log->info('pay-notify updated', ['public_id' => $result->publicId, 'status' => $status, 'result' => 'ok']);
http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
echo 'OK';
