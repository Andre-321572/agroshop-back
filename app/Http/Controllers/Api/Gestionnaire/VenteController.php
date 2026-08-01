<?php

namespace App\Http\Controllers\Api\Gestionnaire;

use App\Http\Controllers\Controller;
use App\Models\BoutiqueProduit;
use App\Models\Commande;
use App\Models\CommandeArticle;
use App\Models\Gestionnaire;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VenteController extends Controller
{
    public function index(Request $request)
    {
        /** @var Gestionnaire|null $gestionnaire */
        $gestionnaire = Auth::user();
        $boutiqueId = $request->header('X-Boutique-Id')
            ?? $request->query('boutique_id')
            ?? $gestionnaire?->boutique_id;

        $hasBoutiqueId = Schema::hasColumn('commandes', 'boutique_id');

        $query = Commande::with(['articles.produit', 'boutique']);

        if ($hasBoutiqueId && $boutiqueId) {
            $query->where(function ($q) use ($boutiqueId) {
                $q->where('boutique_id', $boutiqueId)
                  ->orWhereNull('boutique_id');
            });
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('code_reference', 'like', $search)
                  ->orWhere('nom_client', 'like', $search)
                  ->orWhere('prenom_client', 'like', $search)
                  ->orWhere('telephone', 'like', $search);
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $commandes = $query->orderBy('created_at', 'desc')->get();

        $caTotal = $commandes->where('statut_commande', '!=', 'annulee')->sum('montant_total');
        $nombreVentes = $commandes->count();
        $panierMoyen = $nombreVentes > 0 ? round($caTotal / $nombreVentes, 2) : 0;

        return response()->json([
            'status' => 'success',
            'data' => $commandes,
            'stats' => [
                'ca_total' => $caTotal,
                'nombre_ventes' => $nombreVentes,
                'panier_moyen' => $panierMoyen,
            ]
        ]);
    }

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

                $produit = \App\Models\Produit::find($item['produit_id']);
                $nomProduit = $produit ? ($produit->nom_commercial ?? $produit->nom ?? ('Produit #' . $item['produit_id'])) : ('Produit #' . $item['produit_id']);

                $montant_ligne = $item['quantite'] * $item['prix_unitaire'];

                CommandeArticle::create([
                    'commande_id' => $commande->id,
                    'produit_id' => $item['produit_id'],
                    'nom_produit' => $nomProduit,
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

    public function genererRecuPdf($id)
    {
        $relations = ['articles.produit'];
        if (Schema::hasColumn('commandes', 'boutique_id')) {
            $relations[] = 'boutique';
        }

        $commande = Commande::with($relations)->findOrFail($id);
        $html = view('pdf.recu_ticket', compact('commande'))->render();

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadHTML($html)
                ->setPaper([0, 0, 226.77, 600], 'portrait');
            return $pdf->stream("recu_{$commande->code_reference}.pdf");
        }

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper([0, 0, 226.77, 600], 'portrait');
            $dompdf->render();
            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="recu_' . $commande->code_reference . '.pdf"'
            ]);
        }

        return response()->json(['message' => 'Bibliothèque Dompdf non disponible'], 500);
    }

    /**
     * POST/PUT /api/gestionnaire/commandes/{id}/statut
     */
    public function updateStatut(Request $request, $id): JsonResponse
    {
        $request->validate([
            'statut_commande' => 'required|string|in:en_attente,confirmee,preparee,expediee,livree,annulee',
        ]);

        $commande = Commande::findOrFail($id);
        $ancienStatut = $commande->statut_commande;
        $nouveauStatut = $request->input('statut_commande');

        $updateData = ['statut_commande' => $nouveauStatut];
        if ($nouveauStatut === 'livree') {
            $updateData['statut_paiement'] = 'paye';
        }

        $commande->update($updateData);

        try {
            if (Schema::hasTable('commande_suivis')) {
                \App\Models\CommandeSuivi::create([
                    'commande_id' => $commande->id,
                    'statut_precedent' => $ancienStatut,
                    'nouveau_statut' => $nouveauStatut,
                    'commentaire' => $request->input('commentaire', 'Changement de statut par le gestionnaire'),
                    'utilisateur_id' => null,
                ]);
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'status' => 'success',
            'message' => 'Statut de la commande mis à jour avec succès.',
            'data' => $commande->fresh()
        ]);
    }
}
