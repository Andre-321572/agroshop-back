<?php

namespace App\Http\Controllers\Api\Gestionnaire;

use App\Http\Controllers\Controller;
use App\Models\BoutiqueProduit;
use App\Models\Commande;
use App\Models\CommandeArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VenteController extends Controller
{
    public function store(Request $request)
    {
        $gestionnaire = Auth::user();

        $validated = $request->validate([
            'nom_client' => 'required|string',
            'prenom_client' => 'required|string',
            'telephone' => 'required|string',
            'articles' => 'required|array|min:1',
            'articles.*.produit_id' => 'required|exists:produits,id',
            'articles.*.quantite' => 'required|integer|min:1',
            'articles.*.prix_unitaire' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $montant_total = 0;

            // Création de la commande liée à cette boutique
            $commande = Commande::create([
                'code_reference' => 'B' . $gestionnaire->boutique_id . '-' . strtoupper(uniqid()),
                'nom_client' => $validated['nom_client'],
                'prenom_client' => $validated['prenom_client'],
                'telephone' => $validated['telephone'],
                'boutique_id' => $gestionnaire->boutique_id,
                'statut_commande' => 'livree', // Vente directe en boutique = livrée
                'statut_paiement' => 'paye', // Vente directe = payé
                'type_livraison' => 'retrait_agence',
            ]);

            foreach ($validated['articles'] as $item) {
                // Vérifier et déduire le stock
                $stock = BoutiqueProduit::where('boutique_id', $gestionnaire->boutique_id)
                                        ->where('produit_id', $item['produit_id'])
                                        ->first();

                if (!$stock || $stock->stock_disponible < $item['quantite']) {
                    throw new \Exception("Stock insuffisant pour le produit ID: {$item['produit_id']}");
                }

                $stock->stock_disponible -= $item['quantite'];
                $stock->save();

                $montant_ligne = $item['quantite'] * $item['prix_unitaire'];
                $montant_total += $montant_ligne;

                CommandeArticle::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $item['produit_id'],
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $item['prix_unitaire'],
                    'montant_ligne' => $montant_ligne,
                ]);
            }

            $commande->update(['montant_total' => $montant_total, 'montant_ttc' => $montant_total]);

            DB::commit();

            return response()->json(['message' => 'Vente enregistrée avec succès', 'commande' => $commande], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la vente', 'error' => $e->getMessage()], 400);
        }
    }
}
