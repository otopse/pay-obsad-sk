<?php

declare(strict_types=1);

namespace App;

class PaymentService
{
    public function __construct(
        private Db $db,
        private XPayClient $xPayClient,
        private Config $config
    ) {
    }

    /**
     * Inicializuje novú platbu.
     */
    public function initPayment(float $amount, string $orderId, array $metadata = []): array
    {
        // TODO: Uloženie platby do DB a vytvorenie v XPay
        return [];
    }

    /**
     * Spracuje návrat z platobnej brány (return URL).
     */
    public function handleReturn(array $data): bool
    {
        // TODO: Overenie a aktualizácia stavu platby
        return true;
    }

    /**
     * Spracuje notifikáciu (webhook) o zmene stavu platby.
     */
    public function handleNotify(array $data): bool
    {
        // TODO: Overenie podpisu, aktualizácia platby v DB
        return true;
    }
}
