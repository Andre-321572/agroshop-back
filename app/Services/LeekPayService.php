<?php

namespace App\Services;

use App\Models\Commande;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeekPayService
{
    private string $secretKey;
    private string $publicKey;
    private string $baseUrl = 'https://leekpay.fr/api/v1';

    public function __construct()
    {
        $this->secretKey = config('services.leekpay.secret_key', '');
        $this->publicKey = config('services.leekpay.public_key', '');
    }

    /**
     * Crée un checkout LeekPay pour une commande.
     * Retourne [ 'checkout_id' => '...', 'payment_url' => '...' ] ou lance une exception.
     */
    public function createCheckout(Commande $commande, string $returnUrl, string $cancelUrl, string $webhookUrl): array
    {
        $customerName = trim(($commande->prenom_client ?? '') . ' ' . ($commande->nom_client ?? ''));

        $payload = [
            'amount'          => (int) $commande->montant_total,
            'currency'        => 'XOF',
            'description'     => "Commande AgroShop #{$commande->code_reference}",
            'return_url'      => $returnUrl,
            'cancel_url'      => $cancelUrl,
            'webhook_url'     => $webhookUrl,
            'customer_name'   => $customerName ?: 'Client AgroShop',
            'customer_email'  => $commande->email ?: null,
            'customer_phone'  => $commande->telephone ?: null,
            'metadata'        => [
                'commande_id'       => $commande->id,
                'code_reference'    => $commande->code_reference,
            ],
        ];

        $response = Http::withToken($this->secretKey)
            ->timeout(15)
            ->post("{$this->baseUrl}/checkout", $payload);

        if (!$response->successful()) {
            Log::error('[LeekPay] Échec création checkout', [
                'status'   => $response->status(),
                'body'     => $response->body(),
                'commande' => $commande->code_reference,
            ]);
            throw new \RuntimeException(
                'Impossible d\'initialiser le paiement LeekPay : ' . $response->body()
            );
        }

        $data = $response->json('data');

        return [
            'checkout_id' => $data['id'] ?? null,
            'payment_url' => $data['payment_url'] ?? null,
        ];
    }

    /**
     * Vérifie la signature HMAC du webhook LeekPay.
     * La signature est calculée avec la clé PUBLIQUE (pk_live_xxx) selon la doc LeekPay.
     */
    public function verifyWebhookSignature(string $rawPayload, string $signature): bool
    {
        if (empty($this->publicKey) || empty($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawPayload, $this->publicKey);

        return hash_equals($expected, $signature);
    }

    /**
     * Vérifie le statut d'un checkout (polling fallback).
     */
    public function getCheckoutStatus(string $checkoutId): ?array
    {
        $response = Http::withToken($this->secretKey)
            ->timeout(10)
            ->get("{$this->baseUrl}/checkout/{$checkoutId}");

        if (!$response->successful()) {
            return null;
        }

        return $response->json('data');
    }
}
