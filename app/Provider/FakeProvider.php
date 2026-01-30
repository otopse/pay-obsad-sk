<?php

declare(strict_types=1);

namespace App\Provider;

use App\Config;
use App\Payment;

final class FakeProvider implements PaymentProviderInterface
{
    public function __construct(
        private Config $config,
    ) {
    }

    public function createRedirect(Payment $payment): ProviderRedirectResult
    {
        $baseUrl = rtrim($this->config->get('APP_URL', 'https://pay.obsad.sk'), '/');
        $status = $this->config->get('FAKE_DEFAULT_STATUS', 'paid');
        $redirectUrl = $baseUrl . '/pay-return.php?status=' . urlencode($status) . '&public_id=' . urlencode($payment->publicId);
        return new ProviderRedirectResult($redirectUrl, ['fake' => true]);
    }

    public function verifyReturn(array $queryOrPost): ProviderVerificationResult
    {
        $publicId = $queryOrPost['public_id'] ?? null;
        $status = $queryOrPost['status'] ?? 'paid';
        if ($publicId === null || $publicId === '') {
            return new ProviderVerificationResult(false, null, null, null);
        }
        $validStatuses = ['paid', 'failed', 'cancelled'];
        if (!in_array($status, $validStatuses, true)) {
            $status = 'paid';
        }
        return new ProviderVerificationResult(true, $publicId, $status, null);
    }

    public function handleNotify(string $rawBody, array $headers): ProviderNotifyResult
    {
        $data = json_decode($rawBody, true) ?: [];
        $publicId = $data['public_id'] ?? $data['order_id'] ?? null;
        $status = $data['status'] ?? 'paid';
        if ($publicId === null || $publicId === '') {
            return new ProviderNotifyResult(false, null, null, null);
        }
        return new ProviderNotifyResult(true, $publicId, $status, null);
    }
}
