<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gestionnaire;
use Illuminate\Http\Request;

class GestionnaireController extends Controller
{
    public function index()
    {
        $gestionnaires = Gestionnaire::with('boutiques')->get()->map(function ($g) {
            return array_merge($g->toArray(), [
                'boutique_ids' => $g->boutiques->pluck('id')->toArray(),
                // Compatibilité rétrograde : retourne la première boutique comme "boutique"
                'boutique'     => $g->boutiques->first(),
            ]);
        });

        return response()->json(['data' => $gestionnaires]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom'          => 'required|string|max:255',
            'prenom'       => 'required|string|max:255',
            'email'        => 'required|email|unique:gestionnaires,email',
            'telephone'    => 'nullable|string|max:30',
            'password'     => 'required|string|min:8',
            // Un gestionnaire peut gérer une ou plusieurs boutiques
            'boutique_ids' => 'required|array|min:1',
            'boutique_ids.*' => 'exists:boutiques,id',
            'statut'       => 'nullable|in:actif,inactif',
        ]);

        $gestionnaire = Gestionnaire::create([
            'nom'       => $validated['nom'],
            'prenom'    => $validated['prenom'],
            'email'     => $validated['email'],
            'telephone' => $validated['telephone'] ?? null,
            'password'  => $validated['password'], // Cast 'hashed' hash automatiquement
            'statut'    => $validated['statut'] ?? 'actif',
        ]);

        // Attacher les boutiques via la pivot table
        $gestionnaire->boutiques()->sync($validated['boutique_ids']);

        $gestionnaire->load('boutiques');

        return response()->json([
            'message'     => 'Gestionnaire créé avec succès',
            'data'        => array_merge($gestionnaire->toArray(), [
                'boutique_ids' => $gestionnaire->boutiques->pluck('id')->toArray(),
                'boutique'     => $gestionnaire->boutiques->first(),
            ]),
        ], 201);
    }

    public function show($id)
    {
        $gestionnaire = Gestionnaire::with('boutiques')->findOrFail($id);
        return response()->json([
            'data' => array_merge($gestionnaire->toArray(), [
                'boutique_ids' => $gestionnaire->boutiques->pluck('id')->toArray(),
                'boutique'     => $gestionnaire->boutiques->first(),
            ]),
        ]);
    }

    public function update(Request $request, $id)
    {
        $gestionnaire = Gestionnaire::findOrFail($id);

        $validated = $request->validate([
            'nom'          => 'required|string|max:255',
            'prenom'       => 'required|string|max:255',
            'email'        => 'required|email|unique:gestionnaires,email,' . $id,
            'telephone'    => 'nullable|string|max:30',
            'boutique_ids' => 'required|array|min:1',
            'boutique_ids.*' => 'exists:boutiques,id',
            'statut'       => 'nullable|in:actif,inactif',
        ]);

        // Mettre à jour les informations principales
        $gestionnaire->update([
            'nom'       => $validated['nom'],
            'prenom'    => $validated['prenom'],
            'email'     => $validated['email'],
            'telephone' => $validated['telephone'] ?? $gestionnaire->telephone,
            'statut'    => $validated['statut'] ?? $gestionnaire->statut,
        ]);

        // Mettre à jour le mot de passe si fourni (cast 'hashed' hash auto)
        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8']);
            $gestionnaire->update(['password' => $request->password]);
        }

        // Sync boutiques (remplace toutes les associations)
        $gestionnaire->boutiques()->sync($validated['boutique_ids']);

        $gestionnaire->load('boutiques');

        return response()->json([
            'message' => 'Gestionnaire mis à jour avec succès',
            'data'    => array_merge($gestionnaire->toArray(), [
                'boutique_ids' => $gestionnaire->boutiques->pluck('id')->toArray(),
                'boutique'     => $gestionnaire->boutiques->first(),
            ]),
        ]);
    }

    public function destroy($id)
    {
        $gestionnaire = Gestionnaire::findOrFail($id);
        $gestionnaire->boutiques()->detach(); // Nettoyer la pivot
        $gestionnaire->delete();

        return response()->json(['message' => 'Gestionnaire supprimé avec succès']);
    }
}
