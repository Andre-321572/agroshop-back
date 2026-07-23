<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'nom',
        'description',
        'parent_id',
        'slug',
        'ordre_affichage',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'ordre_affichage' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(Categorie::class, 'parent_id');
    }

    public function enfants()
    {
        return $this->hasMany(Categorie::class, 'parent_id');
    }

    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'produit_categories', 'categorie_id', 'produit_id')
                    ->withPivot('principale')
                    ->withTimestamps();
    }
}
