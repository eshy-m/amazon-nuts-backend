<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // <-- NO OLVIDES IMPORTAR CARBON AQUÍ

class Asistencia extends Model
{
    use HasFactory;

    protected $table = 'asistencias';

    protected $fillable = [
        'trabajador_id', 
        'turno_id', 
        'fecha', 
        'hora_entrada', 
        'hora_salida', 
        'estado', 
        'observaciones'
    ];

    // REPOTENCIADO: Forzamos el casteo a string para evitar que Laravel
    // intente convertir la hora en un objeto DateTime incompleto.
    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'hora_entrada' => 'string',
        'hora_salida' => 'string',
    ];
    protected $appends = ['horas_extras', 'horas_trabajadas'];
    // 🔥 ESTO ES NUEVO: La lógica matemática de las horas extras
    public function getHorasExtrasAttribute()
    {
        // Si no ha salido, no tiene turno asignado, o no se encontró el turno, retorna 0
        if (!$this->hora_salida || !$this->turno_id || !$this->turno) {
            return 0;
        }

        try {
            $salidaReal = Carbon::parse($this->hora_salida);
            $salidaProgramada = Carbon::parse($this->turno->hora_salida);

            // Calculamos la diferencia en minutos
            $diffMinutos = $salidaProgramada->diffInMinutes($salidaReal, false);

            // OPCIÓN B: Solo cuenta si se quedó 15 minutos o más después de su hora
            if ($diffMinutos >= 15) {
                // Lo convertimos a horas decimales (ej. 90 min = 1.5 horas)
                // Para que tu función del frontend lo traduzca a "1 h y 30 min"
                return round($diffMinutos / 60, 2);
            }
        } catch (\Exception $e) {
            return 0;
        }

        return 0;
    }
    public function getHorasTrabajadasAttribute()
    {
        if (!$this->hora_entrada || !$this->hora_salida || $this->hora_salida === '00:00:00') {
            return 0; // Si no ha marcado salida, retorna 0
        }
        try {
            $entrada = Carbon::parse($this->hora_entrada);
            $salida = Carbon::parse($this->hora_salida);
            $minutos = $entrada->diffInMinutes($salida);
            return round($minutos / 60, 2); // Retorna en decimales (ej: 8.5)
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    public function turno()
    {
        return $this->belongsTo(TurnoPlanificado::class, 'turno_id');
    }
}