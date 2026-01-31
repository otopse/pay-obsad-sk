<?php

declare(strict_types=1);

/**
 * Voliteľný admin endpoint na spustenie migrácií.
 * Zapnúť: RUN_MIGRATIONS_ENDPOINT=1 a MIGRATE_TOKEN=<tajný token> v .env
 * Defaultne je vypnutý. Volanie: POST/GET s ?token=<MIGRATE_TOKEN> alebo header X-Migrate-Token.
 */
require_once dirname(__DIR__) . '/vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

$config = new \App\Config();
$enabled = $config->get('RUN_MIGRATIONS_ENDPOINT');
if ($enabled !== true && $enabled !== '1' && $enabled !== 'on') {
    http_response_code(404);
    echo json_encode(['error' => 'Not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$token = $config->get('MIGRATE_TOKEN');
if ($token === null || $token === '') {
    http_response_code(503);
    echo json_encode(['error' => 'Migrations endpoint not configured'], JSON_UNESCAPED_UNICODE);
    exit;
}

$provided = $_GET['token'] ?? $_SERVER['HTTP_X_MIGRATE_TOKEN'] ?? '';
if ($provided !== $token) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = new \App\Db($config);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$dir = dirname(__DIR__) . '/storage/migrations';
$files = is_dir($dir) ? glob($dir . '/*.sql') : [];
sort($files);
$pdo = $db->getConnection();
$results = [];

foreach ($files as $file) {
    $name = basename($file);
    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        continue;
    }
    try {
        $pdo->exec($sql);
        $results[] = ['file' => $name, 'status' => 'ok'];
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Migration failed', 'file' => $name, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

echo json_encode(['status' => 'ok', 'migrations' => $results], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
