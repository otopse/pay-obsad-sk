<?php

declare(strict_types=1);

namespace App;

final class Log
{
    private const SENSITIVE_KEYS = [
        'secret', 'password', 'pass', 'key', 'token', 'authorization',
        'sign', 'signature', 'shared_secret', 'private_key', 'api_key',
    ];

    public function __construct(
        private Config $config,
    ) {
    }

    public function info(string $message, array $context = []): void
    {
        $this->write('info', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('error', $message, $context);
    }

    private function write(string $level, string $message, array $context): void
    {
        $file = $this->config->get('LOG_FILE');
        if ($file === null || $file === '') {
            return;
        }
        $context = $this->sanitize($context);
        $line = date('Y-m-d H:i:s') . ' [' . strtoupper($level) . '] ' . $message;
        if ($context !== []) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        $line .= "\n";
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private function sanitize(array $context): array
    {
        $out = [];
        foreach ($context as $k => $v) {
            $lower = strtolower((string) $k);
            foreach (self::SENSITIVE_KEYS as $sensitive) {
                if (str_contains($lower, $sensitive)) {
                    $v = '***';
                    break;
                }
            }
            $out[$k] = is_scalar($v) ? $v : json_encode($v);
        }
        return $out;
    }
}
