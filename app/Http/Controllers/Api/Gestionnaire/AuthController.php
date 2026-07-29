<?php

namespace App\Http\Controllers\Api\Gestionnaire;

use App\Http\Controllers\Controller;
use App\Models\Gestionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * POST /api/gestionnaire/login
     * Connexion du gestionnaire avec vérification manuelle (pas Auth::attempt)
     * car le guard sanctum ne supporte pas attempt() avec session.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required'    => 'L\'adresse email est requise.',
            'email.email'       => 'Format d\'email invalide.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min'      => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);

        // Rechercher le gestionnaire par email
        $gestionnaire = Gestionnaire::where('email', $request->email)->first();

        // Vérifier email + mot de passe
        if (!$gestionnaire || !Hash::check($request->password, $gestionnaire->password)) {
            return response()->json([
                'message' => 'Identifiants invalides. Vérifiez votre email et mot de passe.'
            ], 401);
        }

        // Vérifier que le compte est actif
        if ($gestionnaire->statut !== 'actif') {
            return response()->json([
                'message' => 'Votre compte est inactif. Contactez l\'administrateur.'
            ], 403);
        }

        // Révoquer les anciens tokens et créer un nouveau
        $gestionnaire->tokens()->delete();
        $token = $gestionnaire->createToken('gestionnaire-token')->plainTextToken;

        // Charger les boutiques associées
        $gestionnaire->load('boutiques');

        return response()->json([
            'token'        => $token,
            'gestionnaire' => [
                'id'        => $gestionnaire->id,
                'nom'       => $gestionnaire->nom,
                'prenom'    => $gestionnaire->prenom,
                'email'     => $gestionnaire->email,
                'telephone' => $gestionnaire->telephone,
                'statut'    => $gestionnaire->statut,
            ],
            // Toutes les boutiques gérées par ce gestionnaire
            'boutiques'    => $gestionnaire->boutiques->map(fn($b) => [
                'id'          => $b->id,
                'nom'         => $b->nom,
                'type'        => $b->type,
                'types'       => array_values(array_filter(explode(',', $b->type ?? ''))),
                'localisation' => $b->adresse,
                'is_active'   => $b->statut === 'actif',
            ]),
            // Première boutique active par défaut
            'boutique'     => $gestionnaire->boutiques->first() ? [
                'id'          => $gestionnaire->boutiques->first()->id,
                'nom'         => $gestionnaire->boutiques->first()->nom,
                'type'        => $gestionnaire->boutiques->first()->type,
                'localisation' => $gestionnaire->boutiques->first()->adresse,
            ] : null,
        ]);
    }

    /**
     * POST /api/gestionnaire/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    /**
     * GET /api/gestionnaire/me
     * Profil du gestionnaire connecté + ses boutiques
     */
    public function me(Request $request)
    {
        $gestionnaire = $request->user();
        $gestionnaire->load('boutiques');

        return response()->json([
            'gestionnaire' => [
                'id'        => $gestionnaire->id,
                'nom'       => $gestionnaire->nom,
                'prenom'    => $gestionnaire->prenom,
                'email'     => $gestionnaire->email,
                'telephone' => $gestionnaire->telephone,
                'statut'    => $gestionnaire->statut,
            ],
            'boutiques' => $gestionnaire->boutiques->map(fn($b) => [
                'id'           => $b->id,
                'nom'          => $b->nom,
                'type'         => $b->type,
                'types'        => array_values(array_filter(explode(',', $b->type ?? ''))),
                'localisation' => $b->adresse,
                'is_active'    => $b->statut === 'actif',
            ]),
        ]);
    }
}
