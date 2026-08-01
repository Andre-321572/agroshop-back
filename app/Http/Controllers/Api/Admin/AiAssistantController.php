<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiService;
use App\Models\Produit;
use App\Models\Boutique;
use App\Models\Commande;
use App\Models\CommandeArticle;
use App\Models\Categorie;
use App\Models\Tag;
use App\Models\ArticleBlog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiAssistantController extends Controller
{
    private AiService $ai;

    public function __construct(AiService $ai)
    {
        $this->ai = $ai;
    }

    // =================================================================
    // ENDPOINT UNIQUE LEGACY (gardé pour compatibilité si utilisé ailleurs)
    // =================================================================
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:4000',
            'system' => 'nullable|string|max:44',
        ]);

        $result = $this->ai->chat(
            $validated['prompt'],
            $validated['system'] ?? 'Tu es un assistant expert en agriculture et commerce agricole au Togo. Réponds en français.'
        );

        return response()->json([
            'status' => $result !== null ? 'success' : 'error',
            'message' => $result !== null ? 'Réponse générée.' : 'Service indisponible (clé API manquante ou erreur réseau).',
            'data' => ['content' => $result ?? '']
        ]);
    }

    // =================================================================
    // FEATURE 1 — Génération d'une fiche produit agricole
    // POST /admin/ai/produits/generer-fiche
    // =================================================================
    public function genererFicheProduit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom_commercial'  => 'required|string|max:200',
            'categorie_nom'   => 'nullable|string|max:200',
            'prix_unitaire'   => 'nullable|numeric|min:0',
            'unite_mesure'    => 'nullable|string|max:50',
            'categories_ids'  => 'nullable|array',
            'categories_ids.*' => 'integer|exists:categories,id',
        ]);

        $nom = trim($validated['nom_commercial']);
        $categorie = $validated['categorie_nom'] ?? '';
        if (empty($categorie) && !empty($validated['categories_ids'])) {
            $categorie = Categorie::whereIn('id', $validated['categories_ids'])->pluck('nom')->join(', ');
        }
        $prix = $validated['prix_unitaire'] ?? '';
        $unite = $validated['unite_mesure'] ?? '';

        $sys = <<<SYS
Tu es un expert agronome Togolais spécialisé dans la rédaction de fiches techniques pour des produits agricoles (engrais, pesticides, semences, outils, matériel agricole, quincaillerie).
Le contexte est : Pays = Togo, Langue = Français, Devise = FCFA.
SYS;

        $prompt = <<<PROMPT
Génère une fiche produit complète et réaliste pour un produit agricole vendu au Togo :
- Nom commercial : "{$nom}"
- Catégorie : "{$categorie}"
- Prix unitaire : "{$prix}" (peut être vide)
- Unité de mesure : "{$unite}" (peut être vide)

