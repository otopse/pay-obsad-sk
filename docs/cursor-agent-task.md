Cieľ
V projekte pay-obsad-sk (PHP 8.1, MariaDB, jednoduché jednorazové platby) priprav integračnú kostru pre platobnú bránu VÚB eCard v sandbox/test režime. Výsledok má byť nasaditeľný na pay.obsad.sk a testovateľný aj bez reálnych eCard údajov (fake mode). Keď neskôr doplníme reálne sandbox údaje, stačí zmeniť .env bez úprav kódu.

Kontext infra

Webroot subdomény: /public_html/pay/public

Projekt root na serveri: /public_html/pay/

.env je na serveri iba manuálne: /public_html/pay/.env (necommitovať)

Deploy ide cez GitHub Actions cez SFTP na /public_html/pay/

Požadované deliverables

Konfiguračný a validačný modul

Uprav app/Config.php:

ignoruj komentáre začínajúce # aj ;

podpor inline komentáre iba mimo úvodzoviek

pridaj automatické typovanie (0/1/true/false, int, float)

pridaj metódu require(key) ktorá vyhodí výnimku keď chýba povinná hodnota

get() má fallback na getenv()

Základná doménová logika platieb (DB + stavový automat)

Vytvor DB migráciu v adresári /storage/sql/001_init.sql a pridaj do README ako spustiť v phpMyAdmin:

tabuľka payments:

id (PK, bigint auto)

public_id (varchar(40), unique) – verejný identifikátor platby do URL

client_id (varchar(64), nullable) – neskôr pre multi-web klientov

amount_cents (int) – suma v centoch

currency (char(3)) default EUR

description (varchar(255), nullable)

status (enum alebo varchar) – created, redirect_sent, paid, cancelled, failed, expired

provider (varchar(32)) default ecard

provider_ref (varchar(128), nullable) – id transakcie u providera

provider_payload (json/text, nullable) – uložiť raw odpoveď/notifikáciu

return_url (varchar(255), nullable)

created_at, updated_at (datetime)

Uprav app/Db.php aby čítal DB_* z Configu a používal PDO s rozumnými nastaveniami a chybami do výnimiek.

Provider adaptér (ecard) s dvomi režimami: fake a real

Vytvor rozhranie app/Provider/PaymentProviderInterface.php:

createRedirect(Payment $payment): ProviderRedirectResult

verifyReturn(array $queryOrPost): ProviderVerificationResult

handleNotify(string $rawBody, array $headers): ProviderNotifyResult

Vytvor implementáciu:

app/Provider/FakeProvider.php

vráti redirect URL na lokálnu stránku /pay-return.php s parametrami simulujúcimi úspech/neúspech

notify môže byť voliteľný endpoint /dev/fake-notify

app/Provider/ECardProvider.php

zatiaľ iba skeleton s jasne označenými TODO a jedným miestom, kde sa doplní podpis/parametre podľa dokumentácie

načítava ECARD_* z Configu

buduje redirect URL na ECARD_GATEWAY_URL s parametrami (merchant id, amount, currency, return/cancel/notify url, order id)

podpisovanie:

ak je ECARD_SHARED_SECRET nastavený, priprav HMAC sign mechanizmus ako placeholder funkciu sign(array $params)

ak sú nastavené ECARD_PRIVATE_KEY_PATH a ECARD_PUBLIC_CERT_PATH, priprav RSA sign/verify placeholder (openssl_sign / openssl_verify)

vráti objekt s redirectUrl a payloadom

V app/PaymentService.php:

vyber provider podľa PAYMENT_FAKE_MODE (1 = FakeProvider, 0 = ECardProvider)

implementuj:

initPayment($amountCents, $description, $returnUrl): Payment + redirect

markPaid/markCancelled/markFailed podľa return/notify

HTTP endpointy a flow

public/index.php:

jednoduchá stránka “Pay Obsad SK” + formulár na test platby (suma + popis) pre sandbox

public/pay-init.php (POST):

validácia vstupu

vytvor payment record v DB

zavolaj PaymentService->initPayment a urob HTTP redirect na provider redirect URL

public/pay-return.php (GET):

over návrat z providera (v fake mode len podľa query)

aktualizuj status

zobraz výsledok + public_id

public/pay-cancel.php (GET):

nastav cancelled

public/pay-notify.php (POST):

prijmi notifikáciu

over podpis/token (aspoň NOTIFY_TOKEN ako fallback: header alebo query token)

idempotentne aktualizuj payment status

vráť 200 OK text “OK”

Pridaj bezpečnostný guard:

v pay-notify.php skontroluj NOTIFY_TOKEN (napr. header X-Notify-Token) ak je nastavený

Logovanie a diagnostika

Vytvor jednoduchý logger app/Log.php:

zapisuje do LOG_FILE

log levely info/error

nikdy neloguje tajomstvá (secret, keys)

Každý endpoint loguje: public_id, akcia, výsledok

Dokumentácia

README.md doplň:

štruktúra .env (odkaz na .env.example)

ako zapnúť fake mode: PAYMENT_FAKE_MODE=1

ako otestovať lokálne na hostingu:

otvoriť /, zadať sumu, odoslať

fake mode presmeruje na return, zmení stav v DB

čo treba doplniť pre reálny sandbox: ECARD_GATEWAY_URL, ECARD_MERCHANT_ID, atď.

Pridaj .env.example ktorý obsahuje všetky kľúče (bez reálnych hodnôt)

Akceptačné kritériá (čo musí fungovať po deployi)

Na https://pay.obsad.sk/
 sa zobrazí stránka s formulárom

V režime PAYMENT_FAKE_MODE=1:

pay-init vytvorí záznam v DB (status created)

prebehne redirect na pay-return

pay-return nastaví status paid (alebo failed podľa parametra)

v DB je vidno zmenu statusu

pay-notify endpoint odmietne request bez správneho NOTIFY_TOKEN (403), a prijme s tokenom (200)

Poznámky

Necommitovať .env ani žiadne tajomstvá

Všetko písať jednoducho, bez frameworku

Dodržať existujúcu štruktúru projektu (app/, public/, storage/)

Čo má agent konkrétne urobiť v repozitári

vytvoriť/ upraviť súbory podľa vyššie uvedeného

upraviť composer.json ak treba (napr. autoload psr-4 App\ => app/)

pridať storage/sql/001_init.sql a README.md inštrukcie

pridať/opraviť endpointy v public/

zabezpečiť, že vendor/ sa vytvára v CI (composer install) a deploy posiela aj vendor/

Dôležité: vendor/autoload.php chyba
Momentálne na serveri chýba vendor/. V CI musí prebehnúť composer install a do SFTP deployu musí ísť aj vendor/ adresár. Oprav workflow tak, aby:

pred deployom spustil composer install (už máš)

deploy poslal vendor/ (local_path ./ už to zahrnie)

YAML musí byť validný (opraviť indentáciu, žiadne komentáre na zlom mieste)