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
        ];

        foreach ($sousCategories as $c) {
            Categorie::updateOrCreate(['id' => $c['id']], $c);
        }

        // 4. Seed Produits de démonstration
        $produit1 = Produit::updateOrCreate(
            ['id' => 1],
            [
                'nom_commercial' => 'Urée YARA',
                'description' => 'Engrais azoté concentré contenant 46 % d’azote, idéal pour stimuler la croissance végétative des cultures.',
                'composition' => 'Urée granulée contenant 46 % de N (azote total).',
                'principes_actifs' => 'Azote (N) = 46 %.',
                'mode_emploi' => 'Appliquer au sol avant ou après semis, puis arroser pour faciliter la dissolution.',
                'dosage_recommande' => '50 à 100 kg/ha selon la culture.',
                'precautions_usage' => 'Conserver à l’abri de l’humidité. Porter des gants lors de la manipulation.',
                'contre_indications' => 'Ne pas mélanger directement avec des engrais phosphatés ou potassiques concentrés.',
                'prix_unitaire' => 15000.00,
                'unite_mesure' => 'kg',
                'stock_disponible' => 1000,
                'stock_alerte' => 10,
                'poids' => 40.00,
                'statut' => 'actif',
                'featured' => true,
                'slug' => 'ur-e-yara',
            ]
        );

        $produit2 = Produit::updateOrCreate(
            ['id' => 2],
            [
                'nom_commercial' => 'Betterave BORO F1',
                'description' => 'Variété hybride de betterave rouge très productive, à racines rondes, chair rouge intense, adaptée aux climats tropicaux.',
                'composition' => 'Graines hybrides de betterave rouge (Beta vulgaris).',
                'prix_unitaire' => 4000.00,
                'unite_mesure' => 'g',
                'stock_disponible' => 9996,
                'stock_alerte' => 10,
                'poids' => 12.00,
                'statut' => 'actif',
                'featured' => true,
                'slug' => 'betterave-boro-f1',
            ]
        );

        $produit3 = Produit::updateOrCreate(
            ['id' => 4],
            [
                'nom_commercial' => 'Atomiseur STIHL SR 450',
                'description' => 'Appareil de pulvérisation motorisé porté sur le dos, utilisé pour la protection des cultures.',
                'prix_unitaire' => 515000.00,
                'unite_mesure' => 'unité',
                'stock_disponible' => 50,
                'stock_alerte' => 20,
                'poids' => 10.00,
                'statut' => 'actif',
                'featured' => true,
                'slug' => 'atomiseur-stihl-sr-450',
            ]
        );

        // Association catégories & images
        $produit1->categories()->syncWithoutDetaching([8 => ['principale' => 1]]);
        $produit2->categories()->syncWithoutDetaching([4 => ['principale' => 1]]);
        $produit3->categories()->syncWithoutDetaching([5 => ['principale' => 1]]);

        ProduitImage::updateOrCreate(['id' => 1], ['produit_id' => 1, 'nom_fichier' => 'urea.jpg', 'url_image' => 'storage/produits/urea.jpg', 'alt_text' => 'Image Urée YARA', 'ordre_affichage' => 0, 'principale' => true]);
        ProduitImage::updateOrCreate(['id' => 2], ['produit_id' => 2, 'nom_fichier' => 'boro.jpg', 'url_image' => 'storage/produits/boro.jpg', 'alt_text' => 'Image Betterave', 'ordre_affichage' => 0, 'principale' => true]);
        ProduitImage::updateOrCreate(['id' => 3], ['produit_id' => 4, 'nom_fichier' => 'stihl.jpg', 'url_image' => 'storage/produits/stihl.jpg', 'alt_text' => 'Image STIHL', 'ordre_affichage' => 0, 'principale' => true]);

        // 5. Seed Tags & Articles de Blog
        $tag1 = Tag::updateOrCreate(['slug' => 'conseils-agricoles'], ['nom' => 'Conseils Agricoles', 'couleur' => '#10B981']);
        $tag2 = Tag::updateOrCreate(['slug' => 'fertilisation'], ['nom' => 'Fertilisation', 'couleur' => '#3B82F6']);

        $article = ArticleBlog::updateOrCreate(
            ['slug' => 'guide-complet-utilisation-uree-yara'],
            [
                'titre' => 'Guide complet sur l\'utilisation de l\'Urée YARA',
                'contenu' => 'L\'urée YARA est un engrais hautement concentré en azote (46%). Découvrez comment l\'appliquer efficacement sur vos parcelles pour maximiser vos rendements tout en évitant les pertes par volatilisation.',
                'extrait' => 'Conseils pratiques et dosages recommandés pour l\'application de l\'urée sur vos cultures.',
                'statut' => 'publie',
                'auteur_id' => $admin1->id,
                'image_principale' => 'storage/blog/guide_uree.jpg',
                'meta_title' => 'Guide d\'utilisation de l\'Urée YARA - Agroshop',
                'meta_description' => 'Tout savoir sur le dosage et l\'application de l\'urée YARA.',
                'date_publication' => now(),
                'vues' => 12,
            ]
        );

        $article->tags()->syncWithoutDetaching([$tag1->id, $tag2->id]);
        $article->produits()->syncWithoutDetaching([$produit1->id]);
    }
}
