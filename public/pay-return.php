<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

$query = $_GET;
$result = $paymentService->getProvider()->verifyReturn($query);

if (!$result->success || $result->publicId === null) {
    $log->error('pay-return verify failed', ['query' => $query, 'result' => 'invalid']);
    http_response_code(400);
    echo 'Neplatný návrat z platby.';
    exit;
}

$payment = $paymentService->findByPublicId($result->publicId);
if ($payment === null) {
    $log->error('pay-return payment not found', ['public_id' => $result->publicId, 'result' => 'not_found']);
    http_response_code(404);
    echo 'Platba nebola nájdená.';
    exit;
}

$status = $result->status ?? 'paid';
if ($status === 'paid') {
    $paymentService->markPaid($result->publicId, $result->providerRef, null);
} elseif ($status === 'failed') {
    $paymentService->markFailed($result->publicId, $result->providerRef, null);
} else {
    $paymentService->markCancelled($result->publicId);
}

$log->info('pay-return updated', ['public_id' => $result->publicId, 'status' => $status, 'result' => 'ok']);
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Výsledok platby – Pay Obsad SK</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 420px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.25rem; }
        .status { padding: 0.5rem; margin: 1rem 0; border-radius: 4px; }
        .status.paid { background: #d4edda; color: #155724; }
        .status.failed { background: #f8d7da; color: #721c24; }
        .status.cancelled { background: #fff3cd; color: #856404; }
        code { font-size: 0.875rem; word-break: break-all; }
    </style>
</head>
<body>
    <h1>Výsledok platby</h1>
    <p class="status <?php echo htmlspecialchars($status); ?>">
        Stav: <strong><?php echo htmlspecialchars($status); ?></strong>
    </p>
    <p>ID platby: <code><?php echo htmlspecialchars($result->publicId); ?></code></p>
    <p><a href="index.php">Späť na formulár</a></p>
</body>
</html>
