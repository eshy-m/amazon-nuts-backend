<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProcesoSecado;
use App\Models\LoteProduccion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class SecadoController extends Controller
{
    // 1. Obtener lo que está actualmente en el Horno
    public function getProcesosActivos()
    {
        // Buscamos el lote que está actualmente "En Proceso"
        $loteActivo = LoteProduccion::where('estado', 'En Proceso')->first();

        // Buscamos si hay bandejas/coches en estado "Secando"
        $procesosSecando = ProcesoSecado::where('estado', 'Secando')
                            ->orderBy('hora_inicio', 'desc')
                            ->get();

        return response()->json([
            'lote' => $loteActivo,
            'procesos_activos' => $procesosSecando
        ]);
    }

    // 2. Ingresar Castaña al Horno
    public function iniciarSecado(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lote_id' => 'required|exists:lotes_producciones,id',
            'categoria' => 'required|in:Primera,Partida,Ojos',
            'temperatura_celsius' => 'required|numeric',
            'peso_entrada_kg' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Verificamos que esa categoría no esté ya secándose para ese lote
        $existe = ProcesoSecado::where('lote_id', $request->lote_id)
                    ->where('categoria', $request->categoria)
                    ->where('estado', 'Secando')
                    ->first();

        if ($existe) {
            return response()->json(['error' => 'Esta categoría ya está en el horno.'], 400);
        }

        $secado = ProcesoSecado::create([
        'lote_id' => $request->lote_id,
        'usuario_id' => auth()->id() ?? 1, // Usa el ID del que inició sesión
        'categoria' => $request->categoria,
        'temperatura_celsius' => $request->temperatura_celsius,
        'peso_entrada_kg' => $request->peso_entrada_kg,
        'hora_inicio' => Carbon::now(),
        'estado' => 'Secando'
    ]);

        return response()->json(['message' => 'Ingreso al horno registrado', 'data' => $secado], 201);
    }

    // 3. Sacar Castaña del Horno (Registrar Merma)
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
            return response()->json(['error' => 'El peso de salida no puede ser mayor al de entrada (No habría merma)'], 400);
        }

        $secado->update([
            'peso_salida_kg' => $request->peso_salida_kg,
            'hora_fin' => Carbon::now(),
            'estado' => 'Finalizado'
        ]);

        return response()->json(['message' => 'Salida del horno registrada exitosamente', 'data' => $secado]);
    }
}