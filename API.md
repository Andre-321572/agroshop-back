# Agroshop API Documentation

## Base URL
Toutes les requêtes sont préfixées par :  
`http://localhost:8000/api` (ou votre URL de production)

---

## 1. Routes Publiques (Client / E-commerce)

### 1.1 Page d'accueil
**GET** `/home`  
Récupère les données pour la page d'accueil (produits en vedette, blog, etc.)

---

### 1.2 Catalogue Produits
#### Liste des produits
**GET** `/produits`  
Paramètres de requête (optionnels) :
- `search` : Recherche par nom
- `categorie` : Filtre par slug de catégorie
- `min_prix` / `max_prix` : Filtre par prix
- `per_page` : Nombre de produits par page (défaut: 12)

#### Détails d'un produit
**GET** `/produits/{slug}`  
Récupère un produit et ses images/documents par son slug

---

### 1.3 Catégories
#### Liste des catégories
**GET** `/categories`  
Récupère toutes les catégories (arborescence)

#### Détails d'une catégorie
**GET** `/categories/{slug}`  
Récupère une catégorie et ses produits

---

### 1.4 Commandes
#### Créer une commande
**POST** `/commandes`  
Body de requête :
```json
{
  "prenom_client": "John",
  "nom_client": "Doe",
  "telephone": "+22891234567",
  "email": "john@example.com",
  "mode_livraison": "domicile", // "domicile" ou "retrait"
  "adresse_ligne1": "Adresse 1",
  "adresse_ligne2": "",
  "ville": "Lomé",
  "mode_paiement": "especes", // "especes", "mobile_money", "virement"
  "commentaires": "Notes",
  "articles": [
    { "produit_id": 1, "quantite": 2 },
    { "produit_id": 3, "quantite": 1 }
  ]
}
```

#### Suivre une commande
**GET** `/commandes/suivi/{reference}`  
Récupère le statut d'une commande par son code de référence

---

### 1.5 Blog
#### Liste des articles de blog
**GET** `/blog`  
Paramètres de requête (optionnels) :
- `search` : Recherche dans le titre/contenu
- `tag` : Filtre par slug de tag
- `per_page` : Nombre d'articles par page

#### Détails d'un article
**GET** `/blog/{slug}`  
Récupère un article de blog par son slug

---

### 1.6 FAQ
#### Obtenir la FAQ
**GET** `/faq`  
Récupère la FAQ complète par catégorie

---

### 1.7 Contact
#### Envoyer un message
**POST** `/contact`  
Body de requête :
```json
{
  "nom": "John Doe",
  "email": "john@example.com",
  "telephone": "+22891234567",
  "sujet": "Demande de renseignement",
  "message": "Bonjour, ..."
}
```

---

### 1.8 Paramètres publics
#### Obtenir les paramètres système
**GET** `/parametres`  
Récupère les paramètres visibles par les clients

---

---

## 2. Routes Administration (Protégées par Sanctum)

Toutes ces routes nécessitent un token Bearer :  
`Authorization: Bearer {votre_token}`

### 2.1 Authentification Admin
#### Login
**POST** `/admin/login`  
Body de requête :
```json
{
  "email": "admin@agroshop.tg",
  "password": "MotDePasse"
}
```
Réponse :
```json
{
  "status": "success",
  "data": {
    "token": "...",
    "user": { /* données admin */ }
  }
}
```

#### Logout
**POST** `/admin/logout`  
Nécessite un token valide

#### Profil Admin
**GET** `/admin/me`  
Récupère les infos de l'admin connecté

---

### 2.2 Tableau de bord
#### Obtenir les stats
**GET** `/admin/dashboard`  
Récupère les stats (ventes, commandes, etc.)

---

### 2.3 Produits (Admin)
#### Liste paginée
**GET** `/admin/produits`  
Paramètres de requête (optionnels) :
- `search` : Recherche par nom/référence
- `categorie` : Filtre par catégorie
- `actif` : Filtre par statut

