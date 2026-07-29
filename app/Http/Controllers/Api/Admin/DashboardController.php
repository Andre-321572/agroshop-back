<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Boutique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function statsGenerales()
    {
        $chiffreAffairesTotal = Commande::where('statut_commande', '!=', 'annulee')->sum('montant_total');
        $commandesTotal = Commande::count();
        $boutiquesTotal = Boutique::count();
        
        // Ventes par boutique
        $ventesParBoutique = Boutique::withSum(['commandes' => function ($query) {
            $query->where('statut_commande', '!=', 'annulee');
        }], 'montant_total')->get();

        return response()->json([
            'chiffre_affaires_total' => $chiffreAffairesTotal,
            'commandes_total' => $commandesTotal,
            'boutiques_total' => $boutiquesTotal,
            'ventes_par_boutique' => $ventesParBoutique
        ]);
    }
}
