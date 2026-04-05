<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trabajador extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'trabajadores';

    // 🔥 Agregamos todos los campos nuevos a la lista de permitidos
    protected $fillable = [
        'nombres',
        'apellidos',
        'dni',
        'genero', // Lo mantuve por si lo sigues usando, si no, puedes borrarlo
        'area',
        'celular',
        'direccion',
        'experiencia',
        'observaciones',
        'fecha_inicio',
        'qr_code',
        'activo'
    ];
}