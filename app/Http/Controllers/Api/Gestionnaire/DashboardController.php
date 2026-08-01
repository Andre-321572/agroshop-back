<?php

namespace App\Http\Controllers\Api\Gestionnaire;

use App\Http\Controllers\Controller;
use App\Models\BoutiqueProduit;
use App\Models\Commande;
use App\Models\Gestionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function stats()
    {
        /** @var Gestionnaire|null $gestionnaire */
        $gestionnaire = Auth::user();
        $boutiqueId = $gestionnaire?->boutique_id ?? 1;
        $hasBoutiqueId = Schema::hasColumn('commandes', 'boutique_id');

        $chiffreAffaires = 0;
        $ventesAujourdhui = 0;
        $caAujourdhui = 0;

        try {
            $query = Commande::query();
            if ($hasBoutiqueId && $boutiqueId) {
                $query->where('boutique_id', $boutiqueId);
            }

            $chiffreAffaires = (float) (clone $query)->where('statut_commande', '!=', 'annulee')->sum('montant_total');

            $ventesAujourdhui = (int) (clone $query)->whereDate('created_at', today())->count();

            $caAujourdhui = (float) (clone $query)->whereDate('created_at', today())
                ->where('statut_commande', '!=', 'annulee')
                ->sum('montant_total');
        } catch (\Throwable $e) {
            // Log fallback
        }

        $produitsEnStock = (int) BoutiqueProduit::where('boutique_id', $boutiqueId)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'chiffre_affaires_total' => $chiffreAffaires,
                'ventes_du_jour'         => $ventesAujourdhui,
                'ca_du_jour'             => $caAujourdhui,
                'produits_en_stock'      => $produitsEnStock,
            ],
            'ca_du_jour'             => $caAujourdhui,
            'ventes_du_jour'         => $ventesAujourdhui,
            'produits_en_stock'      => $produitsEnStock,
            'chiffre_affaires_total' => $chiffreAffaires,
        ]);
    }
}
