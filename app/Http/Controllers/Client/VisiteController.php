<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VisiteLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisiteController extends Controller
{
    /**
     * POST /api/track-visite
     * Enregistre les visites et les clics/actions effectuées par les visiteurs par IP.
     */
    public function store(Request $request): JsonResponse
    {
        $ip = $request->ip() ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $userAgent = substr($request->userAgent() ?? '', 0, 500);

        $page = $request->input('page', '/');
        $typeAction = $request->input('type_action', 'visite_page');
        $details = $request->input('details');

        VisiteLog::create([
            'ip_adresse' => $ip,
            'user_agent' => $userAgent,
            'page_visitee' => substr($page, 0, 255),
            'type_action' => substr($typeAction, 0, 50),
            'details' => is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Action enregistrée avec succès.'
        ]);
    }
}
