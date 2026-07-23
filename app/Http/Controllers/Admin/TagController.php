<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    /**
     * GET /api/admin/tags
     * Liste de tous les tags.
     */
    public function index(): JsonResponse
    {
        $tags = Tag::withCount('articles')->orderBy('nom')->get();

        return response()->json([
            'status' => 'success',
            'data' => $tags,
        ]);
    }

    /**
     * POST /api/admin/tags
     * Création d'un tag.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => 'required|string|max:100|unique:tags,nom',
            'couleur' => 'nullable|string|max:7',
        ]);

        $tag = Tag::create([
            'nom' => $request->nom,
            'slug' => Str::slug($request->nom),
            'couleur' => $request->input('couleur', '#007bff'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tag créé avec succès.',
            'data' => $tag,
        ], 201);
    }

    /**
     * PUT /api/admin/tags/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);

        $request->validate([
            'nom' => 'sometimes|required|string|max:100|unique:tags,nom,' . $id,
            'couleur' => 'nullable|string|max:7',
        ]);

        $data = $request->only(['nom', 'couleur']);
        if ($request->has('nom')) {
            $data['slug'] = Str::slug($request->nom);
        }

        $tag->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Tag mis à jour.',
            'data' => $tag->fresh(),
        ]);
    }

    /**
     * DELETE /api/admin/tags/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        $tag->articles()->detach();
        $tag->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Tag supprimé avec succès.',
        ]);
    }
}
