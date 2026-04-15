<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TurnoPlanificado;
use Carbon\Carbon;

class TurnoPlanificadoController extends Controller
{
    // 1. LISTAR TODOS LOS TURNOS
    public function index()
    {
        $turnos = TurnoPlanificado::orderBy('fecha', 'desc')->get();
        return response()->json($turnos);
    }

    // 2. CREACIÓN MASIVA (El formulario de Angular manda fecha_inicio y fecha_fin)
    public function store(Request $request)
    {
        $request->validate([
            'area' => 'required|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'tipo_registro' => 'required|string',
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio);
        $fechaFin = Carbon::parse($request->fecha_fin);
        $turnosCreados = 0;

        // Bucle para crear un turno por cada día en el rango
        while ($fechaInicio->lte($fechaFin)) {
            TurnoPlanificado::create([
                'area' => $request->area,
                'fecha' => $fechaInicio->format('Y-m-d'),
                'hora_entrada' => $request->tipo_registro === 'Turno de Trabajo' ? $request->hora_entrada : null,
                'hora_salida' => $request->tipo_registro === 'Turno de Trabajo' ? $request->hora_salida : null,
                'tipo_registro' => $request->tipo_registro,
                'tolerancia_minutos' => $request->tolerancia_minutos ?? 15,
                'es_nocturno' => $request->es_nocturno ?? false,
                'estado' => 'Activo'
            ]);

            $fechaInicio->addDay();
            $turnosCreados++;
        }

        return response()->json([
            'success' => true,
            'message' => "Se han programado $turnosCreados turnos correctamente."
        ]);
    }

    // 3. ACTUALIZAR UN TURNO ESPECÍFICO (Modo Edición)
    public function update(Request $request, $id)
    {
        $turno = TurnoPlanificado::findOrFail($id);

        if ($turno->estado === 'Cerrado') {
            return response()->json(['success' => false, 'message' => 'No se puede editar un turno cerrado.'], 400);
        }

        $turno->update([
            'area' => $request->area,
            'fecha' => $request->fecha_inicio, // En edición puntual, fecha_inicio es la fecha exacta
            'hora_entrada' => $request->tipo_registro === 'Turno de Trabajo' ? $request->hora_entrada : null,
            'hora_salida' => $request->tipo_registro === 'Turno de Trabajo' ? $request->hora_salida : null,
            'tipo_registro' => $request->tipo_registro,
            'tolerancia_minutos' => $request->tolerancia_minutos ?? 15,
            'es_nocturno' => $request->es_nocturno ?? false,
        ]);

        return response()->json(['success' => true, 'message' => 'Turno actualizado con éxito.']);
    }

    // 4. ELIMINAR UN TURNO (Con protección)
    public function destroy($id)
    {
        $turno = TurnoPlanificado::findOrFail($id);

        // Verificamos si tiene asistencias asociadas (protección de datos)
        if ($turno->asistencias()->count() > 0) {
            return response()->json([
                'success' => false, 
                'message' => 'No puedes eliminar este turno porque ya tiene registros de asistencia asociados. Considera cerrarlo.'
            ], 400);
        }

        $turno->delete();
        return response()->json(['success' => true, 'message' => 'Turno eliminado correctamente.']);
    }

    // 5. AUTO-CIERRE DE TURNOS PASADOS (El Piloto Automático)
    public function autoCierre()
    {
        $ahora = Carbon::now();
        $turnosActivos = TurnoPlanificado::where('estado', 'Activo')->get();
        $cerradosContador = 0;

        foreach ($turnosActivos as $turno) {
            if ($turno->tipo_registro === 'Turno de Trabajo') {
                $fechaSalida = Carbon::parse($turno->fecha . ' ' . $turno->hora_salida);
                
                if ($turno->es_nocturno) {
                    $fechaSalida->addDay(); // Si es nocturno, termina al día siguiente
                }

                // Cerramos 2 horas después de la salida oficial
                if ($ahora->greaterThan($fechaSalida->addHours(2))) {
                    $turno->estado = 'Cerrado';
                    $turno->save();
                    $cerradosContador++;
                }
            } else {
                // Si es Día Libre o Vacaciones, se cierra al final de ese mismo día (23:59)
                $finDeDia = Carbon::parse($turno->fecha)->endOfDay();
                if ($ahora->greaterThan($finDeDia)) {
                    $turno->estado = 'Cerrado';
                    $turno->save();
                    $cerradosContador++;
                }
            }
        }

        return response()->json(['success' => true, 'cerrados' => $cerradosContador]);
    }
}