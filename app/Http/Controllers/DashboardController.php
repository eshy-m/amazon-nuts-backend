<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trabajador;
use App\Models\Asistencia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function obtenerMetricasDiarias(Request $request)
    {
        $hoy = Carbon::today()->toDateString();

        // 1. Total de personal (Puedes agregar ->where('estado', 'Activo') si tienes esa columna)
        $totalTrabajadores = Trabajador::count();

        // 2. Asistencias de hoy
        $asistenciasHoy = Asistencia::with('trabajador')->whereDate('fecha', $hoy)->get();

        $presentes = $asistenciasHoy->count();
        $faltas = $totalTrabajadores - $presentes; // Si no marcó, asumimos falta por ahora
        
        // 3. Tardanzas de hoy (Asumiendo que guardas 'Tardanza' en la columna estado)
        $tardanzas = $asistenciasHoy->where('estado', 'Tardanza')->count();

        // 4. Asistencia por Áreas (Para el gráfico de Dona)
        // Agrupamos a los que vinieron hoy según su área
        $asistenciaPorArea = $asistenciasHoy->groupBy(function($asistencia) {
            return $asistencia->trabajador->area ?? 'Sin Área';
        })->map(function($grupo) {
            return $grupo->count();
        });

        // Preparamos los datos para los gráficos de Angular (Labels y Data)
        $graficoAreas = [
            'labels' => $asistenciaPorArea->keys(),
            'data'   => $asistenciaPorArea->values()
        ];

        // 5. Retornamos todo empacado en un JSON limpio
        return response()->json([
            'status' => true,
            'data' => [
                'kpis' => [
                    'total_personal' => $totalTrabajadores,
                    'presentes_hoy'  => $presentes,
                    'faltas_hoy'     => $faltas,
                    'tardanzas_hoy'  => $tardanzas,
                    'porcentaje_asistencia' => $totalTrabajadores > 0 ? round(($presentes / $totalTrabajadores) * 100, 1) : 0
                ],
                'graficos' => [
                    'areas' => $graficoAreas
                ]
            ]
        ]);
    }
}