<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: index.php');
    exit;
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
