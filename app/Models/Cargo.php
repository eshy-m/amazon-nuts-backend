<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion', 'estado'];

    // Relación: Un cargo puede tener muchos trabajadores
    public function trabajadores()
    {
        return $this->hasMany(Trabajador::class, 'cargo_id');
    }
}