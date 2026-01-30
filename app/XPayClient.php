<?php

declare(strict_types=1);

namespace App;

class XPayClient
{
    public function __construct(
        private Config $config
    ) {
    }

    /**
     * Vytvorí platbu v XPay platobnej bráne.
     */
    public function createPayment(array $params): array
    {
        // TODO: Implementácia volania XPay API
        return [];
    }

    /**
     * Overí podpis notifikácie.
     */
    public function verifyNotifySignature(array $data): bool
    {
        // TODO: Implementácia overenia podpisu
        return false;
    }
}
