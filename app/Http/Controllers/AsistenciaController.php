<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asistencia;
use App\Models\Trabajador; // Asegúrate de tener importado el modelo del Trabajador
use Carbon\Carbon;

class AsistenciaController extends Controller
{
    public function registrar(Request $request)
    {
        // 1. Validamos que nos envíen el ID o DNI desde el QR
        // Ajusta 'trabajador_id' o 'dni' según lo que envíe tu Angular actualmente
        $request->validate([
            'trabajador_id' => 'required' 
        ]);

        // 2. Buscamos al trabajador en la base de datos
        $trabajador = Trabajador::find($request->trabajador_id);

        if (!$trabajador) {
            return response()->json([
                'status' => 'error',
                'message' => 'Trabajador no encontrado en la base de datos.'
            ], 404);
        }

        $fechaActual = Carbon::now()->toDateString();
        $horaActual = Carbon::now()->toTimeString();

        // 3. Verificamos si ya tiene registro de ENTRADA hoy
        $asistencia = Asistencia::where('trabajador_id', $trabajador->id)
                                ->where('fecha', $fechaActual)
                                ->first();

        $estadoRegistro = 'ENTRADA';

        if ($asistencia) {
            // Si ya tiene entrada y salida, bloqueamos
            if ($asistencia->hora_salida) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El trabajador ya completó su turno de hoy.'
                ], 400);
            }
            
            // Si solo tiene entrada, marcamos la SALIDA
            $asistencia->hora_salida = $horaActual;
            $asistencia->save();
            $estadoRegistro = 'SALIDA';
            
        } else {
            // 4. No tiene registro hoy, creamos la ENTRADA
            // AQUÍ LA MAGIA: Jalamos el área directamente del perfil del trabajador
            Asistencia::create([
                'trabajador_id' => $trabajador->id,
                'fecha' => $fechaActual,
                'hora_entrada' => $horaActual,
                'area_trabajo' => $trabajador->area, // <-- Tomamos su área asignada
                'estado' => 'Asistió'
            ]);
        }

        // 5. Devolvemos la data completa a Angular para la ventana flotante
        return response()->json([
            'status' => 'success',
            'message' => 'Asistencia registrada correctamente.',
            'data' => [
                'nombres' => $trabajador->nombres,
                'apellidos' => $trabajador->apellidos,
                'area' => $trabajador->area, // El área que mostraremos en verde
                'hora' => $horaActual,
                'estado' => $estadoRegistro // Para saber si fue entrada o salida
            ]
        ], 200);
    }
}