<?php

declare(strict_types=1);

namespace App;

final class Payment
{
    public function __construct(
        public readonly int $id,
        public readonly string $publicId,
        public readonly ?string $clientId,
        public readonly int $amountCents,
        public readonly string $currency,
        public readonly ?string $description,
        public readonly string $status,
        public readonly string $provider,
        public readonly ?string $providerRef,
        public readonly ?string $providerPayload,
        public readonly ?string $returnUrl,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            $row['public_id'],
            $row['client_id'] ?? null,
            (int) $row['amount_cents'],
            $row['currency'] ?? 'EUR',
            $row['description'] ?? null,
            $row['status'],
            $row['provider'] ?? 'ecard',
            $row['provider_ref'] ?? null,
            isset($row['provider_payload']) ? (is_string($row['provider_payload']) ? $row['provider_payload'] : json_encode($row['provider_payload'])) : null,
            $row['return_url'] ?? null,
            $row['created_at'],
            $row['updated_at'],
        );
    }
}
