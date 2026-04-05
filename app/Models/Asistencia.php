<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

    protected $table = 'asistencias';

    protected $fillable = [
        'trabajador_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'horas_trabajadas',
        'observacion'
    ];

    // Una asistencia pertenece a un trabajador
    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class);
    }
}