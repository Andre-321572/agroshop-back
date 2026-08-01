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
            try {
                if (class_exists(Pdf::class)) {
                    $pdfOutput = Pdf::loadHTML($html)->output();
                }
            } catch (\Throwable $e) {}

            if (!$pdfOutput && class_exists(\Dompdf\Dompdf::class)) {
                $options = new \Dompdf\Options();
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isRemoteEnabled', true);
                $dompdf = new \Dompdf\Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->render();
                $pdfOutput = $dompdf->output();
            }
        }

        $fileName = 'rapports/' . $boutiqueId . '_' . $validated['type'] . '_' . $date->format('Y_m_d_His') . '.pdf';
        
        if ($pdfOutput) {
            Storage::disk('public')->put($fileName, $pdfOutput);
        }

        $gestionnaireId = $gestionnaire?->id ?? Gestionnaire::first()?->id ?? 1;

        $rapport = Rapport::create([
            'boutique_id' => $boutiqueId,
            'gestionnaire_id' => $gestionnaireId,
            'type' => $validated['type'],
            'titre' => $titre,
            'fichier_pdf' => $fileName,
            'date_rapport' => $date->toDateString(),
            'statut_lecture' => false
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Rapport généré et envoyé à l\'administrateur avec succès',
            'rapport' => $rapport
        ]);
    }

    public function enregistrerRapportIa(Request $request)
    {
        /** @var Gestionnaire|null $gestionnaire */
        $gestionnaire = Auth::user();
        $boutique = $gestionnaire?->boutique
            ?? (object)['id' => 1, 'nom' => 'Boutique Principale'];

        $boutiqueId = $boutique->id ?? $request->input('boutique_id', 1);
        $boutiqueNom = $boutique->nom ?? 'Boutique Principale';

        $validated = $request->validate([
            'type' => 'nullable|string|in:journalier,hebdomadaire,mensuel,inventaire',
            'date_rapport' => 'required|date',
            'titre' => 'nullable|string',
            'introduction' => 'nullable|string',
            'section_activite' => 'nullable|string',
            'section_stocks' => 'nullable|string',
            'section_anomalies' => 'nullable|string',
            'section_recommandations' => 'nullable|string',
            'conclusion' => 'nullable|string',
        ]);

        $type = $validated['type'] ?? 'journalier';
        $typeFormate = in_array($type, ['journalier', 'mensuel']) ? $type : 'journalier';
        $date = Carbon::parse($validated['date_rapport']);
        $titre = $validated['titre'] ?? "Rapport IA {$type} - " . $date->format('d/m/Y');

        $data = [
            'titre' => $titre,
            'boutique' => $boutiqueNom,
            'gestionnaire' => ($gestionnaire?->prenom ?? 'Gestionnaire') . ' ' . ($gestionnaire?->nom ?? ''),
            'date_generation' => now()->format('d/m/Y H:i'),
            'date_rapport' => $date->format('d/m/Y'),
            'introduction' => $validated['introduction'] ?? '',
            'section_activite' => $validated['section_activite'] ?? '',
            'section_stocks' => $validated['section_stocks'] ?? '',
            'section_anomalies' => $validated['section_anomalies'] ?? '',
            'section_recommandations' => $validated['section_recommandations'] ?? '',
            'conclusion' => $validated['conclusion'] ?? '',
        ];

        $pdfOutput = null;
        if (view()->exists('pdf.rapport_ia')) {
            $html = view('pdf.rapport_ia', $data)->render();
            try {
                if (class_exists(Pdf::class)) {
                    $pdfOutput = Pdf::loadHTML($html)->output();
                }
            } catch (\Throwable $e) {}

            if (!$pdfOutput && class_exists(\Dompdf\Dompdf::class)) {
                $options = new \Dompdf\Options();
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isRemoteEnabled', true);
                $dompdf = new \Dompdf\Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->render();
                $pdfOutput = $dompdf->output();
            }
        }

        $fileName = 'rapports/ia_' . $boutiqueId . '_' . $typeFormate . '_' . $date->format('Y_m_d_His') . '.pdf';

        if ($pdfOutput) {
            Storage::disk('public')->put($fileName, $pdfOutput);
        }

        $gestionnaireId = $gestionnaire?->id ?? Gestionnaire::first()?->id ?? 1;

        $rapport = Rapport::create([
            'boutique_id' => $boutiqueId,
            'gestionnaire_id' => $gestionnaireId,
            'type' => $typeFormate,
            'titre' => $titre,
            'fichier_pdf' => $fileName,
            'date_rapport' => $date->toDateString(),
            'statut_lecture' => false
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Rapport IA transmis à l\'administrateur avec succès !',
            'rapport' => $rapport
        ]);
    }
}
