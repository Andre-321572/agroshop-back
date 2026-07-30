<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use Illuminate\Http\Request;

class BoutiqueController extends Controller
{
    public function index()
    {
        $boutiques = Boutique::withCount('gestionnaires', 'produits')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($b) => $this->format($b));

        return response()->json(['data' => $boutiques]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom'          => 'required|string|max:255',
            'type'         => 'nullable|string|max:255',
            'types'        => 'nullable|array',
            'types.*'      => 'required|string|in:quincaillerie,agricole',
            'localisation' => 'nullable|string|max:255',
            'description'  => 'nullable|string',
            'is_active'    => 'nullable|boolean',
        ]);

        $typeStr = $this->resolveTypeString($validated);
        $this->assertTypeStringValide($typeStr);

        $boutique = Boutique::create([
            'nom'         => $validated['nom'],
            'type'        => $typeStr,
            'adresse'     => $validated['localisation'] ?? null,
            'ville'       => null,
            'statut'      => ($validated['is_active'] ?? true) ? 'actif' : 'inactif',
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Boutique créée avec succès',
            'data'    => $this->format($boutique),
        ], 201);
    }

    public function show($id)
    {
        $boutique = Boutique::with('gestionnaires')->findOrFail($id);
        return response()->json(['data' => $this->format($boutique)]);
    }

    public function update(Request $request, $id)
    {
        $boutique = Boutique::findOrFail($id);

        $validated = $request->validate([
            'nom'          => 'required|string|max:255',
            'type'         => 'nullable|string|max:255',
            'types'        => 'nullable|array',
            'types.*'      => 'required|string|in:quincaillerie,agricole',
            'localisation' => 'nullable|string|max:255',
            'description'  => 'nullable|string',
            'is_active'    => 'nullable|boolean',
        ]);

        $typeStr = $this->resolveTypeString($validated, $boutique->type);
        $this->assertTypeStringValide($typeStr);

        $boutique->update([
            'nom'         => $validated['nom'],
            'type'        => $typeStr,
            'adresse'     => $validated['localisation'] ?? $boutique->adresse,
            'statut'      => isset($validated['is_active'])
                                ? ($validated['is_active'] ? 'actif' : 'inactif')
                                : $boutique->statut,
            'description' => $validated['description'] ?? $boutique->description,
        ]);

        return response()->json([
            'message' => 'Boutique mise à jour avec succès',
            'data'    => $this->format($boutique->fresh()),
        ]);
    }

    public function destroy($id)
    {
        $boutique = Boutique::findOrFail($id);

        if ($boutique->commandes()->count() > 0) {
            $boutique->update(['statut' => 'inactif']);
            return response()->json(['message' => 'Cette boutique a des commandes liées. Elle a été désactivée.'], 200);
        }

        $boutique->delete();
        return response()->json(['message' => 'Boutique supprimée avec succès']);
    }

    /**
     * Convertit types[] ou type string en chaîne normalisée.
     */
    private function resolveTypeString(array $validated, ?string $fallback = 'agricole'): string
    {
        if (!empty($validated['types']) && is_array($validated['types'])) {
            return implode(',', array_unique($validated['types']));
        }
        if (!empty($validated['type'])) {
            return $validated['type'];
        }
        return $fallback ?? 'agricole';
    }

    /**
     * Vérifie que chaque élément de la chaîne type (séparée par virgules)
     * est une valeur autorisée (quincaillerie ou agricole).
     * Lève une ValidationException si une valeur inconnue est détectée.
     */
    private function assertTypeStringValide(string $typeStr): void
    {
        $autorises = ['quincaillerie', 'agricole'];
        $types = array_values(array_filter(explode(',', $typeStr)));

        if (count($types) === 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type' => 'Au moins un type de boutique est requis.',
            ]);
        }

        foreach ($types as $t) {
            $t = trim($t);
            if (!in_array($t, $autorises, true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'type' => "Le type \"{$t}\" n'est pas autorisé. Valeurs acceptées : " . implode(', ', $autorises) . '.',
                ]);
            }
        }
    }

    /**
     * Normalise le modèle Boutique pour le Frontend.
     */
    private function format(Boutique $b): array
    {
        $typesArray = array_values(array_filter(explode(',', $b->type ?? 'agricole')));

        return [
            'id'           => $b->id,
            'nom'          => $b->nom,
            'type'         => $b->type,               // String ex: "quincaillerie,agricole"
            'types'        => $typesArray,            // Array ex: ["quincaillerie", "agricole"]
            'localisation' => $b->adresse,
            'description'  => $b->description ?? null,
            'is_active'    => $b->statut === 'actif',
            'gestionnaires_count' => $b->gestionnaires_count ?? 0,
            'produits_count'      => $b->produits_count ?? 0,
            'created_at'   => $b->created_at,
        ];
    }
}
