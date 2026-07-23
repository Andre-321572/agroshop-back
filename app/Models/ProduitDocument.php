<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduitDocument extends Model
{
    use HasFactory;

    protected $table = 'produit_documents';

    public $timestamps = false;

    protected $fillable = [
        'produit_id',
        'nom_document',
        'type_document',
        'url_document',
        'taille_fichier',
    ];

    protected $casts = [
        'taille_fichier' => 'integer',
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}
