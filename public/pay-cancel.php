<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$publicId = trim((string) ($_GET['public_id'] ?? ''));
if ($publicId === '') {
    $log->error('pay-cancel missing public_id', ['result' => 'bad_request']);
    http_response_code(400);
    echo 'Chýba public_id.';
    exit;
}

$payment = $paymentService->findByPublicId($publicId);
if ($payment === null) {
    $log->error('pay-cancel payment not found', ['public_id' => $publicId, 'result' => 'not_found']);
    http_response_code(404);
    echo 'Platba nebola nájdená.';
    exit;
}

$paymentService->markCancelled($publicId);
$log->info('pay-cancel updated', ['public_id' => $publicId, 'result' => 'ok']);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platba zrušená – Pay Obsad SK</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 420px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.25rem; }
    </style>
</head>
<body>
    <h1>Platba zrušená</h1>
    <p>Platba <code><?php echo htmlspecialchars($publicId); ?></code> bola zrušená.</p>
    <p><a href="index.php">Späť na formulár</a></p>
</body>
</html>
