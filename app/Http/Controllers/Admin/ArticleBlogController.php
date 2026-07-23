<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleBlog;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleBlogController extends Controller
{
    /**
     * GET /api/admin/articles
     * Liste paginée des articles de blog avec filtres.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ArticleBlog::with(['auteur:id,nom,prenom', 'tags:id,nom,couleur']);

        // Filtre par statut
        if ($statut = $request->input('statut')) {
            $query->where('statut', $statut);
        }

        // Recherche textuelle
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'LIKE', "%{$search}%")
                  ->orWhere('contenu', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 10);
        $articles = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $articles,
        ]);
    }

    /**
     * GET /api/admin/articles/{id}
     */
    public function show(int $id): JsonResponse
    {
        $article = ArticleBlog::with(['auteur:id,nom,prenom', 'tags', 'produits:id,nom_commercial,slug'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $article,
        ]);
    }

    /**
     * POST /api/admin/articles
     * Création d'un article de blog.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'titre' => 'required|string|max:200',
            'contenu' => 'required|string',
            'extrait' => 'nullable|string',
            'statut' => 'required|in:brouillon,publie,archive',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string',
            'image_principale' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
            'produits' => 'nullable|array',
            'produits.*' => 'integer|exists:produits,id',
        ]);

        DB::beginTransaction();

        try {
            // Génération du slug
            $slug = Str::slug($request->titre);
            if (ArticleBlog::where('slug', $slug)->exists()) {
                $slug = $slug . '-' . time();
            }

            // Upload de l'image principale
        $imagePath = null;
        if ($request->hasFile('image_principale')) {
            $file = $request->file('image_principale');
            $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('blog', $fileName, 'public');
            $imagePath = 'storage/blog/' . $fileName;
        }

            $article = ArticleBlog::create([
                'titre' => $request->titre,
                'contenu' => $request->contenu,
                'extrait' => $request->extrait,
                'slug' => $slug,
                'statut' => $request->statut,
                'auteur_id' => $request->user()->id,
                'image_principale' => $imagePath,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'date_publication' => ($request->statut === 'publie') ? now() : null,
                'vues' => 0,
            ]);

            // Associer les tags
            if ($request->has('tags')) {
                $article->tags()->sync($request->tags);
            }

            // Associer les produits
            if ($request->has('produits')) {
                $article->produits()->sync($request->produits);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Article créé avec succès.',
                'data' => $article->load(['auteur:id,nom,prenom', 'tags', 'produits:id,nom_commercial']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/admin/articles/{id}
     * Mise à jour d'un article.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $article = ArticleBlog::findOrFail($id);

        $request->validate([
            'titre' => 'sometimes|required|string|max:200',
            'contenu' => 'sometimes|required|string',
            'extrait' => 'nullable|string',
            'statut' => 'sometimes|in:brouillon,publie,archive',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string',
            'image_principale' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
            'produits' => 'nullable|array',
            'produits.*' => 'integer|exists:produits,id',
        ]);

        DB::beginTransaction();

        try {
            $data = $request->except(['image_principale', 'tags', 'produits']);

            // Régénérer le slug si le titre change
            if ($request->has('titre') && $request->titre !== $article->titre) {
                $slug = Str::slug($request->titre);
                if (ArticleBlog::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $slug . '-' . time();
                }
                $data['slug'] = $slug;
            }

            // Gérer la publication
            if ($request->has('statut') && $request->statut === 'publie' && $article->statut !== 'publie') {
                $data['date_publication'] = now();
            }

            // Upload de la nouvelle image
            if ($request->hasFile('image_principale')) {
                $file = $request->file('image_principale');
                $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('blog', $fileName, 'public');
                $data['image_principale'] = 'storage/blog/' . $fileName;
            }

            $article->update($data);

            // Synchroniser les tags
            if ($request->has('tags')) {
                $article->tags()->sync($request->tags);
            }

            // Synchroniser les produits
            if ($request->has('produits')) {
                $article->produits()->sync($request->produits);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Article mis à jour avec succès.',
                'data' => $article->fresh()->load(['auteur:id,nom,prenom', 'tags', 'produits:id,nom_commercial']),
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
     * DELETE /api/admin/articles/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $article = ArticleBlog::findOrFail($id);

        $article->tags()->detach();
        $article->produits()->detach();
        $article->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Article supprimé avec succès.',
        ]);
    }
}
