<?php

declare(strict_types=1);

namespace App;

final class Config
{
    private array $config = [];

    public function __construct(?string $envFile = null)
    {
        $envFile = $envFile ?? (dirname(__DIR__) . '/.env');

        if (!is_file($envFile) || !is_readable($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES);

        foreach ($lines as $line) {
            if ($line === false) {
                continue;
            }

            $line = trim($line);

            // skip empty
            if ($line === '') {
                continue;
            }

            // skip comments (# or ;)
            if ($line[0] === '#' || $line[0] === ';') {
                continue;
            }

            // must contain "="
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            if ($key === '') {
                continue;
            }

            // remove inline comments, but only if value is NOT quoted
            if ($value !== '' && ($value[0] !== '"' && $value[0] !== "'")) {
                // cut at " #..." or " ;..."
                $value = preg_split('/\s+[;#].*$/', $value)[0] ?? $value;
                $value = trim($value);
            }

            // strip surrounding quotes
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            $this->config[$key] = $this->cast($value);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // priority: .env -> actual env vars -> default
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        $env = getenv($key);
        if ($env !== false) {
            return $this->cast($env);
        }

        return $default;
    }

    public function require(string $key): mixed
    {
        $val = $this->get($key, null);
        if ($val === null || $val === '') {
            throw new \RuntimeException("Missing required config key: {$key}");
        }
        return $val;
    }

    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->config)) {
            return true;
        }
        return getenv($key) !== false;
    }

    private function cast(string $value): mixed
    {
        $v = trim($value);

        $lower = strtolower($v);

        // booleans
        if (in_array($lower, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($lower, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        // null
        if ($lower === 'null') {
            return null;
        }

        // int
        if (preg_match('/^-?\d+$/', $v)) {
            return (int) $v;
        }

        // float
        if (preg_match('/^-?\d+\.\d+$/', $v)) {
            return (float) $v;
        }

        return $v;
    }
}
