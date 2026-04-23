<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoteProduccion;
use App\Models\MuestreoCalibracion;
use App\Models\PesajeSeleccion; 
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
    // 2. OBTENER MÉTRICAS Y KPIs (Dashboard del Ingeniero)
    // ==========================================
    public function getMetricas()
{
    // 1. Buscamos el lote. Si no se define aquí, el sistema "explota"
    $loteActivo = LoteProduccion::where('estado', 'Abierto')->first();

    // 2. Si no hay lote, devolvemos una estructura vacía para que Angular no de error
    if (!$loteActivo) {
        return response()->json([
            'lote' => null,
            'metricas' => [
                'kpis' => ['total_procesado' => 0],
                'historial_muestreos' => []
            ]
        ]);
    }

    // 3. Si hay lote, traemos los muestreos incluyendo el nuevo campo
    $muestreos = MuestreoCalibracion::where('lote_id', $loteActivo->id)
        ->orderBy('created_at', 'desc')
        ->get();

    // ... aquí sigue tu lógica de KPIs (asegúrate de que usen $loteActivo->id) ...
    
    return response()->json([
        'lote' => $loteActivo,
        'metricas' => [
            'kpis' => $tusKpisCalculados, 
            'historial_muestreos' => $muestreos
        ]
    ]);
}

    // ==========================================
    // 3. REGISTRAR MUESTREO (Test de 2 min)
    // ==========================================
    public function registrarMuestreo(Request $request)
{
    // 1. Validación (Mantenemos tus reglas)
    $validator = Validator::make($request->all(), [
        'lote_id' => 'required',
        'peso_muestra' => 'required|numeric',
        'peso_entera' => 'required|numeric',
        'peso_partida' => 'required|numeric',
        'peso_ojos' => 'required|numeric',
        'peso_podrido' => 'numeric',
        'peso_reproceso' => 'numeric'
    ]);

    if ($validator->fails()) return response()->json($validator->errors(), 400);

    // 2. Cálculo del porcentaje (Según tu fórmula de alerta)
    $denominador = $request->peso_entera + $request->peso_partida;
    $porcentaje = $denominador > 0 ? ($request->peso_partida / $denominador) * 100 : 0;
    $alerta = $porcentaje > 13;

    // 3. INSERCIÓN SEGURA (Aquí está el cambio clave)
    // En lugar de all(), listamos solo lo que SI existe en tu base de datos
    $muestreo = MuestreoCalibracion::create([
        'lote_id'            => $request->lote_id,
        'peso_muestra'       => $request->peso_muestra,
        'peso_entera'        => $request->peso_entera,
        'peso_partida'       => $request->peso_partida,
        'peso_ojos'          => $request->peso_ojos,
        'peso_podrido'       => $request->peso_podrido ?? 0,
        'peso_reproceso'     => $request->peso_reproceso ?? 0,
        'porcentaje_partida' => $porcentaje // Guardamos el cálculo para los KPIs
    ]);

    // 4. Respuesta (Mantenemos tu formato para que Angular no falle)
    return response()->json([
        'message' => 'Muestreo guardado',
        'muestreo' => $muestreo,
        'alerta' => $alerta
    ]);
}

    // ==========================================
    // 4. GUARDAR PESAJE (Desde Area Selección)
    // ==========================================
    public function guardarPesaje(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lote_id' => 'required',
            'categoria' => 'required',
            'peso' => 'required|numeric'
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 400);

        $pesaje = PesajeSeleccion::create([
            'lote_id' => $request->lote_id,
            'categoria' => $request->categoria,
            'peso' => $request->peso,
            'hora_registro' => Carbon::now()->format('H:i:s')
        ]);

        return response()->json($pesaje);
    }

    // ==========================================
    // 5. CERRAR LOTE (Finalización de Jornada)
    // ==========================================
    public function cerrarLote($id)
    {
        $lote = LoteProduccion::find($id);
        if (!$lote) return response()->json(['error' => 'Lote no encontrado'], 404);

        $lote->update([
            'estado' => 'Finalizado',
            'updated_at' => Carbon::now() // Esto servirá como hora de cierre
        ]);

        return response()->json(['message' => 'Lote cerrado exitosamente']);
    }

    // Nota: He omitido las funciones de sincronización masiva para mantener el código limpio, 
    // pero se pueden reintegrar si usas el modo offline.
    // ==========================================
    // OBTENER LOTE ACTIVO (EXCLUSIVO PARA TABLET)
    // ==========================================
    public function getLoteActivo()
    {
        // La tablet necesita saber cuál es el lote actual y qué pesajes tiene
        $lote = LoteProduccion::with(['pesajes' => function($query) {
    $query->latest(); // Esto ordena por created_at de forma descendente automáticamente
}])->where('estado', 'En Proceso')->first();
        
        if (!$lote) {
            return response()->json(null);
        }
        
        return response()->json($lote);
    }
}