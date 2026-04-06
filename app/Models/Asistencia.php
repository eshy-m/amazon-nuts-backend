<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'trabajador_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'area_trabajo',
        'estado',
        'observaciones'
    ];

    // Relación: Una asistencia pertenece a Un trabajador
    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }
}