<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// --- Controllers Admin ---
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProduitController as AdminProduitController;
use App\Http\Controllers\Admin\CategorieController as AdminCategorieController;
use App\Http\Controllers\Admin\CommandeController as AdminCommandeController;
use App\Http\Controllers\Admin\ArticleBlogController as AdminArticleBlogController;
use App\Http\Controllers\Admin\AdministrateurController as AdminAdministrateurController;
use App\Http\Controllers\Admin\ParametreSystemeController as AdminParametreSystemeController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\VisiteController as AdminVisiteController;
use App\Http\Controllers\Admin\PartenaireController as AdminPartenaireController;

// --- Controllers Client / Public ---
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\ProduitController;
use App\Http\Controllers\Client\CategorieController;
use App\Http\Controllers\Client\CommandeController;
use App\Http\Controllers\Client\BlogController;
use App\Http\Controllers\Client\ContactController;
use App\Http\Controllers\Client\ParametreController;
use App\Http\Controllers\Client\FaqController;
use App\Http\Controllers\Client\PartenaireController as ClientPartenaireController;
use App\Http\Controllers\Client\VisiteController as ClientVisiteController;

/*
|--------------------------------------------------------------------------
| API Routes - Agroshop API
|--------------------------------------------------------------------------
*/

// --- Fallback pour l'authentification API (évite l'erreur Route [login] not defined) ---
Route::get('/unauthorized', function () {
    return response()->json(['message' => 'Unauthenticated. Token invalide ou expiré.'], 401);
})->name('login');

// =========================================================================
// 1. ROUTES PUBLIQUES CLIENT / E-COMMERCE (Web & Mobile App)
// =========================================================================

// --- Tracking des visites et actions ---
Route::post('/track-visite', [ClientVisiteController::class, 'store']);

// --- Page d'accueil ---
Route::get('/home', [HomeController::class, 'index']);

// --- Catalogue Produits ---
Route::post('/produits/recherche-ia', [ProduitController::class, 'rechercheIa']);
Route::get('/produits', [ProduitController::class, 'index']);
Route::get('/produits/{slug}', [ProduitController::class, 'show']);

// --- Catégories ---
Route::get('/categories', [CategorieController::class, 'index']);
Route::get('/categories/{slug}', [CategorieController::class, 'show']);

// --- Commandes & Suivi Public ---
Route::post('/commandes', [CommandeController::class, 'store']);
Route::get('/commandes/suivi/{reference}', [CommandeController::class, 'suivi']);

// --- Blog Public ---
Route::get('/blog', [BlogController::class, 'index']);
Route::get('/blog/{slug}', [BlogController::class, 'show']);

// --- Contact ---
Route::post('/contact', [ContactController::class, 'store']);

// --- Paramètres Publics ---
Route::get('/parametres', [ParametreController::class, 'index']);

// --- FAQ ---
Route::get('/faq', [FaqController::class, 'index']);

// --- Partenaires ---
Route::get('/partenaires', [ClientPartenaireController::class, 'index']);

// --- IA publique : recommandations produits (sans auth, optimisé pour panier / fiche produit)
Route::post('/ai/produits/recommandations', [\App\Http\Controllers\Api\Admin\AiAssistantController::class, 'produitsRecommandes']);


// =========================================================================
// 2. ROUTES ADMINISTRATION (Admin Panel API)
// =========================================================================

// --- Auth Admin (Public) ---
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login']);
});

