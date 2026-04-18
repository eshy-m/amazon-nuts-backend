<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LoteProduccion extends Model
{
    protected $table = 'lotes_producciones';
    protected $guarded = [];

    // Relaciones
    public function muestreos() {
        return $this->hasMany(MuestreoCalibracion::class, 'lote_id');
    }

    public function pesajes() {
        return $this->hasMany(PesajeSeleccion::class, 'lote_id');
    }
}