Rozšír public/health.php o kontroly pripravenosti pre sandbox: pridaj payment_mode_valid (true/false), ecard_config_present (true/false podľa toho, či sú vyplnené minimálne ECARD_GATEWAY_URL a ECARD_MERCHANT_ID), base_url_https (true/false podľa toho, či APP_BASE_URL začína https://) a nič citlivé nevypisuj, iba boolean a bezpečné info.

Priprav databázovú schému pre platby: vytvor SQL migráciu (napr. storage/migrations/001_create_payments.sql) pre tabuľku payments (public_id, amount_cents, currency, description, client_return_url, status, provider, provider_txn_id/provider_payment_id, created_at, updated_at) a tabuľku payment_events alebo notify_log (payment_id/public_id, event_type, payload_hash, received_at, raw_payload voliteľne). Zvoľ minimálny rozsah tak, aby sa dali ukladať výsledky sandbox platieb a notify udalosti.

Pridaj jednoduchý spôsob spustenia migrácií: buď CLI skript (preferované) alebo dočasný admin endpoint, ktorý je bezpečne chránený (napr. tokenom z .env) a defaultne vypnutý. Cieľ: vedieť založiť tabuľky bez ručného kopírovania SQL.

Uprav pay-init.php tak, aby pri PAYMENT_MODE=sandbox alebo live vytvoril záznam v DB (status=new/redirected), zalogoval request_id, public_id, amount_cents, return_url a následne spravil redirect do gateway (alebo placeholder, ak nie je nakonfigurované). Pri fake nech ostane správanie rovnaké, ale tiež nech sa loguje request_id.

Implementuj idempotenciu a audit pre pay-notify.php: pridaj request_id, načítaj relevantnú platbu podľa identifikátora (public_id alebo provider id), ulož payload_hash + timestamp do notify_log a pri opakovanom notify s rovnakým hashom alebo pri už finálnom statuse (paid/cancelled) nerob nič okrem zalogovania “duplicate notify”. Vráť vždy 200 OK, aby sa gateway nezacyklila.

Priprav kostru overenia pravosti notifikácie pre eCard: vytvor funkciu verifyEcardSignature(...) ktorá zatiaľ vráti true, ale má jasné TODO a logovanie, aby sa po dodaní údajov od VÚB doplnilo reálne overovanie (HMAC alebo certifikát). Nezavádzaj “vlastný token” ako povinný, iba voliteľný.

Zjednoť logovanie naprieč endpointmi (pay-init, pay-return, pay-cancel, pay-notify, health): všade generuj request_id, loguj v jednotnom formáte (ideálne key=value alebo JSON), nikdy neloguj secrets ani celé payloady bez hashovania (ak treba, len skrátený výsek + hash).

Doplň README.md o postup “fake → sandbox”: čo treba nastaviť v .env (PAYMENT_MODE=sandbox + ECARD_*), ako spustiť migrácie, ako otestovať /health.php a aké očakávané správanie má pay-init/return/notify v sandboxe.

Priprav voliteľný režim “CLIENTS_MODE=on” pre budúcu integráciu viacerých webov: navrhni a implementuj podpis pay-init requestu pomocou HMAC (client_id, ts, nonce, sig), pričom default nech je CLIENTS_MODE=off a keď je off, správanie je kompatibilné s dneškom.

Daj pozor na bezpečnosť a kompatibilitu s hostingom: žiadne vypisovanie .env na web, žiadne debug výpisy v prod režime, a všetko musí fungovať na PHP 8.1 (FPM/FastCGI), bez potreby shell príkazov na serveri.