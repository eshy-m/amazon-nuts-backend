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
    // ✍️ 1. REGISTRAR ASISTENCIA (MANUAL)
    // ==========================================
    public function registrar(Request $request)
    {
        // 1. Validación básica
        $validator = Validator::make($request->all(), [
            'trabajador_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'ID de trabajador requerido'], 400);
        }

        // 2. Identificar al trabajador (por ID o DNI)
        $trabajador = Trabajador::with(['areaMaestra', 'cargoMaestro'])
            ->where('id', $request->trabajador_id)
            ->orWhere('dni', $request->trabajador_id)
            ->first();

        if (!$trabajador) {
            return response()->json(['status' => 'error', 'message' => 'Trabajador no encontrado'], 404);
        }

        $ahora = Carbon::now();
        $fechaActual = $ahora->toDateString();
        $horaActual = $ahora->format('H:i:s'); 

        // 3. Buscar turno activo para el área
        $turno = TurnoPlanificado::where('area_id', $trabajador->area_id)
            ->where('fecha', $fechaActual)
            ->where('estado', 'Activo')
            ->first();

        if (!$turno) {
            return response()->json([
                'status' => 'error', 
                'message' => 'No hay turno planificado hoy para el área: ' . ($trabajador->areaMaestra->nombre ?? 'N/A')
            ], 403);
        }

        // 4. Buscar registro existente hoy
        $asistencia = Asistencia::where('trabajador_id', $trabajador->id)
            ->where('fecha', $fechaActual)
            ->first();

        // --- LÓGICA DE ENTRADA ---
        if (!$asistencia) {
            $horaEntradaTurno = Carbon::parse($turno->hora_entrada);
            $horaMarcada = Carbon::parse($horaActual);
            
            // Si marca hasta 15 min después del turno es Puntual, si no, Tardanza
            $estado = 'Puntual';
            if ($horaMarcada->gt($horaEntradaTurno->copy()->addMinutes(15))) {
                $estado = 'Tardanza';
            }

            $nuevaAsistencia = Asistencia::create([
                'trabajador_id' => $trabajador->id,
                'turno_id'      => $turno->id,
                'fecha'         => $fechaActual,
                'hora_entrada'  => $horaActual,
                'estado'        => $estado
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'ENTRADA MARCADA: ' . $estado,
                'data'    => $nuevaAsistencia->load(['trabajador.areaMaestra', 'trabajador.cargoMaestro', 'turno'])
            ]);
        }

        // --- LÓGICA DE SALIDA ---
        if (!$asistencia->hora_salida || $asistencia->hora_salida == '00:00:00') {
            $asistencia->hora_salida = $horaActual;
            $asistencia->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'SALIDA MARCADA EXITOSAMENTE',
                'data'    => $asistencia->load(['trabajador.areaMaestra', 'trabajador.cargoMaestro', 'turno'])
            ]);
        }

        return response()->json([
            'status'  => 'warning', 
            'message' => 'Ya registraste entrada y salida el día de hoy.'
        ]);
    }

    // ==========================================
    // 📷 2. REGISTRAR ASISTENCIA POR QR
    // ==========================================
    public function registrarQR(Request $request)
    {
        // Funciona exactamente igual que registrar(), reutilizamos la lógica
        return $this->registrar($request);
    }

    // ==========================================
    // 🕒 3. LISTAR ASISTENCIAS DE HOY
    // ==========================================
    public function hoy()
    {
        $fechaActual = Carbon::now()->toDateString();
        
        // Agregamos cargoMaestro aquí
        $asistencias = Asistencia::with(['trabajador.areaMaestra', 'trabajador.cargoMaestro', 'turno'])
            ->where('fecha', $fechaActual)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $asistencias
        ]);
    }

    // ==========================================
    // 📊 4. OBTENER REPORTES HISTÓRICOS 
    // ==========================================
    public function reportes(Request $request)
    {
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin = $request->query('fecha_fin');

        // Agregamos cargoMaestro aquí
        $query = Asistencia::with(['trabajador.areaMaestra', 'trabajador.cargoMaestro', 'turno']);

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        }

        $reportes = $query->orderBy('fecha', 'desc')
                          ->orderBy('hora_entrada', 'asc')
                          ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $reportes
        ]);
    }

    // ==========================================
    // 📄 5. EXPORTAR A PDF (CONSOLIDADO - MATRIZ)
    // ==========================================
    public function exportarPDF(Request $request)
    {
        $fechaInicio = $request->query('fecha_inicio', Carbon::now()->startOfMonth()->toDateString());
        $fechaFin = $request->query('fecha_fin', Carbon::now()->toDateString());

        $reportes = Asistencia::with(['trabajador.areaMaestra', 'trabajador.cargoMaestro'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderBy('fecha', 'asc')
            ->get();

        $dias = [];
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);
        
        for ($d = $inicio->copy(); $d->lte($fin); $d->addDay()) {
            $dias[] = $d->copy();
        }

        $matriz = [];
        $trabajadores = $reportes->pluck('trabajador')->unique('id');

        foreach ($trabajadores as $trabajador) {
            if (!$trabajador) continue;
            
            $asistenciasTrabajador = $reportes->where('trabajador_id', $trabajador->id);
            $asistenciasMapa = [];
            
            foreach ($asistenciasTrabajador as $asistencia) {
                $fechaKey = Carbon::parse($asistencia->fecha)->format('Y-m-d');
                $asistenciasMapa[$fechaKey] = $asistencia->estado;
            }

            $matriz[] = [
                'nombre' => $trabajador->nombres . ' ' . $trabajador->apellidos,
                'dni' => $trabajador->dni,
                'asistencias' => $asistenciasMapa
            ];
        }

        $pdf = \PDF::loadView('reportes.asistencias_pdf', compact('dias', 'matriz', 'fechaInicio', 'fechaFin'))
                    ->setPaper('a4', 'landscape');

        return $pdf->download('Reporte_Consolidado_Asistencias.pdf');
    }

    // ==========================================
    // 📊 6. EXPORTAR A EXCEL (CONSOLIDADO CON BLADE)
    // ==========================================
    public function exportarExcel(Request $request)
    {
        $fechaInicio = $request->query('fecha_inicio', Carbon::now()->startOfMonth()->toDateString());
        $fechaFin = $request->query('fecha_fin', Carbon::now()->toDateString());

        $reportes = Asistencia::with(['trabajador.areaMaestra', 'trabajador.cargoMaestro'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderBy('fecha', 'asc')
            ->get();

        $dias = [];
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);
        for ($d = $inicio->copy(); $d->lte($fin); $d->addDay()) {
            $dias[] = $d->copy();
        }

        $mes = Carbon::parse($fechaInicio)->translatedFormat('F'); 

        $trabajadores = $reportes->pluck('trabajador')->unique('id');
        $matriz = [];
        
        foreach ($trabajadores as $trabajador) {
            if (!$trabajador) continue;
            
            $asistenciasTrabajador = $reportes->where('trabajador_id', $trabajador->id);
            $asistenciasMapa = [];
            $totalHoras = 0;
            $totalExtras = 0;
            
            foreach ($asistenciasTrabajador as $asistencia) {
                $fechaKey = Carbon::parse($asistencia->fecha)->format('Y-m-d');
                $letra = '';
                if ($asistencia->estado == 'Presente' || $asistencia->estado == 'Puntual') $letra = 'P';
                elseif ($asistencia->estado == 'Tardanza') $letra = 'T';
                elseif ($asistencia->estado == 'Falta') $letra = 'F';
                
                $asistenciasMapa[$fechaKey] = $letra;
                $totalHoras += (float) $asistencia->horas_trabajadas;
                $totalExtras += (float) $asistencia->horas_extras;
            }
            
            $matriz[] = [
                'nombre' => $trabajador->nombres . ' ' . $trabajador->apellidos,
                'dni' => $trabajador->dni,
                'asistencias' => $asistenciasMapa,
                'total_horas' => $totalHoras,
                'total_extras' => $totalExtras
            ];
        }

        return response(view('reportes.excel_consolidado', compact('dias', 'matriz', 'mes')))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="Consolidado_Asistencias.xls"');
    }

    // ==========================================
    // 📄 7. EXPORTAR A PDF (DETALLADO)
    // ==========================================
    public function exportarDetalladoPDF(Request $request)
    {
        $inicio = $request->query('fecha_inicio', Carbon::now()->startOfMonth()->toDateString());
        $fin = $request->query('fecha_fin', Carbon::now()->toDateString());

        $asistencias = Asistencia::with(['trabajador.areaMaestra', 'trabajador.cargoMaestro'])
            ->whereBetween('fecha', [$inicio, $fin])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_entrada', 'asc')
            ->get();

        $pdf = \PDF::loadView('reportes.pdf_detallado', compact('asistencias', 'inicio', 'fin'))
                   ->setPaper('a4', 'landscape');

        return $pdf->download('Reporte_Detallado_Asistencias.pdf');
    }

    // ==========================================
    // 📊 8. EXPORTAR A EXCEL (DETALLADO CON BLADE)
    // ==========================================
    public function exportarDetalladoExcel(Request $request)
    {
        $inicio = $request->query('fecha_inicio', Carbon::now()->startOfMonth()->toDateString());
        $fin = $request->query('fecha_fin', Carbon::now()->toDateString());

        $asistencias = Asistencia::with(['trabajador.areaMaestra', 'trabajador.cargoMaestro'])
            ->whereBetween('fecha', [$inicio, $fin])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_entrada', 'asc')
            ->get();

        return response(view('reportes.excel_detallado', compact('asistencias', 'inicio', 'fin')))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="Detallado_Asistencias.xls"');
    }
    // ==========================================
    // 📊 DASHBOARD: MÉTRICAS DIARIAS
    // ==========================================
    public function dashboardMetricas()
    {
        $hoy = Carbon::now()->toDateString();

        // 1. Total de personal registrado
        $totalPersonal = Trabajador::count();

        // 2. Asistencias de hoy
        $asistenciasHoy = Asistencia::where('fecha', $hoy)->get();

        $presentesHoy = $asistenciasHoy->count();
        $tardanzasHoy = $asistenciasHoy->where('estado', 'Tardanza')->count();
        $faltasHoy = $totalPersonal > 0 ? ($totalPersonal - $presentesHoy) : 0;

        // 3. Porcentaje de asistencia
        $porcentaje = $totalPersonal > 0 ? round(($presentesHoy / $totalPersonal) * 100, 1) : 0;

        // 4. Agrupado por áreas para el gráfico de dona
        $asistenciasConArea = Asistencia::with('trabajador.areaMaestra')->where('fecha', $hoy)->get();
        
        $porArea = $asistenciasConArea->groupBy(function($item) {
            return $item->trabajador->areaMaestra->nombre ?? 'Sin Área';
        })->map(function($grupo, $area) {
            return [
                'area' => $area,
                'cantidad' => $grupo->count()
            ];
        })->values();

        return response()->json([
            'total_personal' => $totalPersonal,
            'presentes_hoy' => $presentesHoy,
            'tardanzas_hoy' => $tardanzasHoy,
            'faltas_hoy' => $faltasHoy,
            'porcentaje_asistencia' => $porcentaje,
            'por_area' => $porArea
        ]);
    }

    // ==========================================
    // 🚀 DASHBOARD: ACTIVIDAD RECIENTE (HOY)
    // ==========================================
    public function registrosHoy()
    {
        $hoy = Carbon::now()->toDateString();
        
        $asistencias = Asistencia::with(['trabajador.areaMaestra'])
            ->where('fecha', $hoy)
            ->orderBy('hora_entrada', 'desc') // Los más recientes primero
            ->get();

        return response()->json([
            'data' => $asistencias
        ]);
    }
}