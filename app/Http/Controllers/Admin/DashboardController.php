<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Produit;
use App\Models\Categorie;
use App\Models\ArticleBlog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard
     * Statistiques complètes du tableau de bord admin (design premium).
     */
    public function index(): JsonResponse
    {
        Cache::forget('admin_dashboard_cache');

        $dashboardData = Cache::remember('admin_dashboard_cache', 5, function () {

            // ── Total des commandes ──
            $commandesTotalCount = Commande::count();

            // ── Commandes validées (confirmées, préparées, expédiées, livrées) ──
            $commandesValidees = Commande::whereIn('statut_commande', ['confirmee', 'preparee', 'expediee', 'livree'])
                ->selectRaw('COUNT(*) as total, COALESCE(SUM(montant_total), 0) as montant')
                ->first();

            // ── Chiffre d'affaires = commandes validées ──
            $chiffreAffaires = (float) ($commandesValidees->montant ?? 0);

            // ── Revenus nets = CA - frais de livraison ──
            $fraisLivraison = (float) Commande::whereIn('statut_commande', ['confirmee', 'preparee', 'expediee', 'livree'])
                ->sum('frais_livraison');
            $revenusNets = $chiffreAffaires - $fraisLivraison;

            // ── Commandes en attente ──
            $commandesAttente = Commande::where('statut_commande', 'en_attente')
                ->selectRaw('COUNT(*) as total, COALESCE(SUM(montant_total), 0) as montant')
                ->first();

            // ── Commandes en cours (non terminées, non annulées) ──
            $commandesEnCours = Commande::whereIn('statut_commande', ['en_attente', 'confirmee', 'preparee', 'expediee'])
                ->count();

            // ── Commandes complétées (livrées) ──
            $commandesCompletees = Commande::where('statut_commande', 'livree')->count();

            // ── Pourcentage de complétion ──
            $pourcentageCompletion = $commandesTotalCount > 0
                ? round(($commandesCompletees / $commandesTotalCount) * 100)
                : 0;

            // ── Produits actifs ──
            $produitsActifs = Produit::where('statut', 'actif')->count();

            // ── Total catégories actives ──
            $categoriesTotal = Categorie::where('actif', true)->count();

            // ── Clients uniques ──
            $clientsTotal = Commande::distinct('telephone')->count('telephone');

            // ── Articles blog ──
            $articlesTotal = ArticleBlog::count();

            // ── Ventes mensuelles (6 derniers mois) pour le graphique ──
            $ventesMensuelles = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $moisLabel = $date->translatedFormat('M Y');

                $montantMois = (float) Commande::whereIn('statut_commande', ['confirmee', 'preparee', 'expediee', 'livree'])
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('montant_total');

                $commandesMois = Commande::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();

                $ventesMensuelles[] = [
                    'mois' => $moisLabel,
                    'montant' => $montantMois,
                    'commandes' => $commandesMois,
                ];
            }

            // ── Score de performance (basé sur plusieurs métriques) ──
            $tauxConversion = $commandesTotalCount > 0
                ? ($commandesCompletees / $commandesTotalCount) * 100
                : 0;
            $tauxAnnulation = $commandesTotalCount > 0
                ? (Commande::where('statut_commande', 'annulee')->count() / $commandesTotalCount) * 100
                : 0;

            // Score = pondération (conversion 60% + non-annulation 40%)
            $scorePerformance = round(($tauxConversion * 0.6) + ((100 - $tauxAnnulation) * 0.4));
            $scorePerformance = min(100, max(0, $scorePerformance));

            // ── Variation mois courant vs mois précédent ──
            $caMoisCourant = (float) Commande::whereIn('statut_commande', ['confirmee', 'preparee', 'expediee', 'livree'])
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('montant_total');

            $caMoisPrecedent = (float) Commande::whereIn('statut_commande', ['confirmee', 'preparee', 'expediee', 'livree'])
                ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->sum('montant_total');

            $variationCA = $caMoisPrecedent > 0
                ? round((($caMoisCourant - $caMoisPrecedent) / $caMoisPrecedent) * 100)
                : 0;

            // ── 5 dernières commandes ──
            $dernieresCommandes = Commande::orderBy('created_at', 'desc')
                ->limit(5)
                ->get([
                    'id', 'reference_commande', 'code_reference',
                    'nom_client', 'prenom_client',
                    'telephone', 'telephone_client',
                    'montant_total', 'statut_commande', 'statut_paiement',
                    'created_at'
                ]);

            return [
                'stats' => [
                    'total_commandes' => $commandesTotalCount,
                    'chiffre_affaires' => $chiffreAffaires,
                    'total_ventes' => $chiffreAffaires,
                    'revenus_nets' => max(0, $revenusNets),
                    'produits_actifs' => $produitsActifs,
                    'total_produits' => $produitsActifs,
                    'categories_total' => $categoriesTotal,
                    'clients_total' => $clientsTotal,
                    'commandes_validees' => (int) ($commandesValidees->total ?? 0),
                    'commandes_attente' => (int) ($commandesAttente->total ?? 0),
                    'commandes_en_cours' => $commandesEnCours,
                    'commandes_completees' => $commandesCompletees,
                    'pourcentage_completion' => $pourcentageCompletion,
                    'ventes_attente' => (float) ($commandesAttente->montant ?? 0),
                    'total_articles' => $articlesTotal,
                    'score_performance' => $scorePerformance,
                    'variation_ca' => $variationCA,
                    'ca_mois_courant' => $caMoisCourant,
                    'ca_mois_precedent' => $caMoisPrecedent,
                ],
                'ventes_mensuelles' => $ventesMensuelles,
                'dernieres_commandes' => $dernieresCommandes,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $dashboardData,
        ]);
    }
}
