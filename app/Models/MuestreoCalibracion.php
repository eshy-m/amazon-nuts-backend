<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MuestreoCalibracion extends Model
{
    use HasFactory;

    protected $table = 'muestreos_calibraciones';
    
    // Usamos fillable para dar seguridad a los nuevos campos solicitados
    protected $fillable = [
        'lote_id',
        'peso_muestra',
        'peso_entera',
        'peso_partida',
        'peso_ojos',
        'peso_podrido',    // NUEVO: Para el análisis de merma
        'peso_reproceso',  // NUEVO: Opcional según el ingeniero
        'observaciones'
    ];

    /**
     * Relación: Un muestreo pertenece a un lote de producción
     */
    public function lote() {
        return $this->belongsTo(LoteProduccion::class, 'lote_id');
    }
}