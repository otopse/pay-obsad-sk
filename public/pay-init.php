<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: index.php');
    exit;
}

$clientsMode = $config->get('CLIENTS_MODE');
$clientsModeOn = $clientsMode === 'on' || $clientsMode === '1' || $clientsMode === true;
if ($clientsModeOn) {
    $clientId = trim((string) ($_POST['client_id'] ?? ''));
    $ts = trim((string) ($_POST['ts'] ?? ''));
    $nonce = trim((string) ($_POST['nonce'] ?? ''));
    $sig = trim((string) ($_POST['sig'] ?? ''));
    $clientSecret = $config->get('CLIENT_SECRET', '');
    if ($clientId === '' || $ts === '' || $nonce === '' || $sig === '' || $clientSecret === '') {
        $log->error('pay-init clients_mode missing params', ['result' => 'bad_request']);
        http_response_code(400);
        echo 'Chýbajú parametre (client_id, ts, nonce, sig) alebo CLIENT_SECRET.';
        exit;
    }
    $payload = $clientId . '|' . $ts . '|' . $nonce;
    $expectedSig = hash_hmac('sha256', $payload, (string) $clientSecret);
    if (!hash_equals($expectedSig, $sig)) {
        $log->error('pay-init clients_mode invalid sig', ['result' => 'forbidden']);
        http_response_code(403);
        echo 'Neplatný podpis.';
        exit;
    }
    $maxAge = (int) ($config->get('CLIENTS_TS_MAX_AGE', 300));
    if ($maxAge > 0 && abs((int) $ts - time()) > $maxAge) {
        $log->error('pay-init clients_mode ts expired', ['result' => 'forbidden']);
        http_response_code(403);
        echo 'Timestamp vypršal.';
        exit;
    }
}

$amountCents = isset($_POST['amount_cents']) ? (int) $_POST['amount_cents'] : 0;
$description = trim((string) ($_POST['description'] ?? ''));
$returnUrl = trim((string) ($_POST['return_url'] ?? ''));

if ($amountCents < 1) {
    $log->error('pay-init validation failed', ['reason' => 'invalid amount', 'amount_cents' => $amountCents, 'result' => 'error']);
    http_response_code(400);
    echo 'Neplatná suma.';
    exit;
}

$mode = $paymentService->getPaymentMode();
$log->info('pay-init start', [
    'mode' => $mode,
    'amount_cents' => $amountCents,
    'description' => $description,
    'return_url' => $returnUrl ?: null,
]);

try {
    $result = $paymentService->initPayment($amountCents, $description, $returnUrl);
    $log->info('pay-init redirect', ['public_id' => $result['payment']->publicId, 'result' => 'redirect']);
    header('Location: ' . $result['redirectUrl']);
    exit;
} catch (\Throwable $e) {
    $log->error('pay-init error', ['message' => $e->getMessage(), 'result' => 'error']);
    http_response_code(500);
    echo 'Chyba pri vytváraní platby.';
    exit;
}
