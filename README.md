# Pay Obsad SK

Platobná integrácia pre pay.obsad.sk – jednorazové platby (PHP 8.1, MariaDB).  
Integračná kostra pre VÚB eCard v sandbox/test režime s fake mode pre testovanie bez reálnych údajov.

## Štruktúra .env

Skopírujte `.env.example` do `.env` a vyplňte hodnoty. Na serveri sa `.env` vytvára iba manuálne (necommitovať).

```bash
cp .env.example .env
```

Kľúče sú popísané v `.env.example`. Povinné pre beh: `DB_HOST`, `DB_NAME`, `DB_USER` (a `DB_PASS` podľa servera).

## Fake mode

Pre testovanie bez reálnej platobnej brány:

```env
PAYMENT_FAKE_MODE=1
```

- `1` = **FakeProvider** – redirect na `/pay-return.php` s parametrami (status, public_id), bez eCard.
- `0` = **ECardProvider** – reálna brána (sandbox/produkcia), potrebné ECARD_* v `.env`.

Po doplnení reálnych sandbox údajov stačí zmeniť `.env` (napr. `PAYMENT_FAKE_MODE=0` a ECARD_*) bez úprav kódu.

## Databáza – migrácia

1. Otvorte phpMyAdmin a vyberte databázu (napr. `pay_obsad`).
2. Záložka **SQL**.
3. Skopírujte obsah súboru `storage/sql/001_init.sql`.
4. Spustite (Execute).

Tabuľka `payments` sa vytvorí s potrebnými stĺpcami (public_id, amount_cents, status, atď.).

## Ako otestovať lokálne / na hostingu

1. Nastavte `.env` (DB_*, `PAYMENT_FAKE_MODE=1`, prípadne `LOG_FILE`).
2. Spustite migráciu `001_init.sql` v phpMyAdmin.
3. Otvorte `/` (resp. `index.php`).
4. Zadajte sumu (v centoch) a popis, odošlite formulár.
5. **Fake mode:** presmeruje na `/pay-return.php` so stavom (paid/failed), v DB sa vytvorí záznam a po return sa aktualizuje status.
6. V databáze overte zmenu stavu platby (created → paid alebo failed).

## Reálny sandbox VÚB eCard

Pre reálny sandbox doplňte do `.env`:

- `ECARD_GATEWAY_URL` – URL brány (sandbox)
- `ECARD_MERCHANT_ID` – ID obchodníka
- `ECARD_SHARED_SECRET` – pre HMAC podpis, alebo
- `ECARD_PRIVATE_KEY_PATH` a `ECARD_PUBLIC_CERT_PATH` – pre RSA podpis

Nastavte `PAYMENT_FAKE_MODE=0`. Podpis a parametre v `app/Provider/ECardProvider.php` treba doplniť podľa oficiálnej dokumentácie VÚB eCard.

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
| `/`            | GET    | Formulár na test platby (suma, popis). |
| `pay-init.php` | POST   | Validácia, vytvorenie platby v DB, redirect na providera. |
| `pay-return.php` | GET  | Návrat z brány; overenie, aktualizácia statusu, zobrazenie výsledku + public_id. |
| `pay-cancel.php` | GET  | Zrušenie platby (query: `public_id`). |
| `pay-notify.php` | POST | Webhook notifikácia; ak je nastavený `NOTIFY_TOKEN`, kontroluje sa header `X-Notify-Token` alebo query/body `token`. Odpoveď 200 OK s telesom `OK`. |

## Akceptačné kritériá (po deployi)

- Na https://pay.obsad.sk/ sa zobrazí stránka s formulárom.
- V režime `PAYMENT_FAKE_MODE=1`:
  - `pay-init` vytvorí záznam v DB (status `created`).
  - Prebehne redirect na `pay-return`.
  - `pay-return` nastaví status `paid` alebo `failed` podľa parametra.
  - V DB je viditeľná zmena statusu.
- `pay-notify` bez správneho `NOTIFY_TOKEN`: **403**.
- `pay-notify` s platným tokenom (header alebo query): **200** a teleso `OK`.
