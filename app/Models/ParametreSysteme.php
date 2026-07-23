<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametreSysteme extends Model
{
    use HasFactory;

    protected $table = 'parametres_systeme';

    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'cle_parametre',
        'valeur_parametre',
        'description_parametre',
        'type_parametre',
    ];

    /**
     * Helper dynamique pour récupérer une valeur typée.
     */
    public static function get(string $cle, $default = null)
    {
        $param = static::where('cle_parametre', $cle)->first();
        if (!$param) {
            return $default;
        }

        return match ($param->type_parametre) {
            'integer' => (int) $param->valeur_parametre,
            'boolean' => filter_var($param->valeur_parametre, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($param->valeur_parametre, true),
            default   => $param->valeur_parametre,
        };
    }
}
