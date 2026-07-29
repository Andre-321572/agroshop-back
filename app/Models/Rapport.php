<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rapport extends Model
{
    use HasFactory;

    protected $fillable = [
        'boutique_id',
        'gestionnaire_id',
        'type',
        'fichier_pdf',
        'date_rapport',
        'statut_lecture',
    ];

    protected $casts = [
        'date_rapport' => 'date',
        'statut_lecture' => 'boolean',
    ];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function gestionnaire()
    {
        return $this->belongsTo(Gestionnaire::class);
    }
}
