<?php

namespace App\Exports;

use App\Models\Asistencia;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AsistenciasDetalladoExport implements FromView, ShouldAutoSize
{
    protected $inicio; protected $fin;

    public function __construct($inicio, $fin) {
        $this->inicio = $inicio;
        $this->fin = $fin;
    }

    public function view(): View {
        $asistencias = Asistencia::with('trabajador')
            ->whereBetween('fecha', [$this->inicio, $this->fin])
            ->orderBy('fecha', 'desc')
            ->get();

        return view('reportes.excel_detallado', [
            'asistencias' => $asistencias,
            'inicio' => $this->inicio,
            'fin' => $this->fin
        ]);
    }
}