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
use Illuminate\Support\Facades\Schema;

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

        // Filtre par date (optionnel)
        if ($request->filled('date_debut')) {
            $query->whereDate('commandes.created_at', '>=', $request->input('date_debut'));
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('commandes.created_at', '<=', $request->input('date_fin'));
        }

        // Filtre par référence ou client
        if ($ref = $request->input('search_reference') ?: $request->input('search')) {
            $searchTerm = "%{$ref}%";
            $query->where(function ($q) use ($searchTerm) {
                $q->where('commandes.code_reference', 'LIKE', $searchTerm)
                  ->orWhere('commandes.nom_client', 'LIKE', $searchTerm)
                  ->orWhere('commandes.prenom_client', 'LIKE', $searchTerm)
                  ->orWhere('commandes.telephone', 'LIKE', $searchTerm);
            });
        }

        // Filtre par statut
        if ($statut = $request->input('statut')) {
            if ($statut !== 'tous') {
                $query->where('commandes.statut_commande', $statut);
            }
        }

        $perPage = $request->input('per_page', 100);
        $commandes = $query->orderBy('commandes.created_at', 'desc')->paginate($perPage);

        // Statistiques globales
        $totalVentes = Commande::whereIn('statut_commande', ['confirmee', 'livree', 'expediee', 'preparee'])->sum('montant_total');
        $statsStatuts = Commande::selectRaw('statut_commande, COUNT(*) as count')
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

        DB::transaction(function () use ($commande, $nouveauStatut, $ancienStatut, $request) {
            $updateData = ['statut_commande' => $nouveauStatut];
            if ($nouveauStatut === 'livree') {
                $updateData['statut_paiement'] = 'paye';
            }
            $commande->update($updateData);

            // Création automatique du suivi (non-bloquant)
            try {
                if (Schema::hasTable('commande_suivis')) {
                    CommandeSuivi::create([
                        'commande_id' => $commande->id,
                        'statut_precedent' => $ancienStatut,
                        'nouveau_statut' => $nouveauStatut,
                        'commentaire' => $request->input('commentaire', 'Changement de statut par l\'administrateur'),
                        'utilisateur_id' => $request->user()?->id,
                    ]);
                }
            } catch (\Throwable $e) {}
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

    /**
     * GET /api/admin/commandes/notifications
     * Récupère les 10 dernières commandes récentes pour le système de notifications du header admin.
     */
    public function notifications(): JsonResponse
    {
        $commandes = Commande::orderBy('created_at', 'desc')
            ->limit(10)
            ->get([
                'id',
                'code_reference',
                'nom_client',
                'prenom_client',
                'montant_total',
                'mode_paiement',
                'statut_paiement',
                'statut_commande',
                'created_at'
            ]);

        $unreadCount = Commande::where('created_at', '>=', now()->subHours(24))
            ->whereIn('statut_commande', ['en_attente', 'confirmee'])
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'notifications' => $commandes,
                'unread_count' => $unreadCount,
            ]
        ]);
    }
}
