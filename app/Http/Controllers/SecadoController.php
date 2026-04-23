<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProcesoSecado;
use App\Models\LoteProduccion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\PesajeSeleccion;

class SecadoController extends Controller
{
    // ===================================================
    // 1. Obtener Lote y Hornos Activos
    // ===================================================
    public function getProcesosActivos()
    {
        $loteActivo = LoteProduccion::where('estado', 'En Secado')->first();
        
        $procesosSecando = [];
        $stockDisponible = ['Primera' => 0, 'Partida' => 0, 'Ojos' => 0];

        if ($loteActivo) {
            $procesosSecando = ProcesoSecado::where('lote_id', $loteActivo->id)
                                ->where('estado', 'Secando')
                                ->orderBy('hora_inicio', 'desc')
                                ->get();

            // 🧮 CÁLCULO DE STOCK AUTOMÁTICO (Evita doble digitación)
            $categorias = ['Primera', 'Partida', 'Ojos'];
            foreach ($categorias as $cat) {
                // 1. Todo lo que la balanza pesó en el área de Selección
                $totalPesado = PesajeSeleccion::where('lote_id', $loteActivo->id)
                                ->where('categoria', $cat)
                                ->sum('peso');
                
                // 2. Lo que ya se metió al horno anteriormente (por si lo meten en 2 partes)
                $totalAlHorno = ProcesoSecado::where('lote_id', $loteActivo->id)
                                ->where('categoria', $cat)
                                ->sum('peso_entrada_kg');
                
                // 3. Lo que queda disponible para secar (mínimo 0)
                $stockDisponible[$cat] = max(0, $totalPesado - $totalAlHorno);
            }
        }

        return response()->json([
            'lote' => $loteActivo,
            'procesos_activos' => $procesosSecando,
            'stock_disponible' => $stockDisponible // Enviamos esto a Angular
        ]);
    }

    // ===================================================
    // 2. Ingresar Castaña al Horno
    // ===================================================
    public function iniciarSecado(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lote_id' => 'required|exists:lotes_producciones,id',
            'categoria' => 'required|in:Primera,Partida,Ojos',
            'temperatura_celsius' => 'required|numeric',
            'peso_entrada_kg' => 'required|numeric|min:0.1'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $secado = ProcesoSecado::create([
            'lote_id' => $request->lote_id,
            'usuario_id' => 1, // Cambiar por auth()->id() si usas login real
            'categoria' => $request->categoria,
            'temperatura_celsius' => $request->temperatura_celsius,
            'peso_entrada_kg' => $request->peso_entrada_kg,
            'hora_inicio' => Carbon::now(),
            'estado' => 'Secando'
        ]);

        return response()->json(['message' => 'Ingreso al horno registrado', 'data' => $secado], 201);
    }

    // ===================================================
    // 3. Sacar Castaña del Horno (Merma)
    // ===================================================
    public function finalizarSecado(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'peso_salida_kg' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $secado = ProcesoSecado::find($id);

        if (!$secado || $secado->estado === 'Finalizado') {
            return response()->json(['error' => 'Proceso no encontrado o ya finalizado'], 404);
        }

        if ($request->peso_salida_kg > $secado->peso_entrada_kg) {
            return response()->json(['error' => 'El peso de salida no puede ser mayor al de entrada'], 400);
        }

        $secado->update([
            'peso_salida_kg' => $request->peso_salida_kg,
            'hora_fin' => Carbon::now(),
            'estado' => 'Finalizado'
        ]);

        return response()->json(['message' => 'Salida del horno registrada', 'data' => $secado]);
    }

    // ===================================================
    // 4. Cerrar Lote Definitivamente (Fin de Jornada)
    // ===================================================
    public function cerrarLoteTotalmente($id)
    {
        $lote = LoteProduccion::find($id);
        if (!$lote) return response()->json(['error' => 'Lote no encontrado'], 404);

        // Seguridad: No cerrar si aún hay castaña en el horno
        $hornosActivos = ProcesoSecado::where('lote_id', $id)->where('estado', 'Secando')->count();
        if ($hornosActivos > 0) {
            return response()->json(['error' => 'Aún hay hornos encendidos'], 400);
        }

        $lote->update([
            'estado' => 'Finalizado',
            'updated_at' => Carbon::now()
        ]);

        return response()->json(['message' => 'Lote cerrado definitivamente']);
    }
}