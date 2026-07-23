<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ArticleBlog;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    /**
     * GET /api/home
     * Données d'accueil pour la page principale (Frontend Web & Mobile App).
     */
    public function index(): JsonResponse
    {
        // 1. Catégories principales actives (sans parent)
        $categoriesVedettes = Categorie::whereNull('parent_id')
            ->where('actif', true)
            ->withCount(['produits' => function ($q) {
                $q->where('statut', 'actif');
            }])
            ->orderBy('ordre_affichage')
            ->limit(6)
            ->get(['id', 'nom', 'description', 'slug']);

        // 2. Produits vedettes (Featured)
        $produitsVedettes = Produit::actif()
            ->featured()
            ->with(['imagePrincipale', 'categories:id,nom,slug'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // 3. Nouveautés produits
        $nouveautes = Produit::actif()
            ->with(['imagePrincipale', 'categories:id,nom,slug'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // 4. Derniers articles de blog publiés
        $derniersArticles = ArticleBlog::where('statut', 'publie')
            ->where('date_publication', '<=', now())
            ->with(['auteur:id,nom,prenom', 'tags:id,nom,slug,couleur'])
            ->orderBy('date_publication', 'desc')
            ->limit(3)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'categories_vedettes' => $categoriesVedettes,
                'produits_vedettes' => $produitsVedettes,
                'nouveautes' => $nouveautes,
                'derniers_articles' => $derniersArticles,
            ],
        ]);
    }
}
