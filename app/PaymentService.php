<?php

declare(strict_types=1);

namespace App;

use App\Provider\PaymentProviderInterface;
use App\Provider\ECardProvider;
use App\Provider\FakeProvider;
use App\Provider\ProviderRedirectResult;
use PDO;

class PaymentService
{
    private PaymentProviderInterface $gateway;
    private string $paymentMode;

    public function __construct(
        private Db $db,
        private Config $config,
        private Log $log,
    ) {
        $raw = $this->config->get('PAYMENT_MODE', 'fake');
        $raw = is_string($raw) ? strtolower(trim($raw)) : 'fake';
        $this->paymentMode = in_array($raw, ['fake', 'sandbox', 'live'], true) ? $raw : 'fake';
        if ($this->paymentMode !== $raw) {
            $this->log->warning('PAYMENT_MODE invalid, fallback to fake', ['raw' => $raw, 'resolved' => 'fake']);
        }
        $this->gateway = $this->paymentMode === 'fake'
            ? new FakeProvider($config)
            : new ECardProvider($config);
    }

    public function getPaymentMode(): string
    {
        return $this->paymentMode;
    }

    /**
     * Vytvorí platbu v DB a vráti redirect URL na providera.
     * @return array{payment: Payment, redirectUrl: string}
     */
    public function initPayment(int $amountCents, string $description, string $returnUrl): array
    {
        $publicId = $this->generatePublicId();
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO payments (public_id, amount_cents, currency, description, status, provider, return_url) 
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $publicId,
            $amountCents,
            $this->config->get('PAYMENT_CURRENCY', 'EUR'),
            $description ?: null,
            'created',
            'ecard',
            $returnUrl ?: null,
        ]);
        $id = (int) $pdo->lastInsertId();
        $pdo->prepare('UPDATE payments SET status = ? WHERE id = ?')->execute(['redirect_sent', $id]);
        $payment = $this->findById($id);
        if ($payment === null) {
            throw new \RuntimeException('Payment insert failed');
        }
        $result = $this->gateway->createRedirect($payment);
        $this->log->info('initPayment', [
            'mode' => $this->paymentMode,
            'amount_cents' => $amountCents,
            'description' => $description,
            'public_id' => $publicId,
            'return_url' => $returnUrl ?: null,
            'result' => 'redirect',
        ]);
        return ['payment' => $payment, 'redirectUrl' => $result->redirectUrl];
    }

    public function markPaid(string $publicId, ?string $providerRef = null, ?string $providerPayload = null): void
    {
        $this->updateStatus($publicId, 'paid', $providerRef, $providerPayload);
        $this->log->info('markPaid', ['public_id' => $publicId, 'result' => 'paid']);
    }

    public function markCancelled(string $publicId): void
    {
        $this->updateStatus($publicId, 'cancelled', null, null);
        $this->log->info('markCancelled', ['public_id' => $publicId, 'result' => 'cancel']);
    }

    public function markFailed(string $publicId, ?string $providerRef = null, ?string $providerPayload = null): void
    {
        $this->updateStatus($publicId, 'failed', $providerRef, $providerPayload);
        $this->log->info('markFailed', ['public_id' => $publicId, 'result' => 'error']);
    }

    public function getGateway(): PaymentProviderInterface
    {
        return $this->gateway;
    }

    public function findByPublicId(string $publicId): ?Payment
    {
        $stmt = $this->db->getConnection()->prepare('SELECT * FROM payments WHERE public_id = ?');
        $stmt->execute([$publicId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Payment::fromRow($row) : null;
    }

    private function findById(int $id): ?Payment
    {
        $stmt = $this->db->getConnection()->prepare('SELECT * FROM payments WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Payment::fromRow($row) : null;
    }

    private function updateStatus(string $publicId, string $status, ?string $providerRef, ?string $providerPayload): void
    {
        $pdo = $this->db->getConnection();
        if ($providerPayload !== null) {
            $stmt = $pdo->prepare('UPDATE payments SET status = ?, provider_ref = ?, provider_payload = ? WHERE public_id = ?');
            $stmt->execute([$status, $providerRef, $providerPayload, $publicId]);
        } else {
            $stmt = $pdo->prepare('UPDATE payments SET status = ?, provider_ref = COALESCE(?, provider_ref) WHERE public_id = ?');
            $stmt->execute([$status, $providerRef, $publicId]);
        }
    }

    private function generatePublicId(): string
    {
        return bin2hex(random_bytes(20));
    }
}
