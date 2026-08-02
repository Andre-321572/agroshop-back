<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\CommandeSuivi;
use App\Services\LeekPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LeekPayWebhookController extends Controller
{
    public function __construct(private readonly LeekPayService $leekPay) {}

    /**
     * POST /api/leekpay/webhook
     * Reçoit les notifications de paiement de LeekPay.
     * Endpoint public — AUCUNE authentification Sanctum.
     */
    public function handle(Request $request)
    {
        $rawPayload = $request->getContent();
        $signature  = $request->header('X-LeekPay-Signature', '');
        $event      = $request->header('X-LeekPay-Event', '');

        // 1. Vérifier la signature HMAC
        if (!$this->leekPay->verifyWebhookSignature($rawPayload, $signature)) {
            Log::warning('[LeekPay Webhook] Signature invalide', [
                'ip'        => $request->ip(),
                'signature' => $signature,
                'event'     => $event,
            ]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $data = json_decode($rawPayload, true);
        $paymentData = $data['data'] ?? [];

        Log::info('[LeekPay Webhook] Événement reçu', [
            'event'          => $event,
            'checkout_id'    => $paymentData['checkout_id'] ?? null,
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'status'         => $paymentData['status'] ?? null,
        ]);

        // 2. Trouver la commande associée via leekpay_checkout_id
        $checkoutId = $paymentData['checkout_id'] ?? null;
        if (!$checkoutId) {
            return response()->json(['message' => 'checkout_id manquant'], 400);
        }

        $commande = Commande::where('leekpay_checkout_id', $checkoutId)->first();
        if (!$commande) {
            Log::warning('[LeekPay Webhook] Commande introuvable pour checkout_id', ['checkout_id' => $checkoutId]);
            // Retourner 200 pour éviter les retries LeekPay
            return response()->json(['message' => 'Commande non trouvée, ignoré'], 200);
        }

        $status = $paymentData['status'] ?? 'unknown';
        $transactionId = $paymentData['transaction_id'] ?? null;

        // 3. Traiter selon le statut du paiement
        match ($status) {
            'paid'      => $this->handlePaymentSuccess($commande, $transactionId, $paymentData),
            'failed'    => $this->handlePaymentFailed($commande),
            'cancelled' => $this->handlePaymentCancelled($commande),
            default     => Log::info('[LeekPay Webhook] Statut ignoré', ['status' => $status]),
        };

        return response()->json(['message' => 'OK'], 200);
    }

    private function handlePaymentSuccess(Commande $commande, ?string $transactionId, array $data): void
    {
        // Éviter les doublons de traitement (idempotence)
        if ($commande->statut_paiement === 'paye') {
            Log::info('[LeekPay Webhook] Paiement déjà traité, ignoré', ['ref' => $commande->code_reference]);
            return;
        }

        $commande->update([
            'statut_paiement'        => 'paye',
            'statut_commande'        => 'confirmee',
            'leekpay_transaction_id' => $transactionId,
        ]);

        // Ajouter un suivi
        $this->addSuivi($commande, 'en_attente', 'confirmee',
            "Paiement Mobile Money confirmé via LeekPay. Transaction: {$transactionId}"
        );

        Log::info('[LeekPay Webhook] Commande payée et confirmée', [
            'ref'            => $commande->code_reference,
            'transaction_id' => $transactionId,
            'amount'         => $data['amount'] ?? null,
        ]);
    }

    private function handlePaymentFailed(Commande $commande): void
    {
        if ($commande->statut_paiement === 'echec') return;

        $commande->update(['statut_paiement' => 'echec']);

        $this->addSuivi($commande, $commande->statut_commande, $commande->statut_commande,
            'Paiement Mobile Money échoué (fonds insuffisants ou erreur technique).'
        );

        Log::info('[LeekPay Webhook] Paiement échoué', ['ref' => $commande->code_reference]);
    }

    private function handlePaymentCancelled(Commande $commande): void
    {
        if ($commande->statut_paiement === 'annule') return;

        $commande->update([
            'statut_paiement' => 'annule',
            'statut_commande' => 'annulee',
        ]);

        $this->addSuivi($commande, 'en_attente', 'annulee',
            'Paiement Mobile Money annulé par le client.'
        );

        Log::info('[LeekPay Webhook] Paiement annulé', ['ref' => $commande->code_reference]);
    }

    private function addSuivi(Commande $commande, string $precedent, string $nouveau, string $commentaire): void
    {
        try {
            if (Schema::hasTable('commande_suivis')) {
                CommandeSuivi::create([
                    'commande_id'       => $commande->id,
                    'statut_precedent'  => $precedent,
                    'nouveau_statut'    => $nouveau,
                    'commentaire'       => $commentaire,
                    'utilisateur_id'    => null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[LeekPay Webhook] Erreur ajout suivi', ['error' => $e->getMessage()]);
        }
    }
}
