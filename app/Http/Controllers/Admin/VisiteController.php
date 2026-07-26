<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisiteLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisiteController extends Controller
{
    /**
     * GET /api/admin/visites
     * Récupère les statistiques de visites filtrables par intervalle de date et IP.
     */
    public function index(Request $request): JsonResponse
    {
        $query = VisiteLog::query();

        // Filtre par intervalle de dates
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Filtre par IP
        if ($ipFilter = $request->input('ip')) {
            $query->where('ip_adresse', 'LIKE', "%{$ipFilter}%");
        }

        // Filtre par type d'action
        if ($actionFilter = $request->input('type_action')) {
            $query->where('type_action', $actionFilter);
        }

        // Base query avec filtres de date pour les statistiques globales
        $statsBaseQuery = VisiteLog::query();
        if ($startDate) $statsBaseQuery->whereDate('created_at', '>=', $startDate);
        if ($endDate) $statsBaseQuery->whereDate('created_at', '<=', $endDate);

        $totalVisites = (clone $statsBaseQuery)->count();
        $ipsUniques = (clone $statsBaseQuery)->distinct('ip_adresse')->count('ip_adresse');

        // Top 5 pages les plus consultées
        $topPages = (clone $statsBaseQuery)
            ->select('page_visitee', DB::raw('COUNT(*) as total'))
            ->groupBy('page_visitee')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        // Statistiques par IP (Top 15 visiteurs les plus actifs)
        $visiteursParIp = (clone $statsBaseQuery)
            ->select(
                'ip_adresse',
                DB::raw('COUNT(*) as total_actions'),
                DB::raw('MAX(created_at) as derniere_visite'),
                DB::raw("SUM(CASE WHEN type_action = 'visite_page' THEN 1 ELSE 0 END) as nb_pages"),
                DB::raw("SUM(CASE WHEN type_action = 'clic_produit' THEN 1 ELSE 0 END) as nb_clics_produits"),
                DB::raw("SUM(CASE WHEN type_action = 'ajout_panier' THEN 1 ELSE 0 END) as nb_ajouts_panier")
            )
            ->groupBy('ip_adresse')
            ->orderBy('total_actions', 'desc')
            ->limit(15)
            ->get();

        $visiteursParIp->transform(function ($item) {
            $item->ip_formatted = $this->formatIpCountry($item->ip_adresse);
            return $item;
        });

        // Liste des récents logs de visite
        $perPage = (int) $request->input('per_page', 30);
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $logs->getCollection()->transform(function ($item) {
            $item->ip_formatted = $this->formatIpCountry($item->ip_adresse);
            return $item;
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [
                    'total_visites' => $totalVisites,
                    'ips_uniques' => $ipsUniques,
                ],
                'top_pages' => $topPages,
                'visiteurs_par_ip' => $visiteursParIp,
                'logs' => $logs,
            ]
        ]);
    }

    /**
     * GET /api/admin/visites/ip-details
     * Récupère l'historique complet d'une adresse IP spécifique pour le Modal.
     */
    public function ipDetails(Request $request): JsonResponse
    {
        $ip = $request->input('ip');

        if (!$ip) {
            return response()->json(['status' => 'error', 'message' => 'Adresse IP requise'], 400);
        }

        $logs = VisiteLog::where('ip_adresse', $ip)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $stats = VisiteLog::where('ip_adresse', $ip)
            ->select(
                DB::raw('COUNT(*) as total_actions'),
                DB::raw("SUM(CASE WHEN type_action = 'visite_page' THEN 1 ELSE 0 END) as nb_pages"),
                DB::raw("SUM(CASE WHEN type_action = 'clic_produit' THEN 1 ELSE 0 END) as nb_clics_produits"),
                DB::raw("SUM(CASE WHEN type_action = 'ajout_panier' THEN 1 ELSE 0 END) as nb_ajouts_panier"),
                DB::raw("SUM(CASE WHEN type_action = 'clic_whatsapp' THEN 1 ELSE 0 END) as nb_whatsapp")
            )
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'ip' => $ip,
                'ip_formatted' => $this->formatIpCountry($ip),
                'stats' => $stats,
                'logs' => $logs
            ]
        ]);
    }

    /**
     * Formate l'adresse IP avec le pays en parenthèse.
     */
    private function formatIpCountry(?string $ip): string
    {
        if (!$ip) return 'Inconnue (🇹🇬)';
        return "{$ip} (🇹🇬)";
    }
}
