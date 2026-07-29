<?php

namespace App\Services;

use App\Models\Boutique;
use App\Models\Produit;
use App\Models\BoutiqueProduit;

class AiAssistantService
{
    /**
     * Interroge la base de données en fonction du prompt de l'admin.
     * Pour une version plus avancée, on pourrait utiliser l'API Gemini pour parser le "prompt"
     * et en déduire la requête SQL. Ici, on simule une réponse intelligente basée sur des mots-clés
     * pour le MVP.
     */
    public function interroger(string $prompt)
    {
        $prompt = strtolower($prompt);

        if (str_contains($prompt, 'stock') && str_contains($prompt, 'ciment')) {
            $produit = Produit::where('nom_commercial', 'like', '%ciment%')->first();
            if ($produit) {
                $stocks = BoutiqueProduit::with('boutique')
                            ->where('produit_id', $produit->id)
                            ->get();
                
                $reponse = "Voici l'état du stock pour le Ciment : \n";
                foreach ($stocks as $stock) {
                    $reponse .= "- " . $stock->boutique->nom . " : " . $stock->stock_disponible . " unités.\n";
                }
                return $reponse;
            }
            return "Je n'ai pas trouvé de ciment dans le catalogue.";
        }

        if (str_contains($prompt, 'rupture') || str_contains($prompt, 'alerte')) {
            $stocksEnAlerte = BoutiqueProduit::with(['boutique', 'produit'])
                                ->whereColumn('stock_disponible', '<=', 'stock_alerte')
                                ->get();
            
            if ($stocksEnAlerte->isEmpty()) {
                return "Tout va bien, aucun produit n'est en rupture de stock !";
            }

            $reponse = "Voici les produits en alerte de stock : \n";
            foreach ($stocksEnAlerte as $stock) {
                $reponse .= "- " . $stock->produit->nom_commercial . " (Boutique: " . $stock->boutique->nom . ") : Reste " . $stock->stock_disponible . " (Seuil: " . $stock->stock_alerte . ").\n";
            }
            return $reponse;
        }

        return "Je suis l'assistant IA d'Agroshop. Pour l'instant, je peux répondre aux questions sur les 'stocks de ciment' ou les produits en 'rupture'. L'intégration complète avec Gemini est en cours !";
    }
}
