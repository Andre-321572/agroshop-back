<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Produit;
use App\Models\ArticleBlog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard
     * Statistiques générales du tableau de bord admin (Cache optimisé 60s).
     */
    public function index(): JsonResponse
    {
        $dashboardData = Cache::remember('admin_dashboard_cache', 60, function () {
            // Statistiques générales
            $totalVentes = Commande::where('statut_commande', '!=', 'annulee')->sum('montant_total');

            $commandesValidees = Commande::whereIn('statut_commande', ['confirmee', 'preparee', 'expediee', 'livree'])
                ->selectRaw('COUNT(*) as total, COALESCE(SUM(montant_total), 0) as montant')
                ->first();

            $commandesAttente = Commande::where('statut_commande', 'en_attente')
                ->selectRaw('COUNT(*) as total, COALESCE(SUM(montant_total), 0) as montant')
                ->first();

            $commandesEnCours = Commande::whereIn('statut_commande', ['en_attente', 'confirmee', 'preparee', 'expediee'])
                ->count();

            $produitsActifs = Produit::where('statut', 'actif')->count();
            $articlesTotal = ArticleBlog::count();
            $clientsTotal = Commande::distinct('telephone')->count('telephone');
            $commandesTotalCount = Commande::count();

            // 5 dernières commandes
            $dernieresCommandes = Commande::orderBy('created_at', 'desc')
                ->limit(6)
                ->get(['id', 'reference_commande', 'code_reference', 'nom_client', 'prenom_client', 'telephone', 'telephone_client', 'montant_total', 'statut_commande', 'statut_paiement', 'created_at']);

            return [
                'stats' => [
                    'total_ventes' => (float) $totalVentes,
                    'chiffre_affaires' => (float) $totalVentes,
                    'commandes_validees' => (int) ($commandesValidees->total ?? 0),
                    'ventes_validees' => (float) ($commandesValidees->montant ?? 0),
                    'commandes_attente' => (int) ($commandesAttente->total ?? 0),
                    'ventes_attente' => (float) ($commandesAttente->montant ?? 0),
                    'commandes_en_cours' => $commandesEnCours,
                    'commandes_livrees' => (int) ($commandesValidees->total ?? 0),
                    'total_commandes' => $commandesTotalCount,
                    'total_produits' => $produitsActifs,
                    'produits_actifs' => $produitsActifs,
                    'total_articles' => $articlesTotal,
                    'clients_total' => $clientsTotal,
                ],
                'dernieres_commandes' => $dernieresCommandes,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $dashboardData,
        ]);
    }
}
