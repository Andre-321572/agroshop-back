<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $table = 'tags';

    public $timestamps = false;

    protected $fillable = [
        'nom',
        'slug',
        'couleur',
    ];

    public function articles()
    {
        return $this->belongsToMany(ArticleBlog::class, 'article_tags', 'tag_id', 'article_id');
    }
}
