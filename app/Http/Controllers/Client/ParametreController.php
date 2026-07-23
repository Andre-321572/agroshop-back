<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ParametreSysteme;
use Illuminate\Http\JsonResponse;

class ParametreController extends Controller
{
    /**
     * GET /api/parametres
     * Configuration publique du site (TVA, frais de livraison, nom du site).
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'site_nom' => ParametreSysteme::get('site_nom', 'AGROSHOP'),
                'tva_taux' => (float) ParametreSysteme::get('tva_taux', 18),
                'frais_livraison_base' => (float) ParametreSysteme::get('frais_livraison_base', 5000),
            ],
        ]);
    }
}
