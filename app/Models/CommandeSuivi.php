<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandeSuivi extends Model
{
    use HasFactory;

    protected $table = 'commande_suivis';

    public $timestamps = false;

    protected $fillable = [
        'commande_id',
        'statut_precedent',
        'nouveau_statut',
        'commentaire',
        'utilisateur_id',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function utilisateur()
    {
        return $this->belongsTo(Administrateur::class, 'utilisateur_id');
    }
}
