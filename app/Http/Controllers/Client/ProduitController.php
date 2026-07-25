<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Models\Categorie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
