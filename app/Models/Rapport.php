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
        'type_rapport',
        'titre',
        'description',
        'fichier_pdf',
        'date_rapport',
        'statut_lecture',
    ];

    protected $casts = [
        'date_rapport' => 'date',
        'statut_lecture' => 'boolean',
    ];

    protected $appends = [
        'lu_par_admin',
    ];

    public function getLuParAdminAttribute()
    {
        return (bool) $this->statut_lecture;
    }

    public function getTypeRapportAttribute()
    {
        return $this->attributes['type_rapport'] ?? $this->attributes['type'] ?? 'journalier';
    }

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function gestionnaire()
    {
        return $this->belongsTo(Gestionnaire::class);
    }
}
