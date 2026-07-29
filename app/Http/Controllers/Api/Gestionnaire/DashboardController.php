<?php

namespace App\Http\Controllers\Api\Gestionnaire;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function stats()
    {
        $gestionnaire = Auth::user();

        // Chiffre d'affaires de SA boutique (toutes les ventes validées)
        $chiffreAffaires = Commande::where('boutique_id', $gestionnaire->boutique_id)
                                   ->where('statut_commande', '!=', 'annulee')
                                   ->sum('montant_total');

        // Nombre de ventes aujourd'hui
        $ventesAujourdhui = Commande::where('boutique_id', $gestionnaire->boutique_id)
                                    ->whereDate('created_at', today())
                                    ->count();
                                    
        $caAujourdhui = Commande::where('boutique_id', $gestionnaire->boutique_id)
                                ->whereDate('created_at', today())
                                ->where('statut_commande', '!=', 'annulee')
                                ->sum('montant_total');

        return response()->json([
            'chiffre_affaires_total' => $chiffreAffaires,
            'ventes_aujourdhui' => $ventesAujourdhui,
            'ca_aujourdhui' => $caAujourdhui,
        ]);
    }
}
