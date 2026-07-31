<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoutiqueProduit extends Model
{
    use HasFactory;

    protected $table = 'boutique_produit';

    protected $fillable = [
        'boutique_id',
        'produit_id',
        'stock_disponible',
        'stock_alerte',
    ];

    protected $casts = [
        'stock_disponible' => 'integer',
        'stock_alerte' => 'integer',
    ];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class, 'boutique_id');
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}
