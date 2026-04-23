<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcesoSecado extends Model
{
    use HasFactory;

    // Conectamos el modelo con la tabla exacta que creamos en SQL
    protected $table = 'procesos_secados';

    // Campos que permitimos llenar masivamente
    protected $fillable = [
        'lote_id',
        'usuario_id',
        'categoria',
        'temperatura_celsius',
        'peso_entrada_kg',
        'peso_salida_kg',
        'hora_inicio',
        'hora_fin',
        'estado'
    ];

    // Relación: Un proceso de secado pertenece a un Lote
    public function lote()
    {
        return $this->belongsTo(LoteProduccion::class, 'lote_id');
    }

    // Relación: Un proceso de secado es registrado por un Usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}