<?php

namespace App\Http\Controllers\Api\Gestionnaire;

use App\Http\Controllers\Controller;
use App\Models\BoutiqueProduit;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    public function index()
    {
        $gestionnaire = Auth::user();
        
        $stocks = BoutiqueProduit::where('boutique_id', $gestionnaire->boutique_id)
                    ->with('produit')
                    ->get();
                    
        return response()->json($stocks);
    }

    public function ajuster(Request $request, $produit_id)
    {
        $gestionnaire = Auth::user();

        $validated = $request->validate([
            'quantite' => 'required|integer', // peut être positif ou négatif
            'type_ajustement' => 'required|string', // ex: 'reception', 'inventaire', 'perte'
        ]);

        $stock = BoutiqueProduit::firstOrCreate(
            ['boutique_id' => $gestionnaire->boutique_id, 'produit_id' => $produit_id],
            ['stock_disponible' => 0, 'stock_alerte' => 10]
        );

        $stock->stock_disponible += $validated['quantite'];
        $stock->save();

        // TODO: Enregistrer l'historique de l'ajustement (dans une table dédiée si nécessaire)

        return response()->json(['message' => 'Stock ajusté avec succès', 'stock' => $stock]);
    }
}
