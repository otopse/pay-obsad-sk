<?php

declare(strict_types=1);

namespace App\Provider;

final class ProviderNotifyResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $publicId = null,
        public readonly ?string $status = null,
        public readonly ?string $providerRef = null,
    ) {
    }
}
