<?php

namespace App\Exports;

use App\Models\Trabajador;
use App\Models\Asistencia;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AsistenciasExport implements FromView, ShouldAutoSize
{
    protected $inicio;
    protected $fin;

    public function __construct($inicio, $fin)
    {
        $this->inicio = $inicio;
        $this->fin = $fin;
    }

    public function view(): View
    {
        $fechaInicio = Carbon::parse($this->inicio);
        $fechaFin = Carbon::parse($this->fin);

        // 1. Generamos el rango de días del reporte
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);
        $dias = [];
        foreach ($periodo as $date) {
            $dias[] = $date;
        }

        // 2. Traemos a los trabajadores activos
        $trabajadores = Trabajador::orderBy('nombres', 'asc')->get();

        // 3. Traemos todas las asistencias del rango para procesarlas
        $asistencias = Asistencia::whereBetween('fecha', [$this->inicio, $this->fin])->get();

        // 4. Construimos la Matriz de datos
        $matriz = [];
        foreach ($trabajadores as $t) {
            $asistenciasDelTrabajador = $asistencias->where('trabajador_id', $t->id);
            
            $asistenciasMapeadas = [];
            $totalHoras = 0;

            foreach ($asistenciasDelTrabajador as $asist) {
                // Mapeamos la letra del estado por fecha
                $asistenciasMapeadas[$asist->fecha] = $asist->estado_letra ?? substr($asist->estado, 0, 1);
                
                // Sumamos las horas trabajadas (asegúrate que el campo exista en tu BD)
                $totalHoras += (float) ($asist->horas_trabajadas ?? 0);
            }

            $matriz[] = [
                'nombre'      => $t->nombres . ' ' . $t->apellidos,
                'dni'         => $t->dni,
                'asistencias' => $asistenciasMapeadas,
                'total_horas' => number_format($totalHoras, 2)
            ];
        }

        // Texto del mes para el subtítulo (Ej: ABRIL 2026)
        $mesNombre = $fechaInicio->translatedFormat('F Y');

        return view('reportes.excel', [
            'matriz' => $matriz,
            'dias'   => $dias,
            'mes'    => $mesNombre
        ]);
    }
}