<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TurnoPlanificado;
use Carbon\Carbon;

class TurnoPlanificadoController extends Controller
{
    /**
     * Listar todos los turnos.
     * Se puede llamar desde el frontend para llenar el calendario o la tabla.
     */
    public function index()
    {
        // Traemos los turnos ordenados por fecha de forma descendente
        $turnos = TurnoPlanificado::orderBy('fecha', 'desc')->get();
        return response()->json($turnos);
    }

    /**
     * PROGRAMACIÓN MASIVA: Crea registros día por día en un rango de fechas.
     */
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
        $turnosCreados = [];

        while ($fechaInicio->lte($fechaFin)) {
            $turno = TurnoPlanificado::create([
                'area' => $request->area,
                'fecha' => $fechaInicio->format('Y-m-d'),
                'hora_entrada' => $request->tipo_registro === 'Turno de Trabajo' ? $request->hora_entrada : null,
                'hora_salida' => $request->tipo_registro === 'Turno de Trabajo' ? $request->hora_salida : null,
                'tolerancia_minutos' => $request->tolerancia_minutos ?? 15,
                'es_nocturno' => $request->es_nocturno ?? false,
                'tipo_registro' => $request->tipo_registro,
                'estado' => 'Activo'
            ]);

            $turnosCreados[] = $turno;
            $fechaInicio->addDay();
        }

        return response()->json([
            'success' => true,
            'message' => count($turnosCreados) . ' turno(s) programado(s) correctamente.',
            'data' => $turnosCreados
        ]);
    }

    /**
     * ACTUALIZAR UN TURNO ESPECÍFICO: 
     * Permite al ingeniero corregir errores en un día puntual.
     */
    public function update(Request $request, $id)
    {
        $turno = TurnoPlanificado::findOrFail($id);

        // Si el turno ya está cerrado, no permitimos editarlo para mantener integridad
        if ($turno->estado === 'Cerrado') {
            return response()->json(['success' => false, 'message' => 'No se puede editar un turno que ya está cerrado.'], 400);
        }

        $turno->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Turno actualizado correctamente',
            'data' => $turno
        ]);
    }

    /**
     * ELIMINAR UN TURNO:
     * El sistema verifica si tiene asistencias antes de borrar.
     */
    public function destroy($id)
    {
        $turno = TurnoPlanificado::withCount('asistencias')->findOrFail($id);

        // Seguridad: Si ya hay trabajadores que marcaron en este turno, no dejamos borrarlo.
        if ($turno->asistencias_count > 0) {
            return response()->json([
                'success' => false, 
                'message' => 'No puedes eliminar este turno porque ya tiene registros de asistencia vinculados.'
            ], 400);
        }

        $turno->delete();
        return response()->json(['success' => true, 'message' => 'Turno eliminado correctamente']);
    }

    /**
     * CIERRE AUTOMÁTICO (El "Robot"):
     * Esta función busca turnos cuya fecha y hora de salida ya pasaron y los cierra.
     */
    public function autoCerrarTurnos()
    {
        $ahora = Carbon::now();
        
        // Buscamos turnos activos
        $turnosActivos = TurnoPlanificado::where('estado', 'Activo')->get();
        $cerradosContador = 0;

        foreach ($turnosActivos as $turno) {
            // Creamos un objeto con la fecha del turno y su hora de salida
            // Si es nocturno, la salida es al día siguiente
            $fechaSalida = Carbon::parse($turno->fecha . ' ' . $turno->hora_salida);
            if ($turno->es_nocturno) {
                $fechaSalida->addDay();
            }

            // Agregamos un margen de 2 horas después de la salida para permitir marcas tardías
            if ($ahora->greaterThan($fechaSalida->addHours(2))) {
                $turno->estado = 'Cerrado';
                $turno->save();
                $cerradosContador++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Se han cerrado $cerradosContador turnos automáticamente."
        ]);
    }

    /**
     * CIERRE MANUAL: Por si el ingeniero quiere forzar el cierre de un turno.
     */
    public function cerrarTurno($id)
    {
        $turno = TurnoPlanificado::find($id);
        if($turno) {
            $turno->estado = 'Cerrado';
            $turno->save();
            return response()->json(['success' => true, 'message' => 'Turno cerrado manualmente']);
        }
        return response()->json(['success' => false, 'message' => 'Turno no encontrado'], 404);
    }
}