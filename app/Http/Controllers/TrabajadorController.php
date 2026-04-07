<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trabajador;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode; // Si usas la librería de QRs

class TrabajadorController extends Controller
{
    // 📊 1. ESTADÍSTICAS POR ÁREA (Para las tarjetas superiores)
    public function estadisticas()
    {
        $estadisticas = Trabajador::select('area', DB::raw('count(*) as total'))
                                  ->groupBy('area')
                                  ->get();

        $totalPersonal = Trabajador::count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'por_area' => $estadisticas,
                'total' => $totalPersonal
            ]
        ], 200);
    }

    // 📋 2. LISTAR TODOS (Para tu tabla en Angular)
    public function index()
    {
        $trabajadores = Trabajador::orderBy('id', 'desc')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $trabajadores
        ], 200);
    }

    // ➕ 3. CREAR NUEVO TRABAJADOR
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dni' => 'required|unique:trabajadores,dni',
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'area' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        // Creamos el trabajador
        $trabajador = Trabajador::create($request->all());

        // Generamos el QR guardándolo en storage/app/public/qrcodes/
        try {
            if (!Storage::disk('public')->exists('qrcodes')) {
                Storage::disk('public')->makeDirectory('qrcodes');
            }
            QrCode::format('svg')
                  ->size(300)
                  ->generate($trabajador->dni, storage_path('app/public/qrcodes/' . $trabajador->dni . '.svg'));
        } catch (\Exception $e) {
            // Si no tienes instalada la librería QrCode, simplemente pasará de largo sin romper la creación
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Trabajador registrado correctamente.',
            'data' => $trabajador
        ], 201);
    }

    // ✏️ 4. ACTUALIZAR (EDITAR)
    public function update(Request $request, $id)
    {
        $trabajador = Trabajador::find($id);

        if (!$trabajador) {
            return response()->json(['status' => 'error', 'message' => 'Trabajador no encontrado.'], 404);
        }

        // Ignoramos el DNI actual por si no lo cambia
        $validator = Validator::make($request->all(), [
            'dni' => 'required|unique:trabajadores,dni,'.$id,
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'area' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        $trabajador->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Trabajador actualizado correctamente.',
            'data' => $trabajador
        ], 200);
    }

    // 🗑️ 5. ELIMINAR
    public function destroy($id)
    {
        $trabajador = Trabajador::find($id);

        if (!$trabajador) {
            return response()->json(['status' => 'error', 'message' => 'Trabajador no encontrado.'], 404);
        }

        $trabajador->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Trabajador eliminado correctamente.'
        ], 200);
    }
}