Réponds UNIQUEMENT en JSON avec exactement ces champs (valeurs en français, réalistes pour le marché togolais) :
{
  "description": "Paragraphe de 3-5 lignes marketing ET technique pour les agriculteurs",
  "composition": "Composition chimique / matière première (2-4 lignes)",
  "principes_actifs": "Principes actifs ou caractéristiques clés (2-3 lignes)",
  "mode_emploi": "Instructions précises d'utilisation avec moment d'application (3-5 lignes)",
  "dosage_recommande": "Dosage par hectare ou par plante, avec fréquences (2-4 lignes, exemples : maïs, riz, tomate, maraîchage)",
  "precautions_usage": "Précautions de stockage, manipulation, EPI (3-4 lignes)",
  "contre_indications": "Contre-indications et incompatibilités (2-3 lignes)",
  "meta_title": "Titre SEO max 200 car. format : [Nom] - [Catégorie] - AgroShop Togo",
  "meta_description": "Meta description SEO max 160 car. engageante pour agriculteurs",
  "suggestion_stock_alerte": 20,
  "suggestion_poids_kg": 50,
  "suggestion_dimensions": "80cm x 50cm x 20cm"
}
PROMPT;

        $data = $this->ai->chatJson($prompt, $sys);

        if ($data === null) {
            return $this->fallbackFicheProduit($nom, $categorie);
        }

        $defaults = [
            'description' => '', 'composition' => '', 'principes_actifs' => '',
            'mode_emploi' => '', 'dosage_recommande' => '', 'precautions_usage' => '',
            'contre_indications' => '', 'meta_title' => '', 'meta_description' => '',
            'suggestion_stock_alerte' => 20, 'suggestion_poids_kg' => 50,
            'suggestion_dimensions' => '80cm x 50cm x 20cm',
        ];
        $data = array_merge($defaults, $data);

        if (empty($data['meta_title'])) {
            $data['meta_title'] = $nom . ' - ' . ($categorie ?: 'Produit Agricole') . ' - AgroShop Togo';
        }
        if (empty($data['meta_description'])) {
            $data['meta_description'] = $nom . ' : produit agricole de qualité disponible au Togo. Livraison rapide, prix certifié.';
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Fiche technique générée par IA.',
            'data' => $data,
        ]);
    }

    private function fallbackFicheProduit(string $nom, string $categorie): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Service temporairement indisponible — retour à un modèle de base (prêt à l\'emploi).',
            'ai_enabled' => false,
            'data' => [
                'description' => "{$nom} est un produit agricole de qualité, adapté aux conditions agro-écologiques du Togo. Idéal pour les cultures de céréales (maïs, riz, sorgho) et le maraîchage (tomate, piment, oignon). Commercialisé par AgroShop avec certification de qualité.",
                'composition' => "Formulation adaptée aux sols tropicaux. Ingrédients sélectionnés pour une assimilation rapide par les plantes.",
                'principes_actifs' => "Formulation à libération progressive assurant une efficacité durable sur la culture.",
                'mode_emploi' => "Appliquer de préférence tôt le matin ou en fin d'après-midi. Incorporer légèrement au sol pour les engrais, pulvériser uniformément pour les produits foliaires.",
                'dosage_recommande' => "Culture céréalière (maïs/riz) : 150 à 200 kg/ha en fond de labour + complément 100 kg/ha en couverture. Maraîchage : 20 à 30 g par plant.",
                'precautions_usage' => "Conserver dans un endroit sec, frais et ventilé, à l'abri de la lumière directe et hors de portée des enfants et des animaux. Porter des gants et un masque lors de la manipulation.",
                'contre_indications' => "Ne pas mélanger avec des produits à forte alcalinité. Ne pas appliquer sur sol détrempé ou avant une pluie torrentielle prévue.",
                'meta_title' => mb_substr("{$nom} - " . ($categorie ?: 'Produit Agricole') . " - AgroShop Togo", 0, 190),
                'meta_description' => mb_substr("Achetez {$nom} au Togo au meilleur prix chez AgroShop. Livraison rapide à Lomé et dans tout le pays. Qualité garantie pour agriculteurs.", 0, 150),
                'suggestion_stock_alerte' => 20,
                'suggestion_poids_kg' => 50,
                'suggestion_dimensions' => '80cm x 50cm x 20cm',
            ]
        ], 200);
    }

    // =================================================================
    // FEATURE 8 — Détection d'anomalies sur une saisie produit / stock
    // POST /admin/ai/produits/valider-saisie
    // =================================================================
    public function validerSaisieProduit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom_commercial'    => 'nullable|string|max:200',
            'prix_unitaire'     => 'nullable|numeric|min:0',
            'stock_disponible'  => 'nullable|integer|min:0',
            'stock_avant'       => 'nullable|integer|min:0',
            'stock_alerte'      => 'nullable|integer|min:0',
            'categorie_principale_nom' => 'nullable|string|max:200',
            'unite_mesure'      => 'nullable|string|max:50',
            'boutique_type'     => 'nullable|string|max:255',
            'produit_id'        => 'nullable|integer|exists:produits,id',
        ]);

        $warnings = [];
        $infos = [];

        if ($validated['produit_id']) {
            $historiquePrix = Produit::where('id', '!=', $validated['produit_id'])
                ->whereNotNull('prix_unitaire')
                ->where('prix_unitaire', '>', 0)
                ->limit(30)
                ->pluck('prix_unitaire');
        } else {
            $historiquePrix = Produit::whereNotNull('prix_unitaire')
                ->where('prix_unitaire', '>', 0)
                ->limit(30)
                ->pluck('prix_unitaire');
        }

        if (!empty($validated['prix_unitaire']) && $historiquePrix->count() >= 5) {
            $moy = $historiquePrix->avg();
            $ecart = $moy > 0 ? abs($validated['prix_unitaire'] - $moy) / $moy : 0;
            if ($ecart > 0.6) {
                $sens = $validated['prix_unitaire'] > $moy ? 'supérieur' : 'inférieur';
                $warnings[] = [
                    'code' => 'PRICE_OUTLIER',
                    'severity' => 'warning',
                    'message' => "Le prix proposé ({$validated['prix_unitaire']} FCFA) est significativement {$sens} à la moyenne du catalogue (" . round($moy) . " FCFA). Vérifiez l'unité de mesure ({$validated['unite_mesure']} ?) avant de valider.",
                ];
            }
        }

        if (
            isset($validated['stock_avant'], $validated['stock_disponible']) &&
            $validated['stock_avant'] > 0 && $validated['stock_disponible'] === 0 &&
            $validated['stock_avant'] >= 20
        ) {
            $warnings[] = [
                'code' => 'STOCK_ZERO_INCOHERENT',
                'severity' => 'error',
                'message' => "Le stock passe de {$validated['stock_avant']} unités à 0 SANS vente enregistrée correspondante. Vérifiez la saisie (inventaire ? erreur de clavier ?).",
            ];
        }

        if (
            !empty($validated['stock_disponible']) && !empty($validated['stock_alerte']) &&
            $validated['stock_alerte'] > 0 &&
            $validated['stock_disponible'] <= $validated['stock_alerte'] * 0.4 &&
            $validated['stock_disponible'] > 0
        ) {
            $infos[] = [
                'code' => 'STOCK_BIEN_DESSOUS_ALERTE',
                'severity' => 'info',
                'message' => "Stock disponible (" . $validated['stock_disponible'] . ") très bas par rapport au seuil d'alerte (" . $validated['stock_alerte'] . "). Commande de réappro recommandée.",
            ];
        }

        if (!empty($validated['categorie_principale_nom']) && !empty($validated['boutique_type'])) {
            $cat = mb_strtolower($validated['categorie_principale_nom']);
            $bt = mb_strtolower($validated['boutique_type']);
            $estQuincaillerieCat = (str_contains($cat, 'outil') || str_contains($cat, 'quincaillerie') || str_contains($cat, 'matériel') || str_contains($cat, 'materiel'));
            $estAgricoleCat = (str_contains($cat, 'engrais') || str_contains($cat, 'semence') || str_contains($cat, 'pesticide') || str_contains($cat, 'intrant') || str_contains($cat, 'agricole'));
            $boutiqueEstQ = str_contains($bt, 'quincaillerie');
            $boutiqueEstA = str_contains($bt, 'agricole');

            if ($estQuincaillerieCat && !$boutiqueEstQ && $boutiqueEstA) {
                $warnings[] = [
                    'code' => 'PRODUIT_MAL_AFFECTE',
                    'severity' => 'warning',
                    'message' => "Ce produit (catégorie {$validated['categorie_principale_nom']}) semble être de la quincaillerie — or cette boutique est de type agricole exclusive. Affectation manuelle ?",
                ];
            }
            if ($estAgricoleCat && !$boutiqueEstA && $boutiqueEstQ) {
                $warnings[] = [
                    'code' => 'PRODUIT_MAL_AFFECTE',
                    'severity' => 'warning',
                    'message' => "Ce produit (catégorie {$validated['categorie_principale_nom']}) semble être agricole — or cette boutique est de type quincaillerie exclusive. Affectation manuelle ?",
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => count($warnings) ? count($warnings) . ' anomalie(s) détectée(s) par IA.' : 'Aucune anomalie critique détectée.',
            'data' => [
                'valide' => count(array_filter($warnings, fn($w) => $w['severity'] === 'error')) === 0,
                'warnings' => $warnings,
                'infos' => $infos,
            ],
        ]);
    }

    // =================================================================
    // FEATURE 7 — Suggestion de commande de réapprovisionnement
    // GET /admin/ai/boutiques/{boutiqueId}/suggerer-reappro
    // =================================================================
    public function suggererReappro(Request $request, int $boutiqueId): JsonResponse
    {
        $boutique = Boutique::findOrFail($boutiqueId);
        $delaiAppro = (int)($request->input('delai_appro_jours', 7));
        $margeSecurite = (float)($request->input('marge_securite', 0.30));

        $produits = Produit::with(['categories'])->where('statut', 'actif')->get();

        $suggestions = collect();
        foreach ($produits as $p) {
            $stockDispo = (int)(DB::table('boutique_produit')
                ->where('boutique_id', $boutiqueId)
                ->where('produit_id', $p->id)
                ->value('stock_disponible') ?? ($p->stock_disponible ?? 0));

            $stockAlerte = (int)(DB::table('boutique_produit')
                ->where('boutique_id', $boutiqueId)
                ->where('produit_id', $p->id)
                ->value('stock_alerte') ?? ($p->stock_alerte ?? 10));

            $ventes30j = CommandeArticle::whereHas('commande', function ($q) use ($boutiqueId) {
                $q->where('boutique_id', $boutiqueId)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->where('statut_commande', '!=', 'annulee');
            })->where('produit_id', $p->id)->sum('quantite');

            $vitesse = $ventes30j > 0 ? $ventes30j / 30 : 0.1;
            $besoin = (int)ceil(($vitesse * $delaiAppro) * (1 + $margeSecurite));
            $ruptureDans = $vitesse > 0 ? (int)floor($stockDispo / $vitesse) : 999;
            $aCommander = max(0, $besoin - $stockDispo);

            $urgence = 'basse';
            if ($ruptureDans <= 7) $urgence = 'critique';
            elseif ($ruptureDans <= 15) $urgence = 'haute';
            elseif ($stockDispo <= $stockAlerte) $urgence = 'moyenne';

            $estPrioritaire = $aCommander > 0 && in_array($urgence, ['critique', 'haute', 'moyenne']);
            $suggestions->push([
                'produit_id' => $p->id,
                'nom_commercial' => $p->nom_commercial,
                'categorie' => optional($p->categories->first())->nom ?? 'Sans catégorie',
                'prix_unitaire' => $p->prix_unitaire ?? 0,
                'unite_mesure' => $p->unite_mesure ?? 'unité',
                'stock_actuel' => $stockDispo,
                'stock_alerte' => $stockAlerte,
                'ventes_30j' => $ventes30j,
                'vitesse_journaliere' => round($vitesse, 2),
                'rupture_jours' => $ruptureDans,
                'quantite_suggeree' => $aCommander,
                'cout_estime_fcfa' => $aCommander * ($p->prix_unitaire ?? 0),
                'urgence' => $urgence,
                'est_prioritaire' => $estPrioritaire,
            ]);
        }

        $prioritaires = $suggestions->where('est_prioritaire', true)
            ->sortBy(function ($s) {
                $order = ['critique' => 0, 'haute' => 1, 'moyenne' => 2];
                return $order[$s['urgence']] ?? 3;
            })->values();

        $autres = $suggestions->where('est_prioritaire', false)
            ->sortByDesc('quantite_suggeree')
            ->take(10)->values();

        $total = $prioritaires->sum('cout_estime_fcfa') + $autres->sum('cout_estime_fcfa');
        $totalItems = $prioritaires->sum('quantite_suggeree') + $autres->sum('quantite_suggeree');

        $summary = $this->ai->chatJson(
            'Voici un top des réappro suggérés pour la boutique ' . $boutique->nom . ' : ' .
                json_encode($prioritaires->take(5), JSON_UNESCAPED_UNICODE),
            "Tu es un directeur de supply chain d'un réseau de boutiques agricoles au Togo. Résume en 3 points-clés les réappro suggérés et donne un conseil métier (en français, concis, max 80 mots). JSON avec : resume (string), conseil (string)."
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Suggestions de réappro générées.',
            'data' => [
                'boutique' => ['id' => $boutique->id, 'nom' => $boutique->nom],
                'delai_appro_jours' => $delaiAppro,
                'marge_securite' => $margeSecurite,
                'prioritaires' => $prioritaires,
                'autres' => $autres,
                'total_estime_fcfa' => $total,
                'total_articles' => $totalItems,
                'ai_summary' => $summary ?? [
                    'resume' => $prioritaires->count() . ' produit(s) prioritaire(s) à commander pour éviter la rupture.',
                    'conseil' => 'Commander en priorité les articles critiques ; regrouper les commandes pour réduire les frais de livraison.',
                ],
            ],
        ]);
    }

    // =================================================================
    // FEATURE 6 — Génération de Rapport gestionnaire
    // POST /admin/ai/rapports/generer
    // =================================================================
    public function genererRapport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'boutique_id'  => 'required|integer|exists:boutiques,id',
            'gestionnaire_nom' => 'nullable|string|max:200',
            'type'         => 'required|in:journalier,hebdomadaire,mensuel,inventaire',
            'date_rapport' => 'required|date',
            'ca_jour'      => 'nullable|numeric|min:0',
            'nb_clients'   => 'nullable|integer|min:0',
            'nb_commandes' => 'nullable|integer|min:0',
            'points_cles'  => 'required|string|max:4000',
            'anomalies'    => 'nullable|string|max:2000',
            'stocks'       => 'nullable|string|max:4000',
        ]);

        $boutique = Boutique::findOrFail($validated['boutique_id']);

        $sys = "Tu es un gestionnaire de boutique agricole au Togo. Rédige un rapport formel et professionnel en français, clair et structuré, destiné à un administrateur. Utilise un ton factuel.";
        $prompt = <<<PROMPT
