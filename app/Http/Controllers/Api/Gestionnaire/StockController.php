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
        $boutiqueId = $gestionnaire->boutique_id ?? 1;

        $stocks = BoutiqueProduit::where('boutique_id', $boutiqueId)
                    ->with('produit')
                    ->get()
                    ->map(function ($bp) {
                        $bp->quantite_en_stock = $bp->stock_disponible;
                        return $bp;
                    });

        return response()->json([
            'status' => 'success',
            'data' => $stocks
        ]);
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
