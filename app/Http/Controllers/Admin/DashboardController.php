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
                    'id', 'code_reference',
                    'nom_client', 'prenom_client',
                    'telephone',
                    'montant_total', 'statut_commande', 'statut_paiement',
                    'created_at'
                ]);

            // ── Alertes opérationnelles (Temps réel) ──
            $produitsEnRuptureList = Produit::where('statut', 'actif')
                ->whereColumn('stock_disponible', '<=', 'stock_alerte')
                ->limit(5)
                ->get(['id', 'nom_commercial', 'stock_disponible', 'stock_alerte', 'slug']);

            $totalProduitsRupture = Produit::where('statut', 'actif')
                ->whereColumn('stock_disponible', '<=', 'stock_alerte')
                ->count();

            $commandesSouffranceCount = Commande::where('statut_commande', 'en_attente')
                ->where('created_at', '<=', Carbon::now()->subHours(2))
                ->count();

            $paiementsAttenteCount = Commande::where('statut_paiement', 'en_attente')->count();

            // ── Ventes par Catégorie ──
            $ventesCategories = DB::table('categories')
                ->leftJoin('produit_categories', 'categories.id', '=', 'produit_categories.categorie_id')
                ->leftJoin('produits', 'produit_categories.produit_id', '=', 'produits.id')
                ->leftJoin('commande_articles', 'produits.id', '=', 'commande_articles.produit_id')
                ->leftJoin('commandes', function ($join) {
                    $join->on('commande_articles.commande_id', '=', 'commandes.id')
                        ->whereIn('commandes.statut_commande', ['confirmee', 'preparee', 'expediee', 'livree']);
                })
                ->select(
                    'categories.id',
                    'categories.nom as nom_categorie',
                    'categories.slug',
                    DB::raw('COUNT(DISTINCT produits.id) as total_produits'),
                    DB::raw('COALESCE(SUM(commande_articles.montant_ligne), 0) as total_ventes')
                )
                ->where('categories.actif', true)
                ->groupBy('categories.id', 'categories.nom', 'categories.slug')
                ->orderByDesc('total_ventes')
                ->limit(5)
                ->get();

            // ── Répartition des Modes & Statuts de Paiement ──
            $modesPaiementData = Commande::select('statut_paiement', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(montant_total), 0) as montant'))
                ->groupBy('statut_paiement')
                ->get();

            // ── Top 5 Produits les Plus Vendus vs Stock ──
            $topProduits = DB::table('produits')
                ->leftJoin('commande_articles', 'produits.id', '=', 'commande_articles.produit_id')
                ->leftJoin('commandes', function ($join) {
                    $join->on('commande_articles.commande_id', '=', 'commandes.id')
                        ->whereIn('commandes.statut_commande', ['confirmee', 'preparee', 'expediee', 'livree']);
                })
                ->select(
                    'produits.id',
                    'produits.nom_commercial',
                    'produits.stock_disponible',
                    'produits.prix_unitaire',
                    DB::raw('COALESCE(SUM(commande_articles.quantite), 0) as total_vendu'),
                    DB::raw('COALESCE(SUM(commande_articles.montant_ligne), 0) as total_revenu')
                )
                ->where('produits.statut', 'actif')
                ->groupBy('produits.id', 'produits.nom_commercial', 'produits.stock_disponible', 'produits.prix_unitaire')
                ->orderByDesc('total_vendu')
                ->orderByDesc('produits.stock_disponible')
                ->limit(5)
                ->get();

            // ── Ventes par Ville / Zone Logistique ──
            $ventesVilles = Commande::select('ville', DB::raw('COUNT(*) as total_commandes'), DB::raw('COALESCE(SUM(montant_total), 0) as montant_total'))
                ->whereNotNull('ville')
                ->where('ville', '!=', '')
                ->groupBy('ville')
                ->orderByDesc('montant_total')
                ->limit(5)
                ->get();

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
                'alerts' => [
                    'produits_rupture' => $produitsEnRuptureList,
                    'total_produits_rupture' => $totalProduitsRupture,
                    'commandes_souffrance' => $commandesSouffranceCount,
                    'paiements_attente' => $paiementsAttenteCount,
                ],
                'ventes_mensuelles' => $ventesMensuelles,
                'ventes_categories' => $ventesCategories,
                'modes_paiement' => $modesPaiementData,
                'top_produits' => $topProduits,
                'ventes_villes' => $ventesVilles,
                'dernieres_commandes' => $dernieresCommandes,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $dashboardData,
        ]);
    }
}
