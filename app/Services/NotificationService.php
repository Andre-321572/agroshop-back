<?php

namespace App\Services;

use App\Models\Commande;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Envoie une notification en temps réel (Pusher / Webhook / Beams) lors de la création d'une nouvelle commande.
     */
    public function notifyNewOrder(Commande $commande): void
    {
        $orderId = $commande->id;
        $customer = $commande->prenom_client . ' ' . $commande->nom_client;
        $formattedAmount = number_format($commande->montant_total, 0, ',', ' ') . ' FCFA';
        $reference = $commande->code_reference;

        Log::info("Nouvelle commande créée: #{$reference} par {$customer} pour un montant de {$formattedAmount}");

        // 1. Pusher Channels (si configuré dans .env)
        $pusherKey = env('PUSHER_APP_KEY');
        $pusherAppId = env('PUSHER_APP_ID');
        $pusherSecret = env('PUSHER_APP_SECRET');
        $pusherCluster = env('PUSHER_APP_CLUSTER', 'eu');

        if (!empty($pusherKey) && !empty($pusherSecret) && !empty($pusherAppId)) {
            try {
                $payload = json_encode([
                    'name' => 'new-order',
                    'channel' => 'admin-channel',
                    'data' => json_encode([
                        'order_id' => $orderId,
                        'customer' => $customer,
                        'formatted_amount' => $formattedAmount,
                        'reference' => $reference,
                        'timestamp' => now()->toIso8601String(),
                    ]),
                ]);

                // Appel HTTP direct à l'API Pusher
                $authTimestamp = time();
                $authVersion = '1.0';
                $bodyMd5 = md5($payload);
                $stringToSign = "POST\n/apps/{$pusherAppId}/events\nauth_key={$pusherKey}&auth_timestamp={$authTimestamp}&auth_version={$authVersion}&body_md5={$bodyMd5}";
                $authSignature = hash_hmac('sha256', $stringToSign, $pusherSecret);

                Http::withHeaders(['Content-Type' => 'application/json'])
                    ->post("https://api-{$pusherCluster}.pusher.com/apps/{$pusherAppId}/events?auth_key={$pusherKey}&auth_timestamp={$authTimestamp}&auth_version={$authVersion}&body_md5={$bodyMd5}&auth_signature={$authSignature}", json_decode($payload, true));

                Log::info("Notification Pusher envoyée pour la commande #{$reference}");
            } catch (\Exception $e) {
                Log::error("Échec de l'envoi Pusher pour la commande #{$reference}: " . $e->getMessage());
            }
        }

        // 2. Pusher Beams Push Notifications (si configuré)
        $beamsInstanceId = env('BEAMS_INSTANCE_ID');
        $beamsSecretKey = env('BEAMS_SECRET_KEY');

        if (!empty($beamsInstanceId) && !empty($beamsSecretKey)) {
            try {
                Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $beamsSecretKey,
                ])->post("https://{$beamsInstanceId}.pushnotifications.pusher.com/publish_api/v1/instances/{$beamsInstanceId}/publishes", [
                    'interests' => ['admin-notifications'],
                    'web' => [
                        'notification' => [
                            'title' => 'Nouvelle commande #' . $reference,
                            'body' => "Client: {$customer}\nMontant: {$formattedAmount}",
                            'data' => [
                                'order_id' => $orderId,
                                'reference' => $reference,
                            ],
                        ],
                    ],
                ]);

                Log::info("Notification Pusher Beams envoyée pour la commande #{$reference}");
            } catch (\Exception $e) {
                Log::error("Échec de l'envoi Pusher Beams pour la commande #{$reference}: " . $e->getMessage());
            }
        }
    }
}
