<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MuestreoCalibracion extends Model
{
    use HasFactory;

    protected $table = 'muestreos_calibraciones';
    protected $guarded = [];

    public function lote() {
        return $this->belongsTo(LoteProduccion::class, 'lote_id');
    }
}