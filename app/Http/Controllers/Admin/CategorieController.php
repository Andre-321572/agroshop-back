<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategorieController extends Controller
{
    /**
     * GET /api/admin/categories
     * Liste hiérarchique des catégories avec statistiques.
     */
    public function index(): JsonResponse
    {
        $categories = Categorie::select('categories.*')
            ->selectRaw('(SELECT COUNT(*) FROM categories c2 WHERE c2.parent_id = categories.id) as nb_enfants')
            ->selectRaw('(SELECT COUNT(*) FROM produit_categories pc WHERE pc.categorie_id = categories.id) as nb_produits')
            ->with('parent:id,nom')
            ->orderByRaw('parent_id IS NULL DESC, parent_id ASC, ordre_affichage ASC, nom ASC')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories,
        ]);
    }

    /**
     * GET /api/admin/categories/parentes
     * Liste des catégories parentes actives (pour les formulaires).
     */
    public function parentes(): JsonResponse
    {
        $parentes = Categorie::whereNull('parent_id')
            ->where('actif', true)
            ->orderBy('nom')
            ->get(['id', 'nom']);

        return response()->json([
            'status' => 'success',
            'data' => $parentes,
        ]);
    }

    /**
     * GET /api/admin/categories/{id}
     */
    public function show(int $id): JsonResponse
    {
        $categorie = Categorie::with(['parent:id,nom', 'enfants:id,nom,parent_id'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $categorie,
        ]);
    }

    /**
     * POST /api/admin/categories
     * Création d'une catégorie.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:100',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'ordre_affichage' => 'nullable|integer|min:0',
        ]);

        $slug = Str::slug($request->nom);
        if (Categorie::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . time();
        }

        $categorie = Categorie::create([
            'nom' => $request->nom,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'slug' => $slug,
            'ordre_affichage' => $request->input('ordre_affichage', 0),
            'actif' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie ajoutée avec succès.',
            'data' => $categorie,
        ], 201);
    }

    /**
     * PUT /api/admin/categories/{id}
     * Mise à jour d'une catégorie.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $categorie = Categorie::findOrFail($id);

        $request->validate([
            'nom' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'ordre_affichage' => 'nullable|integer|min:0',
            'actif' => 'nullable|boolean',
        ]);

        $data = $request->only(['nom', 'description', 'parent_id', 'ordre_affichage', 'actif']);

        // Régénérer le slug si le nom change
        if ($request->has('nom') && $request->nom !== $categorie->nom) {
            $slug = Str::slug($request->nom);
            if (Categorie::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $slug . '-' . time();
            }
            $data['slug'] = $slug;
        }

        // Empêcher la catégorie d'être son propre parent
        if (isset($data['parent_id']) && $data['parent_id'] == $id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une catégorie ne peut pas être son propre parent.',
            ], 422);
        }

        $categorie->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie mise à jour avec succès.',
            'data' => $categorie->fresh(),
        ]);
    }

    /**
     * DELETE /api/admin/categories/{id}
     * Suppression d'une catégorie (seulement si pas de sous-catégories).
     */
    public function destroy(int $id): JsonResponse
    {
        $categorie = Categorie::findOrFail($id);

        // Vérifier s'il y a des sous-catégories
        $hasChildren = Categorie::where('parent_id', $id)->exists();
        if ($hasChildren) {
            return response()->json([
                'status' => 'error',
                'message' => 'Impossible de supprimer une catégorie qui contient des sous-catégories.',
            ], 422);
        }

        $categorie->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Catégorie supprimée avec succès.',
        ]);
    }
}
