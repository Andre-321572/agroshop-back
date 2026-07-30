<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Produit extends Model
{
    use HasFactory;

    protected $table = 'produits';

    protected $fillable = [
        'nom_commercial',
        'description',
        'composition',
        'principes_actifs',
        'mode_emploi',
        'dosage_recommande',
        'precautions_usage',
        'contre_indications',
        'prix_unitaire',
        'unite_mesure',
        'stock_disponible',
        'stock_alerte',
        'poids',
        'dimensions',
        'statut',
        'featured',
        'meta_title',
        'meta_description',
        'slug',
    ];

    protected $casts = [
        'prix_unitaire' => 'float',
        'poids' => 'float',
        'stock_disponible' => 'integer',
        'stock_alerte' => 'integer',
        'featured' => 'boolean',
    ];

    // Relations
    public function categories()
    {
        return $this->belongsToMany(Categorie::class, 'produit_categories', 'produit_id', 'categorie_id')
                    ->withPivot('principale');
    }

    public function categorie()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function images()
    {
        return $this->hasMany(ProduitImage::class, 'produit_id')->orderBy('ordre_affichage');
    }

    public function imagePrincipale()
    {
        return $this->hasOne(ProduitImage::class, 'produit_id')->where('principale', true);
    }

    public function documents()
    {
        return $this->hasMany(ProduitDocument::class, 'produit_id');
    }

    public function articlesBlog()
    {
        return $this->belongsToMany(ArticleBlog::class, 'article_produits', 'produit_id', 'article_id');
    }

    // Scopes (Equivalents des Vues SQL)
    public function scopeActif(Builder $query): Builder
    {
        return $query->where('statut', 'actif');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    public function scopeEnRupture(Builder $query): Builder
    {
        return $query->where('statut', 'actif')
                    ->whereColumn('stock_disponible', '<=', 'stock_alerte');
    }
}
