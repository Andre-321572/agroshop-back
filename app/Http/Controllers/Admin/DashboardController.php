<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Produit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard
     * Statistiques générales du tableau de bord admin.
     */
    public function index(): JsonResponse
    {
        // Statistiques générales
        $totalVentes = Commande::where('statut_commande', '!=', 'annulee')
            ->sum('montant_total');

        $commandesValidees = Commande::whereIn('statut_commande', ['confirmee', 'preparee', 'expediee', 'livree'])
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(montant_total), 0) as montant')
            ->first();

        $commandesAttente = Commande::where('statut_commande', 'en_attente')
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(montant_total), 0) as montant')
            ->first();

        $commandesEnCours = Commande::whereIn('statut_commande', ['en_attente', 'confirmee', 'preparee', 'expediee'])
            ->count();

        $produitsActifs = Produit::where('statut', 'actif')->count();

        $clientsTotal = Commande::distinct('telephone')->count('telephone');

        // Statistiques par catégories (top 6)
        $categoriesStats = DB::select("
            SELECT c.nom, COUNT(pc.produit_id) as nb_produits,
                   COALESCE(SUM(ca.quantite), 0) as ventes
            FROM categories c
            LEFT JOIN produit_categories pc ON c.id = pc.categorie_id
            LEFT JOIN produits p ON pc.produit_id = p.id
            LEFT JOIN commande_articles ca ON p.id = ca.produit_id
            LEFT JOIN commandes cmd ON ca.commande_id = cmd.id AND cmd.statut_commande != 'annulee'
            WHERE c.parent_id IS NULL
            GROUP BY c.id, c.nom
            ORDER BY ventes DESC
            LIMIT 6
        ");

        // Ventes mensuelles (6 derniers mois)
        $ventesMensuelles = DB::select("
            SELECT
                DATE_FORMAT(created_at, '%m-%Y') as mois,
                DATE_FORMAT(created_at, '%b %Y') as mois_format,
                SUM(montant_total) as total_ventes,
                COUNT(*) as nb_commandes
            FROM commandes
            WHERE statut_commande != 'annulee'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%m-%Y'), DATE_FORMAT(created_at, '%b %Y')
            ORDER BY DATE_FORMAT(created_at, '%Y-%m')
        ");

        // 5 dernières commandes
        $dernieresCommandes = Commande::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'code_reference', 'nom_client', 'prenom_client', 'telephone', 'montant_total', 'statut_commande', 'statut_paiement', 'created_at']);

        // Top 5 produits les plus vendus
        $produitsPopulaires = DB::select("
            SELECT p.nom_commercial,
                   SUM(ca.quantite) as total_vendu,
                   SUM(ca.quantite * ca.prix_unitaire) as chiffre_affaires
            FROM produits p
            JOIN commande_articles ca ON p.id = ca.produit_id
            JOIN commandes c ON ca.commande_id = c.id
            WHERE c.statut_commande != 'annulee'
            GROUP BY p.id, p.nom_commercial
            ORDER BY total_vendu DESC
            LIMIT 5
        ");

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [
                    'total_ventes' => (float) $totalVentes,
                    'commandes_validees' => (int) $commandesValidees->total,
                    'ventes_validees' => (float) $commandesValidees->montant,
                    'commandes_attente' => (int) $commandesAttente->total,
                    'ventes_attente' => (float) $commandesAttente->montant,
                    'commandes_en_cours' => $commandesEnCours,
                    'produits_actifs' => $produitsActifs,
                    'clients_total' => $clientsTotal,
                ],
                'categories_stats' => $categoriesStats,
                'ventes_mensuelles' => $ventesMensuelles,
                'dernieres_commandes' => $dernieresCommandes,
                'produits_populaires' => $produitsPopulaires,
            ],
        ]);
    }
}
