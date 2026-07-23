<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Administrateur extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'administrateurs';

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'mot_de_passe',
        'role',
        'actif',
        'derniere_connexion',
    ];

    protected $hidden = [
        'mot_de_passe',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'derniere_connexion' => 'datetime',
    ];

    public function articlesBlog()
    {
        return $this->hasMany(ArticleBlog::class, 'auteur_id');
    }
}
