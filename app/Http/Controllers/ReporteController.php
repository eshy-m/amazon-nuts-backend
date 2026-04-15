<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trabajador;
use App\Models\Asistencia;
use App\Models\TurnoPlanificado;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf; // La librería de PDF

class ReporteController extends Controller
{

    ///reporte excel

    // ... tu función generalPdf() termina aquí arriba ...
public function detalladoPdf(Request $request)
    {
        $inicio = $request->query('inicio');
        $fin = $request->query('fin');

        $asistencias = Asistencia::with('trabajador')
            ->whereBetween('fecha', [$inicio, $fin])
            ->orderBy('fecha', 'desc')
            ->get();

        // 1. Calculamos el nombre del mes basado en la fecha de inicio
        $mes = \Carbon\Carbon::parse($inicio)->translatedFormat('F Y');

        $data = [
            'asistencias' => $asistencias,
            'inicio' => $inicio,
            'fin' => $fin,
            // 2. Pasamos la variable $mes a la vista
            'mes' => $mes 
        ];

        $pdf = Pdf::loadView('reportes.pdf_detallado', $data)->setPaper('a4', 'landscape');
        
        return $pdf->download("Reporte_Detallado_Asistencia.pdf");
    }


   public function detalladoExcel(Request $request) {
    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\AsistenciasDetalladoExport($request->inicio, $request->fin),
        'Reporte_Detallado_Asistencia.xlsx'
    );
}
    /**
     * REPORTE GENERAL (MATRIZ) EN PDF
     * Este es el formato de doble entrada que el ingeniero usa a mano.
     */
    public function generalPdf(Request $request)
    {
        $fechaInicio = Carbon::parse($request->query('inicio', Carbon::now()->startOfMonth()));
        $fechaFin = Carbon::parse($request->query('fin', Carbon::now()->endOfMonth()));

        // 1. Obtener todos los trabajadores (Ya corregido, sin el filtro de estado)
        $trabajadores = Trabajador::orderBy('nombres')->get();
        
        // 2. Crear la lista de días para las columnas de la tabla
        $diasDelMes = [];
        $diaActual = $fechaInicio->copy();
        while ($diaActual->lte($fechaFin)) {
            $diasDelMes[] = $diaActual->copy();
            $diaActual->addDay();
        }

        // 3. Armar la Matriz
        $matriz = [];
        
        foreach ($trabajadores as $trabajador) {
            $fila = [
                'dni' => $trabajador->dni,
                'nombre' => $trabajador->nombres . ' ' . $trabajador->apellidos,
                'asistencias' => []
            ];

            foreach ($diasDelMes as $dia) {
                $fechaStr = $dia->format('Y-m-d');
                
                // 🔥 CORRECCIÓN VITAL: Buscamos en la columna 'fecha' (Como está en tu SQL)
                $asistencia = Asistencia::where('trabajador_id', $trabajador->id)
                                        ->where('fecha', $fechaStr)
                                        ->first();
                
                // Buscamos si estaba programado ese día
                $turno = TurnoPlanificado::where('fecha', $fechaStr)
                                         ->where('estado', '!=', 'Cancelado')
                                         ->first();

                $letra = ''; // Lo que irá en la celda

                if ($asistencia) {
                    // Verificamos si llegó tarde
                    $letra = ($asistencia->estado === 'Tardanza') ? 'T' : 'A';
                } else if ($turno) {
                    // Si no vino, pero había turno...
                    if ($turno->tipo_registro === 'Turno de Trabajo') {
                        $letra = 'F'; // Falta
                    } else if ($turno->tipo_registro === 'Vacaciones') {
                        $letra = 'V'; // Vacaciones
                    } else if ($turno->tipo_registro === 'Descanso Médico') {
                        $letra = 'DM'; // Descanso Médico
                    } else {
                        $letra = '-';
                    }
                } else {
                    // No vino y no había turno (Día libre normal)
                    $letra = '-';
                }

                $fila['asistencias'][$fechaStr] = $letra;
            }

            $matriz[] = $fila;
        }

        // 4. Enviar los datos a la vista y generar el PDF
        $data = [
            'matriz' => $matriz,
            'dias' => $diasDelMes,
            'mes' => $fechaInicio->translatedFormat('F Y'), 
        ];

        // Generamos el PDF
        $pdf = Pdf::loadView('reportes.general', $data)->setPaper('a4', 'landscape');
        
        return $pdf->download("Asistencia_General_{$data['mes']}.pdf");
    }
    public function generalExcel(Request $request) {
    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\AsistenciasConsolidadoExport($request->inicio, $request->fin),
        'Reporte_Consolidado_Asistencia.xlsx'
    );
}

}