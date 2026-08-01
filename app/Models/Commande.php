<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    use HasFactory;

    protected $table = 'commandes';

    protected $fillable = [
        'code_reference',
        'nom_client',
        'prenom_client',
        'telephone',
        'email',
        'adresse_ligne1',
        'adresse_ligne2',
        'ville',
        'code_postal',
        'pays',
        'montant_ht',
        'montant_tva',
        'montant_ttc',
        'frais_livraison',
        'montant_total',
        'type_livraison',
        'adresse_livraison',
        'date_livraison_souhaitee',
        'instructions_livraison',
        'statut_commande',
        'statut_paiement',
        'notes_admin',
        'commentaire',
        'ip_client',
        'user_agent',
        'boutique_id',
    ];

    protected $casts = [
        'montant_ht' => 'float',
        'montant_tva' => 'float',
        'montant_ttc' => 'float',
        'frais_livraison' => 'float',
        'montant_total' => 'float',
        'date_livraison_souhaitee' => 'date',
    ];

    public function articles()
    {
        return $this->hasMany(CommandeArticle::class, 'commande_id');
    }

    public function suivis()
    {
        return $this->hasMany(CommandeSuivi::class, 'commande_id')->orderBy('created_at', 'desc');
    }

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }
}
