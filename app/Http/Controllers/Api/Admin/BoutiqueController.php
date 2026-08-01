<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoutiqueController extends Controller
{
    public function index()
    {
        $boutiques = Boutique::withCount('gestionnaires', 'produits')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($b) => $this->format($b));

        return response()->json(['data' => $boutiques]);
    }

    /**
     * Obtenir tous les produits du catalogue avec leur état dans cette boutique
     */
    public function getProduitsApprovisionnement($id)
    {
        $boutique = Boutique::findOrFail($id);

        $produits = Produit::with('categorie:id,nom')
            ->select('id', 'nom_commercial', 'categorie_id', 'prix_unitaire', 'unite_mesure')
            ->orderBy('nom_commercial')
            ->get();

        $existingPivot = DB::table('boutique_produit')
            ->where('boutique_id', $id)
            ->get()
            ->keyBy('produit_id');

        $data = $produits->map(function ($p) use ($existingPivot) {
            $pivot = $existingPivot->get($p->id);
            return [
                'id'             => $p->id,
                'nom_commercial' => $p->nom_commercial,
                'categorie_nom'  => $p->categorie ? $p->categorie->nom : 'Général',
                'prix_unitaire'  => (float) $p->prix_unitaire,
                'unite_mesure'   => $p->unite_mesure,
                'stock_actuel'   => $pivot ? (int) $pivot->stock_disponible : 0,
                'stock_alerte'   => $pivot ? (int) $pivot->stock_alerte : 10,
                'deja_associe'   => $pivot !== null,
            ];
        });

        return response()->json([
            'boutique' => [
                'id'  => $boutique->id,
                'nom' => $boutique->nom,
            ],
            'data' => $data,
        ]);
    }

    /**
     * Approvisionner la boutique avec une liste de produits et quantités
     */
    public function approvisionner(Request $request, $id)
    {
        $boutique = Boutique::findOrFail($id);

        $validated = $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.produit_id'   => 'required|exists:produits,id',
            'items.*.quantite'     => 'required|integer|min:1',
            'items.*.stock_alerte' => 'nullable|integer|min:1',
        ], [
            'items.required' => 'Veuillez cocher et renseigner au moins un produit à approvisionner.',
            'items.min'      => 'Veuillez sélectionner au moins un produit.',
        ]);

        $approvisionnesCount = 0;
        $totalQuantiteAjoutee = 0;

        foreach ($validated['items'] as $item) {
            $produitId = $item['produit_id'];
            $quantite  = (int) $item['quantite'];
            $alerte    = isset($item['stock_alerte']) ? (int) $item['stock_alerte'] : 10;

            $pivot = DB::table('boutique_produit')
                ->where('boutique_id', $id)
                ->where('produit_id', $produitId)
                ->first();

            if ($pivot) {
                DB::table('boutique_produit')
                    ->where('boutique_id', $id)
                    ->where('produit_id', $produitId)
                    ->update([
                        'stock_disponible' => $pivot->stock_disponible + $quantite,
                        'stock_alerte'     => $alerte > 0 ? $alerte : $pivot->stock_alerte,
                        'updated_at'       => now(),
                    ]);
            } else {
                DB::table('boutique_produit')->insert([
                    'boutique_id'      => $id,
                    'produit_id'       => $produitId,
                    'stock_disponible' => $quantite,
                    'stock_alerte'     => $alerte,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            $approvisionnesCount++;
            $totalQuantiteAjoutee += $quantite;
        }

        return response()->json([
            'message' => "Approvisionnement réussi ! {$approvisionnesCount} produit(s) mis à jour ({$totalQuantiteAjoutee} unités ajoutées).",
            'boutique' => [
                'id'             => $boutique->id,
                'nom'            => $boutique->nom,
                'produits_count' => $boutique->produits()->count(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom'          => 'required|string|max:255',
            'type'         => 'nullable|string|max:255',
            'types'        => 'nullable|array',
            'types.*'      => 'required|string|in:quincaillerie,agricole',
            'localisation' => 'nullable|string|max:255',
            'description'  => 'nullable|string',
            'is_active'    => 'nullable|boolean',
        ]);

        $typeStr = $this->resolveTypeString($validated);
        $this->assertTypeStringValide($typeStr);

        $boutique = Boutique::create([
            'nom'         => $validated['nom'],
            'type'        => $typeStr,
            'adresse'     => $validated['localisation'] ?? null,
            'ville'       => null,
            'statut'      => ($validated['is_active'] ?? true) ? 'actif' : 'inactif',
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Boutique créée avec succès',
            'data'    => $this->format($boutique),
        ], 201);
    }

    public function show($id)
    {
        $boutique = Boutique::with('gestionnaires')->findOrFail($id);
        return response()->json(['data' => $this->format($boutique)]);
    }

    public function update(Request $request, $id)
    {
        $boutique = Boutique::findOrFail($id);

        $validated = $request->validate([
            'nom'          => 'required|string|max:255',
            'type'         => 'nullable|string|max:255',
            'types'        => 'nullable|array',
            'types.*'      => 'required|string|in:quincaillerie,agricole',
            'localisation' => 'nullable|string|max:255',
            'description'  => 'nullable|string',
            'is_active'    => 'nullable|boolean',
        ]);

        $typeStr = $this->resolveTypeString($validated, $boutique->type);
        $this->assertTypeStringValide($typeStr);

        $boutique->update([
            'nom'         => $validated['nom'],
            'type'        => $typeStr,
            'adresse'     => $validated['localisation'] ?? $boutique->adresse,
            'statut'      => isset($validated['is_active'])
                                ? ($validated['is_active'] ? 'actif' : 'inactif')
                                : $boutique->statut,
            'description' => $validated['description'] ?? $boutique->description,
        ]);

        return response()->json([
            'message' => 'Boutique mise à jour avec succès',
            'data'    => $this->format($boutique->fresh()),
        ]);
    }

    public function destroy($id)
    {
        $boutique = Boutique::findOrFail($id);

        if ($boutique->commandes()->count() > 0) {
            $boutique->update(['statut' => 'inactif']);
            return response()->json(['message' => 'Cette boutique a des commandes liées. Elle a été désactivée.'], 200);
        }

        $boutique->delete();
        return response()->json(['message' => 'Boutique supprimée avec succès']);
    }

    /**
     * Convertit types[] ou type string en chaîne normalisée.
     */
    private function resolveTypeString(array $validated, ?string $fallback = 'agricole'): string
    {
        if (!empty($validated['types']) && is_array($validated['types'])) {
            return implode(',', array_unique($validated['types']));
        }
        if (!empty($validated['type'])) {
            return $validated['type'];
        }
        return $fallback ?? 'agricole';
    }

    /**
     * Vérifie que chaque élément de la chaîne type (séparée par virgules)
     * est une valeur autorisée (quincaillerie ou agricole).
     * Lève une ValidationException si une valeur inconnue est détectée.
     */
    private function assertTypeStringValide(string $typeStr): void
    {
        $autorises = ['quincaillerie', 'agricole'];
        $types = array_values(array_filter(explode(',', $typeStr)));

        if (count($types) === 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type' => 'Au moins un type de boutique est requis.',
            ]);
        }

        foreach ($types as $t) {
            $t = trim($t);
            if (!in_array($t, $autorises, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'type' => "Le type \"{$t}\" n'est pas autorisé. Valeurs acceptées : " . implode(', ', $autorises) . '.',
                ]);
            }
        }
    }

    /**
     * Normalise le modèle Boutique pour le Frontend.
     */
    private function format(Boutique $b): array
    {
        $typesArray = array_values(array_filter(explode(',', $b->type ?? 'agricole')));

        return [
            'id'           => $b->id,
            'nom'          => $b->nom,
            'type'         => $b->type,               // String ex: "quincaillerie,agricole"
            'types'        => $typesArray,            // Array ex: ["quincaillerie", "agricole"]
            'localisation' => $b->adresse,
            'description'  => $b->description ?? null,
            'is_active'    => $b->statut === 'actif',
            'gestionnaires_count' => $b->gestionnaires_count ?? 0,
            'produits_count'      => $b->produits_count ?? 0,
            'created_at'   => $b->created_at,
        ];
    }

    /**
     * Obtenir le rapport détaillé des stocks et ventes pour une boutique
     */
    public function getRapportBoutique($id)
    {
        $boutique = Boutique::findOrFail($id);

        // 1. Produits affectés avec stock disponible et total vendu
        $pivots = DB::table('boutique_produit')
            ->where('boutique_id', $id)
            ->get();

        $produitsStockVentes = $pivots->map(function ($pivot) use ($id) {
            $produit = Produit::with('categorie:id,nom')->find($pivot->produit_id);
            if (!$produit) return null;

            // Calcul du total vendu de ce produit spécifiquement dans cette boutique
            $ventesStats = DB::table('commande_articles as ca')
                ->join('commandes as c', 'ca.commande_id', '=', 'c.id')
                ->where('c.boutique_id', $id)
                ->where('ca.produit_id', $pivot->produit_id)
                ->selectRaw('COALESCE(SUM(ca.quantite), 0) as quantite_vendue, COALESCE(SUM(ca.montant_ligne), 0) as total_ca')
                ->first();

            $quantiteVendue = (int) ($ventesStats->quantite_vendue ?? 0);
            $chiffreAffaires = (float) ($ventesStats->total_ca ?? 0);

            $catName = 'Général';
            if ($produit->categorie) {
                $catName = $produit->categorie->nom;
            } elseif ($produit->categories && $produit->categories->first()) {
                $catName = $produit->categories->first()->nom;
            }

            return [
                'produit_id'      => $produit->id,
                'nom_commercial'  => $produit->nom_commercial,
                'categorie_nom'   => $catName,
                'prix_unitaire'   => (float) $produit->prix_unitaire,
                'unite_mesure'    => $produit->unite_mesure,
                'stock_restant'   => (int) $pivot->stock_disponible,
                'stock_alerte'    => (int) $pivot->stock_alerte,
                'quantite_vendue' => $quantiteVendue,
                'chiffre_affaires'=> $chiffreAffaires,
            ];
        })->filter()->values();

        // 2. Commandes / Ventes récentes de la boutique
        $commandes = DB::table('commandes')
            ->where('boutique_id', $id)
            ->orderByDesc('created_at')
            ->take(50)
            ->get()
            ->map(function ($cmd) {
                $articles = DB::table('commande_articles as ca')
                    ->join('produits as p', 'ca.produit_id', '=', 'p.id')
                    ->where('ca.commande_id', $cmd->id)
                    ->select('p.nom_commercial', 'ca.quantite', 'ca.prix_unitaire', 'ca.montant_ligne')
                    ->get();

                return [
                    'id'             => $cmd->id,
                    'code_reference' => $cmd->code_reference,
                    'nom_client'     => $cmd->nom_client . ($cmd->prenom_client ? ' ' . $cmd->prenom_client : ''),
                    'telephone'      => $cmd->telephone ?? $cmd->telephone_client ?? '—',
                    'statut'         => $cmd->statut_commande,
                    'montant_total'  => (float) $cmd->montant_total,
                    'created_at'     => $cmd->created_at,
                    'articles'       => $articles,
                ];
            });

        // 3. Totaux globaux
        $totalCA = DB::table('commandes')->where('boutique_id', $id)->sum('montant_total');
        $totalCommandes = DB::table('commandes')->where('boutique_id', $id)->count();
        $totalArticlesVendus = DB::table('commande_articles as ca')
            ->join('commandes as c', 'ca.commande_id', '=', 'c.id')
            ->where('c.boutique_id', $id)
            ->sum('ca.quantite');

        return response()->json([
            'status' => 'success',
            'boutique' => [
                'id'       => $boutique->id,
                'nom'      => $boutique->nom,
                'adresse'  => $boutique->adresse,
            ],
            'kpi' => [
                'chiffre_affaires_total' => (float) $totalCA,
                'nombre_commandes'       => (int) $totalCommandes,
                'total_articles_vendus'  => (int) $totalArticlesVendus,
                'valeur_stock_restant'   => (float) $produitsStockVentes->sum(fn($p) => $p['stock_restant'] * $p['prix_unitaire']),
            ],
            'produits'  => $produitsStockVentes,
            'commandes' => $commandes,
        ]);
    }
}
