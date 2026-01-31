<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$out = [
    'status' => 'ok',
    'checks' => [],
];

$vendorOk = is_file(dirname(__DIR__) . '/vendor/autoload.php');
$out['checks']['vendor_autoload'] = $vendorOk;

if (!$vendorOk) {
    $out['status'] = 'error';
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

$config = new \App\Config();
$mode = $config->getPaymentMode();
$out['payment_mode'] = $mode;

$dbOk = false;
try {
    $db = new \App\Db($config);
    $db->getConnection()->query('SELECT 1');
    $dbOk = true;
} catch (\Throwable $e) {
    $out['checks']['db_error'] = 'connection failed';
}
$out['checks']['db'] = $dbOk;
if (!$dbOk) {
    $out['status'] = 'error';
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
