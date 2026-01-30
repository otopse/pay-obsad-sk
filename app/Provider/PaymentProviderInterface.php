<?php

declare(strict_types=1);

namespace App\Provider;

use App\Payment;

interface PaymentProviderInterface
{
    public function createRedirect(Payment $payment): ProviderRedirectResult;

    public function verifyReturn(array $queryOrPost): ProviderVerificationResult;

    public function handleNotify(string $rawBody, array $headers): ProviderNotifyResult;
}
