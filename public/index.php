<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pay Obsad SK</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 420px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.25rem; }
        label { display: block; margin-top: 0.75rem; }
        input, textarea { width: 100%; padding: 0.5rem; margin-top: 0.25rem; box-sizing: border-box; }
        button { margin-top: 1rem; padding: 0.5rem 1rem; background: #333; color: #fff; border: none; cursor: pointer; }
        .hint { font-size: 0.875rem; color: #666; margin-top: 0.25rem; }
    </style>
</head>
<body>
    <h1>Pay Obsad SK</h1>
    <p><strong>Aktívny režim:</strong> <span class="mode"><?php echo htmlspecialchars($config->getPaymentMode()); ?></span></p>
    <p>Testovacia platba</p>
    <form method="post" action="pay-init.php">
        <label>
            Suma (centy)
            <input type="number" name="amount_cents" value="100" min="1" required>
            <span class="hint">napr. 100 = 1,00 €</span>
        </label>
        <label>
            Popis
            <input type="text" name="description" value="Test platba" placeholder="Popis platby">
        </label>
        <label>
            Return URL (voliteľne)
            <input type="url" name="return_url" placeholder="https://...">
        </label>
        <button type="submit">Začať platbu</button>
    </form>
</body>
</html>
