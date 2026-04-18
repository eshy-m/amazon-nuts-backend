<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asistencia;
use App\Models\Trabajador;
use App\Models\TurnoPlanificado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class AsistenciaController extends Controller
{
   public function registrar(Request $request)
    {
        // 1. Validación básica
        $validator = Validator::make($request->all(), [
            'trabajador_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'ID de trabajador requerido'], 400);
        }

        // 2. Identificar al trabajador (por ID o DNI)
        $trabajador = Trabajador::with(['areaMaestra'])
            ->where('id', $request->trabajador_id)
            ->orWhere('dni', $request->trabajador_id)
            ->first();

        if (!$trabajador) {
            return response()->json(['status' => 'error', 'message' => 'Trabajador no encontrado'], 404);
        }

        $ahora = Carbon::now();
        $fechaActual = $ahora->toDateString();
        $horaActual = $ahora->format('H:i:s'); // Formato exacto HH:MM:SS para MySQL TIME

        // 3. Buscar turno activo para el área
        $turno = TurnoPlanificado::where('area_id', $trabajador->area_id)
            ->where('fecha', $fechaActual)
            ->where('estado', 'Activo')
            ->first();

        if (!$turno) {
            return response()->json([
                'status' => 'error', 
                'message' => 'No hay turno planificado hoy para el área: ' . ($trabajador->areaMaestra->nombre ?? 'N/A')
            ], 403);
        }

        // 4. Buscar registro existente hoy
        $asistencia = Asistencia::where('trabajador_id', $trabajador->id)
            ->where('fecha', $fechaActual)
            ->first();

        // --- LÓGICA DE ENTRADA ---
        if (!$asistencia) {
            $horaConfigurada = Carbon::parse($turno->hora_entrada);
            $tolerancia = $turno->tolerancia_minutos ?? 0;
            
            // Si la hora actual supera la entrada + tolerancia -> Tardanza
            $estado = $ahora->gt($horaConfigurada->addMinutes($tolerancia)) ? 'Tardanza' : 'Presente';

            // Usamos create para asegurar que se apliquen los $fillable y $casts
            $nuevaAsistencia = Asistencia::create([
                'trabajador_id' => $trabajador->id,
                'turno_id'      => $turno->id,
                'fecha'         => $fechaActual,
                'hora_entrada'  => $horaActual,
                'estado'        => $estado
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'ENTRADA MARCADA: ' . $estado,
                'data'    => $nuevaAsistencia->load('trabajador.areaMaestra')
            ]);
        }

        // --- LÓGICA DE SALIDA ---
        if (!$asistencia->hora_salida || $asistencia->hora_salida == '00:00:00') {
            $asistencia->hora_salida = $horaActual;
            $asistencia->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'SALIDA MARCADA EXITOSAMENTE',
                'data'    => $asistencia->load('trabajador.areaMaestra')
            ]);
        }

        return response()->json([
            'status'  => 'warning', 
            'message' => 'Ya registraste entrada y salida el día de hoy.'
        ]);
    }
    public function hoy()
    {
        $fechaActual = Carbon::now()->toDateString();
        $asistencias = Asistencia::with(['trabajador.areaMaestra', 'turno'])
            ->where('fecha', $fechaActual)
            ->orderBy('updated_at', 'desc')
            ->get();
        return response()->json(['status' => 'success', 'data' => $asistencias]);
    }
    // ==========================================
    // 📊 5. OBTENER REPORTES HISTÓRICOS (FILTRO POR FECHAS)
    // ==========================================
    public function reportes(Request $request)
    {
        // 1. Recibimos las fechas desde Angular
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin = $request->query('fecha_fin');

        // 2. Preparamos la consulta incluyendo las relaciones para que se vea el Área
        $query = Asistencia::with(['trabajador.areaMaestra', 'turno']);

        // 3. Aplicamos el filtro de fechas si Angular las envió
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        }

        // 4. Obtenemos los datos ordenados (los más recientes primero)
        $reportes = $query->orderBy('fecha', 'desc')
                          ->orderBy('hora_entrada', 'asc')
                          ->get();

        // 5. Devolvemos la respuesta al Frontend
        return response()->json([
            'status' => 'success',
            'data' => $reportes
        ]);
    }
}