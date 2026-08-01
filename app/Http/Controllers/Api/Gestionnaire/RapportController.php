<?php

namespace App\Http\Controllers\Api\Gestionnaire;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Gestionnaire;
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
        /** @var Gestionnaire|null $gestionnaire */
        $gestionnaire = Auth::user();
        $boutique = $gestionnaire?->boutique
            ?? (object)['id' => 1, 'nom' => 'Boutique Principale'];

        $boutiqueId = $boutique->id ?? 1;
        $boutiqueNom = $boutique->nom ?? 'Boutique Principale';

        $validated = $request->validate([
            'type' => 'required|in:journalier,mensuel',
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($validated['date']);

        if ($validated['type'] === 'journalier') {
            $commandes = Commande::with('articles.produit')
                                 ->where('boutique_id', $boutiqueId)
                                 ->whereDate('created_at', $date)
                                 ->get();
            $titre = "Rapport Journalier - " . $date->format('d/m/Y');
        } else {
            $commandes = Commande::with('articles.produit')
                                 ->where('boutique_id', $boutiqueId)
                                 ->whereMonth('created_at', $date->month)
                                 ->whereYear('created_at', $date->year)
                                 ->get();
            $titre = "Rapport Mensuel - " . $date->format('F Y');
        }

        $chiffreAffaires = $commandes->where('statut_commande', '!=', 'annulee')->sum('montant_total');
        
        $data = [
            'titre' => $titre,
            'boutique' => $boutiqueNom,
            'gestionnaire' => ($gestionnaire?->prenom ?? 'Gestionnaire') . ' ' . ($gestionnaire?->nom ?? ''),
            'date_generation' => now()->format('d/m/Y H:i'),
            'commandes' => $commandes,
            'chiffre_affaires' => $chiffreAffaires
        ];

        // Génération du PDF avec fallback Dompdf
        $pdfOutput = null;
        if (view()->exists('pdf.rapport_ventes')) {
            $html = view('pdf.rapport_ventes', $data)->render();
            if (class_exists(Pdf::class)) {
                $pdfOutput = Pdf::loadHTML($html)->output();
            } elseif (class_exists(\Dompdf\Dompdf::class)) {
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html);
                $dompdf->render();
                $pdfOutput = $dompdf->output();
            }
        }

        $fileName = 'rapports/' . $boutiqueId . '_' . $validated['type'] . '_' . $date->format('Y_m_d_His') . '.pdf';
        
        if ($pdfOutput) {
            Storage::disk('public')->put($fileName, $pdfOutput);
        }

        $rapport = Rapport::create([
            'boutique_id' => $boutiqueId,
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
