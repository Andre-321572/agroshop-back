<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rapport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RapportController extends Controller
{
    public function index(Request $request)
    {
        $query = Rapport::with(['boutique', 'gestionnaire'])->orderBy('created_at', 'desc');

        if ($request->has('boutique_id')) {
            $query->where('boutique_id', $request->boutique_id);
        }

        return response()->json($query->paginate(15));
    }

    public function marquerCommeLu($id)
    {
        $rapport = Rapport::findOrFail($id);
        $rapport->update(['statut_lecture' => true]);

        return response()->json(['message' => 'Rapport marqué comme lu.']);
    }

    public function telecharger($id)
    {
        $rapport = Rapport::findOrFail($id);
        
        if (!Storage::disk('public')->exists($rapport->fichier_pdf)) {
            return response()->json(['message' => 'Fichier introuvable sur le serveur.'], 404);
        }

        $rapport->update(['statut_lecture' => true]);

        return Storage::disk('public')->download($rapport->fichier_pdf);
    }
}
