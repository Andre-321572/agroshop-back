<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boutique extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'adresse',
        'ville',
        'telephone',
        'type',
        'statut',
        'description',
    ];

    /**
     * Une boutique peut avoir plusieurs gestionnaires (et un gestionnaire peut gérer plusieurs boutiques)
     */
    public function gestionnaires()
    {
        return $this->belongsToMany(Gestionnaire::class, 'boutique_gestionnaire')
                    ->withTimestamps();
    }

    public function produits()
    {
        return $this->belongsToMany(Produit::class, 'boutique_produit')
            ->withPivot('stock_disponible', 'stock_alerte')
            ->withTimestamps();
    }

    public function rapports()
    {
        return $this->hasMany(Rapport::class);
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }
}
