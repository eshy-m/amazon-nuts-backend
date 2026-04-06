<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asistencia;
use App\Models\Trabajador;
use Illuminate\Support\Carbon;

class AsistenciaController extends Controller
{
    // Función principal para escanear QR o ingresar DNI manual
    public function registrar(Request $request)
    {
        $request->validate([
            'dni' => 'required|string|size:8',
            'area_trabajo' => 'required|string'
        ]);

        // 1. Buscar al trabajador
        $trabajador = Trabajador::where('dni', $request->dni)->first();

        if (!$trabajador) {
            return response()->json(['message' => 'Trabajador no encontrado'], 404);
        }

        $hoy = Carbon::today()->toDateString();
        $horaActual = Carbon::now()->toTimeString();

        // 2. Buscar si ya tiene una asistencia hoy
        $asistenciaHoy = Asistencia::where('trabajador_id', $trabajador->id)
                                   ->where('fecha', $hoy)
                                   ->first();

        // 3. Lógica de Entrada o Salida
        if (!$asistenciaHoy) {
            // NO TIENE REGISTRO HOY -> MARCAR ENTRADA
            $nuevaAsistencia = Asistencia::create([
                'trabajador_id' => $trabajador->id,
                'fecha' => $hoy,
                'hora_entrada' => $horaActual,
                'area_trabajo' => $request->area_trabajo,
                'estado' => 'Asistió'
            ]);

            return response()->json([
                'status' => 'entrada',
                'message' => 'Entrada registrada correctamente',
                'trabajador' => $trabajador->nombres . ' ' . $trabajador->apellidos,
                'hora' => $horaActual
            ], 200);

        } else {
            // YA TIENE REGISTRO HOY -> EVALUAR SALIDA
            if ($asistenciaHoy->hora_salida == null) {
                // MARCAR SALIDA
                $asistenciaHoy->update([
                    'hora_salida' => $horaActual
                ]);

                return response()->json([
                    'status' => 'salida',
                    'message' => 'Salida registrada correctamente',
                    'trabajador' => $trabajador->nombres . ' ' . $trabajador->apellidos,
                    'hora' => $horaActual
                ], 200);
            } else {
                // YA MARCÓ ENTRADA Y SALIDA
                return response()->json([
                    'status' => 'completado',
                    'message' => 'El trabajador ya completó su turno de hoy.',
                    'trabajador' => $trabajador->nombres . ' ' . $trabajador->apellidos
                ], 400);
            }
        }
    }
}