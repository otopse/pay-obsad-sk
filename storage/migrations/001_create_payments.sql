-- Migrácia 001: tabuľky payments a notify_log
-- Spustenie: php bin/migrate.php alebo cez admin endpoint (ak je zapnutý)
-- Schéma payments je zladená so storage/sql/001_init.sql (return_url, provider_ref)

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id VARCHAR(40) NOT NULL,
    client_id VARCHAR(64) NULL,
    amount_cents INT NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'EUR',
    description VARCHAR(255) NULL,
    return_url VARCHAR(512) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'created',
    provider VARCHAR(32) NOT NULL DEFAULT 'ecard',
    provider_ref VARCHAR(128) NULL,
    provider_payload JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_public_id (public_id),
    KEY idx_status (status),
    KEY idx_provider_ref (provider_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notify_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id VARCHAR(40) NOT NULL,
    event_type VARCHAR(32) NOT NULL,
    payload_hash VARCHAR(64) NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    raw_payload TEXT NULL,
    PRIMARY KEY (id),
    KEY idx_public_id (public_id),
    KEY idx_payload_hash (payload_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
