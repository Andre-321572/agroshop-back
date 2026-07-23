<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParametreSysteme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParametreSystemeController extends Controller
{
    /**
     * GET /api/admin/parametres
     * Liste de tous les paramètres système.
     */
    public function index(): JsonResponse
    {
        $parametres = ParametreSysteme::all();

        return response()->json([
            'status' => 'success',
            'data' => $parametres,
        ]);
    }

    /**
     * GET /api/admin/parametres/{cle}
     * Récupère un paramètre par sa clé.
     */
    public function show(string $cle): JsonResponse
    {
        $parametre = ParametreSysteme::where('cle_parametre', $cle)->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data' => $parametre,
        ]);
    }

    /**
     * PUT /api/admin/parametres/{cle}
     * Mise à jour d'un paramètre.
     */
    public function update(Request $request, string $cle): JsonResponse
    {
        $parametre = ParametreSysteme::where('cle_parametre', $cle)->firstOrFail();

        $request->validate([
            'valeur_parametre' => 'nullable|string',
        ]);

        $parametre->update([
            'valeur_parametre' => $request->valeur_parametre,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Paramètre « {$cle} » mis à jour.",
            'data' => $parametre->fresh(),
        ]);
    }
}
