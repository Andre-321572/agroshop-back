<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\CommandeArticle;
use App\Models\CommandeSuivi;
use App\Models\ParametreSysteme;
use App\Models\Produit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

class CommandeController extends Controller
{
    /**
     * POST /api/commandes
     * Prise de commande par un client (panier + coordonnées client).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'telephone' => ['required', 'string', 'regex:/^[0-9\+\s]{8,20}$/'],
            'email' => 'nullable|email|max:150',
            'adresse_ligne1' => 'nullable|string|max:200',
            'adresse_ligne2' => 'nullable|string|max:200',
            'ville' => 'nullable|string|max:100',
            'code_postal' => 'nullable|string|max:20',
            'pays' => 'nullable|string|max:100',
            'type_livraison' => 'required|in:livraison,retrait_agence',
            'adresse_livraison' => 'required_if:type_livraison,livraison|nullable|string',
            'date_livraison_souhaitee' => 'nullable|date|after_or_equal:today',
            'instructions_livraison' => 'nullable|string',
            'commentaire' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.produit_id' => 'required|integer|exists:produits,id',
            'items.*.quantite' => 'required|integer|min:1',
        ], [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.regex' => 'Le format du numéro de téléphone est invalide.',
            'type_livraison.required' => 'Veuillez sélectionner un mode de livraison.',
            'adresse_livraison.required_if' => 'L\'adresse de livraison est obligatoire pour une livraison à domicile.',
            'items.required' => 'Votre panier ne peut pas être vide.',
        ]);

        DB::beginTransaction();

        try {
            // 1. Calculer les montants réels en base de données (Sécurité anti-tampering)
            $montantHt = 0;
            $articlesAInserer = [];

            foreach ($request->items as $item) {
                $produit = Produit::where('statut', 'actif')->findOrFail($item['produit_id']);

                // Vérifier la disponibilité en stock
                if ($produit->stock_disponible < $item['quantite']) {
                    throw new \Exception("Le produit « {$produit->nom_commercial} » n'a plus que {$produit->stock_disponible} unité(s) en stock.");
                }

                $prixUnitaire = (float) $produit->prix_unitaire;
                $montantLigne = $prixUnitaire * $item['quantite'];
                $montantHt += $montantLigne;

                $articlesAInserer[] = [
                    'produit_id' => $produit->id,
                    'nom_produit' => $produit->nom_commercial,
                    'prix_unitaire' => $prixUnitaire,
                    'quantite' => $item['quantite'],
                    'montant_ligne' => $montantLigne,
                ];

                // Décrémenter le stock du produit
                $produit->decrement('stock_disponible', $item['quantite']);
            }

            // 2. Calcul des taxes et frais de livraison
            $tvaTaux = (float) ParametreSysteme::get('tva_taux', 18);
            $fraisBase = (float) ParametreSysteme::get('frais_livraison_base', 5000);

            $fraisLivraison = ($request->type_livraison === 'livraison') ? $fraisBase : 0.00;
            $montantTva = ($montantHt * $tvaTaux) / 100;
            $montantTtc = $montantHt + $montantTva;
            $montantTotal = $montantTtc + $fraisLivraison;

            // 3. Génération automatique du code de référence unique
            $nextId = (Commande::max('id') ?? 0) + 1;
            $codeReference = 'AGR' . date('Y') . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);

            // 4. Création de la commande
            $hasBoutiqueId = Schema::hasColumn('commandes', 'boutique_id');

            $commandeData = [
                'code_reference' => $codeReference,
                'nom_client' => $request->nom_client ?? $request->nom ?? 'Client',
                'prenom_client' => $request->prenom_client ?? $request->prenom ?? '',
                'telephone' => $request->telephone,
                'email' => $request->email,
                'adresse_ligne1' => $request->adresse_ligne1,
                'adresse_ligne2' => $request->adresse_ligne2,
                'ville' => $request->ville,
                'code_postal' => $request->code_postal,
                'pays' => $request->input('pays', 'Togo'),
                'montant_ht' => $montantHt,
                'montant_tva' => $montantTva,
                'montant_ttc' => $montantTtc,
                'frais_livraison' => $fraisLivraison,
                'montant_total' => $montantTotal,
                'type_livraison' => $request->mode_livraison ?? $request->type_livraison ?? 'retrait_agence',
                'adresse_livraison' => $request->adresse_livraison ?? $request->adresse_ligne1,
                'date_livraison_souhaitee' => $request->date_livraison_souhaitee,
                'instructions_livraison' => $request->instructions_livraison,
                'statut_commande' => 'en_attente',
                'statut_paiement' => 'en_attente',
                'commentaire' => $request->commentaire,
                'ip_client' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            if ($hasBoutiqueId) {
                $commandeData['boutique_id'] = $request->input('boutique_id', 1);
            }

            $commande = Commande::create($commandeData);

            // 5. Création des articles de la commande
            foreach ($articlesAInserer as $articleData) {
                $articleData['commande_id'] = $commande->id;
                CommandeArticle::create($articleData);
            }

            // 6. Historique initial de suivi
            CommandeSuivi::create([
                'commande_id' => $commande->id,
                'statut_precedent' => null,
                'nouveau_statut' => 'en_attente',
                'commentaire' => 'Commande créée par le client',
                'utilisateur_id' => null,
            ]);

            DB::commit();

            // Envoi de la notification temps réel (Pusher / Push Notifications)
            try {
                app(NotificationService::class)->notifyNewOrder($commande);
            } catch (\Exception $e) {
                // Log non-bloquant pour la commande
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Votre commande a été enregistrée avec succès !',
                'data' => [
                    'code_reference' => $commande->code_reference,
                    'montant_total' => $commande->montant_total,
                    'statut_commande' => $commande->statut_commande,
                    'created_at' => $commande->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de l\'enregistrement de la commande : ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/commandes/suivi/{reference}
     * Suivi public d'une commande par code de référence ou par numéro de téléphone.
     */
    public function suivi(string $reference): JsonResponse
    {
        $commande = Commande::where('code_reference', $reference)
            ->orWhere('telephone', $reference)
            ->with(['articles:id,commande_id,nom_produit,prix_unitaire,quantite,montant_ligne', 'suivis:id,commande_id,statut_precedent,nouveau_statut,commentaire,created_at'])
            ->orderBy('created_at', 'desc')
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $commande,
        ]);
    }
}
