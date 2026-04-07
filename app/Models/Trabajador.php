<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trabajador extends Model
{
    use HasFactory;

    // Nombre exacto de la tabla en la base de datos
    protected $table = 'trabajadores';

    // 🔥 Lista de campos permitidos para guardado masivo (incluye los nuevos)
    protected $fillable = [
        'nombres',
        'apellidos',
        'dni',
        'genero',
        'area',
        'celular',
        'direccion',
        'experiencia',
        'observaciones',
        'fecha_inicio',
        'qr_code',
        'activo'
    ];

    // Relación: Un trabajador tiene muchas asistencias
    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }
}