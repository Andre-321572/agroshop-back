<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Models\ProduitImage;
use App\Models\Categorie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProduitController extends Controller
{
    /**
     * GET /api/admin/produits
     * Liste paginée des produits avec filtres et recherche.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Produit::with(['categories', 'imagePrincipale'])
            ->where('statut', '!=', 'inactif');

        // Recherche textuelle
        if ($search = $request->input('search')) {
            $searchTerm = "%{$search}%";
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nom_commercial', 'LIKE', $searchTerm)
                  ->orWhere('description', 'LIKE', $searchTerm)
                  ->orWhere('composition', 'LIKE', $searchTerm);
            });
        }

        // Filtre par catégorie
        if ($categoryId = $request->input('category')) {
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        // Filtre par statut
        if ($status = $request->input('status')) {
            $query->where('statut', $status);
        }

        $perPage = $request->input('per_page', 10);
        $produits = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $produits,
        ]);
    }

    /**
     * GET /api/admin/produits/{id}
     * Détail complet d'un produit.
     */
    public function show(int $id): JsonResponse
    {
        $produit = Produit::with(['categories', 'images', 'documents'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $produit,
        ]);
    }

    /**
     * POST /api/admin/produits
     * Création d'un produit avec images et catégories.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nom_commercial' => 'required|string|max:200',
            'description' => 'nullable|string',
            'composition' => 'nullable|string',
            'principes_actifs' => 'nullable|string',
            'mode_emploi' => 'nullable|string',
            'dosage_recommande' => 'nullable|string|max:500',
            'precautions_usage' => 'nullable|string',
            'contre_indications' => 'nullable|string',
            'prix_unitaire' => 'required|numeric|min:0',
            'unite_mesure' => 'required|string|max:50',
            'stock_disponible' => 'required|integer|min:0',
            'stock_alerte' => 'nullable|integer|min:0',
            'poids' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'statut' => 'required|in:actif,inactif,rupture',
            'featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string',
            'slug' => 'nullable|string|max:200',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
            'categorie_principale' => 'nullable|integer|exists:categories,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // Génération du slug
            $slug = $request->input('slug') ?: Str::slug($request->nom_commercial);

            // Vérification d'unicité du slug
            if (Produit::where('slug', $slug)->exists()) {
                $slug = $slug . '-' . time();
            }

            $produit = Produit::create(array_merge(
                $request->except(['categories', 'categorie_principale', 'images', 'slug']),
                ['slug' => $slug, 'featured' => $request->boolean('featured')]
            ));

            // Association des catégories
            if ($request->has('categories')) {
                $categoriePrincipale = $request->input('categorie_principale');
                foreach ($request->categories as $categorieId) {
                    $produit->categories()->attach($categorieId, [
                        'principale' => ($categorieId == $categoriePrincipale) ? 1 : 0,
                    ]);
                }
            }

            // Upload des images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $fileName = $image->getClientOriginalName();
                $newFileName = uniqid('img_') . '.' . $image->getClientOriginalExtension();
                $image->storeAs('produits', $newFileName, 'public');

                ProduitImage::create([
                    'produit_id' => $produit->id,
                    'nom_fichier' => $fileName,
                    'url_image' => 'storage/produits/' . $newFileName,
                    'alt_text' => 'Image de ' . $produit->nom_commercial,
                    'ordre_affichage' => $index,
                    'principale' => ($index === 0) ? true : false,
                ]);
            }
        }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Produit ajouté avec succès !',
                'data' => $produit->load(['categories', 'images']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création du produit : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/admin/produits/{id}
     * Mise à jour d'un produit.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $produit = Produit::findOrFail($id);

        $request->validate([
            'nom_commercial' => 'sometimes|required|string|max:200',
            'description' => 'nullable|string',
            'composition' => 'nullable|string',
            'principes_actifs' => 'nullable|string',
            'mode_emploi' => 'nullable|string',
            'dosage_recommande' => 'nullable|string|max:500',
            'precautions_usage' => 'nullable|string',
            'contre_indications' => 'nullable|string',
            'prix_unitaire' => 'sometimes|required|numeric|min:0',
            'unite_mesure' => 'sometimes|required|string|max:50',
            'stock_disponible' => 'sometimes|required|integer|min:0',
            'stock_alerte' => 'nullable|integer|min:0',
            'poids' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'statut' => 'sometimes|in:actif,inactif,rupture',
            'featured' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
            'categorie_principale' => 'nullable|integer|exists:categories,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        DB::beginTransaction();

        try {
            $data = $request->except(['categories', 'categorie_principale', 'images']);

            // Mise à jour du slug si le nom change
            if ($request->has('nom_commercial') && $request->nom_commercial !== $produit->nom_commercial) {
                $slug = Str::slug($request->nom_commercial);
                if (Produit::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $slug . '-' . time();
                }
                $data['slug'] = $slug;
            }

            if ($request->has('featured')) {
                $data['featured'] = $request->boolean('featured');
            }

            $produit->update($data);

            // Synchroniser les catégories
            if ($request->has('categories')) {
                $categoriePrincipale = $request->input('categorie_principale');
                $syncData = [];
                foreach ($request->categories as $categorieId) {
                    $syncData[$categorieId] = [
                        'principale' => ($categorieId == $categoriePrincipale) ? 1 : 0,
                    ];
                }
                $produit->categories()->sync($syncData);
            }

            // Upload de nouvelles images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $fileName = $image->getClientOriginalName();
                $newFileName = uniqid('img_') . '.' . $image->getClientOriginalExtension();
                $image->storeAs('produits', $newFileName, 'public');

                ProduitImage::create([
                    'produit_id' => $produit->id,
                    'nom_fichier' => $fileName,
                    'url_image' => 'storage/produits/' . $newFileName,
                    'alt_text' => 'Image de ' . $produit->nom_commercial,
                    'ordre_affichage' => $produit->images()->count() + $index,
                    'principale' => false,
                ]);
            }
        }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Produit mis à jour avec succès.',
                'data' => $produit->load(['categories', 'images']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/admin/produits/{id}
     * Soft delete : passe le statut en inactif.
     */
    public function destroy(int $id): JsonResponse
    {
        $produit = Produit::findOrFail($id);
        $produit->update(['statut' => 'inactif']);

        return response()->json([
            'status' => 'success',
            'message' => 'Produit supprimé avec succès.',
        ]);
    }

    /**
     * POST /api/admin/produits/{id}/toggle-featured
     * Toggle du statut vedette.
     */
    public function toggleFeatured(int $id): JsonResponse
    {
        $produit = Produit::findOrFail($id);
        $produit->update(['featured' => !$produit->featured]);

        return response()->json([
            'status' => 'success',
            'message' => 'Statut vedette mis à jour.',
            'data' => ['featured' => $produit->fresh()->featured],
        ]);
    }

    /**
     * DELETE /api/admin/produits/{produitId}/images/{imageId}
     * Suppression d'une image de produit.
     */
    public function deleteImage(int $produitId, int $imageId): JsonResponse
    {
        $image = ProduitImage::where('produit_id', $produitId)
            ->where('id', $imageId)
            ->firstOrFail();

        // Supprimer le fichier physique
        Storage::disk('public')->delete('produits/' . basename($image->url_image));

        $image->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Image supprimée avec succès.',
        ]);
    }
}
