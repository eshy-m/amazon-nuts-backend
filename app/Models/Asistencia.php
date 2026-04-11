<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

    // Campos que Laravel permite llenar masivamente (Mass Assignment)
    protected $fillable = [
        'trabajador_id',
        'turno_id',       // Puede ser null si es un ingreso sin turno programado
        'fecha',
        'hora_ingreso',   // Formato DATETIME
        'hora_salida',    // Formato DATETIME
        'estado',         // Presente, Tardanza, Falta, Permiso...
        'observaciones'
    ];

    /**
     * Relación: Una asistencia pertenece a un Trabajador.
     * Permite hacer: $asistencia->trabajador->nombres
     */
    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    /**
     * Relación: Una asistencia está vinculada a un Turno Planificado.
     * Permite saber a qué hora le tocaba entrar realmente ese día.
     */
    public function turno()
    {
        return $this->belongsTo(TurnoPlanificado::class, 'turno_id');
    }
}