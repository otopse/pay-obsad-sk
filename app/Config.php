<?php

declare(strict_types=1);

namespace App;

class Config
{
    private array $config = [];

    public function __construct()
    {
        $envFile = dirname(__DIR__) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$key, $value] = explode('=', $line, 2);
                    $this->config[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
                }
            }
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->config[$key]);
    }
}
