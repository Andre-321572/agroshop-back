<?php

namespace App\Http\Controllers\Api\Gestionnaire;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Rapport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class RapportController extends Controller
{
    public function genererEtEnvoyer(Request $request)
    {
        $gestionnaire = Auth::user();
        $boutique = $gestionnaire->boutique;

        $validated = $request->validate([
            'type' => 'required|in:journalier,mensuel',
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($validated['date']);

        if ($validated['type'] === 'journalier') {
            $commandes = Commande::with('articles.produit')
                                 ->where('boutique_id', $boutique->id)
                                 ->whereDate('created_at', $date)
                                 ->get();
            $titre = "Rapport Journalier - " . $date->format('d/m/Y');
        } else {
            $commandes = Commande::with('articles.produit')
                                 ->where('boutique_id', $boutique->id)
                                 ->whereMonth('created_at', $date->month)
                                 ->whereYear('created_at', $date->year)
                                 ->get();
            $titre = "Rapport Mensuel - " . $date->format('F Y');
        }

        $chiffreAffaires = $commandes->where('statut_commande', '!=', 'annulee')->sum('montant_total');
        
        $data = [
            'titre' => $titre,
            'boutique' => $boutique->nom,
            'gestionnaire' => $gestionnaire->prenom . ' ' . $gestionnaire->nom,
            'date_generation' => now()->format('d/m/Y H:i'),
            'commandes' => $commandes,
            'chiffre_affaires' => $chiffreAffaires
        ];

        // Génération du PDF
        $pdf = Pdf::loadView('pdf.rapport_ventes', $data);
        
        $fileName = 'rapports/' . $boutique->id . '_' . $validated['type'] . '_' . $date->format('Y_m_d_His') . '.pdf';
        
        // Sauvegarder sur le disque public
        Storage::disk('public')->put($fileName, $pdf->output());

        // Enregistrer en BDD pour l'Admin
        $rapport = Rapport::create([
            'boutique_id' => $boutique->id,
            'gestionnaire_id' => $gestionnaire->id,
            'type' => $validated['type'],
            'fichier_pdf' => $fileName,
            'date_rapport' => $date->toDateString(),
            'statut_lecture' => false
        ]);

        return response()->json([
            'message' => 'Rapport généré et envoyé à l\'administrateur avec succès',
            'rapport' => $rapport
        ]);
    }
}
