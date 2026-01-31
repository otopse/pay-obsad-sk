<?php

declare(strict_types=1);

namespace App;

/**
 * Kostra overenia pravosti notifikácie od VÚB eCard.
 * TODO: Po dodaní údajov od VÚB doplniť reálne overovanie (HMAC alebo certifikát).
 */
final class EcardVerify
{
    public function __construct(
        private Config $config,
        private Log $log,
    ) {
    }

    /**
     * Overí podpis notifikácie. Zatiaľ vracia true (placeholder).
     * TODO: Implementovať overenie podľa dokumentácie VÚB eCard (HMAC alebo RSA certifikát).
     */
    public function verifyEcardSignature(string $rawBody, array $headers): bool
    {
        $this->log->info('EcardVerify.verifyEcardSignature called', [
            'body_length' => strlen($rawBody),
            'headers_count' => count($headers),
            'todo' => 'Implement real verification (HMAC or cert) from VUB eCard docs',
        ]);
        return true;
    }
}
