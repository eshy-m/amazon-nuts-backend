<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trabajador extends Model
{
    use HasFactory;

    protected $table = 'trabajadores';

    protected $fillable = [
        'nombres',
        'apellidos',
        'condicion_laboral', 
        'foto',               
        'dni',
        'fecha_nacimiento', 
        'genero',
        'area',
        'fecha_inicio',   
        'fecha_fin',      
        'celular',       
        'direccion',     
        'experiencia',   
        'observaciones', 
        'qr_code',
        'activo'
    ];

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }
}