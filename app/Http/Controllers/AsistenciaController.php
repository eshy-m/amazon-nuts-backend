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
        $trabajador = Trabajador::with(['areaMaestra'])
            ->where('id', $request->trabajador_id)
            ->orWhere('dni', $request->trabajador_id)
            ->first();

        if (!$trabajador) {
            return response()->json(['status' => 'error', 'message' => 'Trabajador no encontrado'], 404);
        }

        $ahora = Carbon::now();
        $fechaActual = $ahora->toDateString();
        $horaActual = $ahora->format('H:i:s'); // Formato exacto HH:MM:SS para MySQL TIME

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
            $horaConfigurada = Carbon::parse($turno->hora_entrada);
            $tolerancia = $turno->tolerancia_minutos ?? 0;
            
            // Si la hora actual supera la entrada + tolerancia -> Tardanza
            $estado = $ahora->gt($horaConfigurada->addMinutes($tolerancia)) ? 'Tardanza' : 'Presente';

            // Usamos create para asegurar que se apliquen los $fillable y $casts
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
                'data'    => $nuevaAsistencia->load('trabajador.areaMaestra')
            ]);
        }

        // --- LÓGICA DE SALIDA ---
        if (!$asistencia->hora_salida || $asistencia->hora_salida == '00:00:00') {
            $asistencia->hora_salida = $horaActual;
            $asistencia->save();

            return response()->json([
                'status'  => 'success',
                'message' => 'SALIDA MARCADA EXITOSAMENTE',
                'data'    => $asistencia->load('trabajador.areaMaestra')
            ]);
        }

        return response()->json([
            'status'  => 'warning', 
            'message' => 'Ya registraste entrada y salida el día de hoy.'
        ]);
    }
    public function hoy()
    {
        $fechaActual = Carbon::now()->toDateString();
        $asistencias = Asistencia::with(['trabajador.areaMaestra', 'turno'])
            ->where('fecha', $fechaActual)
            ->orderBy('updated_at', 'desc')
            ->get();
        return response()->json(['status' => 'success', 'data' => $asistencias]);
    }
    // ==========================================
    // 📊 5. OBTENER REPORTES HISTÓRICOS (FILTRO POR FECHAS)
    // ==========================================
    public function reportes(Request $request)
    {
        // 1. Recibimos las fechas desde Angular
        $fechaInicio = $request->query('fecha_inicio');
        $fechaFin = $request->query('fecha_fin');

        // 2. Preparamos la consulta incluyendo las relaciones para que se vea el Área
        $query = Asistencia::with(['trabajador.areaMaestra', 'turno']);

        // 3. Aplicamos el filtro de fechas si Angular las envió
        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        }

        // 4. Obtenemos los datos ordenados (los más recientes primero)
        $reportes = $query->orderBy('fecha', 'desc')
                          ->orderBy('hora_entrada', 'asc')
                          ->get();

        // 5. Devolvemos la respuesta al Frontend
        return response()->json([
            'status' => 'success',
            'data' => $reportes
        ]);
    }
    // ==========================================
    // 📄 6. EXPORTAR A PDF
    // ==========================================
    // ==========================================
    // 📄 6. EXPORTAR A PDF (CONSOLIDADO - MATRIZ)
    // ==========================================
    public function exportarPDF(Request $request)
    {
        // 1. Obtener las fechas (Si no envían, tomamos el mes actual por defecto)
        $fechaInicio = $request->query('fecha_inicio', Carbon::now()->startOfMonth()->toDateString());
        $fechaFin = $request->query('fecha_fin', Carbon::now()->toDateString());

        // 2. Traer todas las asistencias de ese periodo
        $reportes = Asistencia::with(['trabajador'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->orderBy('fecha', 'asc')
            ->get();

        // 3. GENERAR LA LISTA DE DÍAS ($dias)
        $dias = [];
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);
        
        // Creamos un array con todos los días entre la fecha de inicio y fin
        for ($d = $inicio->copy(); $d->lte($fin); $d->addDay()) {
            $dias[] = $d->copy();
        }

        // 4. GENERAR LA CUADRÍCULA AGRUPADA ($matriz)
        $matriz = [];
        
        // Obtenemos solo los trabajadores únicos que tienen asistencias en este periodo
        $trabajadores = $reportes->pluck('trabajador')->unique('id');

        foreach ($trabajadores as $trabajador) {
            if (!$trabajador) continue;
            
            // Filtramos las asistencias solo de este trabajador
            $asistenciasTrabajador = $reportes->where('trabajador_id', $trabajador->id);
            $asistenciasMapa = [];
            
            // Creamos un diccionario [ "2026-04-18" => "Presente" ]
            foreach ($asistenciasTrabajador as $asistencia) {
                $fechaKey = Carbon::parse($asistencia->fecha)->format('Y-m-d');
                $asistenciasMapa[$fechaKey] = $asistencia->estado;
            }

            // Guardamos la fila del trabajador en la matriz
            $matriz[] = [
                'nombre' => $trabajador->nombres . ' ' . $trabajador->apellidos,
                'dni' => $trabajador->dni,
                'asistencias' => $asistenciasMapa
            ];
        }

        // 5. Generar el PDF enviando $dias y $matriz a la vista
        // Usamos setPaper para forzar que la hoja sea Horizontal (Landscape) y quepan las columnas
        $pdf = \PDF::loadView('reportes.asistencias_pdf', compact('dias', 'matriz', 'fechaInicio', 'fechaFin'))
                    ->setPaper('a4', 'landscape');

        return $pdf->download('Reporte_Consolidado_Asistencias.pdf');
    }
    // ==========================================
    // 📊 7. EXPORTAR A EXCEL
    // ==========================================
    // ==========================================
    // 📊 7. EXPORTAR A EXCEL (CONSOLIDADO NATIVO)
    // ==========================================
    // ==========================================
    // 📊 EXPORTAR A EXCEL (CONSOLIDADO CON TU DISEÑO BLADE)
    // ==========================================
    public function exportarExcel(Request $request)
    {
        $fechaInicio = $request->query('fecha_inicio', Carbon::now()->startOfMonth()->toDateString());
        $fechaFin = $request->query('fecha_fin', Carbon::now()->toDateString());

        $reportes = Asistencia::with(['trabajador'])->whereBetween('fecha', [$fechaInicio, $fechaFin])->orderBy('fecha', 'asc')->get();

        $dias = [];
        $inicio = Carbon::parse($fechaInicio);
        $fin = Carbon::parse($fechaFin);
        for ($d = $inicio->copy(); $d->lte($fin); $d->addDay()) {
            $dias[] = $d->copy();
        }

        // Para el título: "MES DE ABRIL"
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
                if ($asistencia->estado == 'Presente') $letra = 'P';
                elseif ($asistencia->estado == 'Tardanza') $letra = 'T';
                elseif ($asistencia->estado == 'Falta') $letra = 'F';
                
                $asistenciasMapa[$fechaKey] = $letra;
                
                // Sumamos las horas de la base de datos (asegurando que sean números)
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

        // 🔥 MAGIA: Renderizamos el HTML pero lo devolvemos como si fuera Excel
        return response(view('reportes.excel_consolidado', compact('dias', 'matriz', 'mes')))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="Consolidado_Asistencias.xls"');
    }

    // ==========================================
    // 📄 EXPORTAR A PDF (DETALLADO)
    // ==========================================
    public function exportarDetalladoPDF(Request $request)
    {
        $inicio = $request->query('fecha_inicio', Carbon::now()->startOfMonth()->toDateString());
        $fin = $request->query('fecha_fin', Carbon::now()->toDateString());

        $asistencias = Asistencia::with(['trabajador.areaMaestra'])
            ->whereBetween('fecha', [$inicio, $fin])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_entrada', 'asc')
            ->get();

        $pdf = \PDF::loadView('reportes.pdf_detallado', compact('asistencias', 'inicio', 'fin'))
                   ->setPaper('a4', 'landscape');

        return $pdf->download('Reporte_Detallado_Asistencias.pdf');
    }

    // ==========================================
    // 📊 EXPORTAR A EXCEL (DETALLADO CON TU DISEÑO BLADE)
    // ==========================================
    public function exportarDetalladoExcel(Request $request)
    {
        $inicio = $request->query('fecha_inicio', Carbon::now()->startOfMonth()->toDateString());
        $fin = $request->query('fecha_fin', Carbon::now()->toDateString());

        $asistencias = Asistencia::with(['trabajador.areaMaestra'])
            ->whereBetween('fecha', [$inicio, $fin])
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_entrada', 'asc')
            ->get();

        return response(view('reportes.excel_detallado', compact('asistencias', 'inicio', 'fin')))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="Detallado_Asistencias.xls"');
    }
}