#### Créer un produit
**POST** `/admin/produits`  
Body de requête (multipart/form-data) :
- `nom_commercial`
- `nom_scientifique`
- `description`
- `prix_unitaire`
- `categorie_ids[]`
- `quantite_stock`
- `actif` (booléen)
- `en_vedette` (booléen)
- `images[]` (fichiers)
- `documents[]` (fichiers)

#### Détails / Mettre à jour / Supprimer
**GET** `/admin/produits/{id}`  
**PUT** `/admin/produits/{id}`  
**DELETE** `/admin/produits/{id}`

#### Mettre en vedette / Retirer
**POST** `/admin/produits/{id}/toggle-featured`

#### Supprimer une image
**DELETE** `/admin/produits/{produitId}/images/{imageId}`

---

### 2.4 Catégories (Admin)
#### Liste des catégories parentes
**GET** `/admin/categories/parentes`

#### CRUD Catégories
**GET** `/admin/categories`  
**POST** `/admin/categories`  
**GET** `/admin/categories/{id}`  
**PUT** `/admin/categories/{id}`  
**DELETE** `/admin/categories/{id}`

---

### 2.5 Commandes (Admin)
#### Liste paginée
**GET** `/admin/commandes`  
Paramètres de requête (optionnels) :
- `date_debut` / `date_fin` : Filtre par date
- `search_reference` : Recherche par référence
- `statut` : Filtre par statut

#### Détails d'une commande
**GET** `/admin/commandes/{id}`

#### Télécharger le reçu PDF
**GET** `/admin/commandes/{id}/receipt`  
Renvoie un fichier PDF de reçu en ligne

#### Mettre à jour le statut
**PUT** `/admin/commandes/{id}/statut`  
Body :
```json
{
  "statut_commande": "confirmee", // "en_attente", "confirmee", "preparee", "expediee", "livree", "annulee"
  "commentaires": "Notes"
}
```

#### Mettre à jour le paiement
**PUT** `/admin/commandes/{id}/paiement`  
Body :
```json
{
  "statut_paiement": "paye" // "en_attente", "paye", "echec", "rembourse"
}
```

#### Mettre à jour les notes
**PUT** `/admin/commandes/{id}/notes`  
Body :
```json
{
  "notes_admin": "Notes interne"
}
```

---

### 2.6 Blog (Admin)
#### CRUD Articles
**GET** `/admin/blog`  
**POST** `/admin/blog`  
**GET** `/admin/blog/{id}`  
**PUT** `/admin/blog/{id}`  
**DELETE** `/admin/blog/{id}`

Alias : `/admin/articles` (mêmes endpoints)

---

### 2.7 Tags (Admin)
#### CRUD Tags
**GET** `/admin/tags`  
**POST** `/admin/tags`  
**GET** `/admin/tags/{id}`  
**PUT** `/admin/tags/{id}`  
**DELETE** `/admin/tags/{id}`

---

### 2.8 Administrateurs (Admin)
#### CRUD Administrateurs
**GET** `/admin/administrateurs`  
**POST** `/admin/administrateurs`  
Body :
```json
{
  "nom": "Nom",
  "prenom": "Prénom",
  "email": "email@example.com",
  "mot_de_passe": "password",
  "role": "admin", // "super_admin", "admin", "gestionnaire_stock", "gestionnaire_commandes"
  "actif": true
}
```
**GET** `/admin/administrateurs/{id}`  
**PUT** `/admin/administrateurs/{id}`  
**DELETE** `/admin/administrateurs/{id}`

#### Réinitialiser le mot de passe
**POST** `/admin/administrateurs/{id}/reset-password`  
Génère et renvoie un mot de passe temporaire

---

### 2.9 Paramètres Système (Admin)
#### Obtenir / Mettre à jour
**GET** `/admin/parametres`  
**GET** `/admin/parametres/{cle}`  
**PUT** `/admin/parametres/{cle}`

---