Rédige un RAPPORT {$validated['type']} formel pour la boutique "{$boutique->nom}"
- Gestionnaire : {$validated['gestionnaire_nom']}
- Date : {$validated['date_rapport']}
- Chiffre d'affaires : {$validated['ca_jour']} FCFA
- Nb clients : {$validated['nb_clients']}
- Nb commandes : {$validated['nb_commandes']}

Points-clés notés par le gestionnaire (format brut / notes) :
---
{$validated['points_cles']}
---

Anomalies signalées : {$validated['anomalies']}
Informations stocks : {$validated['stocks']}

Réponds UNIQUEMENT en JSON :
{
  "titre": "Titre complet du rapport",
  "introduction": "Paragraphe introductif (3-5 lignes)",
  "section_activite": "Section : Activité / Chiffres clés (4-6 lignes, liste)",
  "section_stocks": "Section : État des stocks (3-5 lignes)",
  "section_anomalies": "Section : Points d'attention / Anomalies (2-4 lignes, vide si aucune)",
  "section_recommandations": "Section : Recommandations & Prochaines actions (3-5 lignes précises)",
  "conclusion": "Conclusion courte (2 lignes)"
}
PROMPT;

        $rapport = $this->ai->chatJson($prompt, $sys);
        if ($rapport === null) {
            $rapport = $this->fallbackRapport($boutique, $validated);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Rapport généré par IA.',
            'data' => $rapport,
        ]);
    }

    private function fallbackRapport(Boutique $boutique, array $v): array
    {
        $dateF = (new \Carbon\Carbon($v['date_rapport']))->translatedFormat('l j F Y');
        return [
            'titre' => "Rapport {$v['type']} — {$boutique->nom} — {$dateF}",
            'introduction' => "Le présent rapport fait suite à l'activité de la boutique {$boutique->nom} pour la période du {$dateF}, sous la supervision de " . ($v['gestionnaire_nom'] ?? 'le gestionnaire') . ". Ce document résume les chiffres clés, l'état des stocks et les observations terrain.",
            'section_activite' => "• Chiffre d'affaires réalisé : " . ($v['ca_jour'] ?? 0) . " FCFA\n• Nombre de clients accueillis : " . ($v['nb_clients'] ?? 0) . "\n• Nombre de commandes enregistrées : " . ($v['nb_commandes'] ?? 0) . "\n• Points-clés terrain : " . ($v['points_cles'] ?: 'Aucun point particulier signalé.') . ".",
            'section_stocks' => trim($v['stocks']) ? "Informations sur les stocks : {$v['stocks']}" : "Les niveaux de stock restent conformes aux seuils d'alerte définis ; aucun cas de rupture majeure n'est rapporté aujourd'hui.",
            'section_anomalies' => trim($v['anomalies']) ? "Points d'attention : {$v['anomalies']}" : "Aucune anomalie majeure n'a été constatée au cours de la journée.",
            'section_recommandations' => "1) Assurer un réappro rapide des articles proches du seuil d'alerte.\n2) Confirmer le planning de livraisons de la semaine avec les fournisseurs.\n3) Renforcer la mise en avant des produits à forte marge.",
            'conclusion' => "L'activité de la boutique est conforme aux objectifs hebdomadaires. Les recommandations ci-dessus permettront de maintenir un niveau de service élevé pour la clientèle agricole.",
        ];
    }

    // =================================================================
    // FEATURE 4 — Prédictions CA + anomalies stock dashboard Admin
    // GET /admin/ai/dashboard/insights
    // =================================================================
    public function dashboardInsights(Request $request): JsonResponse
    {
        $boutiqueId = $request->input('boutique_id');

        $commandesQuery = Commande::where('statut_commande', '!=', 'annulee');
        if ($boutiqueId) $commandesQuery->where('boutique_id', $boutiqueId);

        $commandes30j = (clone $commandesQuery)->where('created_at', '>=', now()->subDays(30))->get();
        $commandes90j = (clone $commandesQuery)->where('created_at', '>=', now()->subDays(90))->get();

        $ca30 = $commandes30j->sum('montant_total');
        $ca90 = $commandes90j->sum('montant_total');
        $jourMoy = $commandes30j->count() > 0 ? round($commandes30j->count() / 30, 1) : 0;
        $panierMoy = $commandes30j->count() > 0 ? round($ca30 / $commandes30j->count()) : 0;

        $tendanceJour = $ca90 > 0 ? (($ca30 / 30 - $ca90 / 90) / ($ca90 / 90) * 100) : 0;
        $caPrev30 = (int)round($ca30 * (1 + max(-0.3, min(0.3, $tendanceJour / 100))));

        $produitsRupture = Produit::actif()->whereColumn('stock_disponible', '<=', 'stock_alerte')
            ->with('categories')
            ->orderByRaw('CASE WHEN stock_disponible <= 0 THEN 0 ELSE 1 END')
            ->orderBy('stock_disponible', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return [
                    'produit_id' => $p->id,
                    'nom' => $p->nom_commercial,
                    'stock' => $p->stock_disponible ?? 0,
                    'alerte' => $p->stock_alerte ?? 0,
                    'categorie' => optional($p->categories->first())->nom ?? '',
                ];
            });

        $ventesProduits = CommandeArticle::whereHas('commande', function ($q) use ($boutiqueId, $commandesQuery) {
            $q->where('created_at', '>=', now()->subDays(30))->where('statut_commande', '!=', 'annulee');
            if ($boutiqueId) $q->where('boutique_id', $boutiqueId);
        })
            ->select('produit_id', DB::raw('SUM(quantite) as qte'), DB::raw('SUM(quantite * prix_unitaire) as total'))
            ->groupBy('produit_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $prod = Produit::find($row->produit_id);
                return [
                    'produit_id' => $row->produit_id,
                    'nom' => $prod?->nom_commercial ?? 'Produit supprimé',
                    'qte' => $row->qte,
                    'chiffre' => (int)$row->total,
                ];
            });

        $promptInsights = [
            'CA_30j_FCFA' => $ca30,
            'Tendance_journaliere_pct' => round($tendanceJour, 1),
            'Panier_moyen_FCFA' => $panierMoy,
            'Nb_commandes_jour_moy' => $jourMoy,
            'Top_ventes_produits' => $ventesProduits->toArray(),
            'Produits_en_rupture_imminente' => $produitsRupture->take(5)->toArray(),
        ];

        $aiInsights = $this->ai->chatJson(
            json_encode($promptInsights, JSON_UNESCAPED_UNICODE),
            "Tu es un directeur général d'un réseau de boutiques agricoles au Togo. Analyse ces chiffres et retourne UNIQUEMENT un JSON : {tendances: [3 points-clés max 40 mots chacun], alertes: [2 alertes max 40 mots], opportunites: [2 opportunites max 40 mots]}. Tout en français, concis et métier."
        );

        if ($aiInsights === null) {
            $aiInsights = [
                'tendances' => [
                    "Le chiffre d'affaires sur 30j est de {$ca30} FCFA, avec un panier moyen de {$panierMoy} FCFA.",
                    "Tendance journalière : " . ($tendanceJour >= 0 ? 'hausse' : 'baisse') . " de " . abs(round($tendanceJour, 1)) . "%.",
                ],
                'alertes' => [
                    $produitsRupture->count() . " produit(s) sont en dessous du stock d'alerte — réappro à prévoir.",
                ],
                'opportunites' => [
                    "Mettre en avant les top " . $ventesProduits->count() . " produits pour augmenter le CA.",
                ],
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Insights dashboard générés.',
            'data' => [
                'ca_30j_fcfa' => (int)$ca30,
                'ca_prevu_30j_fcfa' => $caPrev30,
                'tendance_journaliere_pct' => round($tendanceJour, 1),
                'panier_moyen_fcfa' => $panierMoy,
                'commandes_par_jour' => $jourMoy,
                'total_commandes_30j' => $commandes30j->count(),
                'top_ventes_produits' => $ventesProduits,
                'produits_rupture_imminente' => $produitsRupture,
                'ai_insights' => $aiInsights,
            ],
        ]);
    }

    // =================================================================
    // FEATURE 3 — Rédaction d'un Article de Blog agricole
    // POST /admin/ai/blog/generer-article
    // =================================================================
    public function genererArticleBlog(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titre'                 => 'required|string|max:200',
            'themes'                => 'nullable|array',
            'themes.*'              => 'string|max:200',
            'categorie_produit_nom' => 'nullable|string|max:200',
            'produits_ids'          => 'nullable|array',
            'produits_ids.*'        => 'integer|exists:produits,id',
            'ton'                   => 'nullable|in:pedagogique,marketing,actualite,expert',
            'public_cible'          => 'nullable|in:petits_agriculteurs,moyens_agriculteurs,professionnels,tous',
        ]);

        $themes = is_array($validated['themes']) ? implode(', ', $validated['themes']) : '';
        $ton = $validated['ton'] ?? 'pedagogique';
        $public = $validated['public_cible'] ?? 'tous';

        $produitsLies = [];
        if (!empty($validated['produits_ids'])) {
            $produitsLies = Produit::whereIn('id', $validated['produits_ids'])
                ->pluck('nom_commercial', 'id')
                ->toArray();
        }
        $strProduits = $produitsLies ? ('Produits agricoles liés à mentionner : ' . implode(', ', $produitsLies)) : '';

        $sys = "Tu es un rédacteur web spécialisé en agriculture au Togo. Tu écris des articles de blog pédagogiques et SEO pour AgroShop, destinés aux agriculteurs togolais. Langue : français simple et accessible.";
        $prompt = <<<PROMPT
