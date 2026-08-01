<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service de communication avec l'API Groq AI (LLM rapide et économique).
 * Utilise le modèle Llama 3.1 70B par défaut (excellent rapport qualité/vitesse).
 */
class AiService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.groq.com/openai/v1';
    private string $defaultModel;
    private int $timeout = 45;

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key', env('GROQ_API_KEY', ''));
        $this->defaultModel = config('services.groq.model', env('GROQ_MODEL', 'llama-3.3-70b-versatile'));
    }

    /**
     * Appel générique au Chat Completions de Groq.
     * Retourne la réponse texte brute ou null en cas d'erreur silencieuse.
     */
    public function chat(
        string $userPrompt,
        string $systemPrompt = '',
        ?string $model = null,
        float $temperature = 0.3,
        int $maxTokens = 2048
    ): ?string {
        if (empty($this->apiKey)) {
            Log::warning('[AiService] GROQ_API_KEY non configurée — fonctionnalités IA désactivées.');
            return null;
        }

        $messages = [];
        if (!empty($systemPrompt)) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ])
                ->post($this->baseUrl . '/chat/completions', [
                    'model'       => $model ?? $this->defaultModel,
                    'messages'    => $messages,
                    'temperature' => $temperature,
                    'max_tokens'  => $maxTokens,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                Log::error('[AiService] Erreur Groq HTTP ' . $response->status(), [
                    'body' => $response->body(),
                ]);
                return null;
            }

            $body = $response->json();
            return $body['choices'][0]['message']['content'] ?? null;
        } catch (\Exception $e) {
            Log::error('[AiService] Exception Groq : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Exécute un chat et tente de parser la réponse en JSON.
     * Retourne un tableau associatif ou null.
     */
    public function chatJson(
        string $userPrompt,
        string $systemPrompt,
        ?string $model = null,
        float $temperature = 0.3,
        int $maxTokens = 2048
    ): ?array {
        $envelopePrompt = $systemPrompt . "\n\nIMPORTANT : Tu dois répondre UNIQUEMENT en JSON valide, sans texte supplémentaire, sans markdown.";
        $raw = $this->chat($userPrompt, $envelopePrompt, $model, $temperature, $maxTokens);
        if ($raw === null) return null;

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $cleaned = $this->extractJson($raw);
            $decoded = $cleaned ? json_decode($cleaned, true) : null;
        }
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Tente d'extraire un bloc JSON {...} d'une réponse potentiellement bruyante.
     */
    private function extractJson(string $text): ?string
    {
        $first = strpos($text, '{');
        $last = strrpos($text, '}');
        if ($first === false || $last === false || $last <= $first) return null;
        return substr($text, $first, $last - $first + 1);
    }
}
