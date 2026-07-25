<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ArticleBlog;
use App\Models\Produit;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * GET /api/blog
     * Liste des articles de blog publiés avec recherche et filtres par tag.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ArticleBlog::where('statut', 'publie')
            ->where('date_publication', '<=', now())
            ->with(['auteur:id,nom,prenom', 'tags:id,nom,slug,couleur']);

        // Recherche par titre ou contenu
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'LIKE', "%{$search}%")
                  ->orWhere('contenu', 'LIKE', "%{$search}%");
            });
        }

        // Filtre par tag
        if ($tagSlug = $request->input('tag')) {
            $query->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('tags.slug', $tagSlug)
                  ->orWhere('tags.id', $tagSlug);
            });
        }

        $perPage = $request->input('per_page', 9);
        $articles = $query->orderBy('date_publication', 'desc')->paginate($perPage);

        // Tags populaires pour la barre latérale / filtres
        $tags = Tag::has('articles')->withCount(['articles' => function ($q) {
            $q->where('statut', 'publie');
        }])->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'articles' => $articles,
                'tags' => $tags,
            ],
        ]);
    }

    /**
     * GET /api/blog/{slug}
     * Lecture d'un article avec incrémentation des vues et produits recommandés.
     */
    public function show(string $slug): JsonResponse
    {
        $article = ArticleBlog::where('statut', 'publie')
            ->where('date_publication', '<=', now())
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                  ->orWhere('id', $slug);
            })
            ->with(['auteur:id,nom,prenom', 'tags:id,nom,slug,couleur', 'produits' => function ($q) {
                $q->where('statut', 'actif')->with('imagePrincipale');
            }])
            ->firstOrFail();

        // Incrémentation des vues en base de données
        $article->increment('vues');
        $article->refresh();

        // Produits associés / recommandés d'après les tags de l'admin
        $produitsRecommandes = $article->produits;
        if ($produitsRecommandes->isEmpty()) {
            $produitsRecommandes = Produit::where('statut', 'actif')
                ->with('imagePrincipale')
                ->inRandomOrder()
                ->limit(4)
                ->get();
        }

        // Articles récents suggérés
        $articlesSuggest = ArticleBlog::where('statut', 'publie')
            ->where('id', '!=', $article->id)
            ->where('date_publication', '<=', now())
            ->orderBy('date_publication', 'desc')
            ->limit(3)
            ->get(['id', 'titre', 'slug', 'image_principale', 'date_publication', 'extrait', 'vues']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'article' => $article,
                'produits_recommandes' => $produitsRecommandes,
                'articles_suggeres' => $articlesSuggest,
            ],
        ]);
    }
}
