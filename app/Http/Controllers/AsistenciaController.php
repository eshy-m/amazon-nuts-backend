<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asistencia;
use App\Models\Trabajador;
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
        
        // Traemos las asistencias de hoy, incluyendo los datos del trabajador asociado
        // Ordenamos por 'updated_at' para que el último en marcar salga arriba en la tabla
        $asistencias = Asistencia::with('trabajador')
            ->where('fecha', $fechaActual)
            ->orderBy('updated_at', 'desc') 
            ->get();

        return response()->json([
            'status' => 'success', 
            'data' => $asistencias
        ], 200);
    }

    // ==========================================
    // 📷 2. REGISTRAR ENTRADA O SALIDA
    // ==========================================
    public function registrar(Request $request)
    {
        // 1. Validar que el FrontEnd envíe el dato
        $validator = Validator::make($request->all(), [
            'trabajador_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error', 
                'message' => 'El código del trabajador es obligatorio.'
            ], 400);
        }

        // 2. Buscar al trabajador por su DNI (que en el front viene como trabajador_id)
        $trabajador = Trabajador::where('dni', $request->trabajador_id)->first();

        if (!$trabajador) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Trabajador no encontrado en la base de datos.'
            ], 404);
        }

        // 3. Preparar las fechas y horas usando Carbon
        $fechaActual = Carbon::now()->toDateString();
        $horaActualCarbon = Carbon::now();
        $horaActualStr = $horaActualCarbon->toTimeString();

        // 4. Verificamos si este trabajador ya tiene un registro creado HOY
        $asistencia = Asistencia::where('trabajador_id', $trabajador->id)
            ->where('fecha', $fechaActual)
            ->first();

        $estadoRegistro = ''; // Variable para decirle al front si fue Entrada o Salida

        // ------------------------------------------
        // 🚪 CASO A: YA TIENE ENTRADA -> MARCAR SALIDA
        // ------------------------------------------
        if ($asistencia) {
            
            // Si ya tiene hora de salida, significa que ya terminó su turno completo
            if ($asistencia->hora_salida) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'El trabajador ya completó su turno de hoy.'
                ], 400);
            }
            
            // Calculamos las horas trabajadas (Diferencia entre la entrada y este momento)
            $horaEntradaCarbon = Carbon::parse($asistencia->hora_entrada);
            $horasTrabajadas = $horaActualCarbon->diffInMinutes($horaEntradaCarbon) / 60;

            // Actualizamos el registro existente
            $asistencia->hora_salida = $horaActualStr;
            $asistencia->horas_trabajadas = round($horasTrabajadas, 2);
            $asistencia->save();
            
            $estadoRegistro = 'SALIDA';

        } 
        // ------------------------------------------
        // 🚶‍♂️ CASO B: NO TIENE REGISTRO -> MARCAR ENTRADA
        // ------------------------------------------
        else {
            
            // Regla de puntualidad: Si es antes de las 08:00:59 AM es Puntual, sino es Tarde
            $horaLimite = Carbon::createFromTime(8, 0, 59);
            $estadoPuntualidad = $horaActualCarbon->lte($horaLimite) ? 'Puntual' : 'Tarde';

            // Creamos un nuevo registro
            Asistencia::create([
                'trabajador_id' => $trabajador->id,
                'fecha' => $fechaActual,
                'hora_entrada' => $horaActualStr,
                'area_trabajo' => $trabajador->area, // Heredamos el área de su perfil
                'estado' => $estadoPuntualidad
            ]);
            
            $estadoRegistro = 'ENTRADA';
        }

        // 5. Devolvemos la respuesta exitosa al FrontEnd con la data para la ventana flotante
        return response()->json([
            'status' => 'success',
            'message' => 'Asistencia registrada correctamente.',
            'data' => [
                'nombres' => $trabajador->nombres,
                'apellidos' => $trabajador->apellidos,
                'area' => $trabajador->area,
                'hora' => $horaActualStr,
                'estado' => $estadoRegistro
            ]
        ], 200);
    }

    // ==========================================
    // 📊 3. OBTENER REPORTES HISTÓRICOS (Con Filtros)
    // ==========================================
    public function reportes(Request $request)
    {
        // Iniciamos la consulta vinculando al trabajador
        $query = Asistencia::with('trabajador');

        // 🔍 Filtro 1: Rango de Fechas
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        // 🔍 Filtro 2: Búsqueda por Nombre o DNI
        if ($request->has('busqueda') && $request->busqueda != '') {
            $busqueda = $request->busqueda;
            // Buscamos dentro de la relación 'trabajador'
            $query->whereHas('trabajador', function($q) use ($busqueda) {
                $q->where('dni', 'LIKE', "%{$busqueda}%")
                  ->orWhere('nombres', 'LIKE', "%{$busqueda}%")
                  ->orWhere('apellidos', 'LIKE', "%{$busqueda}%");
            });
        }

        // Ordenamos por fecha (más reciente primero)
        $reportes = $query->orderBy('fecha', 'desc')->orderBy('hora_entrada', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $reportes
        ], 200);
    }
}