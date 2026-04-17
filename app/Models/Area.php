<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion', 'estado'];

    // Relación: Un área puede tener muchos turnos asignados
    public function turnos()
    {
        return $this->hasMany(TurnoPlanificado::class, 'area_id');
    }
}