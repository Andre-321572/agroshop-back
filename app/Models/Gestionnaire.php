<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Gestionnaire extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'password',
        'statut',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Un gestionnaire peut gérer plusieurs boutiques (quincaillerie ET agricole).
     */
    public function boutiques()
    {
        return $this->belongsToMany(Boutique::class, 'boutique_gestionnaire')
                    ->withTimestamps();
    }

    /**
     * Raccourci : retourne la première boutique (pour compatibilité)
     */
    public function getBoutiqueAttribute()
    {
        return $this->boutiques->first();
    }

    /**
     * IDs des boutiques gérées
     */
    public function getBoutiqueIdsAttribute(): array
    {
        return $this->boutiques->pluck('id')->toArray();
    }

    /**
     * ID de la première boutique gérée (ou 1 par défaut)
     */
    public function getBoutiqueIdAttribute()
    {
        if (isset($this->attributes['boutique_id']) && $this->attributes['boutique_id']) {
            return $this->attributes['boutique_id'];
        }
        $b = $this->boutiques->first();
        return $b ? $b->id : 1;
    }
}
