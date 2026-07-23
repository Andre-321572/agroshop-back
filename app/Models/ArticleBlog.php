<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleBlog extends Model
{
    use HasFactory;

    protected $table = 'articles_blog';

    protected $fillable = [
        'titre',
        'contenu',
        'extrait',
        'slug',
        'statut',
        'auteur_id',
        'image_principale',
        'meta_title',
        'meta_description',
        'date_publication',
        'vues',
    ];

    protected $casts = [
        'date_publication' => 'datetime',
        'vues' => 'integer',
    ];

    public function auteur()
    {
        return $this->belongsTo(Administrateur::class, 'auteur_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'article_tags', 'article_id', 'tag_id');
    }

    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'article_produits', 'article_id', 'produit_id');
    }
}
