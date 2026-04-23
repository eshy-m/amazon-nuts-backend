<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoteProduccion;
use App\Models\MuestreoCalibracion;
use App\Models\PesajeSeleccion; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Exception; // Importante para atrapar errores

class OperacionesController extends Controller
{
    // ==========================================
    // 1. INICIAR LOTE (Materia Prima)
    // ==========================================
    public function iniciarLote(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cantidad_sacos' => 'required|numeric',
                'peso_por_saco' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $pesoTotal = $request->cantidad_sacos * $request->peso_por_saco;

            $lote = LoteProduccion::create([
                'fecha' => Carbon::now()->toDateString(),
                'cantidad_sacos' => $request->cantidad_sacos,
                'peso_por_saco' => $request->peso_por_saco,
                'peso_total_ingreso' => $pesoTotal,
                'estado' => 'En Proceso'
            ]);

            return response()->json(['lote' => $lote], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al iniciar lote: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 2. OBTENER MÉTRICAS Y KPIs (Dashboard del Ingeniero)
    // ==========================================
   // ... dentro de OperacionesController.php ...

public function getMetricas()
{
    try {
        // Buscamos el lote que esté "En Proceso"
        $lote = LoteProduccion::where('estado', 'En Proceso')->first();

        if (!$lote) {
           return response()->json([
                'lote' => null,
                'kpis' => [
                    'total_primera' => 0, 'total_partida' => 0, 'total_ojos' => 0,
                    'total_procesado' => 0, 'rendimiento_barrica' => 0, 'meta_entera_kg' => 0,
                    'progreso_entera_porcentaje' => 0, 'porcentaje_partida_muestreo' => 0,
                    'porcentaje_avance' => 0, 'alerta_partida' => false
                ],
                'historial_muestreos' => [],
                'historial_seleccion' => []
            ], 200);
        }

        // Obtenemos pesajes del lote
        $pesajes = PesajeSeleccion::where('lote_id', $lote->id)->get();

        // CORRECCIÓN: Sumar ignorando si es mayúscula o minúscula
        $totalPrimera = $pesajes->filter(fn($p) => strtolower($p->categoria) == 'primera')->sum('peso');
        $totalPartida = $pesajes->filter(fn($p) => strtolower($p->categoria) == 'partida')->sum('peso');
        $totalOjos    = $pesajes->filter(fn($p) => strtolower($p->categoria) == 'ojos')->sum('peso');
        
        $totalProcesado = $totalPrimera + $totalPartida + $totalOjos;

        // Metas y KPIs
        $metaEntera = $lote->peso_total_ingreso * 0.45;
        $porcentajeAvance = ($lote->peso_total_ingreso > 0) ? ($totalProcesado / $lote->peso_total_ingreso) * 100 : 0;

        // Historiales
        $historialSeleccion = PesajeSeleccion::where('lote_id', $lote->id)->orderBy('id', 'desc')->take(10)->get();
        $historialMuestreos = MuestreoCalibracion::where('lote_id', $lote->id)->orderBy('id', 'desc')->take(5)->get();

        return response()->json([
            'lote' => $lote, // Enviamos el objeto lote completo
            'kpis' => [
                'total_primera' => round($totalPrimera, 2),
                'total_partida' => round($totalPartida, 2),
                'total_ojos'    => round($totalOjos, 2),
                'total_procesado' => round($totalProcesado, 2),
                'meta_entera_kg' => round($metaEntera, 2),
                'porcentaje_avance' => round($porcentajeAvance, 1),
                'rendimiento_barrica' => ($totalProcesado > 0) ? round($totalProcesado / 70, 1) : 0,
                'progreso_entera_porcentaje' => ($metaEntera > 0) ? round(($totalPrimera / $metaEntera) * 100, 1) : 0,
            ],
            'historial_muestreos' => $historialMuestreos,
            'historial_seleccion' => $historialSeleccion
        ], 200);

    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    // ==========================================
    // 3. REGISTRAR MUESTREO (Calidad)
    // ==========================================
    public function registrarMuestreo(Request $request)
    {
        try {
            // 1. Nos aseguramos de que todos los valores sean números (Si Angular envía nulo o vacío, usamos 0)
            $pesoEntera = (float) $request->input('peso_entera', 0);
            $pesoPartida = (float) $request->input('peso_partida', 0);
            $pesoOjos = (float) $request->input('peso_ojos', 0);
            $pesoPodrido = (float) $request->input('peso_podrido', 0);
            $pesoReproceso = (float) $request->input('peso_reproceso', 0);

            // 2. Calculamos el peso TOTAL de la muestra aquí mismo en el servidor
            $totalMuestra = $pesoEntera + $pesoPartida + $pesoOjos + $pesoPodrido + $pesoReproceso;

            // 3. Armamos el paquete de datos exacto como lo pide la base de datos
            $datosParaGuardar = [
                'lote_id' => $request->lote_id,
                'peso_muestra' => $totalMuestra, // <-- ESTE ERA EL DATO QUE FALTABA Y HACÍA EXPLOTAR LA BD
                'peso_entera' => $pesoEntera,
                'peso_partida' => $pesoPartida,
                'peso_ojos' => $pesoOjos,
                'peso_podrido' => $pesoPodrido,
                'peso_reproceso' => $pesoReproceso,
                'observaciones' => $request->input('observaciones', 'Sin observaciones')
            ];

            // 4. Guardamos en la base de datos
            $muestreo = MuestreoCalibracion::create($datosParaGuardar);
            
            // 5. Calculamos la alerta (Si la partida supera el 13%)
            $porcentajePartida = ($totalMuestra > 0) ? ($pesoPartida / $totalMuestra) * 100 : 0;

            // 6. Devolvemos respuesta exitosa (Status 200)
            return response()->json([
                'muestreo' => $muestreo,
                'alerta' => $porcentajePartida > 13
            ], 200);

        } catch (\Exception $e) {
            // Si sigue fallando, ahora Laravel nos dirá el motivo EXACTO
            return response()->json([
                'error' => 'Fallo en la base de datos', 
                'mensaje_secreto' => $e->getMessage(),
                'linea' => $e->getLine()
            ], 500);
        }
    }

    // ==========================================
    // 4. GUARDAR PESAJE (Desde Area Selección)
    // ==========================================
    public function guardarPesaje(Request $request)
    {
        try {
            $pesaje = PesajeSeleccion::create([
                'lote_id' => $request->lote_id,
                'categoria' => $request->categoria,
                'peso' => $request->peso,
                'hora_registro' => Carbon::now()->format('H:i:s')
            ]);

            return response()->json($pesaje, 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al guardar pesaje: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 5. CERRAR LOTE
    // ==========================================
    public function cerrarLote($id)
    {
        try {
            $lote = LoteProduccion::find($id);
            if (!$lote) return response()->json(['error' => 'Lote no encontrado'], 404);

            $lote->update([
                'estado' => 'Finalizado',
                'updated_at' => Carbon::now() 
            ]);

            return response()->json(['message' => 'Lote cerrado exitosamente'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al cerrar lote: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // OBTENER LOTE ACTIVO (Para la Tablet de Selección)
    // ==========================================
    public function getLoteActivo()
    {
        try {
            $lote = LoteProduccion::with(['pesajes' => function($query) {
                $query->orderBy('id', 'desc'); // Reemplazamos latest() por seguridad
            }])->where('estado', 'En Proceso')->first();
            
            if (!$lote) {
                return response()->json(null, 200);
            }
            
            return response()->json($lote, 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Fallo al buscar lote activo: ' . $e->getMessage()], 500);
        }
    }
}