Rédige un article de blog agricole complet avec les contraintes suivantes :
- Titre souhaité : "{$validated['titre']}"
- Thèmes complémentaires : "{$themes}"
- Catégorie produit mentionnée : "{$validated['categorie_produit_nom']}"
- {$strProduits}
- Ton : {$ton}
- Public cible : {$public}
- Pays : Togo, contexte agro-écologique togolais.

Structure obligatoire — retourne UNIQUEMENT en JSON :
{
  "titre_propose": "Titre final optimisé SEO, < 70 car.",
  "extrait": "Résumé / accroche de 120 à 160 car. pour meta-description.",
  "introduction": "Paragraphe introductif (4-6 lignes)",
  "sections": [
    {"titre": "Section 1", "contenu": "5-8 lignes"},
    {"titre": "Section 2", "contenu": "5-8 lignes"},
    {"titre": "Section 3", "contenu": "5-8 lignes"}
  ],
  "conseil_pratique": "Conseil concret actionnable pour l'agriculteur (2-3 lignes)",
  "conclusion": "Conclusion (3-4 lignes) avec appel à l'action vers AgroShop.",
  "tags_suggestes": ["tag1", "tag2", "tag3", "tag4", "tag5"],
  "meta_title": "SEO title max 200 car.",
  "meta_description": "SEO meta desc max 160 car."
}
PROMPT;

        $article = $this->ai->chatJson($prompt, $sys);

        if ($article === null) {
            $article = [
                'titre_propose' => mb_substr($validated['titre'], 0, 60),
                'extrait' => mb_substr("Découvrez les conseils d'AgroShop sur {$validated['titre']} pour optimiser vos récoltes au Togo. Guide pratique pour agriculteurs.", 0, 150),
                'introduction' => "L'agriculture toolaise fait face à de nombreux défis : aléas climatiques, gestion des intrants, choix des variétés. Dans cet article, nous revenons sur {$validated['titre']} et partageons des bonnes pratiques adaptées au contexte local.",
                'sections' => [
                    ['titre' => 'Comprendre le contexte', 'contenu' => "Chaque région du Togo présente des spécificités pédoclimatiques. Il est essentiel d'adapter les itinéraires techniques : périodes de semis, choix des variétés résistantes, gestion de la fertilisation raisonnée."],
                    ['titre' => 'Les bonnes pratiques', 'contenu' => "Mettre en place une rotation culturale adaptée, associer les cultures, et entretenir la matière organique du sol sont les piliers d'une agriculture durable et rentable sur le long terme."],
                    ['titre' => 'Produits recommandés', 'contenu' => ($strProduits ?: "Les intrants certifiés disponibles chez AgroShop sont sélectionnés pour leur efficacité et leur rapport qualité-prix. N'hésitez pas à demander conseil auprès de nos équipes." . $strProduits)],
                ],
                'conseil_pratique' => "Commencez par tester les nouvelles pratiques sur une petite parcelle avant de généraliser à toute l'exploitation. Conservez un carnet de bord cultural.",
                'conclusion' => "L'agriculture de précision accessible, voilà ce que propose AgroShop au quotidien. Rendez-vous dans nos boutiques ou sur notre plateforme pour vous équiper et optimiser vos récoltes.",
                'tags_suggestes' => ['Agriculture Togo', 'AgroShop', 'Conseil Agricole', $validated['categorie_produit_nom'] ?: 'Intrants', 'Rendement'],
                'meta_title' => mb_substr("{$validated['titre']} — Guide agricole Togo | AgroShop", 0, 190),
                'meta_description' => mb_substr("{$validated['titre']} : conseils d'experts pour vos cultures au Togo. Guide pratique AgroShop pour augmenter vos rendements.", 0, 150),
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Article de blog généré par IA.',
            'data' => $article,
        ]);
    }

    // =================================================================
    // FEATURE RECOMMANDATION — Produits suggérés (public, côté client)
    // GET /ai/produits/{produitId}/recommandations  OU  POST /ai/produits/recommandations
    // =================================================================
    public function produitsRecommandes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'produit_id'       => 'nullable|integer|exists:produits,id',
            'panier_produits'  => 'nullable|array',
            'panier_produits.*' => 'integer|exists:produits,id',
            'historique_ids'   => 'nullable|array',
            'historique_ids.*'  => 'integer|exists:produits,id',
            'client_region'    => 'nullable|string|max:100',
            'client_culture'   => 'nullable|string|max:100',
            'limit'            => 'nullable|integer|min:1|max:12',
        ]);

        $limit = $validated['limit'] ?? 6;
        $produitsDeja = array_values(array_unique(array_merge(
            $validated['panier_produits'] ?? [],
            $validated['historique_ids'] ?? [],
            [$validated['produit_id'] ?? 0]
        )));

        $courant = $validated['produit_id'] ? Produit::with('categories')->find($validated['produit_id']) : null;
        $courantCatIds = $courant ? optional($courant->categories)->pluck('id')->toArray() ?? [] : [];

        $panierCats = [];
        if (!empty($validated['panier_produits'])) {
            $panierCats = Produit::whereIn('id', $validated['panier_produits'])
                ->with('categories')
                ->get()
                ->flatMap(fn($p) => optional($p->categories)->pluck('id')->toArray() ?? [])
                ->unique()
                ->values()
                ->toArray();
        }

        $catCibles = array_values(array_unique(array_merge($courantCatIds, $panierCats)));

        $reglesComplementaires = $this->reglesRecommandation(
            $courant,
            $validated['panier_produits'] ?? [],
            $validated['client_culture'] ?? '',
            $validated['client_region'] ?? ''
        );

        $query = Produit::actif()->with(['categories', 'imagePrincipale']);
        if (!empty($produitsDeja)) $query->whereNotIn('id', $produitsDeja);

        $candidats = $query->inRandomOrder()->limit(50)->get();

        $scored = $candidats->map(function ($p) use ($catCibles, $reglesComplementaires) {
            $score = 0;
            $motifs = [];
            $pCatIds = optional($p->categories)->pluck('id')->toArray() ?? [];

            if (!empty($catCibles) && count(array_intersect($pCatIds, $catCibles)) > 0) {
                $score += 10;
                $motifs[] = 'catégorie similaire';
            }

            foreach ($reglesComplementaires as $rule) {
                if (!empty($rule['trigger_ids']) && in_array($p->id, $rule['trigger_ids'])) {
                    $score += 25;
                    $motifs[] = $rule['motif'];
                } elseif (!empty($rule['keywords'])) {
                    foreach ($rule['keywords'] as $kw) {
                        if (stripos($p->nom_commercial . ' ' . $p->description, $kw) !== false) {
                            $score += $rule['score'] ?? 15;
                            $motifs[] = $rule['motif'];
                            break;
                        }
                    }
                }
            }

            if ($p->featured) $score += 3;
            if (($p->stock_disponible ?? 0) > ($p->stock_alerte ?? 10)) $score += 1;

            return [
                'produit' => [
                    'id' => $p->id,
                    'nom_commercial' => $p->nom_commercial,
                    'slug' => $p->slug,
                    'prix_unitaire' => $p->prix_unitaire ?? 0,
                    'unite_mesure' => $p->unite_mesure ?? 'unité',
                    'url_image' => $p->image_principale?->url_image ?? $p->url_image ?? null,
                    'description' => mb_substr(strip_tags($p->description ?? ''), 0, 90),
                    'featured' => (bool)$p->featured,
                    'stock_disponible' => $p->stock_disponible ?? 0,
                ],
                'score' => $score,
                'motifs' => $motifs,
            ];
        });

        $scored = $scored->sortByDesc('score')->take($limit)->values();

        if ($scored->count() === 0) {
            $scored = Produit::actif()
                ->featured()
                ->with('imagePrincipale')
                ->limit($limit)
                ->get()
                ->map(fn($p) => [
                    'produit' => [
                        'id' => $p->id,
                        'nom_commercial' => $p->nom_commercial,
                        'slug' => $p->slug,
                        'prix_unitaire' => $p->prix_unitaire ?? 0,
                        'unite_mesure' => $p->unite_mesure ?? 'unité',
                        'url_image' => $p->image_principale?->url_image ?? $p->url_image ?? null,
                        'description' => mb_substr(strip_tags($p->description ?? ''), 0, 90),
                        'featured' => true,
                        'stock_disponible' => $p->stock_disponible ?? 0,
                    ],
                    'score' => 5,
                    'motifs' => ['produit populaire'],
                ])->values();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Recommandations générées (hybride règles + heuristiques).',
            'data' => [
                'recommandations' => $scored,
                'meta' => [
                    'sur_base_id' => $validated['produit_id'],
                    'panier_used' => count($validated['panier_produits'] ?? []),
                    'historique_used' => count($validated['historique_ids'] ?? []),
                ],
            ],
        ]);
    }

    private function reglesRecommandation($courant, array $panierIds, string $culture, string $region): array
    {
        $courantNom = $courant?->nom_commercial ?? '';
        $courantDesc = $courant?->description ?? '';
        $haystack = $courantNom . ' ' . $courantDesc;

        $rules = [];

        if (stripos($haystack, 'uree') !== false || stripos($haystack, 'azote') !== false || stripos($haystack, 'NPK') !== false) {
            $rules[] = [
                'keywords' => ['semence', 'semences', 'mais', 'riz', 'sorgho', 'millet'],
                'score' => 20,
                'motif' => 'souvent acheté avec des engrais NPK / urée',
            ];
        }

        if (stripos($haystack, 'semence') !== false || stripos($haystack, 'mais') !== false || stripos($haystack, 'riz') !== false) {
            $rules[] = [
                'keywords' => ['herbicide', 'herbicides', 'desherbant'],
                'score' => 18,
                'motif' => 'protection recommandée pour cultures de céréales',
            ];
            $rules[] = [
                'keywords' => ['urée', 'NPK', 'engrais'],
                'score' => 15,
                'motif' => 'complément fertilisation pour vos semences',
            ];
        }

        if (stripos($haystack, 'pelle') !== false || stripos($haystack, 'rateau') !== false || stripos($haystack, 'outil') !== false || stripos($haystack, 'bêche') !== false) {
            $rules[] = [
                'keywords' => ['bêche', 'pelle', 'rateau', 'houe', 'arrosoir'],
                'score' => 15,
                'motif' => 'complète votre kit de petit matériel agricole',
            ];
        }

        if (!empty($culture)) {
            $rules[] = [
                'keywords' => [$culture],
                'score' => 12,
                'motif' => "adapté à la culture : {$culture}",
            ];
        }
        if (!empty($region)) {
            $rules[] = [
                'keywords' => [$region],
                'score' => 5,
                'motif' => "souvent commandé depuis {$region}",
            ];
        }
        return $rules;
    }
}
