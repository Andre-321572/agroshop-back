<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduitImage extends Model
{
    use HasFactory;

    protected $table = 'produit_images';

    public $timestamps = false;

    protected $fillable = [
        'produit_id',
        'nom_fichier',
        'url_image',
        'alt_text',
        'ordre_affichage',
        'principale',
    ];

    protected $casts = [
        'principale' => 'boolean',
        'ordre_affichage' => 'integer',
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}
