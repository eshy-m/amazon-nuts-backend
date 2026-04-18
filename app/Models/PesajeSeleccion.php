<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesajeSeleccion extends Model
{
    use HasFactory;

    // Aquí le decimos exactamente qué tabla usar
    protected $table = 'pesajes_selecciones'; 

    // Permitimos que se guarden datos masivamente
    protected $guarded = []; 

    // Relación con el Lote
    public function lote() {
        return $this->belongsTo(LoteProduccion::class, 'lote_id');
    }
}