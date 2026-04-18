<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TurnoPlanificado;
use App\Models\Area;
use Carbon\Carbon;

class TurnoPlanificadoController extends Controller
{
    public function index()
    {
       $turnos = TurnoPlanificado::with('areaMaestra')->orderBy('fecha', 'desc')->get();
       return response()->json($turnos);
    }

   public function store(Request $request)
{
    $request->validate([
        'areas_ids' => 'required|array',
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date',
        'tipo_registro' => 'required|string', // Esto asegura que no sea null
    ]);

    $areas = \App\Models\Area::whereIn('id', $request->areas_ids)->get();
    $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio);
    $fechaFin = \Carbon\Carbon::parse($request->fecha_fin);
    
    foreach ($areas as $area) {
        $fechaActual = $fechaInicio->copy();
        while ($fechaActual->lte($fechaFin)) {
            \App\Models\TurnoPlanificado::create([
                'area_id' => $area->id,
                'fecha' => $fechaActual->toDateString(),
                'hora_entrada' => $request->hora_entrada,
                'hora_salida' => $request->hora_salida,
                'es_nocturno' => $request->es_nocturno ? 1 : 0,
                'tipo_registro' => $request->tipo_registro, // <--- Obligatorio
                'tolerancia_minutos' => $request->tolerancia_minutos ?? 15,
                'estado' => 'Activo',
                'cargos_ids' => $request->cargos_ids
            ]);
            $fechaActual->addDay();
        }
    }

    // RESPUESTA LIMPIA PARA EVITAR EL JSON LARGO EN PANTALLA
    return response()->json([
        'success' => true,
        'message' => 'Planificación creada correctamente'
    ]);
}

    public function update(Request $request, $id)
{
    $turno = \App\Models\TurnoPlanificado::findOrFail($id);

    // Actualizamos asegurando que tipo_registro no sea null
    $turno->update([
        'fecha' => $request->fecha,
        'hora_entrada' => $request->hora_entrada,
        'hora_salida' => $request->hora_salida,
        'es_nocturno' => $request->es_nocturno ? 1 : 0,
        'area_id' => $request->area_id,
        'tipo_registro' => $request->tipo_registro ?? 'Turno de Trabajo', // <--- FIX PARA EL LOG
        'tolerancia_minutos' => $request->tolerancia_minutos
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Turno actualizado correctamente'
    ]);
}

    public function destroy($id)
    {
        $turno = TurnoPlanificado::findOrFail($id);
        if ($turno->asistencias()->count() > 0) {
            return response()->json([
                'success' => false, 
                'message' => 'No puedes eliminar este turno con asistencias asociadas.'
            ], 400);
        }
        $turno->delete();
        return response()->json(['success' => true, 'message' => 'Turno eliminado correctamente.']);
    }

    public function autoCierre()
    {
        $ahora = Carbon::now();
        $turnosActivos = TurnoPlanificado::where('estado', 'Activo')->get();
        $cerradosContador = 0;

        foreach ($turnosActivos as $turno) {
            $fechaSalida = Carbon::parse($turno->fecha . ' ' . $turno->hora_salida);
            if ($turno->es_nocturno) $fechaSalida->addDay();

            if ($ahora->greaterThan($fechaSalida->addHours(2))) {
                $turno->update(['estado' => 'Cerrado']);
                $cerradosContador++;
            }
        }
        return response()->json(['success' => true, 'cerrados' => $cerradosContador]);
    }
}