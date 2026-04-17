<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TurnoPlanificado extends Model
{
    use HasFactory;

    protected $table = 'turnos_planificados';

    protected $fillable = [
        'area',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'tolerancia_minutos',
        'estado',
        'es_nocturno',
        'tipo_registro',
        'area_id'
    ];

    // Relación: Un turno tiene muchas asistencias
    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'turno_id');
    }

    
    public function areaMaestra()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}