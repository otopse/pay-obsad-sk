-- Migrácia 001: tabuľka payments
-- Spustenie v phpMyAdmin: vyberte databázu, záložka SQL, vložte obsah a spustite.

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id VARCHAR(40) NOT NULL,
    client_id VARCHAR(64) NULL,
    amount_cents INT NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'EUR',
    description VARCHAR(255) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'created',
    provider VARCHAR(32) NOT NULL DEFAULT 'ecard',
    provider_ref VARCHAR(128) NULL,
    provider_payload JSON NULL,
    return_url VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_public_id (public_id),
    KEY idx_status (status),
    KEY idx_provider_ref (provider_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
