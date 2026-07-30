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

        $disk = Storage::disk('public');
        $fichier = $rapport->fichier_pdf;

        if (!$fichier || !$disk->exists($fichier)) {
            return response()->json([
                'message' => 'Fichier introuvable sur le serveur. Le PDF n\'a peut-être pas encore été généré.',
                'rapport_id' => $rapport->id,
                'fichier' => $fichier
            ], 404);
        }

        try {
            $rapport->update(['statut_lecture' => true]);
        } catch (\Throwable $e) {
        }

        $fileName = $rapport->titre
            ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $rapport->titre) . '.pdf'
            : 'rapport-' . $rapport->id . '.pdf';

        $cheminComplet = $disk->path($fichier);
        $mimeType = function_exists('mime_content_type') && is_file($cheminComplet)
            ? mime_content_type($cheminComplet)
            : 'application/pdf';
        if (!$mimeType || $mimeType === false) {
            $mimeType = 'application/pdf';
        }

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Description' => 'File Transfer',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'public',
        ];

        return response()->download($cheminComplet, $fileName, $headers);
    }
}
