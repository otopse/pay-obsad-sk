<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

$publicId = trim((string) ($_GET['public_id'] ?? ''));
$mode = $config->getPaymentMode();
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>eCard – Pay Obsad SK</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 420px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.25rem; }
        .notice { background: #fff3cd; color: #856404; padding: 0.75rem; border-radius: 4px; margin: 1rem 0; }
        code { font-size: 0.875rem; word-break: break-all; }
    </style>
</head>
<body>
    <h1>Platobná brána eCard</h1>
    <p class="notice">Režim <strong><?php echo htmlspecialchars($mode); ?></strong> – eCard platobná brána zatiaľ nie je implementovaná.</p>
    <?php if ($publicId !== ''): ?>
    <p>ID platby: <code><?php echo htmlspecialchars($publicId); ?></code></p>
    <?php endif; ?>
    <p><a href="index.php">Späť na formulár</a></p>
</body>
</html>
