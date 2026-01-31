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

$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(8));
$out['request_id'] = $requestId;

$config = new \App\Config();
$mode = $config->getPaymentMode();
$out['payment_mode'] = $mode;

$paymentModeValid = in_array($mode, ['fake', 'sandbox', 'live'], true);
$out['checks']['payment_mode_valid'] = $paymentModeValid;

$baseUrl = (string) ($config->get('APP_BASE_URL') ?? $config->get('APP_URL') ?? '');
$baseUrlHttps = $baseUrl !== '' && str_starts_with(strtolower($baseUrl), 'https://');
$out['checks']['base_url_https'] = $baseUrlHttps;

$ecardGateway = $config->get('ECARD_GATEWAY_URL');
$ecardMerchant = $config->get('ECARD_MERCHANT_ID');
$ecardConfigPresent = ($ecardGateway !== null && $ecardGateway !== '') && ($ecardMerchant !== null && $ecardMerchant !== '');
$out['checks']['ecard_config_present'] = $ecardConfigPresent;

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
