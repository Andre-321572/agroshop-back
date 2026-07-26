<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisiteLog extends Model
{
    use HasFactory;

    protected $table = 'visites_logs';

    protected $fillable = [
        'ip_adresse',
        'user_agent',
        'page_visitee',
        'type_action',
        'details',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
