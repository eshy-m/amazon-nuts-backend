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
    // ==========================================
    // 📋 1. OBTENER ASISTENCIAS DE HOY
    // ==========================================
    public function hoy()
    {
        $fechaActual = Carbon::now()->toDateString();
        
        // Traemos asistencias de hoy con datos del trabajador Y DE SU TURNO
        $asistencias = Asistencia::with(['trabajador', 'turno'])
            ->where('fecha', $fechaActual)
            ->orderBy('updated_at', 'desc') 
            ->get();

        return response()->json([
            'status' => 'success', 
            'data' => $asistencias
        ], 200);
    }

    // ==========================================
    // ✍️ 2. REGISTRO MANUAL (Acepta DNI o ID)
    // ==========================================
    public function registrar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trabajador_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'El código del trabajador es obligatorio.'], 400);
        }

        $input = $request->trabajador_id;

        // Buscamos por DNI primero, y si no, por ID del sistema.
        $trabajador = Trabajador::where('dni', $input)
                                ->orWhere('id', $input)
                                ->first();
        
        if (!$trabajador) {
            return response()->json(['status' => 'error', 'message' => 'Trabajador no encontrado en la base de datos.'], 404);
        }

        if (!$trabajador->activo) {
            return response()->json(['status' => 'error', 'message' => 'Trabajador inactivo o cesado.'], 403);
        }

        // Llamamos al "Cerebro" para procesar
        $respuesta = $this->procesarAsistencia($trabajador);
        
        return response()->json($respuesta, 200);
    }

    // ==========================================
    // 📷 3. REGISTRO POR CÓDIGO QR 
    // ==========================================
    public function registrarQR(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dni' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'El DNI es obligatorio.'], 400);
        }

        $trabajador = Trabajador::where('dni', $request->dni)->first();
        
        if (!$trabajador) {
            return response()->json(['status' => 'error', 'message' => 'DNI no registrado en el sistema.'], 404);
        }

        if (!$trabajador->activo) {
            return response()->json(['status' => 'error', 'message' => 'Trabajador inactivo o cesado.'], 403);
        }

        // Llamamos al "Cerebro" para procesar
        $respuesta = $this->procesarAsistencia($trabajador);
        return response()->json($respuesta, 200);
    }

    // ==========================================
    // 🧠 EL "CEREBRO": Lógica central de Entrada/Salida
    // ==========================================
    private function procesarAsistencia($trabajador)
    {
        $ahora = Carbon::now();
        $fechaHoy = $ahora->toDateString();

        // PASO 1: VERIFICAR SI HAY UN INGRESO SIN CERRAR (En las últimas 24 hrs)
        // PASO 1: VERIFICAR SI HAY UN INGRESO SIN CERRAR (En las últimas 24 hrs)
        $asistenciaAbierta = Asistencia::where('trabajador_id', $trabajador->id)
            ->whereNull('hora_salida')
            ->where('created_at', '>=', $ahora->copy()->subHours(24))
            ->first();

        if ($asistenciaAbierta) {
            // ES UNA SALIDA
            $asistenciaAbierta->hora_salida = $ahora;
            
            // 🔥 NUEVO: Cálculo exacto de horas trabajadas (con 2 decimales, ej: 8.50)
            $horaEntrada = Carbon::parse($asistenciaAbierta->hora_ingreso);
            $horasTrabajadas = round($horaEntrada->floatDiffInHours($ahora), 2);
            
            $asistenciaAbierta->horas_trabajadas = $horasTrabajadas;
            $asistenciaAbierta->save();

            return [
                'status' => 'success',
                'message' => 'Salida registrada correctamente. Total: ' . $horasTrabajadas . ' horas.',
                'data' => [
                    'tipo' => 'salida',
                    'nombres' => $trabajador->nombres . ' ' . $trabajador->apellidos,
                    'hora' => $ahora->format('H:i A'),
                    'estado' => 'Salida'
                ]
            ];
        }
        // PASO 2: ES UN INGRESO. BUSCAMOS EL TURNO PLANIFICADO PARA HOY Y SU ÁREA
        $turno = TurnoPlanificado::where('fecha', $fechaHoy)
            ->where('area', $trabajador->area)
            ->where('estado', 'Activo')
            ->first();

        $estadoRegistro = 'Puntual'; // Por defecto lo asumimos Puntual
        $turnoId = null;

        if ($turno) {
            // ESCENARIO A: TIENE TURNO HOY
            $turnoId = $turno->id;
            
            $horaIngresoOficial = Carbon::parse($fechaHoy . ' ' . $turno->hora_entrada);
            $horaLimite = $horaIngresoOficial->copy()->addMinutes($turno->tolerancia_minutos);

            if ($ahora->greaterThan($horaLimite)) {
                $estadoRegistro = 'Tardanza';
            }

        } else {
            // ESCENARIO B: NO TIENE TURNO HOY. Buscamos el último turno que tuvo su área.
            $turnoHistorico = TurnoPlanificado::where('area', $trabajador->area)
                ->where('estado', 'Activo')
                ->orderBy('fecha', 'desc') // Traemos el más reciente
                ->first();

            if ($turnoHistorico) {
                // Usamos la hora esperada y tolerancia de su último turno, pero aplicados a la fecha de HOY
                $horaIngresoReferencia = Carbon::parse($fechaHoy . ' ' . $turnoHistorico->hora_entrada);
                $horaLimite = $horaIngresoReferencia->copy()->addMinutes($turnoHistorico->tolerancia_minutos);

                if ($ahora->greaterThan($horaLimite)) {
                    $estadoRegistro = 'Tardanza';
                } else {
                    $estadoRegistro = 'Puntual';
                }
            } else {
                // ESCENARIO C: NUNCA se ha creado un turno para esta área.
                $estadoRegistro = 'Presente (Sin turno)';
            }
        }

        // Creamos el registro de entrada
        Asistencia::create([
            'trabajador_id' => $trabajador->id,
            'turno_id' => $turnoId, // Puede ser null si entró por Escenario B o C
            'fecha' => $fechaHoy,
            'hora_ingreso' => $ahora,
            'estado' => $estadoRegistro,
        ]);

        return [
            'status' => 'success',
            'message' => 'Ingreso registrado correctamente.',
            'data' => [
                'tipo' => 'ingreso',
                'nombres' => $trabajador->nombres . ' ' . $trabajador->apellidos,
                'hora' => $ahora->format('H:i A'),
                'estado' => $estadoRegistro
            ]
        ];
    }

    // ==========================================
    // 📊 4. OBTENER REPORTES HISTÓRICOS (Con Filtros)
    // ==========================================
    public function reportes(Request $request)
    {
        // Traemos datos del trabajador y de su turno
        $query = Asistencia::with(['trabajador', 'turno']);

        // Filtro: Rango de Fechas
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        // Nota: La búsqueda por texto (DNI/Nombres) ahora la hace Angular en vivo, 
        // pero dejamos esto como respaldo por si luego lo necesitas desde el servidor.
        if ($request->has('busqueda') && $request->busqueda != '') {
            $busqueda = $request->busqueda;
            $query->whereHas('trabajador', function($q) use ($busqueda) {
                $q->where('dni', 'LIKE', "%{$busqueda}%")
                  ->orWhere('nombres', 'LIKE', "%{$busqueda}%")
                  ->orWhere('apellidos', 'LIKE', "%{$busqueda}%");
            });
        }

        // Ordenamos por fecha y hora de ingreso
        $reportes = $query->orderBy('fecha', 'desc')->orderBy('hora_ingreso', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $reportes
        ], 200);
    }
}