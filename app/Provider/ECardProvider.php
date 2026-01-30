<?php

declare(strict_types=1);

namespace App\Provider;

use App\Config;
use App\Payment;

final class ECardProvider implements PaymentProviderInterface
{
    public function __construct(
        private Config $config,
    ) {
    }

    public function createRedirect(Payment $payment): ProviderRedirectResult
    {
        $gatewayUrl = $this->config->require('ECARD_GATEWAY_URL');
        $merchantId = $this->config->require('ECARD_MERCHANT_ID');
        $baseUrl = rtrim($this->config->get('APP_URL', ''), '/');
        $returnUrl = $baseUrl . '/pay-return.php';
        $cancelUrl = $baseUrl . '/pay-cancel.php';
        $notifyUrl = $baseUrl . '/pay-notify.php';

        $params = [
            'merchant_id' => $merchantId,
            'amount' => $payment->amountCents,
            'currency' => $payment->currency,
            'order_id' => $payment->publicId,
            'return_url' => $returnUrl,
            'cancel_url' => $cancelUrl,
            'notify_url' => $notifyUrl,
            'description' => $payment->description ?? '',
        ];

        // TODO: doplniť podpis podľa VÚB eCard dokumentácie
        if ($this->config->get('ECARD_SHARED_SECRET')) {
            $params['sign'] = $this->signHmac($params);
        } elseif ($this->config->get('ECARD_PRIVATE_KEY_PATH') && $this->config->get('ECARD_PUBLIC_CERT_PATH')) {
            $params['sign'] = $this->signRsa($params);
        }

        $query = http_build_query($params);
        $redirectUrl = $gatewayUrl . (str_contains($gatewayUrl, '?') ? '&' : '?') . $query;

        return new ProviderRedirectResult($redirectUrl, $params);
    }

    public function verifyReturn(array $queryOrPost): ProviderVerificationResult
    {
        // TODO: overiť podpis odpovede (HMAC alebo RSA) podľa dokumentácie
        $publicId = $queryOrPost['order_id'] ?? $queryOrPost['public_id'] ?? null;
        $status = $queryOrPost['status'] ?? 'paid';
        $providerRef = $queryOrPost['transaction_id'] ?? $queryOrPost['provider_ref'] ?? null;
        if ($publicId === null || $publicId === '') {
            return new ProviderVerificationResult(false, null, null, null);
        }
        return new ProviderVerificationResult(true, $publicId, $status, $providerRef);
    }

    public function handleNotify(string $rawBody, array $headers): ProviderNotifyResult
    {
        // TODO: overiť podpis notifikácie (HMAC alebo RSA) podľa dokumentácie
        $data = json_decode($rawBody, true) ?: [];
        $publicId = $data['order_id'] ?? $data['public_id'] ?? null;
        $status = $data['status'] ?? 'paid';
        $providerRef = $data['transaction_id'] ?? $data['provider_ref'] ?? null;
        if ($publicId === null || $publicId === '') {
            return new ProviderNotifyResult(false, null, null, null);
        }
        return new ProviderNotifyResult(true, $publicId, $status, $providerRef);
    }

    /**
     * Placeholder: HMAC podpis parametrov (ECARD_SHARED_SECRET).
     */
    private function signHmac(array $params): string
    {
        $secret = $this->config->get('ECARD_SHARED_SECRET', '');
        ksort($params);
        $data = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        return hash_hmac('sha256', $data, $secret);
    }

    /**
     * Placeholder: RSA podpis (ECARD_PRIVATE_KEY_PATH).
     */
    private function signRsa(array $params): string
    {
        $keyPath = $this->config->get('ECARD_PRIVATE_KEY_PATH', '');
        if (!is_file($keyPath) || !is_readable($keyPath)) {
            return '';
        }
        ksort($params);
        $data = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $privateKey = openssl_pkey_get_private('file://' . $keyPath);
        if ($privateKey === false) {
            return '';
        }
        $signature = '';
        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_pkey_free($privateKey);
        return base64_encode($signature);
    }
}
