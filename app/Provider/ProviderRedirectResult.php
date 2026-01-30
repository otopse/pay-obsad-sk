<?php

declare(strict_types=1);

namespace App\Provider;

final class ProviderRedirectResult
{
    public function __construct(
        public readonly string $redirectUrl,
        public readonly array $payload = [],
    ) {
    }
}
