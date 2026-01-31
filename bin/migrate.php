#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Spustenie migrácií z storage/migrations/*.sql
 * Použitie: php bin/migrate.php
 * Vyžaduje .env s DB_HOST, DB_NAME, DB_USER, DB_PASS.
 */

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Run composer install first.\n");
    exit(1);
}
require_once $autoload;

$config = new \App\Config();
try {
    $db = new \App\Db($config);
} catch (\Throwable $e) {
    fwrite(STDERR, "DB connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

$dir = dirname(__DIR__) . '/storage/migrations';
if (!is_dir($dir)) {
    fwrite(STDERR, "Migrations dir not found: {$dir}\n");
    exit(1);
}

$files = glob($dir . '/*.sql');
sort($files);
$pdo = $db->getConnection();

foreach ($files as $file) {
    $name = basename($file);
    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        continue;
    }
    try {
        $pdo->exec($sql);
        echo "OK: {$name}\n";
    } catch (\Throwable $e) {
        fwrite(STDERR, "FAIL: {$name} - " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Migrations done.\n";
