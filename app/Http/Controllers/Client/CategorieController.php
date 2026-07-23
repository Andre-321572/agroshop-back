<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\JsonResponse;

class CategorieController extends Controller
{
    /**
     * GET /api/categories
     * Liste des catégories actives avec leur sous-arborescence.
     */
    public function index(): JsonResponse
    {
        $categories = Categorie::whereNull('parent_id')
            ->where('actif', true)
            ->with(['enfants' => function ($q) {
                $q->where('actif', true)->orderBy('ordre_affichage')->orderBy('nom');
            }])
            ->withCount(['produits' => function ($q) {
                $q->where('statut', 'actif');
            }])
            ->orderBy('ordre_affichage')
            ->orderBy('nom')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * GET /api/categories/{slug}
     * Détails d'une catégorie et ses produits.
     */
    public function show(string $slug): JsonResponse
    {
        $categorie = Categorie::where('actif', true)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                  ->orWhere('id', $slug);
            })
            ->with(['parent:id,nom,slug', 'enfants' => function ($q) {
                $q->where('actif', true);
            }])
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $categorie,
        ]);
    }
}
