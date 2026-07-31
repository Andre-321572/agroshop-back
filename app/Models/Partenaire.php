<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Partenaire extends Model
{
    protected $fillable = [
        'nom',
        'logo_url',
        'actif'
    ];
    
    protected $casts = [
        'actif' => 'boolean',
    ];
}
