<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'section_key',
        'content_type',
        'content_value'
    ];

    // Relación inversa: Un Contenido pertenece a una Página
    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}