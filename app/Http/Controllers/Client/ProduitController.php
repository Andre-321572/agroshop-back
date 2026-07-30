<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Models\Categorie;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProduitController extends Controller
{
    /**
     * GET /api/produits
     * Catalogue public des produits avec filtres, recherche et tri.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Produit::actif()
            ->with(['imagePrincipale', 'categories:id,nom,slug']);

        // Recherche textuelle par nom commercial, description, composition
        if ($search = $request->input('search')) {
            $searchTerm = "%{$search}%";
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nom_commercial', 'LIKE', $searchTerm)
                  ->orWhere('description', 'LIKE', $searchTerm)
                  ->orWhere('composition', 'LIKE', $searchTerm);
            });
        }

        // Filtre par slug ou ID de catégorie (inclut les sous-catégories)
        if ($categorySlug = $request->input('category')) {
            $query->whereHas('categories', function ($q) use ($categorySlug) {
                $q->where('categories.slug', $categorySlug)
                  ->orWhere('categories.id', $categorySlug)
                  ->orWhereHas('parent', function ($parentQuery) use ($categorySlug) {
                      $parentQuery->where('categories.slug', $categorySlug)
                                  ->orWhere('categories.id', $categorySlug);
                  });
            });
        }

        // Filtre par plage de prix
        if ($minPrice = $request->input('min_price')) {
            $query->where('prix_unitaire', '>=', (float) $minPrice);
        }
        if ($maxPrice = $request->input('max_price')) {
            $query->where('prix_unitaire', '<=', (float) $maxPrice);
        }

        // Tri
        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'price_asc'  => $query->orderBy('prix_unitaire', 'asc'),
            'price_desc' => $query->orderBy('prix_unitaire', 'desc'),
            'name_asc'   => $query->orderBy('nom_commercial', 'asc'),
            'name_desc'  => $query->orderBy('nom_commercial', 'desc'),
            default      => $query->orderBy('created_at', 'desc'),
        };

        $perPage = $request->input('per_page', 12);
        $produits = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $produits,
        ]);
    }

    /**
     * GET /api/produits/{slug}
     * Fiche produit détaillée avec images, documents et produits similaires.
     */
    public function show(string $slug): JsonResponse
    {
        $produit = Produit::actif()
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                  ->orWhere('id', $slug);
            })
            ->with(['categories:id,nom,slug', 'images', 'documents'])
            ->firstOrFail();

        // Produits similaires dans les mêmes catégories
        $categoriesIds = $produit->categories->pluck('id')->toArray();
        $produitsSimilaires = Produit::actif()
            ->where('id', '!=', $produit->id)
            ->whereHas('categories', function ($q) use ($categoriesIds) {
                $q->whereIn('categories.id', $categoriesIds);
            })
            ->with(['imagePrincipale', 'categories:id,nom,slug'])
            ->limit(4)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'produit' => $produit,
                'produits_similaires' => $produitsSimilaires,
            ],
        ]);
    }

    /**
     * POST /api/produits/recherche-ia
     * Recherche sémantique assistée par IA avec conseil agronomique.
     */
    public function rechercheIa(Request $request, AiService $aiService): JsonResponse
    {
        $queryText = trim($request->input('query', ''));
        if (empty($queryText)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Veuillez saisir un terme ou une question de recherche.',
                'data' => []
            ], 400);
        }

        // 1. Mise en cache de la réponse IA pour les requêtes identiques (3 heures)
        $cacheKey = 'ai_search_v1_' . md5(mb_strtolower($queryText));
        $aiResult = Cache::remember($cacheKey, now()->addHours(3), function () use ($queryText, $aiService) {
            $prompt = <<<PROMPT
Un client/agriculteur au Togo recherche dans notre boutique agricole : "{$queryText}".

Analyse cette requête et réponds UNIQUEMENT un JSON structuré ainsi :
{
  "mots_cles": ["3 à 5 mots-clés techniques ou noms de molécules/matières/produits"],
  "categorie_recommandee": "Engrais|Pesticides|Semences|Matériel|Toutes",
  "conseil_agronome": "Explication agronomique bienveillante et concise (2-3 phrases max) pour guider le choix du produit, adaptée aux conditions du Togo."
}
PROMPT;

            $sys = "Tu es l'Ingénieur Agronome virtuel d'AgroShop Togo. Tu conseilles les agriculteurs sur les traitements, engrais, semences et matériels.";

            return $aiService->chatJson($prompt, $sys);
        });

        // 2. Extraction des mots-clés IA ou repli sur les mots de la requête
        $motsCles = $aiResult['mots_cles'] ?? array_filter(explode(' ', $queryText), fn($w) => mb_strlen($w) > 2);
        if (empty($motsCles)) {
            $motsCles = [$queryText];
        }

        // 3. Recherche SQL pondérée sur les produits actifs
        $query = Produit::actif()->with(['imagePrincipale', 'categories:id,nom,slug']);

        $query->where(function ($q) use ($motsCles, $queryText) {
            // Correspondance directe exacte en priorité
            $searchTerm = "%{$queryText}%";
            $q->where('nom_commercial', 'LIKE', $searchTerm)
              ->orWhere('description', 'LIKE', $searchTerm)
              ->orWhere('composition', 'LIKE', $searchTerm);

            // Correspondance sur les mots-clés agronomiques identifiés par l'IA
            foreach ($motsCles as $mot) {
                $cleanMot = trim($mot);
                if (mb_strlen($cleanMot) > 2) {
                    $mTerm = "%{$cleanMot}%";
                    $q->orWhere('nom_commercial', 'LIKE', $mTerm)
                      ->orWhere('description', 'LIKE', $mTerm)
                      ->orWhere('composition', 'LIKE', $mTerm)
                      ->orWhere('principes_actifs', 'LIKE', $mTerm);
                }
            }
        });

        $produits = $query->limit(16)->get();

        return response()->json([
            'status' => 'success',
            'query' => $queryText,
            'conseil_ia' => $aiResult['conseil_agronome'] ?? null,
            'mots_cles' => $motsCles,
            'total' => $produits->count(),
            'data' => $produits
        ]);
    }
}