// --- Actions Admin Protégées (Sanctum) ---
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {

    // Authentification & Profil
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/me', [AdminAuthController::class, 'me']);

    // Tableau de bord & Tracking Visiteurs
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/visites', [AdminVisiteController::class, 'index']);
    Route::get('/visites/ip-details', [AdminVisiteController::class, 'ipDetails']);

    // Produits
    Route::apiResource('produits', AdminProduitController::class);
    Route::post('/produits/{id}/toggle-featured', [AdminProduitController::class, 'toggleFeatured']);
    Route::delete('/produits/{produitId}/images/{imageId}', [AdminProduitController::class, 'deleteImage']);

    // Catégories
    Route::get('/categories/parentes', [AdminCategorieController::class, 'parentes']);
    Route::apiResource('categories', AdminCategorieController::class);

    // Commandes
    Route::get('/commandes', [AdminCommandeController::class, 'index']);
    Route::get('/commandes/{id}', [AdminCommandeController::class, 'show']);
    Route::get('/commandes/{id}/receipt', [AdminCommandeController::class, 'receipt']);
    Route::put('/commandes/{id}/statut', [AdminCommandeController::class, 'updateStatut']);
    Route::put('/commandes/{id}/paiement', [AdminCommandeController::class, 'updatePaiement']);
    Route::put('/commandes/{id}/notes', [AdminCommandeController::class, 'updateNotes']);

    // 📰 GESTION DU BLOG ADMIN (Articles)
    Route::get('/blog', [AdminArticleBlogController::class, 'index']);          // Liste des articles
    Route::post('/blog', [AdminArticleBlogController::class, 'store']);         // Créer un article
    Route::get('/blog/{id}', [AdminArticleBlogController::class, 'show']);       // Détails d'un article
    Route::put('/blog/{id}', [AdminArticleBlogController::class, 'update']);     // Modifier un article
    Route::delete('/blog/{id}', [AdminArticleBlogController::class, 'destroy']); // Supprimer un article
    
    // Alias pour la ressource /articles
    Route::apiResource('articles', AdminArticleBlogController::class);

    // 🏷️ GESTION DES TAGS BLOG ADMIN
    Route::apiResource('tags', AdminTagController::class);

    // Administrateurs
    Route::apiResource('administrateurs', AdminAdministrateurController::class);
    Route::post('/administrateurs/{id}/reset-password', [AdminAdministrateurController::class, 'resetPassword']);

    // Partenaires
    Route::apiResource('partenaires', AdminPartenaireController::class);

    // Paramètres Système
    Route::get('/parametres', [AdminParametreSystemeController::class, 'index']);
    Route::get('/parametres/{cle}', [AdminParametreSystemeController::class, 'show']);
    Route::put('/parametres/{cle}', [AdminParametreSystemeController::class, 'update']);

    // --- Multi-Boutiques & Délégués ---
    Route::get('/boutiques/{id}/produits-approvisionnement', [\App\Http\Controllers\Api\Admin\BoutiqueController::class, 'getProduitsApprovisionnement']);
    Route::post('/boutiques/{id}/approvisionner', [\App\Http\Controllers\Api\Admin\BoutiqueController::class, 'approvisionner']);
    Route::apiResource('boutiques', \App\Http\Controllers\Api\Admin\BoutiqueController::class);
    Route::apiResource('gestionnaires', \App\Http\Controllers\Api\Admin\GestionnaireController::class);
    Route::get('/dashboard/stats-generales', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'statsGenerales']);
    
    // Rapports
    Route::get('/rapports', [\App\Http\Controllers\Api\Admin\RapportController::class, 'index']);
    Route::post('/rapports/{id}/marquer-lu', [\App\Http\Controllers\Api\Admin\RapportController::class, 'marquerCommeLu']);
    Route::get('/rapports/{id}/telecharger', [\App\Http\Controllers\Api\Admin\RapportController::class, 'telecharger']);

    // Assistant IA — endpoints spécialisés
    Route::post('/ai/chat', [\App\Http\Controllers\Api\Admin\AiAssistantController::class, 'chat']);
    Route::post('/ai/produits/generer-fiche', [\App\Http\Controllers\Api\Admin\AiAssistantController::class, 'genererFicheProduit']);
    Route::post('/ai/produits/valider-saisie', [\App\Http\Controllers\Api\Admin\AiAssistantController::class, 'validerSaisieProduit']);
    Route::get('/ai/boutiques/{boutiqueId}/suggerer-reappro', [\App\Http\Controllers\Api\Admin\AiAssistantController::class, 'suggererReappro']);
    Route::post('/ai/rapports/generer', [\App\Http\Controllers\Api\Admin\AiAssistantController::class, 'genererRapport']);
    Route::get('/ai/dashboard/insights', [\App\Http\Controllers\Api\Admin\AiAssistantController::class, 'dashboardInsights']);
    Route::post('/ai/blog/generer-article', [\App\Http\Controllers\Api\Admin\AiAssistantController::class, 'genererArticleBlog']);
});

// =========================================================================
// 3. ROUTES GESTIONNAIRE DE BOUTIQUE
// =========================================================================

// --- Auth Gestionnaire (Public) ---
Route::prefix('gestionnaire')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\Gestionnaire\AuthController::class, 'login']);
});

// --- Actions Gestionnaire Protégées ---
Route::prefix('gestionnaire')->middleware('auth:gestionnaire')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Api\Gestionnaire\AuthController::class, 'logout']);
    Route::get('/me',     [\App\Http\Controllers\Api\Gestionnaire\AuthController::class, 'me']);

    Route::get('/dashboard', [\App\Http\Controllers\Api\Gestionnaire\DashboardController::class, 'stats']);

    Route::get('/stock', [\App\Http\Controllers\Api\Gestionnaire\StockController::class, 'index']);
    Route::post('/stock/ajuster/{produit_id}', [\App\Http\Controllers\Api\Gestionnaire\StockController::class, 'ajuster']);

    Route::post('/ventes', [\App\Http\Controllers\Api\Gestionnaire\VenteController::class, 'store']);

    Route::post('/rapports/generer', [\App\Http\Controllers\Api\Gestionnaire\RapportController::class, 'genererEtEnvoyer']);

    // --- Assistant IA — endpoints Gestionnaire
    Route::post('/ai/rapports/generer', [\App\Http\Controllers\Api\Admin\AiAssistantController::class, 'genererRapport']);
    Route::get('/ai/boutiques/{boutiqueId}/suggerer-reappro', [\App\Http\Controllers\Api\Admin\AiAssistantController::class, 'suggererReappro']);
    Route::post('/ai/produits/valider-saisie', [\App\Http\Controllers\Api\Admin\AiAssistantController::class, 'validerSaisieProduit']);
});
