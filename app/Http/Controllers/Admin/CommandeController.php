<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\CommandeArticle;
use App\Models\CommandeSuivi;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommandeController extends Controller
{
    /**
     * GET /api/admin/commandes
     * Liste paginée des commandes avec filtres avancés.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Commande::with('articles')
            ->select('commandes.*')
            ->selectRaw('COUNT(ca.id) as nombre_articles')
            ->selectRaw('COALESCE(SUM(ca.quantite), 0) as quantite_totale')
            ->leftJoin('commande_articles as ca', 'commandes.id', '=', 'ca.commande_id')
            ->groupBy('commandes.id');

        // Filtre par date (par défaut aujourd'hui)
        $dateDebut = $request->input('date_debut', now()->toDateString());
        $dateFin = $request->input('date_fin', now()->toDateString());
        $query->whereDate('commandes.created_at', '>=', $dateDebut)
              ->whereDate('commandes.created_at', '<=', $dateFin);

        // Filtre par référence
        if ($ref = $request->input('search_reference')) {
            $query->where('commandes.code_reference', 'LIKE', "%{$ref}%");
        }

        // Filtre par statut
        if ($statut = $request->input('statut')) {
            if ($statut !== 'tous') {
                $query->where('commandes.statut_commande', $statut);
            }
        }

        $perPage = $request->input('per_page', 15);
        $commandes = $query->orderBy('commandes.created_at', 'desc')->paginate($perPage);

        // Statistiques de la période
        $statsWhere = Commande::whereDate('created_at', '>=', $dateDebut)
            ->whereDate('created_at', '<=', $dateFin);

        $totalVentes = (clone $statsWhere)->where('statut_commande', 'confirmee')
            ->sum('montant_total');

        $statsStatuts = (clone $statsWhere)
            ->selectRaw('statut_commande, COUNT(*) as count')
            ->groupBy('statut_commande')
            ->pluck('count', 'statut_commande');

        return response()->json([
            'status' => 'success',
            'data' => [
                'commandes' => $commandes,
                'stats' => [
                    'total_ventes_confirmees' => (float) $totalVentes,
                    'par_statut' => $statsStatuts,
                ],
            ],
        ]);
    }

    /**
     * GET /api/admin/commandes/{id}
     * Détail complet d'une commande avec ses articles et suivis.
     */
    public function show(int $id): JsonResponse
    {
        $commande = Commande::with(['articles.produit:id,nom_commercial,slug', 'suivis.utilisateur:id,nom,prenom'])
            ->withCount('articles')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $commande,
        ]);
    }

    /**
     * PUT /api/admin/commandes/{id}/statut
     * Mise à jour du statut d'une commande avec création du suivi automatique.
     */
    public function updateStatut(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'statut_commande' => 'required|in:en_attente,confirmee,preparee,expediee,livree,annulee',
            'commentaire' => 'nullable|string|max:500',
        ]);

        $commande = Commande::findOrFail($id);
        $ancienStatut = $commande->statut_commande;
        $nouveauStatut = $request->statut_commande;

        // Validation des transitions de statut autorisées
        $transitionsAutorisees = [
            'en_attente' => ['confirmee', 'annulee'],
            'confirmee' => ['preparee', 'annulee'],
            'preparee' => ['expediee', 'annulee'],
            'expediee' => ['livree'],
            'livree' => [],
            'annulee' => [],
        ];

        if (!in_array($nouveauStatut, $transitionsAutorisees[$ancienStatut] ?? [])) {
            return response()->json([
                'status' => 'error',
                'message' => "Transition de statut non autorisée : {$ancienStatut} → {$nouveauStatut}.",
            ], 422);
        }

        DB::transaction(function () use ($commande, $nouveauStatut, $ancienStatut, $request) {
            $commande->update(['statut_commande' => $nouveauStatut]);

            // Création automatique du suivi
            CommandeSuivi::create([
                'commande_id' => $commande->id,
                'statut_precedent' => $ancienStatut,
                'nouveau_statut' => $nouveauStatut,
                'commentaire' => $request->input('commentaire', 'Changement de statut par l\'administrateur'),
                'utilisateur_id' => $request->user()->id,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => "Commande passée de « {$ancienStatut} » à « {$nouveauStatut} ».",
            'data' => $commande->fresh()->load('suivis'),
        ]);
    }

    /**
     * PUT /api/admin/commandes/{id}/paiement
     * Mise à jour du statut de paiement.
     */
    public function updatePaiement(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'statut_paiement' => 'required|in:en_attente,paye,echec,rembourse',
        ]);

        $commande = Commande::findOrFail($id);
        $commande->update(['statut_paiement' => $request->statut_paiement]);

        return response()->json([
            'status' => 'success',
            'message' => 'Statut de paiement mis à jour.',
            'data' => $commande->fresh(),
        ]);
    }

    /**
     * PUT /api/admin/commandes/{id}/notes
     * Ajout/mise à jour des notes admin.
     */
    public function updateNotes(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'notes_admin' => 'nullable|string',
        ]);

        $commande = Commande::findOrFail($id);
        $commande->update(['notes_admin' => $request->notes_admin]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notes mises à jour.',
        ]);
    }

    public function receipt(int $id)
    {
        $commande = Commande::with(['articles.produit'])->findOrFail($id);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A5', 'portrait');

        $html = view('receipt', compact('commande'))->render();
        $dompdf->loadHtml($html);
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receipt_' . $commande->code_reference . '.pdf"',
        ]);
    }
}
