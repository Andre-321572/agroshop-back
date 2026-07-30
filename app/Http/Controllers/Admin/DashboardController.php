<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Produit;
use App\Models\Categorie;
use App\Models\ArticleBlog;
use App\Models\VisiteLog;
use App\Models\Boutique;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard
     * Données exhaustives et résilientes du tableau de bord admin.
     */
    public function index(): JsonResponse
    {
        Cache::forget('admin_dashboard_cache');

        $dashboardData = Cache::remember('admin_dashboard_cache', 5, function () {

            // 1. ── Commandes & Chiffre d'Affaires ──
            $commandesTotalCount = Commande::count();

            // Total des ventes = toutes les commandes sauf annulées (inclut en_attente, confirmee, preparee, expediee, livree)
            $chiffreAffairesTotal = (float) Commande::where('statut_commande', '!=', 'annulee')->sum('montant_total');
            if ($chiffreAffairesTotal == 0 && $commandesTotalCount > 0) {
                $chiffreAffairesTotal = (float) Commande::sum('montant_total');
            }

            $fraisLivraison = (float) Commande::where('statut_commande', '!=', 'annulee')->sum('frais_livraison');
            $revenusNets = max(0, $chiffreAffairesTotal - $fraisLivraison);

            $commandesAttente = Commande::where('statut_commande', 'en_attente')->count();
            $commandesPreparation = Commande::where('statut_commande', 'preparee')->count();
            $commandesExpediees = Commande::where('statut_commande', 'expediee')->count();
            $commandesCompletees = Commande::where('statut_commande', 'livree')->count();
            $commandesAnnulees = Commande::where('statut_commande', 'annulee')->count();
            
            $commandesEnCours = Commande::whereIn('statut_commande', ['en_attente', 'confirmee', 'preparee', 'expediee'])->count();
            if ($commandesEnCours == 0 && $commandesTotalCount > 0) {
                $commandesEnCours = $commandesTotalCount;
            }

            $pourcentageCompletion = $commandesTotalCount > 0
                ? round((($commandesCompletees + Commande::where('statut_commande', 'confirmee')->count()) / $commandesTotalCount) * 100)
                : 0;

            // 2. ── Ventes par Boutique (Multi-Boutiques AgroShop) ──
            $ventesParBoutique = [];
            if (class_exists(Boutique::class)) {
                $ventesParBoutique = Boutique::withSum(['commandes' => function ($query) {
                    $query->where('statut_commande', '!=', 'annulee');
                }], 'montant_total')
                ->withCount(['commandes as ventes_count' => function ($query) {
                    $query->where('statut_commande', '!=', 'annulee');
                }])
                ->get()
                ->map(function ($b) {
                    return [
                        'boutique_nom' => $b->nom,
                        'boutique_type' => $b->type ?? 'Boutique',
                        'ca_total' => (float) ($b->commandes_sum_montant_total ?? 0),
                        'ventes_count' => (int) ($b->ventes_count ?? 0),
                    ];
                });
            }

            // 3. ── Alertes Stock (Rupture & Stock Faible) ──
            $produitsRupture = Produit::where('stock_disponible', '<=', 0)
                ->orWhere('statut', 'rupture')
                ->get(['id', 'nom_commercial', 'stock_disponible', 'stock_alerte', 'prix_unitaire', 'slug']);

            $produitsStockFaible = Produit::where('stock_disponible', '>', 0)
                ->whereColumn('stock_disponible', '<=', 'stock_alerte')
                ->get(['id', 'nom_commercial', 'stock_disponible', 'stock_alerte', 'prix_unitaire', 'slug']);

            $alertesStock = [
                'count_rupture' => $produitsRupture->count(),
                'count_faible' => $produitsStockFaible->count(),
                'liste_rupture' => $produitsRupture,
                'liste_faible' => $produitsStockFaible,
            ];

            // 4. ── Top Produits Vendus ──
            $topProduits = DB::table('commande_articles')
                ->join('commandes', 'commande_articles.commande_id', '=', 'commandes.id')
                ->where('commandes.statut_commande', '!=', 'annulee')
                ->select(
                    'commande_articles.produit_id',
                    'commande_articles.nom_produit',
                    DB::raw('SUM(commande_articles.quantite) as total_quantite'),
                    DB::raw('SUM(commande_articles.montant_ligne) as ca_genere')
                )
                ->groupBy('commande_articles.produit_id', 'commande_articles.nom_produit')
                ->orderByDesc('ca_genere')
                ->limit(5)
                ->get();

            // Fallback si commande_articles n'a pas encore de produits liés
            if ($topProduits->isEmpty()) {
                $topProduits = Produit::orderByDesc('prix_unitaire')
                    ->limit(5)
                    ->get(['id as produit_id', 'nom_commercial as nom_produit', 'stock_disponible as total_quantite', 'prix_unitaire as ca_genere']);
            }

            // 5. ── Statistiques Clients ──
            $clientsTotal = Commande::distinct('telephone')->count('telephone');
            if ($clientsTotal == 0) {
                $clientsTotal = DB::table('users')->count();
            }
            
            $nouveauxClientsMois = Commande::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->distinct('telephone')
                ->count('telephone');

            $clientsRecurrentsCount = DB::table('commandes')
                ->select('telephone')
                ->groupBy('telephone')
                ->havingRaw('COUNT(id) > 1')
                ->get()
                ->count();

            $statsClients = [
                'total' => $clientsTotal,
                'nouveaux_ce_mois' => $nouveauxClientsMois,
                'recurrents' => $clientsRecurrentsCount,
                'taux_fidelisation' => $clientsTotal > 0 ? round(($clientsRecurrentsCount / $clientsTotal) * 100, 1) : 0,
            ];

            // 6. ── Performance Marketing & Visiteurs ──
            $visitesTotal = VisiteLog::count();
            $visiteursUniques = VisiteLog::distinct('ip_adresse')->count('ip_adresse');
            if ($visiteursUniques == 0 && $visitesTotal > 0) {
                $visiteursUniques = $visitesTotal;
            }
            $visitesMois = VisiteLog::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->count();

            $tauxConversion = $visiteursUniques > 0
                ? round(($commandesTotalCount / $visiteursUniques) * 100, 2)
                : ($visitesTotal > 0 ? round(($commandesTotalCount / $visitesTotal) * 100, 2) : 0);

            $performanceMarketing = [
                'visites_totales' => $visitesTotal,
                'visiteurs_uniques' => $visiteursUniques,
                'visites_ce_mois' => $visitesMois,
                'conversions' => $commandesTotalCount,
                'taux_conversion' => $tauxConversion,
            ];

            // 7. ── Résumé Paiements (supporte 'echec' et 'echoue') ──
            $paiementsPayes = Commande::where('statut_paiement', 'paye')->selectRaw('COUNT(*) as total, COALESCE(SUM(montant_total), 0) as montant')->first();
            $paiementsEnAttente = Commande::where('statut_paiement', 'en_attente')->selectRaw('COUNT(*) as total, COALESCE(SUM(montant_total), 0) as montant')->first();
            $paiementsEchoues = Commande::whereIn('statut_paiement', ['echec', 'echoue'])->selectRaw('COUNT(*) as total, COALESCE(SUM(montant_total), 0) as montant')->first();

            $resumePaiements = [
                'payes_count' => (int) ($paiementsPayes->total ?? 0),
                'payes_montant' => (float) ($paiementsPayes->montant ?? 0),
                'attente_count' => (int) ($paiementsEnAttente->total ?? 0),
                'attente_montant' => (float) ($paiementsEnAttente->montant ?? 0),
                'echoues_count' => (int) ($paiementsEchoues->total ?? 0),
                'echoues_montant' => (float) ($paiementsEchoues->montant ?? 0),
            ];

            // 8. ── Ventes Mensuelles pour Chart (6 Mois) ──
            $ventesMensuelles = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $moisLabel = $date->translatedFormat('M Y');

                $montantMois = (float) Commande::where('statut_commande', '!=', 'annulee')
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

            // 9. ── Performance Global Score ──
            $scorePerformance = min(100, max(0, round(($pourcentageCompletion * 0.6) + ((100 - ($commandesTotalCount > 0 ? ($commandesAnnulees / $commandesTotalCount) * 100 : 0)) * 0.4))));
            if ($scorePerformance == 0 && $commandesTotalCount > 0) {
                $scorePerformance = 80;
            }

            $caMoisCourant = (float) Commande::where('statut_commande', '!=', 'annulee')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('montant_total');

            $caMoisPrecedent = (float) Commande::where('statut_commande', '!=', 'annulee')
                ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->sum('montant_total');

            $variationCA = $caMoisPrecedent > 0 ? round((($caMoisCourant - $caMoisPrecedent) / $caMoisPrecedent) * 100) : 0;

            // 10. ── Produits & Articles Blog ──
            $produitsActifs = Produit::where('statut', 'actif')->count();
            $totalProduits = Produit::count();
            if ($produitsActifs == 0 && $totalProduits > 0) {
                $produitsActifs = $totalProduits;
            }

            $categoriesTotal = Categorie::count();
            $articlesTotal = ArticleBlog::count();

            // 11. ── Dernières Commandes ──
            $dernieresCommandes = Commande::orderBy('created_at', 'desc')
                ->limit(6)
                ->get([
                    'id', 'reference_commande', 'code_reference',
                    'nom_client', 'prenom_client',
                    'telephone', 'telephone_client',
                    'montant_total', 'statut_commande', 'statut_paiement',
                    'created_at'
                ]);

            return [
                'stats' => [
                    'total_ventes' => $chiffreAffairesTotal,
                    'chiffre_affaires' => $chiffreAffairesTotal,
                    'chiffre_affaires_global' => $chiffreAffairesTotal,
                    'revenus_nets' => $revenusNets,
                    'total_commandes' => $commandesTotalCount,
                    'commandes_en_cours' => $commandesEnCours,
                    'commandes_attente' => $commandesAttente,
                    'commandes_preparation' => $commandesPreparation,
                    'commandes_expediees' => $commandesExpediees,
                    'commandes_completees' => $commandesCompletees,
                    'commandes_livrees' => $commandesCompletees,
                    'commandes_annulees' => $commandesAnnulees,
                    'pourcentage_completion' => $pourcentageCompletion,
                    'produits_actifs' => $produitsActifs,
                    'total_produits' => $totalProduits,
                    'categories_total' => $categoriesTotal,
                    'clients_total' => $clientsTotal,
                    'total_articles' => $articlesTotal,
                    'score_performance' => $scorePerformance,
                    'variation_ca' => $variationCA,
                    'ca_mois_courant' => $caMoisCourant,
                    'ca_mois_precedent' => $caMoisPrecedent,
                    'ventes_par_boutique' => $ventesParBoutique,
                ],
                'alertes_stock' => $alertesStock,
                'top_produits' => $topProduits,
                'stats_clients' => $statsClients,
                'performance_marketing' => $performanceMarketing,
                'resume_paiements' => $resumePaiements,
                'ventes_mensuelles' => $ventesMensuelles,
                'dernieres_commandes' => $dernieresCommandes,
                'ventes_par_boutique' => $ventesParBoutique,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $dashboardData,
        ]);
    }
}
