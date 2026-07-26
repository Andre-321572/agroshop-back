<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Administrateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/admin/login
     * Authentification admin avec rate limiting et sécurité complète.
     */
    public function login(Request $request): JsonResponse
    {
        if (!$request->has('password') && $request->has('mot_de_passe')) {
            $request->merge(['password' => $request->input('mot_de_passe')]);
        }

        $request->validate([
            'email' => 'required|email|max:150',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => 'Veuillez saisir votre adresse e-mail.',
            'email.email' => "Format d'adresse e-mail invalide.",
            'password.required' => 'Veuillez saisir votre mot de passe.',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);

        // Rate limiting : 5 tentatives max par email + IP, blocage de 5 minutes
        $rateLimitKey = 'login:' . md5($request->email . '|' . $request->ip());
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $minutes = ceil($seconds / 60);
            
            return response()->json([
                'status' => 'error',
                'message' => "Trop de tentatives échouées. Veuillez patienter encore {$minutes} minute(s).",
            ], 429);
        }

        // Rechercher l'administrateur
        $admin = Administrateur::where('email', $request->email)->first();

        // Message générique pour ne pas révéler l'existence du compte
        if (!$admin || !Hash::check($request->password, $admin->mot_de_passe)) {
            RateLimiter::hit($rateLimitKey, 300); // 5 min de blocage

            $remaining = RateLimiter::remaining($rateLimitKey, 5);

            return response()->json([
                'status' => 'error',
                'message' => "Identifiants incorrects. {$remaining} tentative(s) restante(s).",
            ], 401);
        }

        // Vérifier que le compte est actif
        if (!$admin->actif) {
            return response()->json([
                'status' => 'error',
                'message' => "Ce compte est désactivé. Contactez l'administrateur.",
            ], 403);
        }

        // Vérifier que le rôle est autorisé
        $rolesAutorises = ['super_admin', 'admin', 'gestionnaire_stock', 'gestionnaire_commandes'];
        if (!in_array($admin->role, $rolesAutorises, true)) {
            return response()->json([
                'status' => 'error',
                'message' => "Vous n'êtes pas autorisé à accéder à l'administration.",
            ], 403);
        }

        // Réinitialiser le rate limiter après connexion réussie
        RateLimiter::clear($rateLimitKey);

        // Mettre à jour la dernière connexion
        $admin->update(['derniere_connexion' => now()]);

        // Supprimer les anciens tokens et créer un nouveau token Sanctum
        $admin->tokens()->delete();
        $token = $admin->createToken('admin-api', ['role:' . $admin->role]);

        return response()->json([
            'status' => 'success',
            'message' => 'Connexion réussie.',
            'data' => [
                'admin' => [
                    'id' => $admin->id,
                    'nom' => $admin->nom,
                    'prenom' => $admin->prenom,
                    'email' => $admin->email,
                    'role' => $admin->role,
                ],
                'token' => $token->plainTextToken,
            ],
        ]);
    }

    /**
     * POST /api/admin/logout
     * Déconnexion avec révocation du token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Déconnexion réussie.',
        ]);
    }

    /**
     * GET /api/admin/me
     * Profil de l'administrateur connecté.
     */
    public function me(Request $request): JsonResponse
    {
        $admin = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $admin->id,
                'nom' => $admin->nom,
                'prenom' => $admin->prenom,
                'email' => $admin->email,
                'role' => $admin->role,
                'actif' => $admin->actif,
                'derniere_connexion' => $admin->derniere_connexion,
            ],
        ]);
    }
}
