<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$config = new \App\Config();
$db = new \App\Db($config);
$log = new \App\Log($config);
$paymentService = new \App\PaymentService($db, $config, $log);
