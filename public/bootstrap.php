<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$config = new \App\Config();
$db = new \App\Db($config);
$log = new \App\Log($config);
$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(8));
$log->setRequestId($requestId);
$paymentService = new \App\PaymentService($db, $config, $log);
