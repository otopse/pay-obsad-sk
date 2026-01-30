<?php

declare(strict_types=1);

namespace App;

use PDO;

class Db
{
    private ?PDO $pdo = null;

    public function __construct(Config $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $config->require('DB_HOST'),
            $config->require('DB_NAME')
        );
        $this->pdo = new PDO(
            $dsn,
            $config->require('DB_USER'),
            $config->get('DB_PASS', ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}
