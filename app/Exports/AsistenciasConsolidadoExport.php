<?php

namespace App\Exports;

use App\Models\Trabajador;
use App\Models\Asistencia;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AsistenciasConsolidadoExport implements FromView, ShouldAutoSize
{
    protected $inicio; protected $fin;

    public function __construct($inicio, $fin) {
        $this->inicio = $inicio;
        $this->fin = $fin;
    }

    public function view(): View {
        $fechaInicio = Carbon::parse($this->inicio);
        $fechaFin = Carbon::parse($this->fin);
        $periodo = CarbonPeriod::create($fechaInicio, $fechaFin);
        $dias = iterator_to_array($periodo);

        $trabajadores = Trabajador::whereHas('asistencias', function($q) {
            $q->whereBetween('fecha', [$this->inicio, $this->fin]);
        })->orderBy('nombres', 'asc')->get();   

        $asistencias = Asistencia::whereBetween('fecha', [$this->inicio, $this->fin])->get();

        $matriz = [];
        foreach ($trabajadores as $t) {
            $asistenciasT = $asistencias->where('trabajador_id', $t->id);
            $mapeo = [];
            $totalH = 0;
            foreach ($asistenciasT as $a) {
                $mapeo[$a->fecha] = $a->estado_letra ?? substr($a->estado, 0, 1);
                $totalH += (float)($a->horas_trabajadas ?? 0);
            }
            $matriz[] = [
                'nombre' => $t->nombres . ' ' . $t->apellidos,
                'dni' => $t->dni,
                'asistencias' => $mapeo,
                'total_horas' => number_format($totalH, 2)
            ];
        }

        return view('reportes.excel_consolidado', [
            'matriz' => $matriz,
            'dias' => $dias,
            'mes' => $fechaInicio->translatedFormat('F Y')
        ]);
    }
}