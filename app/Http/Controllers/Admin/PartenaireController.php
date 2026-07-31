<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partenaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PartenaireController extends Controller
{
    public function index()
    {
        $partenaires = Partenaire::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $partenaires]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'actif' => 'boolean'
        ]);

        $data = $request->only(['nom', 'actif']);
        $data['actif'] = $request->input('actif', true);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('partenaires', 'public');
            $data['logo_url'] = 'storage/' . $path;
        }

        $partenaire = Partenaire::create($data);

        return response()->json([
            'message' => 'Partenaire créé avec succès',
            'data' => $partenaire
        ], 201);
    }

    public function show($id)
    {
        $partenaire = Partenaire::findOrFail($id);
        return response()->json(['data' => $partenaire]);
    }

    public function update(Request $request, $id)
    {
        $partenaire = Partenaire::findOrFail($id);

        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'actif' => 'boolean'
        ]);

        $data = $request->only(['nom', 'actif']);

        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($partenaire->logo_url && str_starts_with($partenaire->logo_url, 'storage/')) {
                Storage::disk('public')->delete(substr($partenaire->logo_url, 8));
            }
            $path = $request->file('logo')->store('partenaires', 'public');
            $data['logo_url'] = 'storage/' . $path;
        }

        $partenaire->update($data);

        return response()->json([
            'message' => 'Partenaire mis à jour avec succès',
            'data' => $partenaire
        ]);
    }

    public function destroy($id)
    {
        $partenaire = Partenaire::findOrFail($id);

        // Delete logo
        if ($partenaire->logo_url && str_starts_with($partenaire->logo_url, 'storage/')) {
            Storage::disk('public')->delete(substr($partenaire->logo_url, 8));
        }

        $partenaire->delete();

        return response()->json([
            'message' => 'Partenaire supprimé avec succès'
        ]);
    }
}
