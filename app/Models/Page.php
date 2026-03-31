<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    // 1. Protegemos la tabla indicando qué campos se pueden guardar masivamente
    protected $fillable = [
        'name',
        'slug'
    ];

    // 2. Definimos la relación: Una Página tiene muchos Contenidos (Textos/Imágenes)
    public function contents()
    {
        return $this->hasMany(PageContent::class);
    }
}