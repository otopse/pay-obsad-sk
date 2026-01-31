# Pay Obsad SK

Platobná integrácia pre pay.obsad.sk – jednorazové platby (PHP 8.1, MariaDB).  
Integračná kostra pre VÚB eCard v sandbox/test režime s fake mode pre testovanie bez reálnych údajov.

## Štruktúra .env

Skopírujte `.env.example` do `.env` a vyplňte hodnoty. Na serveri sa `.env` vytvára iba manuálne (necommitovať).

```bash
cp .env.example .env
```

Kľúče sú popísané v `.env.example`. Povinné pre beh: `DB_HOST`, `DB_NAME`, `DB_USER` (a `DB_PASS` podľa servera).

## Režim platieb (PAYMENT_MODE)

Hodnota `PAYMENT_MODE` v `.env` riadi platobnú logiku. Povolené hodnoty: **fake**, **sandbox**, **live**. Default je **fake**. Ak je hodnota iná, použije sa fallback na fake a do logu sa zapíše warning.

```env
PAYMENT_MODE=fake
```

- **fake** = FakePaymentGateway – okamžitý redirect na `/pay-return.php` s parametrami (status, public_id), bez eCard.
- **sandbox** / **live** = ECardPaymentGateway – presmerovanie na eCard bránu (alebo na placeholder `/pay-ecard-placeholder.php`, ak eCard nie je nakonfigurovaná).

Na stránke `/` (index.php) sa zobrazuje **aktívny režim** (fake / sandbox / live). Do HTML sa nikdy nevypisuje celé `.env` – iba bezpečné hodnoty ako PAYMENT_MODE.

## Databáza – migrácia

### Spustenie migrácií (odporúčané)

**CLI (bez shell prístupu na serveri môžete spustiť lokálne a DB musí byť dostupná):**

```bash
php bin/migrate.php
```

Skript načíta `.env`, pripojí sa na DB a spustí všetky `.sql` súbory z `storage/migrations/` v poradí. Vytvorí sa tabuľka `payments` a `notify_log`.

**Voliteľný admin endpoint** (defaultne vypnutý):

- Zapnúť v `.env`: `RUN_MIGRATIONS_ENDPOINT=1` a `MIGRATE_TOKEN=<tajný token>`.
- Volanie: GET/POST na `/migrate.php` s parametrom `?token=<MIGRATE_TOKEN>` alebo hlavičkou `X-Migrate-Token`.
- Bez správneho tokenu vráti 403.

### Manuálne (phpMyAdmin)

1. Otvorte phpMyAdmin a vyberte databázu (napr. `pay_obsad`).
2. Záložka **SQL**.
3. Skopírujte obsah súboru `storage/sql/001_init.sql` alebo `storage/migrations/001_create_payments.sql`.
4. Spustite (Execute).

Tabuľka `payments` a `notify_log` sa vytvorí s potrebnými stĺpcami.

## Ako otestovať lokálne / na hostingu

1. Nastavte `.env` (DB_*, `PAYMENT_MODE=fake`, prípadne `LOG_FILE`).
2. Spustite migráciu `001_init.sql` v phpMyAdmin.
3. Otvorte `/` (resp. `index.php`).
4. Zadajte sumu (v centoch) a popis, odošlite formulár.
5. **Režim fake:** presmeruje na `/pay-return.php` so stavom (paid/failed), v DB sa vytvorí záznam a po return sa aktualizuje status.
6. V databáze overte zmenu stavu platby (created → paid alebo failed).

## Reálny sandbox VÚB eCard

Pre reálny sandbox doplňte do `.env`:

- `ECARD_GATEWAY_URL` – URL brány (sandbox)
- `ECARD_MERCHANT_ID` – ID obchodníka
- `ECARD_SHARED_SECRET` – pre HMAC podpis, alebo
- `ECARD_PRIVATE_KEY_PATH` a `ECARD_PUBLIC_CERT_PATH` – pre RSA podpis

Nastavte `PAYMENT_MODE=sandbox` alebo `PAYMENT_MODE=live`. Podpis a parametre v `app/Provider/ECardProvider.php` treba doplniť podľa oficiálnej dokumentácie VÚB eCard.

