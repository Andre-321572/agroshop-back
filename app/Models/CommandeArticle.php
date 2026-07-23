<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandeArticle extends Model
{
    use HasFactory;

    protected $table = 'commande_articles';

    public $timestamps = false;

    protected $fillable = [
        'commande_id',
        'produit_id',
        'nom_produit',
        'prix_unitaire',
        'quantite',
        'montant_ligne',
    ];

    protected $casts = [
        'prix_unitaire' => 'float',
        'quantite' => 'integer',
        'montant_ligne' => 'float',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}
