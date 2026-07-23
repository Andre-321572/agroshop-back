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

// --- Controllers Client / Public ---
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\ProduitController;
use App\Http\Controllers\Client\CategorieController;
use App\Http\Controllers\Client\CommandeController;
use App\Http\Controllers\Client\BlogController;
use App\Http\Controllers\Client\ContactController;
use App\Http\Controllers\Client\ParametreController;
use App\Http\Controllers\Client\FaqController;

/*
|--------------------------------------------------------------------------
| API Routes - Agroshop API
|--------------------------------------------------------------------------
*/

// =========================================================================
// 1. ROUTES PUBLIQUES CLIENT / E-COMMERCE (Web & Mobile App)
// =========================================================================

// --- Page d'accueil ---
Route::get('/home', [HomeController::class, 'index']);

// --- Catalogue Produits ---
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

    // Tableau de bord
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

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

    // Paramètres Système
    Route::get('/parametres', [AdminParametreSystemeController::class, 'index']);
    Route::get('/parametres/{cle}', [AdminParametreSystemeController::class, 'show']);
    Route::put('/parametres/{cle}', [AdminParametreSystemeController::class, 'update']);
});
