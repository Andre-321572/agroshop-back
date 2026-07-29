<?php

namespace App\Http\Controllers\Api\Gestionnaire;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::guard('gestionnaire')->attempt($credentials)) {
            $gestionnaire = Auth::guard('gestionnaire')->user();
            
            if ($gestionnaire->statut !== 'actif') {
                return response()->json(['message' => 'Votre compte est inactif.'], 403);
            }

            $token = $gestionnaire->createToken('gestionnaire-token')->plainTextToken;
            $gestionnaire->load('boutiques');

            return response()->json([
                'token'       => $token,
                'gestionnaire' => $gestionnaire,
                // Toutes les boutiques gérées par ce gestionnaire
                'boutiques'   => $gestionnaire->boutiques,
                // Première boutique par défaut (active par défaut)
                'boutique'    => $gestionnaire->boutiques->first(),
            ]);
        }

        return response()->json(['message' => 'Identifiants invalides'], 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès']);
    }
}
