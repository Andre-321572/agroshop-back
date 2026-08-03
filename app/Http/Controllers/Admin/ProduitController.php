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
        $query = Produit::with(['categories', 'imagePrincipale', 'images']);

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

        $perPage = $request->input('per_page', 100);
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
            'url_image' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
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

            $mainUrl = $request->input('url_image', 'storage/produits/default.jpg');

            // Upload direct de l'image principale si fichier envoyé
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $newFileName = uniqid('main_') . '.' . $file->getClientOriginalExtension();
                $file->storeAs('produits', $newFileName, 'public');
                $mainUrl = 'storage/produits/' . $newFileName;
            } elseif ($request->hasFile('image_principale')) {
                $file = $request->file('image_principale');
                $newFileName = uniqid('main_') . '.' . $file->getClientOriginalExtension();
                $file->storeAs('produits', $newFileName, 'public');
                $mainUrl = 'storage/produits/' . $newFileName;
            }

            $produit = Produit::create(array_merge(
                $request->except(['categories', 'categorie_principale', 'images', 'image', 'image_principale', 'slug']),
                [
                    'slug' => $slug, 
                    'featured' => $request->boolean('featured'),
                    'url_image' => $mainUrl
                ]
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

            // Sync main ProduitImage relation
            ProduitImage::create([
                'produit_id' => $produit->id,
                'nom_fichier' => basename($mainUrl),
                'url_image' => $mainUrl,
                'alt_text' => 'Image principale de ' . $produit->nom_commercial,
                'ordre_affichage' => 0,
                'principale' => true,
            ]);

            // Upload des images galerie
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
                        'ordre_affichage' => $index + 1,
                        'principale' => false,
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
            'url_image' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories,id',
            'categorie_principale' => 'nullable|integer|exists:categories,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        DB::beginTransaction();

        try {
            $data = $request->except(['categories', 'categorie_principale', 'images', 'image', 'image_principale']);

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

            // Upload direct de l'image principale si fichier envoyé
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $newFileName = uniqid('main_') . '.' . $file->getClientOriginalExtension();
                $file->storeAs('produits', $newFileName, 'public');
                $data['url_image'] = 'storage/produits/' . $newFileName;
            } elseif ($request->hasFile('image_principale')) {
                $file = $request->file('image_principale');
                $newFileName = uniqid('main_') . '.' . $file->getClientOriginalExtension();
                $file->storeAs('produits', $newFileName, 'public');
                $data['url_image'] = 'storage/produits/' . $newFileName;
            }

            $produit->update($data);

            // Synchroniser la relation ProduitImage principale
            if (!empty($data['url_image'])) {
                ProduitImage::where('produit_id', $produit->id)->update(['principale' => false]);
                ProduitImage::updateOrCreate(
                    ['produit_id' => $produit->id, 'url_image' => $data['url_image']],
                    [
                        'nom_fichier' => basename($data['url_image']),
                        'alt_text' => 'Image principale de ' . $produit->nom_commercial,
                        'ordre_affichage' => 0,
                        'principale' => true,
                    ]
                );
            }

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

            // Upload de nouvelles images de galerie
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
                'message' => 'Erreur lors de la mise à jour du produit : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/admin/produits/{id}/toggle-featured
     */
    public function toggleFeatured(int $id): JsonResponse
    {
        $produit = Produit::findOrFail($id);
        $produit->update(['featured' => !$produit->featured]);

        return response()->json([
            'status' => 'success',
            'message' => 'Statut vedette mis à jour.',
            'featured' => $produit->featured,
        ]);
    }

    /**
     * DELETE /api/admin/produits/{produitId}/images/{imageId}
     */
    public function deleteImage(int $produitId, int $imageId): JsonResponse
    {
        $image = ProduitImage::where('produit_id', $produitId)->where('id', $imageId)->firstOrFail();
        
        if ($image->url_image && Storage::disk('public')->exists(str_replace('storage/', '', $image->url_image))) {
            Storage::disk('public')->delete(str_replace('storage/', '', $image->url_image));
        }

        $image->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Image supprimée avec succès.',
        ]);
    }

    /**
     * DELETE /api/admin/produits/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $produit = Produit::findOrFail($id);
        $produit->update(['statut' => 'inactif']);

        return response()->json([
            'status' => 'success',
            'message' => 'Produit désactivé avec succès.',
        ]);
    }

    /**
     * GET /api/admin/produits/{id}/boutiques
     * Récupérer la liste des boutiques avec le stock affecté pour ce produit.
     */
    public function getBoutiques(int $id): JsonResponse
    {
        $produit = Produit::findOrFail($id);
        $boutiques = DB::table('boutiques')
            ->where('statut', 'actif')
            ->get();

        $existingPivot = DB::table('boutique_produit')
            ->where('produit_id', $id)
            ->get()
            ->keyBy('boutique_id');

        $data = $boutiques->map(function ($b) use ($existingPivot) {
            $pivot = $existingPivot->get($b->id);
            return [
                'id' => $b->id,
                'nom' => $b->nom,
                'adresse' => $b->adresse,
                'stock_disponible' => $pivot ? (int) $pivot->stock_disponible : 0,
                'stock_alerte' => $pivot ? (int) $pivot->stock_alerte : 10,
                'affecte' => $pivot !== null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'produit' => ['id' => $produit->id, 'nom_commercial' => $produit->nom_commercial],
            'data' => $data,
        ]);
    }

    /**
     * POST /api/admin/produits/{id}/affecter-boutiques
     * Affecter un produit à une ou plusieurs boutiques avec stock et alerte.
     */
    public function affecterBoutiques(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'boutiques' => 'required|array|min:1',
            'boutiques.*.boutique_id' => 'required|exists:boutiques,id',
            'boutiques.*.stock_disponible' => 'required|integer|min:0',
            'boutiques.*.stock_alerte' => 'nullable|integer|min:0',
        ]);

        $produit = Produit::findOrFail($id);
        $count = 0;

        foreach ($request->boutiques as $item) {
            $boutiqueId = $item['boutique_id'];
            $stock = (int) $item['stock_disponible'];
            $alerte = isset($item['stock_alerte']) ? (int) $item['stock_alerte'] : 10;

            DB::table('boutique_produit')->updateOrInsert(
                ['boutique_id' => $boutiqueId, 'produit_id' => $id],
                [
                    'stock_disponible' => $stock,
                    'stock_alerte' => $alerte,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $count++;
        }

        return response()->json([
            'status' => 'success',
            'message' => "Produit affecté à {$count} boutique(s) avec succès !",
            'data' => $produit,
        ]);
    }
}
