<?php

namespace App\Http\Controllers\Api\Gestionnaire;

use App\Http\Controllers\Controller;
use App\Models\BoutiqueProduit;
use App\Models\Commande;
use App\Models\CommandeArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VenteController extends Controller
{
    public function store(Request $request)
    {
        $gestionnaire = Auth::user();
        $boutiqueId = $gestionnaire->boutique_id ?? $gestionnaire->boutiques()->first()?->id ?? 1;

        $validated = $request->validate([
            'nom_client' => 'required|string',
            'prenom_client' => 'nullable|string',
            'telephone' => 'nullable|string',
            'telephone_client' => 'nullable|string',
            'articles' => 'required|array|min:1',
            'articles.*.produit_id' => 'required|exists:produits,id',
            'articles.*.quantite' => 'required|integer|min:1',
            'articles.*.prix_unitaire' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Calculate total first to avoid NOT NULL DB constraint errors
            $montant_total = 0;
            foreach ($validated['articles'] as $item) {
                $montant_total += ($item['quantite'] * $item['prix_unitaire']);
            }

            $hasBoutiqueId = Schema::hasColumn('commandes', 'boutique_id');

            $commandeData = [
                'code_reference' => 'B' . $boutiqueId . '-' . strtoupper(substr(uniqid(), -6)),
                'nom_client' => $validated['nom_client'],
                'prenom_client' => $validated['prenom_client'] ?? '',
                'telephone' => $validated['telephone'] ?? $validated['telephone_client'] ?? '',
                'email' => $validated['email'] ?? 'comptoir@agroshop.tg',
                'adresse_ligne1' => 'Vente en comptoir boutique',
                'ville' => 'Lomé',
                'pays' => 'Togo',
                'montant_ht' => $montant_total,
                'montant_tva' => 0,
                'montant_ttc' => $montant_total,
                'frais_livraison' => 0,
                'montant_total' => $montant_total,
                'statut_commande' => 'livree', // Vente directe en boutique = livrée
                'statut_paiement' => 'paye', // Vente directe = payé
                'type_livraison' => 'retrait_agence',
            ];

            if ($hasBoutiqueId) {
                $commandeData['boutique_id'] = $boutiqueId;
            }

            $commande = Commande::create($commandeData);

            foreach ($validated['articles'] as $item) {
                // Vérifier et déduire le stock
                $stock = BoutiqueProduit::where('boutique_id', $boutiqueId)
                                        ->where('produit_id', $item['produit_id'])
                                        ->first();

                if (!$stock) {
                    $stock = BoutiqueProduit::create([
                        'boutique_id' => $boutiqueId,
                        'produit_id' => $item['produit_id'],
                        'stock_disponible' => 0,
                        'stock_alerte' => 10,
                    ]);
                }

                $curStock = (int) ($stock->stock_disponible ?? 0);
                $stock->stock_disponible = max(0, $curStock - $item['quantite']);
                $stock->save();

                $montant_ligne = $item['quantite'] * $item['prix_unitaire'];

                CommandeArticle::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $item['produit_id'],
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $item['prix_unitaire'],
                    'montant_ligne' => $montant_ligne,
                ]);
            }

            DB::commit();

            $relations = ['articles.produit'];
            if ($hasBoutiqueId) {
                $relations[] = 'boutique';
            }

            $commandeChargee = $commande->fresh()->load($relations);

            return response()->json([
                'status' => 'success',
                'message' => 'Vente enregistrée avec succès',
                'commande' => $commandeChargee
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?: 'Erreur lors de la vente',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