## Prechod fake → sandbox

1. **.env** – nastavte:
   - `PAYMENT_MODE=sandbox`
   - `ECARD_GATEWAY_URL` a `ECARD_MERCHANT_ID` (minimálne; pre podpis ešte ECARD_SHARED_SECRET alebo ECARD_* certifikáty)
   - `APP_URL` alebo `APP_BASE_URL` s `https://` (pre base_url_https v health)

2. **Migrácie** – spustite:
   - lokálne: `php bin/migrate.php`
   - alebo cez admin endpoint (ak je zapnutý): GET/POST `/migrate.php?token=<MIGRATE_TOKEN>`

3. **Health** – otestujte `/health.php`:
   - `payment_mode_valid`: true (ak je PAYMENT_MODE fake/sandbox/live)
   - `ecard_config_present`: true (ak sú vyplnené ECARD_GATEWAY_URL a ECARD_MERCHANT_ID)
   - `base_url_https`: true (ak APP_BASE_URL alebo APP_URL začína na https://)
   - `db`: true (ak DB pripojenie prebehne)
   - Žiadne citlivé údaje (žiadne .env na výstup).

4. **Očakávané správanie v sandboxe:**
   - **pay-init:** vytvorí záznam v DB (status `new` → `redirected`), zaloguje request_id, public_id, amount_cents, return_url, presmeruje na gateway alebo na placeholder `/pay-ecard-placeholder.php`, ak eCard nie je nakonfigurovaná.
   - **pay-return / pay-cancel:** skeleton + logovanie (pripravené na doplnenie overenia a aktualizácie stavu).
   - **pay-notify:** idempotentné spracovanie – request_id, payload_hash do notify_log, pri duplikáte (rovnaký hash alebo finálny status paid/cancelled) len log "duplicate notify", vždy 200 OK.

## Deploy

- **Webroot na serveri:** `/public_html/pay/public` (document root je priečinok `public/`).
- **Projekt na serveri:** `/public_html/pay/` (root repozitára).
- **.env na serveri:** len manuálne v `/public_html/pay/.env` (necommitovať).

GitHub Actions workflow `.github/workflows/deploy.yml`:

- Pri push do `main` spustí `composer install --no-dev` a nasadí cez **SFTP** do `SFTP_TARGET` (napr. `/public_html/pay/`).
- Do deployu ide aj `vendor/` (local_path `./`), takže `vendor/autoload.php` je na serveri k dispozícii.

Potrebné secrets: `SFTP_HOST`, `SFTP_USER`, `SFTP_PASS`, `SFTP_PORT`, `SFTP_TARGET`.

## Endpointy

| Endpoint       | Metóda | Popis |
|----------------|--------|--------|
| `/`            | GET    | Formulár na test platby (suma, popis). Zobrazuje aktívny režim (fake/sandbox/live). |
| `health.php`   | GET    | Self-check: request_id, payment_mode, payment_mode_valid, ecard_config_present, base_url_https, vendor_autoload, db. Bez citlivých údajov. |
| `pay-init.php` | POST   | Validácia, vytvorenie platby v DB, redirect na providera. |
| `pay-return.php` | GET  | Návrat z brány; overenie, aktualizácia statusu, zobrazenie výsledku + public_id. |
| `pay-cancel.php` | GET  | Zrušenie platby (query: `public_id`). |
| `pay-notify.php` | POST | Webhook notifikácia; ak je nastavený `NOTIFY_TOKEN`, kontroluje sa header `X-Notify-Token` alebo query/body `token`. Odpoveď 200 OK s telesom `OK`. |

## Akceptačné kritériá (po deployi)

- Na https://pay.obsad.sk/ sa zobrazí stránka s formulárom.
- V režime `PAYMENT_MODE=fake`:
  - `pay-init` vytvorí záznam v DB (status `new` → `redirected`).
  - Prebehne redirect na `pay-return`.
  - `pay-return` nastaví status `paid` alebo `failed` podľa parametra.
  - V DB je viditeľná zmena statusu.
- `pay-notify` bez správneho `NOTIFY_TOKEN`: **403**.
- `pay-notify` s platným tokenom (header alebo query): **200** a teleso `OK`.
