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
        'activo',
        'fecha_vencimiento_carnet',
        'contacto_emergencia',
        'numero_emergencia',
        'tipo_pago',
        'cuenta_pago',
        'cargo_id',
        'turno_id',
        'area_id'
    ];
    public function cargoMaestro()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }
    public function areaMaestra() 
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
    public function asistencias()
    {
        return $this->hasMany(Asistencia::class);
    }
}