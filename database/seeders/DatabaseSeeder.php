<?php

namespace Database\Seeders;

use App\Models\Administrateur;
use App\Models\ArticleBlog;
use App\Models\Categorie;
use App\Models\Commande;
use App\Models\CommandeArticle;
use App\Models\CommandeSuivi;
use App\Models\ParametreSysteme;
use App\Models\Produit;
use App\Models\ProduitDocument;
use App\Models\ProduitImage;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial Agroshop data.
     */
    public function run(): void
    {
        // 1. Seed Administrateurs
        $admin1 = Administrateur::updateOrCreate(
            ['email' => 'admin@agroshoptg.store'],
            [
                'nom' => 'Super',
                'prenom' => 'Admin',
                'mot_de_passe' => Hash::make('password123'),
                'role' => 'super_admin',
                'actif' => true,
            ]
        );

        Administrateur::updateOrCreate(
            ['email' => 'admin@agroshop.tg'],
            [
                'nom' => 'Super',
                'prenom' => 'Admin',
                'mot_de_passe' => Hash::make('password123'),
                'role' => 'super_admin',
                'actif' => true,
            ]
        );

        Administrateur::updateOrCreate(
            ['email' => 'sewodakomla@gmail.com'],
            [
                'nom' => 'Sewoda',
                'prenom' => 'Komla',
                'mot_de_passe' => '$2y$10$gco9QjhVMmGHwtRyU2DJJOw9KF7OsechGSgfyZFNEVP5PX6S/4Wjy',
                'role' => 'admin',
                'actif' => true,
            ]
        );

        Administrateur::updateOrCreate(
            ['email' => 'contact@agroshoptg.store'],
            [
                'nom' => 'Contact',
                'prenom' => 'Admin',
                'mot_de_passe' => '$2y$10$YAiafSuzDQgDLm.9WMpBquts0ioPSxpkFhYFdoqC5yjrZxYsC5mmu',
                'role' => 'admin',
                'actif' => true,
            ]
        );

        // 2. Seed Paramètres Système
        $parametres = [
            ['cle_parametre' => 'site_nom', 'valeur_parametre' => 'AGROSHOP', 'description_parametre' => 'Nom du site web', 'type_parametre' => 'string'],
            ['cle_parametre' => 'tva_taux', 'valeur_parametre' => '18', 'description_parametre' => 'Taux de TVA en pourcentage', 'type_parametre' => 'integer'],
            ['cle_parametre' => 'frais_livraison_base', 'valeur_parametre' => '5000', 'description_parametre' => 'Frais de livraison de base en FCFA', 'type_parametre' => 'integer'],
            ['cle_parametre' => 'stock_alerte_global', 'valeur_parametre' => '10', 'description_parametre' => 'Seuil d\'alerte stock par défaut', 'type_parametre' => 'integer'],
            ['cle_parametre' => 'commande_auto_confirm', 'valeur_parametre' => 'false', 'description_parametre' => 'Confirmation automatique des commandes', 'type_parametre' => 'boolean'],
        ];

        foreach ($parametres as $p) {
            ParametreSysteme::updateOrCreate(['cle_parametre' => $p['cle_parametre']], $p);
        }

        // 3. Seed Catégories
        $categoriesParentes = [
            ['id' => 1, 'nom' => 'Intrants Agricoles', 'description' => 'Engrais, urée, NPK et amendements', 'parent_id' => null, 'slug' => 'intrants-agricoles', 'ordre_affichage' => 1, 'actif' => 1],
            ['id' => 2, 'nom' => 'Produits Phytosanitaires', 'description' => 'Insecticides, fongicides, herbicides', 'parent_id' => null, 'slug' => 'produits-phytosanitaires', 'ordre_affichage' => 2, 'actif' => 1],
            ['id' => 3, 'nom' => 'Systèmes d\'Irrigation', 'description' => 'Équipements et accessoires d\'irrigation', 'parent_id' => null, 'slug' => 'systemes-irrigation', 'ordre_affichage' => 3, 'actif' => 1],
            ['id' => 4, 'nom' => 'Semences', 'description' => 'Graines et semences diverses', 'parent_id' => null, 'slug' => 'semences', 'ordre_affichage' => 4, 'actif' => 1],
            ['id' => 5, 'nom' => 'Machines Agricoles', 'description' => 'Tracteurs, motoculteurs, équipements', 'parent_id' => null, 'slug' => 'machines-agricoles', 'ordre_affichage' => 5, 'actif' => 1],
            ['id' => 6, 'nom' => 'Quincaillerie', 'description' => 'Outils et accessoires divers', 'parent_id' => null, 'slug' => 'quincaillerie', 'ordre_affichage' => 6, 'actif' => 1],
        ];

        foreach ($categoriesParentes as $c) {
            Categorie::updateOrCreate(['id' => $c['id']], $c);
        }

        $sousCategories = [
            ['id' => 7, 'nom' => 'Engrais NPK', 'description' => 'Engrais composés azote-phosphore-potassium', 'parent_id' => 1, 'slug' => 'engrais-npk', 'ordre_affichage' => 1, 'actif' => 1],
            ['id' => 8, 'nom' => 'Urée', 'description' => 'Engrais azoté', 'parent_id' => 1, 'slug' => 'uree', 'ordre_affichage' => 2, 'actif' => 1],
            ['id' => 9, 'nom' => 'Engrais Organiques', 'description' => 'Fumiers et composts', 'parent_id' => 1, 'slug' => 'engrais-organiques', 'ordre_affichage' => 3, 'actif' => 1],
            ['id' => 10, 'nom' => 'Insecticides', 'description' => 'Produits contre les insectes nuisibles', 'parent_id' => 2, 'slug' => 'insecticides', 'ordre_affichage' => 1, 'actif' => 1],
            ['id' => 11, 'nom' => 'Fongicides', 'description' => 'Produits contre les maladies fongiques', 'parent_id' => 2, 'slug' => 'fongicides', 'ordre_affichage' => 2, 'actif' => 1],
            ['id' => 12, 'nom' => 'Herbicides', 'description' => 'Produits contre les mauvaises herbes', 'parent_id' => 2, 'slug' => 'herbicides', 'ordre_affichage' => 3, 'actif' => 1],
            ['id' => 13, 'nom' => 'Nématicides', 'description' => 'Produits contre les nématodes', 'parent_id' => 2, 'slug' => 'nematicides', 'ordre_affichage' => 4, 'actif' => 1],
            ['id' => 14, 'nom' => 'Outillage Manuel', 'description' => 'Pelles, pioches, machettes, marteaux et outils à main', 'parent_id' => 6, 'slug' => 'outillage-manuel', 'ordre_affichage' => 1, 'actif' => 1],
            ['id' => 15, 'nom' => 'Équipements & Protection', 'description' => 'Brouettes, gants, bottes et matériel de chantier', 'parent_id' => 6, 'slug' => 'equipements-protection', 'ordre_affichage' => 2, 'actif' => 1],
        ];

        foreach ($sousCategories as $c) {
            Categorie::updateOrCreate(['id' => $c['id']], $c);
        }

        // 4. Seed 7 Produits de démonstration (AgroDop / Phytosanitaire / Semences / Irrigation / Machines / Quincaillerie)
        $produit1 = Produit::updateOrCreate(
            ['id' => 1],
            [
                'nom_commercial' => 'Urée YARA 46% N',
                'description' => 'Engrais azoté concentré contenant 46 % d’azote, idéal pour stimuler la croissance végétative des cultures de maïs, riz et maraîchage.',
                'composition' => 'Urée granulée contenant 46 % d\'Azote total (N).',
                'principes_actifs' => 'Azote (N) = 46 %.',
                'mode_emploi' => 'Appliquer au sol avant ou après semis, puis arroser pour faciliter la dissolution et éviter les pertes par volatilisation.',
                'dosage_recommande' => '50 à 100 kg/ha selon le type de culture et le type de sol.',
                'precautions_usage' => 'Conserver dans un endroit sec et à l’abri de l’humidité. Porter des gants lors de la manipulation.',
                'contre_indications' => 'Ne pas mélanger directement avec des engrais phosphatés ou potassiques concentrés lors de l\'application.',
                'prix_unitaire' => 15000.00,
                'unite_mesure' => 'sac 50kg',
                'stock_disponible' => 1000,
                'stock_alerte' => 20,
                'poids' => 50.00,
                'statut' => 'actif',
                'featured' => true,
                'slug' => 'uree-yara-46-n',
            ]
        );

        $produit2 = Produit::updateOrCreate(
            ['id' => 2],
            [
                'nom_commercial' => 'Engrais NPK 15-15-15 SuperFert',
                'description' => 'Engrais composé équilibré apportant les trois éléments majeurs (Azote, Phosphore, Potassium) pour un développement harmonieux de toutes les cultures.',
                'composition' => '15% N (Azote), 15% P2O5 (Phosphore), 15% K2O (Potassium).',
                'principes_actifs' => 'N-P-K 15-15-15 + Oligo-éléments.',
                'mode_emploi' => 'Enfouir légèrement autour des plants au moment du repiquage ou en fertilisation de couverture.',
                'dosage_recommande' => '150 à 300 kg/ha selon les exigences de la culture.',
                'precautions_usage' => 'Stocker au sec sur des palettes. Éviter le contact direct avec les racines lors du semis.',
                'contre_indications' => 'Éviter le surdosage en période de sécheresse sévère sans irrigation.',
                'prix_unitaire' => 18500.00,
                'unite_mesure' => 'sac 50kg',
                'stock_disponible' => 750,
                'stock_alerte' => 15,
                'poids' => 50.00,
                'statut' => 'actif',
                'featured' => true,
                'slug' => 'engrais-npk-15-15-15-superfert',
            ]
        );

        $produit3 = Produit::updateOrCreate(
            ['id' => 3],
            [
                'nom_commercial' => 'Insecticide Katana 50 EC',
                'description' => 'Insecticide polyvalent à large spectre d\'action, particulièrement efficace contre la chenille légionnaire, les pucerons et les thrips.',
                'composition' => 'Emulsion concentrée contenant 50 g/L de Cyperméthrine.',
                'principes_actifs' => 'Cyperméthrine 50 g/L.',
                'mode_emploi' => 'Diluer dans l\'eau et pulvériser uniformément sur le feuillage dès l\'apparition des premiers prédateurs.',
                'dosage_recommande' => '1 Litre / hectare (soit 50ml pour un pulvérisateur de 15 Litres).',
                'precautions_usage' => 'Porter une tenue de protection complète, des gants et un masque. Ne pas traiter pendant les heures chaudes.',
                'contre_indications' => 'Toxique pour les organismes aquatiques et les abeilles. Ne pas appliquer en période de floraison active.',
                'prix_unitaire' => 7500.00,
                'unite_mesure' => 'flacon 1L',
                'stock_disponible' => 300,
                'stock_alerte' => 10,
                'poids' => 1.20,
                'statut' => 'actif',
                'featured' => false,
                'slug' => 'insecticide-katana-50-ec',
            ]
        );

        $produit4 = Produit::updateOrCreate(
            ['id' => 4],
            [
                'nom_commercial' => 'Semence Maïs Hybride PAN 53',
                'description' => 'Variété de maïs hybride à très haut potentiel de rendement, tolérante à la sécheresse et résistante aux principales maladies foliaires.',
                'composition' => 'Graines certifiées de Maïs hybride (Zea mays). Taux de germination > 95%.',
                'principes_actifs' => 'Traitement de semence fongicide/insecticide préventif.',
                'mode_emploi' => 'Semer à une profondeur de 3 à 5 cm avec un écartement de 75 cm entre les lignes et 25 cm entre les poquets.',
                'dosage_recommande' => '20 à 25 kg/ha pour une densité optimale.',
                'precautions_usage' => 'Conserver dans un endroit frais et sec. Ne pas consommer les graines traitées.',
                'contre_indications' => 'Ne pas semer en sol noyé ou mal drainé.',
                'prix_unitaire' => 12000.00,
                'unite_mesure' => 'sac 5kg',
                'stock_disponible' => 500,
                'stock_alerte' => 10,
                'poids' => 5.00,
                'statut' => 'actif',
                'featured' => true,
                'slug' => 'semence-mais-hybride-pan-53',
            ]
        );

        $produit5 = Produit::updateOrCreate(
            ['id' => 5],
            [
                'nom_commercial' => 'Kit d\'Irrigation Goutte-à-Goutte 500m²',
                'description' => 'Système complet d\'irrigation au goutte-à-goutte prêt à installer pour le maraîchage, serres ou petits vergers. Économise jusqu\'à 60% d\'eau.',
                'composition' => 'Tuyau principal PE 25mm (50m), gaines goutte-à-goutte 16mm avec goutteurs espacés de 20cm (500m), vannes de secteur, filtre à disque et raccords.',
                'mode_emploi' => 'Raccorder à une réserve d\'eau surélevée ou à une pompe. Ouvrir le filtre et nettoyer régulièrement.',
                'dosage_recommande' => 'Débit de 1.5 L/h par goutteur sous pression de 1 bar.',
                'precautions_usage' => 'Installer un filtre à disque pour éviter l\'obturation des goutteurs.',
                'prix_unitaire' => 85000.00,
                'unite_mesure' => 'kit complet',
                'stock_disponible' => 45,
                'stock_alerte' => 5,
                'poids' => 18.00,
                'statut' => 'actif',
                'featured' => true,
                'slug' => 'kit-irrigation-goutte-a-goutte-500m2',
            ]
        );

        $produit6 = Produit::updateOrCreate(
            ['id' => 6],
            [
                'nom_commercial' => 'Atomiseur STIHL SR 450',
                'description' => 'Appareil de pulvérisation motorisé haute performance porté sur le dos, idéal pour la protection des grandes cultures, arbres fruitiers et désinfection.',
                'composition' => 'Moteur 2 temps STIHL 63.3 cm³, réservoir de produit 14 Litres, portée horizontale jusqu\'à 14.5 mètres.',
                'mode_emploi' => 'Remplir le réservoir de carburant (mélange 2%), préparer le produit phytosanitaire et démarrer au lanceur.',
                'dosage_recommande' => 'Utiliser avec des buses adaptées selon le type de traitement (brouillard fin ou gros débit).',
                'precautions_usage' => 'Utiliser des protections auditives et respiratoires. Entretien régulier du filtre à air.',
                'prix_unitaire' => 515000.00,
                'unite_mesure' => 'unité',
                'stock_disponible' => 25,
                'stock_alerte' => 3,
                'poids' => 12.80,
                'statut' => 'actif',
                'featured' => true,
                'slug' => 'atomiseur-stihl-sr-450',
            ]
        );

        $produit7 = Produit::updateOrCreate(
            ['id' => 7],
            [
                'nom_commercial' => 'Brouette de Chantier Renforcée 90L',
                'description' => 'Brouette professionnelle de quincaillerie à châssis tubulaire monobloc et bac galvanisé. Conçue pour le transport de charges lourdes et d\'outils.',
                'composition' => 'Bac en acier galvanisé anticorrosion (épaisseur 1mm), châssis en tube d\'acier 32mm, roue gonflable grand diamètre 400mm.',
                'mode_emploi' => 'Vérifier la pression de la roue gonflable (max 2 bar) avant chargement.',
                'dosage_recommande' => 'Charge utile maximale conseillée : 160 kg.',
                'precautions_usage' => 'Ne pas dépasser la charge maximale. Graisser l\'axe de la roue périodiquement.',
                'prix_unitaire' => 32000.00,
                'unite_mesure' => 'unité',
                'stock_disponible' => 120,
                'stock_alerte' => 10,
                'poids' => 14.50,
                'statut' => 'actif',
                'featured' => false,
                'slug' => 'brouette-de-chantier-renforcee-90l',
            ]
        );

        $produit8 = Produit::updateOrCreate(
            ['id' => 8],
            [
                'nom_commercial' => 'Machette d\'Agriculture & Chantier 22"',
                'description' => 'Outil de coupe et de quincaillerie de haute qualité en acier trempé avec manche ergonomique antidérapant.',
                'composition' => 'Lame en acier au carbone poli 22 pouces (55cm), manche polymère riveté.',
                'mode_emploi' => 'Utiliser pour le défrichement, le débroussaillage et le travail de quincaillerie/chantier.',
                'dosage_recommande' => 'Affûter régulièrement la lame pour une efficacité optimale.',
                'precautions_usage' => 'Manipuler avec précaution. Porter des gants de protection et un étui de rangement.',
                'prix_unitaire' => 4500.00,
                'unite_mesure' => 'unité',
                'stock_disponible' => 250,
                'stock_alerte' => 20,
                'poids' => 0.85,
                'statut' => 'actif',
                'featured' => true,
                'slug' => 'machette-agriculture-chantier-22',
            ]
        );

        // Association catégories & images
        $produit1->categories()->syncWithoutDetaching([8 => ['principale' => 1]]); // Urée
        $produit2->categories()->syncWithoutDetaching([7 => ['principale' => 1]]); // Engrais NPK
        $produit3->categories()->syncWithoutDetaching([10 => ['principale' => 1]]); // Insecticides
        $produit4->categories()->syncWithoutDetaching([4 => ['principale' => 1]]); // Semences
        $produit5->categories()->syncWithoutDetaching([3 => ['principale' => 1]]); // Systèmes d'Irrigation
        $produit6->categories()->syncWithoutDetaching([5 => ['principale' => 1]]); // Machines Agricoles
        $produit7->categories()->syncWithoutDetaching([15 => ['principale' => 1]]); // Quincaillerie / Équipements
        $produit8->categories()->syncWithoutDetaching([14 => ['principale' => 1]]); // Quincaillerie / Outillage Manuel

        ProduitImage::updateOrCreate(['id' => 1], ['produit_id' => 1, 'nom_fichier' => 'urea.jpg', 'url_image' => 'storage/produits/urea.jpg', 'alt_text' => 'Image Urée YARA', 'ordre_affichage' => 0, 'principale' => true]);
        ProduitImage::updateOrCreate(['id' => 2], ['produit_id' => 2, 'nom_fichier' => 'npk.jpg', 'url_image' => 'storage/produits/npk.jpg', 'alt_text' => 'Image Engrais NPK', 'ordre_affichage' => 0, 'principale' => true]);
        ProduitImage::updateOrCreate(['id' => 3], ['produit_id' => 3, 'nom_fichier' => 'katana.jpg', 'url_image' => 'storage/produits/katana.jpg', 'alt_text' => 'Image Insecticide Katana', 'ordre_affichage' => 0, 'principale' => true]);
        ProduitImage::updateOrCreate(['id' => 4], ['produit_id' => 4, 'nom_fichier' => 'mais_pan53.jpg', 'url_image' => 'storage/produits/mais_pan53.jpg', 'alt_text' => 'Image Semence Maïs PAN 53', 'ordre_affichage' => 0, 'principale' => true]);
        ProduitImage::updateOrCreate(['id' => 5], ['produit_id' => 5, 'nom_fichier' => 'irrigation_kit.jpg', 'url_image' => 'storage/produits/irrigation_kit.jpg', 'alt_text' => 'Image Kit Irrigation', 'ordre_affichage' => 0, 'principale' => true]);
        ProduitImage::updateOrCreate(['id' => 6], ['produit_id' => 6, 'nom_fichier' => 'stihl.jpg', 'url_image' => 'storage/produits/stihl.jpg', 'alt_text' => 'Image STIHL SR 450', 'ordre_affichage' => 0, 'principale' => true]);
        ProduitImage::updateOrCreate(['id' => 7], ['produit_id' => 7, 'nom_fichier' => 'brouette.jpg', 'url_image' => 'storage/produits/brouette.jpg', 'alt_text' => 'Image Brouette de Chantier', 'ordre_affichage' => 0, 'principale' => true]);
        ProduitImage::updateOrCreate(['id' => 8], ['produit_id' => 8, 'nom_fichier' => 'machette.jpg', 'url_image' => 'storage/produits/machette.jpg', 'alt_text' => 'Image Machette 22 pouces', 'ordre_affichage' => 0, 'principale' => true]);

        // 5. Seed Tags & Articles de Blog
        $tag1 = Tag::updateOrCreate(['slug' => 'conseils-agronomiques'], ['nom' => 'Conseils Agronomiques', 'couleur' => '#10B981']);
        $tag2 = Tag::updateOrCreate(['slug' => 'fertilisation'], ['nom' => 'Fertilisation & Engrais', 'couleur' => '#3B82F6']);
        $tag3 = Tag::updateOrCreate(['slug' => 'protection-cultures'], ['nom' => 'Protection des Cultures', 'couleur' => '#EF4444']);
        $tag4 = Tag::updateOrCreate(['slug' => 'irrigation-materiel'], ['nom' => 'Irrigation & Équipements', 'couleur' => '#8B5CF6']);

        $articlesData = [
            [
                'slug' => 'guide-complet-utilisation-uree-yara',
                'titre' => 'Guide complet : Maximiser le rendement du Maïs avec l\'Urée YARA 46%',
                'extrait' => 'Découvrez le calendrier d\'épandage et les meilleurs dosages par hectare pour éviter les pertes d\'azote.',
                'contenu' => 'L\'urée YARA 46% N est l\'engrais le plus concentré en azote. Pour maximiser son absorption par le maïs, il est recommandé de fractionner l\'application : un tiers au semis et deux tiers 30 jours après la levée.',
                'image_principale' => 'images/champ-agricole-bg.jpg',
                'tag_ids' => [$tag1->id, $tag2->id]
            ],
            [
                'slug' => 'lutter-efficacement-contre-chenille-legionnaire-togo',
                'titre' => 'Comment protéger vos parcelles contre la Chenille Légionnaire',
                'extrait' => 'Protégez vos cultures de céréales grâce aux traitements insecticides homologués et aux bonnes pratiques de surveillance.',
                'contenu' => 'La chenille légionnaire (Spodoptera frugiperda) peut détruire jusqu\'à 70% des récoltes. L\'utilisation préventive de l\'insecticide Katana 50 EC combinée à l\'inspection régulière des cornets de maïs garantit une protection optimale.',
                'image_principale' => 'images/produits-agroshop-npk-spray-irrigation-mais.png',
                'tag_ids' => [$tag3->id]
            ],
            [
                'slug' => 'avantages-irrigation-goutte-a-goutte-maraichage',
                'titre' => 'Irrigation Goutte-à-Goutte : Économisez 50% d\'eau tout en doublant vos récoltes',
                'extrait' => 'Le guide pratique pour installer un kit d\'irrigation abordable sur une parcelle de maraîchage au Togo.',
                'contenu' => 'L\'irrigation goutte-à-goutte apporte l\'eau et les nutriments directement au niveau des racines. Elle évite l\'évaporation, réduit les mauvaises herbes et garantit une récolte constante toute l\'année.',
                'image_principale' => 'images/Agroshop-hero2.png',
                'tag_ids' => [$tag4->id]
            ],
            [
                'slug' => 'choix-outils-quincaillerie-chantier-agricole',
                'titre' => 'Outillage & Quincaillerie : Choisir son matériel de chantier longue durée',
                'extrait' => 'Brouettes renforcées, pulvérisateurs et tuyaux : comparatif pour s\'équiper efficacement.',
                'contenu' => 'Pour des travaux agricoles et de construction durables, le choix de la qualité est essentiel. Les brouettes 90L à cuve renforcée et les pulvérisateurs à dos STIHL garantissent longévité et sécurité sur le terrain.',
                'image_principale' => 'images/Agroshop-hero3.png',
                'tag_ids' => [$tag4->id]
            ]
        ];

        foreach ($articlesData as $artData) {
            $article = ArticleBlog::updateOrCreate(
                ['slug' => $artData['slug']],
                [
                    'titre' => $artData['titre'],
                    'contenu' => $artData['contenu'],
                    'extrait' => $artData['extrait'],
                    'statut' => 'publie',
                    'auteur_id' => $admin1->id,
                    'image_principale' => $artData['image_principale'],
                    'meta_title' => $artData['titre'] . ' - AgroShop',
                    'meta_description' => $artData['extrait'],
                    'date_publication' => now(),
                    'vues' => rand(15, 120),
                ]
            );
            $article->tags()->syncWithoutDetaching($artData['tag_ids']);
        }

        // 6. Seed Documents Fiches Techniques & Guides
        ProduitDocument::updateOrCreate(
            ['id' => 1],
            [
                'produit_id' => 1,
                'nom_document' => 'Fiche Technique - Urée YARA 46%',
                'type_document' => 'fiche_technique',
                'url_document' => 'storage/documents/ft_urea_yara.pdf',
                'taille_fichier' => 1024000,
            ]
        );

        ProduitDocument::updateOrCreate(
            ['id' => 2],
            [
                'produit_id' => 3,
                'nom_document' => 'Guide de Sécurité & Dosage - Katana 50 EC',
                'type_document' => 'guide_utilisation',
                'url_document' => 'storage/documents/guide_katana_50ec.pdf',
                'taille_fichier' => 512000,
            ]
        );

        ProduitDocument::updateOrCreate(
            ['id' => 3],
            [
                'produit_id' => 6,
                'nom_document' => 'Manuel Utilisateur STIHL SR 450',
                'type_document' => 'guide_utilisation',
                'url_document' => 'storage/documents/manuel_stihl_sr450.pdf',
                'taille_fichier' => 2048000,
            ]
        );

        // 7. Seed Exemples de Commandes de Test
        $commande1 = Commande::updateOrCreate(
            ['code_reference' => 'CMD-2026-0001'],
            [
                'nom_client' => 'KOFFI',
                'prenom_client' => 'Ablam',
                'telephone' => '+22890123456',
                'email' => 'ablam.koffi@example.com',
                'adresse_ligne1' => 'Quartier Tokoin',
                'ville' => 'Lomé',
                'pays' => 'Togo',
                'montant_ht' => 45000.00,
                'montant_tva' => 8100.00,
                'montant_ttc' => 53100.00,
                'frais_livraison' => 5000.00,
                'montant_total' => 58100.00,
                'type_livraison' => 'livraison',
                'adresse_livraison' => 'Ferme maraîchère, Tokoin Lomé',
                'statut_commande' => 'confirmee',
                'statut_paiement' => 'paye',
                'commentaire' => 'Livrer en début de matinée s\'il vous plaît.',
            ]
        );

        CommandeArticle::updateOrCreate(
            ['id' => 1],
            [
                'commande_id' => $commande1->id,
                'produit_id' => 1,
                'nom_produit' => 'Urée YARA 46% N',
                'prix_unitaire' => 15000.00,
                'quantite' => 2,
                'montant_ligne' => 30000.00,
            ]
        );

        CommandeArticle::updateOrCreate(
            ['id' => 2],
            [
                'commande_id' => $commande1->id,
                'produit_id' => 2,
                'nom_produit' => 'Engrais NPK 15-15-15 SuperFert',
                'prix_unitaire' => 15000.00,
                'quantite' => 1,
                'montant_ligne' => 15000.00,
            ]
        );

        CommandeSuivi::updateOrCreate(
            ['id' => 1],
            [
                'commande_id' => $commande1->id,
                'statut_precedent' => 'en_attente',
                'nouveau_statut' => 'confirmee',
                'commentaire' => 'Commande validée après paiement Mobile Money.',
                'utilisateur_id' => $admin1->id,
            ]
        );

        $commande2 = Commande::updateOrCreate(
            ['code_reference' => 'CMD-2026-0002'],
            [
                'nom_client' => 'LAWSON',
                'prenom_client' => 'Jean-Marc',
                'telephone' => '+22891987654',
                'email' => 'jm.lawson@example.com',
                'adresse_ligne1' => 'Grand Marché',
                'ville' => 'Tsévié',
                'pays' => 'Togo',
                'montant_ht' => 117000.00,
                'montant_tva' => 21060.00,
                'montant_ttc' => 138060.00,
                'frais_livraison' => 0.00,
                'montant_total' => 138060.00,
                'type_livraison' => 'retrait_agence',
                'statut_commande' => 'livree',
                'statut_paiement' => 'paye',
                'commentaire' => 'Retrait à l\'agence de Tsévié.',
            ]
        );

        CommandeArticle::updateOrCreate(
            ['id' => 3],
            [
                'commande_id' => $commande2->id,
                'produit_id' => 5,
                'nom_produit' => 'Kit d\'Irrigation Goutte-à-Goutte 500m²',
                'prix_unitaire' => 85000.00,
                'quantite' => 1,
                'montant_ligne' => 85000.00,
            ]
        );

        CommandeArticle::updateOrCreate(
            ['id' => 4],
            [
                'commande_id' => $commande2->id,
                'produit_id' => 7,
                'nom_produit' => 'Brouette de Chantier Renforcée 90L',
                'prix_unitaire' => 32000.00,
                'quantite' => 1,
                'montant_ligne' => 32000.00,
            ]
        );

        CommandeSuivi::updateOrCreate(
            ['id' => 2],
            [
                'commande_id' => $commande2->id,
                'statut_precedent' => 'en_attente',
                'nouveau_statut' => 'livree',
                'commentaire' => 'Colis récupéré au guichet agence.',
                'utilisateur_id' => $admin1->id,
            ]
        );
    }
}
