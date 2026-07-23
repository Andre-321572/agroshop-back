<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // Adaptations syntaxiques pour SQLite (développement/tests)
            DB::statement("DROP VIEW IF EXISTS `v_articles_publies`");
            DB::statement("
                CREATE VIEW `v_articles_publies` AS 
                SELECT 
                    a.id AS id, 
                    a.titre AS titre, 
                    a.extrait AS extrait, 
                    a.slug AS slug, 
                    a.image_principale AS image_principale, 
                    a.date_publication AS date_publication, 
                    a.vues AS vues, 
                    (ad.prenom || ' ' || ad.nom) AS auteur, 
                    GROUP_CONCAT(t.nom, ', ') AS tags 
                FROM articles_blog a
                INNER JOIN administrateurs ad ON a.auteur_id = ad.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE a.statut = 'publie' AND a.date_publication <= CURRENT_TIMESTAMP 
                GROUP BY a.id 
                ORDER BY a.date_publication DESC;
            ");

            DB::statement("DROP VIEW IF EXISTS `v_commandes_details`");
            DB::statement("
                CREATE VIEW `v_commandes_details` AS 
                SELECT 
                    c.id AS id, 
                    c.code_reference AS code_reference, 
                    (c.prenom_client || ' ' || c.nom_client) AS nom_complet_client, 
                    c.telephone AS telephone, 
                    c.email AS email, 
                    c.montant_total AS montant_total, 
                    c.type_livraison AS type_livraison, 
                    c.statut_commande AS statut_commande, 
                    c.statut_paiement AS statut_paiement, 
                    c.created_at AS date_commande, 
                    COUNT(ca.id) AS nombre_articles, 
                    SUM(ca.quantite) AS quantite_totale 
                FROM commandes c
                LEFT JOIN commande_articles ca ON c.id = ca.commande_id
                GROUP BY c.id;
            ");

            DB::statement("DROP VIEW IF EXISTS `v_produits_catalogue`");
            DB::statement("
                CREATE VIEW `v_produits_catalogue` AS 
                SELECT 
                    p.id AS id, 
                    p.nom_commercial AS nom_commercial, 
                    p.description AS description, 
                    p.prix_unitaire AS prix_unitaire, 
                    p.unite_mesure AS unite_mesure, 
                    p.stock_disponible AS stock_disponible, 
                    p.statut AS statut, 
                    p.featured AS featured, 
                    p.slug AS slug, 
                    c.nom AS categorie_principale, 
                    c.slug AS categorie_slug, 
                    pi.url_image AS image_principale 
                FROM produits p
                LEFT JOIN produit_categories pc ON p.id = pc.produit_id AND pc.principale = 1
                LEFT JOIN categories c ON pc.categorie_id = c.id
                LEFT JOIN produit_images pi ON p.id = pi.produit_id AND pi.principale = 1
                WHERE p.statut = 'actif';
            ");

            DB::statement("DROP VIEW IF EXISTS `v_produits_rupture`");
            DB::statement("
                CREATE VIEW `v_produits_rupture` AS 
                SELECT 
                    p.id AS id, 
                    p.nom_commercial AS nom_commercial, 
                    p.stock_disponible AS stock_disponible, 
                    p.stock_alerte AS stock_alerte, 
                    c.nom AS categorie_principale, 
                    p.updated_at AS derniere_maj 
                FROM produits p
                LEFT JOIN produit_categories pc ON p.id = pc.produit_id AND pc.principale = 1
                LEFT JOIN categories c ON pc.categorie_id = c.id
                WHERE p.stock_disponible <= p.stock_alerte AND p.statut = 'actif' 
                ORDER BY p.stock_disponible ASC;
            ");

            DB::statement("DROP VIEW IF EXISTS `v_stats_ventes_produits`");
            DB::statement("
                CREATE VIEW `v_stats_ventes_produits` AS 
                SELECT 
                    p.id AS id, 
                    p.nom_commercial AS nom_commercial, 
                    COUNT(ca.id) AS nombre_commandes, 
                    SUM(ca.quantite) AS quantite_vendue, 
                    SUM(ca.montant_ligne) AS chiffre_affaires, 
                    AVG(ca.prix_unitaire) AS prix_moyen, 
                    MAX(c.created_at) AS derniere_vente 
                FROM produits p
                LEFT JOIN commande_articles ca ON p.id = ca.produit_id
                LEFT JOIN commandes c ON ca.commande_id = c.id AND c.statut_commande <> 'annulee'
                GROUP BY p.id;
            ");
        } else {
            // Sintaxe MySQL / MariaDB (production)
            DB::statement("
                CREATE OR REPLACE VIEW `v_articles_publies` AS 
                SELECT 
                    a.id AS id, 
                    a.titre AS titre, 
                    a.extrait AS extrait, 
                    a.slug AS slug, 
                    a.image_principale AS image_principale, 
                    a.date_publication AS date_publication, 
                    a.vues AS vues, 
                    CONCAT(ad.prenom, ' ', ad.nom) AS auteur, 
                    GROUP_CONCAT(t.nom SEPARATOR ', ') AS tags 
                FROM articles_blog a
                INNER JOIN administrateurs ad ON a.auteur_id = ad.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE a.statut = 'publie' AND a.date_publication <= CURRENT_TIMESTAMP() 
                GROUP BY a.id 
                ORDER BY a.date_publication DESC;
            ");

            DB::statement("
                CREATE OR REPLACE VIEW `v_commandes_details` AS 
                SELECT 
                    c.id AS id, 
                    c.code_reference AS code_reference, 
                    CONCAT(c.prenom_client, ' ', c.nom_client) AS nom_complet_client, 
                    c.telephone AS telephone, 
                    c.email AS email, 
                    c.montant_total AS montant_total, 
                    c.type_livraison AS type_livraison, 
                    c.statut_commande AS statut_commande, 
                    c.statut_paiement AS statut_paiement, 
                    c.created_at AS date_commande, 
                    COUNT(ca.id) AS nombre_articles, 
                    SUM(ca.quantite) AS quantite_totale 
                FROM commandes c
                LEFT JOIN commande_articles ca ON c.id = ca.commande_id
                GROUP BY c.id;
            ");

            DB::statement("
                CREATE OR REPLACE VIEW `v_produits_catalogue` AS 
                SELECT 
                    p.id AS id, 
                    p.nom_commercial AS nom_commercial, 
                    p.description AS description, 
                    p.prix_unitaire AS prix_unitaire, 
                    p.unite_mesure AS unite_mesure, 
                    p.stock_disponible AS stock_disponible, 
                    p.statut AS statut, 
                    p.featured AS featured, 
                    p.slug AS slug, 
                    c.nom AS categorie_principale, 
                    c.slug AS categorie_slug, 
                    pi.url_image AS image_principale 
                FROM produits p
                LEFT JOIN produit_categories pc ON p.id = pc.produit_id AND pc.principale = 1
                LEFT JOIN categories c ON pc.categorie_id = c.id
                LEFT JOIN produit_images pi ON p.id = pi.produit_id AND pi.principale = 1
                WHERE p.statut = 'actif';
            ");

            DB::statement("
                CREATE OR REPLACE VIEW `v_produits_rupture` AS 
                SELECT 
                    p.id AS id, 
                    p.nom_commercial AS nom_commercial, 
                    p.stock_disponible AS stock_disponible, 
                    p.stock_alerte AS stock_alerte, 
                    c.nom AS categorie_principale, 
                    p.updated_at AS derniere_maj 
                FROM produits p
                LEFT JOIN produit_categories pc ON p.id = pc.produit_id AND pc.principale = 1
                LEFT JOIN categories c ON pc.categorie_id = c.id
                WHERE p.stock_disponible <= p.stock_alerte AND p.statut = 'actif' 
                ORDER BY p.stock_disponible ASC;
            ");

            DB::statement("
                CREATE OR REPLACE VIEW `v_stats_ventes_produits` AS 
                SELECT 
                    p.id AS id, 
                    p.nom_commercial AS nom_commercial, 
                    COUNT(ca.id) AS nombre_commandes, 
                    SUM(ca.quantite) AS quantite_vendue, 
                    SUM(ca.montant_ligne) AS chiffre_affaires, 
                    AVG(ca.prix_unitaire) AS prix_moyen, 
                    MAX(c.created_at) AS derniere_vente 
                FROM produits p
                LEFT JOIN commande_articles ca ON p.id = ca.produit_id
                LEFT JOIN commandes c ON ca.commande_id = c.id AND c.statut_commande <> 'annulee'
                GROUP BY p.id;
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `v_stats_ventes_produits`");
        DB::statement("DROP VIEW IF EXISTS `v_produits_rupture`");
        DB::statement("DROP VIEW IF EXISTS `v_produits_catalogue`");
        DB::statement("DROP VIEW IF EXISTS `v_commandes_details`");
        DB::statement("DROP VIEW IF EXISTS `v_articles_publies`");
    }
};
