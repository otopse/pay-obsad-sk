Uprav kód tak, aby prepínač PAYMENT_MODE v .env riadil správanie platobnej logiky.

Požiadavky:

Konfigurácia:

pridaj čítanie hodnoty PAYMENT_MODE (default fake).

povolené hodnoty: fake, sandbox, live.

ak je hodnota iná, sprav fallback na fake a zaloguj warning.

PaymentService:

refaktoruj PaymentService tak, aby mal 2 implementácie:
a) FakePaymentGateway (existujúci fake flow): vráti okamžite paid alebo cancel podľa parametra (alebo vždy paid pre jednoduchosť).
b) ECardPaymentGateway (placeholder): zatiaľ nech iba vyhodí „Not implemented“ alebo vráti jasnú chybu v UI, ale nech je pripravená štruktúra a metódy.

PaymentService nech vyberá gateway podľa PAYMENT_MODE.

Endpointy:

public/pay-init.php:

ak PAYMENT_MODE=fake, pokračuj v fake flow ako doteraz

ak PAYMENT_MODE=sandbox alebo live, volaj ECardPaymentGateway->initPayment(...) a presmeruj na gateway URL (zatiaľ placeholder, ale architektúra hotová)

public/pay-return.php a public/pay-cancel.php:

v fake režime funguje ako doteraz

v sandbox/live režime priprav spracovanie návratu (len skeleton + logovanie)

public/pay-notify.php:

v fake režime buď no-op alebo simuluj notify

v sandbox/live režime skeleton na IPN/notify overenie a update platby

Logging:

zaloguj vždy začiatok platby: mode, amount, description, public_id, return_url

zaloguj výsledok: paid/cancel/error

UI:

na index.php zobraz aktívny režim (fake/sandbox/live), aby som hneď videl, čo beží.

Test:

pridaj jednoduchý self-check endpoint (napr. /health.php) ktorý vypíše PAYMENT_MODE a či existuje vendor/autoload.php, a či DB pripojenie prejde (bez citlivých údajov).

Dodaj:

konkrétne zmeny v súboroch (diff alebo jasný popis)

uisti sa, že nič z .env sa nikdy nevypíše celé do HTML (iba PAYMENT_MODE a bezpečné info).