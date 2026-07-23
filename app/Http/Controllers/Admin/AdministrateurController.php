<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Administrateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdministrateurController extends Controller
{
    /**
     * GET /api/admin/administrateurs
     * Liste de tous les administrateurs.
     */
    public function index(): JsonResponse
    {
        $admins = Administrateur::orderBy('created_at', 'desc')
            ->get(['id', 'nom', 'prenom', 'email', 'role', 'actif', 'derniere_connexion', 'created_at']);

        return response()->json([
            'status' => 'success',
            'data' => $admins,
        ]);
    }

    /**
     * GET /api/admin/administrateurs/{id}
     */
    public function show(int $id): JsonResponse
    {
        $admin = Administrateur::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $admin->only(['id', 'nom', 'prenom', 'email', 'role', 'actif', 'derniere_connexion', 'created_at']),
        ]);
    }

    /**
     * POST /api/admin/administrateurs
     * Création d'un nouvel administrateur.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:administrateurs,email',
            'mot_de_passe' => 'required|string|min:6',
            'role' => 'required|in:super_admin,admin,gestionnaire_stock,gestionnaire_commandes',
            'actif' => 'nullable|boolean',
        ], [
            'email.unique' => 'Cet email est déjà utilisé par un autre administrateur.',
            'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
        ]);

        $admin = Administrateur::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'mot_de_passe' => Hash::make($request->mot_de_passe),
            'role' => $request->role,
            'actif' => $request->input('actif', true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Nouvel administrateur ajouté avec succès.',
            'data' => $admin->only(['id', 'nom', 'prenom', 'email', 'role', 'actif']),
        ], 201);
    }

    /**
     * PUT /api/admin/administrateurs/{id}
     * Mise à jour d'un administrateur.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $admin = Administrateur::findOrFail($id);

        $request->validate([
            'nom' => 'sometimes|required|string|max:100',
            'prenom' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|max:150|unique:administrateurs,email,' . $id,
            'mot_de_passe' => 'nullable|string|min:6',
            'role' => 'sometimes|in:super_admin,admin,gestionnaire_stock,gestionnaire_commandes',
            'actif' => 'nullable|boolean',
        ], [
            'email.unique' => 'Cet email est déjà utilisé par un autre administrateur.',
        ]);

        $data = $request->only(['nom', 'prenom', 'email', 'role', 'actif']);

        // Mise à jour du mot de passe uniquement si fourni
        if ($request->filled('mot_de_passe')) {
            $data['mot_de_passe'] = Hash::make($request->mot_de_passe);
        }

        $admin->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Administrateur modifié avec succès.',
            'data' => $admin->fresh()->only(['id', 'nom', 'prenom', 'email', 'role', 'actif']),
        ]);
    }

    /**
     * DELETE /api/admin/administrateurs/{id}
     * Suppression d'un administrateur.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $admin = Administrateur::findOrFail($id);

        // Empêcher la suppression de soi-même
        if ($request->user()->id === $admin->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 422);
        }

        $admin->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Administrateur supprimé avec succès.',
        ]);
    }

    /**
     * POST /api/admin/administrateurs/{id}/reset-password
     * Réinitialiser le mot de passe d'un administrateur.
     */
    public function resetPassword(int $id): JsonResponse
    {
        $admin = Administrateur::findOrFail($id);

        // Générer un mot de passe temporaire
        $tempPassword = bin2hex(random_bytes(4));
        $admin->update([
            'mot_de_passe' => Hash::make($tempPassword),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Mot de passe réinitialisé avec succès.',
            'data' => [
                'temp_password' => $tempPassword,
            ],
        ]);
    }
}
