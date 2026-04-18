<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoteProduccion;
use App\Models\MuestreoCalibracion;
use App\Models\PesajeSeleccion; // Usamos el modelo que ya tienes de la Fase 1
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class OperacionesController extends Controller
{
    // ==========================================
    // 1. INICIAR LOTE (Materia Prima)
    // ==========================================
    public function iniciarLote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cantidad_sacos' => 'required|numeric',
            'peso_por_saco' => 'required|numeric'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 400);

        $pesoTotal = $request->cantidad_sacos * $request->peso_por_saco;

        $lote = LoteProduccion::create([
            'fecha' => Carbon::now()->toDateString(),
            'cantidad_sacos' => $request->cantidad_sacos,
            'peso_por_saco' => $request->peso_por_saco,
            'peso_total_ingreso' => $pesoTotal,
            'estado' => 'En Proceso'
        ]);

        return response()->json(['lote' => $lote]);
    }

    // ==========================================
    // 2. OBTENER LOTE ACTIVO
    // ==========================================
    public function getLoteActivo()
    {
        $lote = LoteProduccion::where('estado', 'En Proceso')->latest()->first();
        
        if (!$lote) return response()->json(['lote' => null], 404);
        
        return response()->json(['lote' => $lote]);
    }

    // ==========================================
    // 3. REGISTRAR MUESTREO (Test del Ingeniero)
    // ==========================================
    public function registrarMuestreo(Request $request)
    {
        $porcentaje = 0;
        if ($request->peso_muestra > 0) {
            // El % de partida del muestreo = (peso_partida / peso_muestra) * 100
            $porcentaje = ($request->peso_partida / $request->peso_muestra) * 100;
        }

        $muestreo = MuestreoCalibracion::create([
            'lote_id' => $request->lote_id,
            'peso_muestra' => $request->peso_muestra,
            'peso_entera' => $request->peso_entera,
            'peso_partida' => $request->peso_partida,
            'peso_ojos' => $request->peso_ojos,
            'peso_podrido' => $request->peso_podrido,
            'porcentaje_partida' => round($porcentaje, 2)
        ]);

        return response()->json([
            'message' => 'Muestreo Guardado',
            'alerta' => $porcentaje > 13 // True si se pasa del límite
        ]);
    }

    // ==========================================
    // 4. REGISTRAR PESO EN FAJA (Botones del Operario)
    // ==========================================
    public function registrarPesaje(Request $request)
    {
        $pesaje = PesajeSeleccion::create([
            'lote_id' => $request->lote_id,
            'categoria' => $request->categoria, // 'Primera', 'Partida', 'Ojos'
            'peso' => $request->peso,
            'hora_registro' => Carbon::now()->format('H:i:s')
        ]);

        return response()->json(['message' => 'Pesaje registrado con éxito']);
    }

    // ==========================================
    // 5. EL CEREBRO: MÉTRICAS EN VIVO
    // ==========================================
    public function metricasEnVivo($lote_id)
    {
        $lote = LoteProduccion::find($lote_id);
        if (!$lote) return response()->json(['error' => 'Lote no encontrado'], 404);

        // Obtenemos todos los pesajes de los operarios para este lote
        $pesajes = PesajeSeleccion::where('lote_id', $lote_id)->get();

        $ultimoMuestreo = MuestreoCalibracion::where('lote_id', $lote_id)->latest()->first();
    $porcentajeMuestreo = $ultimoMuestreo ? $ultimoMuestreo->porcentaje_partida : 0;

        $totalPrimera = $pesajes->where('categoria', 'Primera')->sum('peso');
        $totalPartida = $pesajes->where('categoria', 'Partida')->sum('peso');
        $totalOjos    = $pesajes->where('categoria', 'Ojos')->sum('peso');

        $totalProcesado = $totalPrimera + $totalPartida + $totalOjos;

        // KPI: Porcentaje de Partida Global = Partida / (Primera + Partida)
        $sumaBase = $totalPrimera + $totalPartida;
        $porcentajePartidaGlobal = $sumaBase > 0 ? round(($totalPartida / $sumaBase) * 100, 2) : 0;

        // Rendimiento: ¿Cuánto hemos procesado del total ingresado?
        $porcentajeAvance = $lote->peso_total_ingreso > 0 ? round(($totalProcesado / $lote->peso_total_ingreso) * 100, 2) : 0;

        return response()->json([
            'kpis' => [
                'total_primera' => $totalPrimera,
                'total_partida' => $totalPartida,
                'total_ojos' => $totalOjos,
                'total_procesado' => $totalProcesado,
                'porcentaje_partida_global' => $porcentajePartidaGlobal,
                'porcentaje_avance' => $porcentajeAvance,
                'porcentaje_partida_muestreo' => $porcentajeMuestreo, // <--- Enviamos este dato nuevo
                'alerta_partida' => ($porcentajePartidaGlobal > 13 || $porcentajeMuestreo > 13) // Se activa si CUALQUIERA de los dos falla
            ]
        ]);
    }
    
}