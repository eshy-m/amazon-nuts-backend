<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trabajador;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TrabajadorController extends Controller
{
    // ==========================================
    // 📊 1. ESTADÍSTICAS POR ÁREA (Tarjetas superiores)
    // ==========================================
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

    // ==========================================
    // 📋 2. LISTAR TODOS (Para la tabla en Angular)
    // ==========================================
    public function index()
    {
        $trabajadores = Trabajador::orderBy('id', 'desc')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $trabajadores
        ], 200);
    }

    // ==========================================
    // ➕ 3. CREAR NUEVO TRABAJADOR (Con Foto)
    // ==========================================
    public function store(Request $request)
    {
        // 1. Validamos los datos y la imagen
        $validator = Validator::make($request->all(), [
            'dni' => 'required|unique:trabajadores',
            'nombres' => 'required',
            'apellidos' => 'required',
            'condicion_laboral' => 'required',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048' // Opcional, solo imágenes, max 2MB
        ]);
        
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        // 2. Extraemos todos los datos MENOS la foto (la trataremos aparte)
        $datos = $request->except('foto');

        // 3. Procesamos la foto si es que el usuario envió una
        if ($request->hasFile('foto')) {
            $archivo = $request->file('foto');
            // Creamos un nombre único: DNI_Hora.jpg (Ej: 72445566_168456.jpg)
            $nombreFoto = $request->dni . '_' . time() . '.' . $archivo->getClientOriginalExtension();
            // Guardamos en la carpeta storage/app/public/fotos_personal
            $rutaFoto = $archivo->storeAs('fotos_personal', $nombreFoto, 'public');
            // Añadimos la ruta al arreglo de datos que irá a la base de datos
            $datos['foto'] = $rutaFoto;
        }

        // 4. Guardamos en la base de datos
        $trabajador = Trabajador::create($datos);

        // 5. Generamos el QR
        try {
            if (!Storage::disk('public')->exists('qrcodes')) {
                Storage::disk('public')->makeDirectory('qrcodes');
            }
            QrCode::format('svg')
                  ->size(300)
                  ->generate($trabajador->dni, storage_path('app/public/qrcodes/' . $trabajador->dni . '.svg'));
        } catch (\Exception $e) {
            // Ignoramos si falla la librería de QR
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Trabajador registrado correctamente.',
            'data' => $trabajador
        ], 201);
    }

    // ==========================================
    // ✏️ 4. ACTUALIZAR / EDITAR (Con Foto)
    // ==========================================
    public function update(Request $request, $id)
    {
        $trabajador = Trabajador::find($id);

        if (!$trabajador) {
            return response()->json(['status' => 'error', 'message' => 'Trabajador no encontrado.'], 404);
        }

        // Validamos asegurándonos de ignorar el DNI del trabajador actual
        $validator = Validator::make($request->all(), [
            'dni' => 'required|unique:trabajadores,dni,'.$id,
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'condicion_laboral' => 'required|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        // Extraemos los datos omitiendo la foto
        $datos = $request->except('foto');
        
        // Limpiamos los datos que Angular envía vacíos
        if (empty($datos['fecha_inicio'])) $datos['fecha_inicio'] = null;
        if (empty($datos['celular'])) $datos['celular'] = null;
        if (empty($datos['direccion'])) $datos['direccion'] = null;

        // 🔥 LA MAGIA DE LA FOTO AL EDITAR
        if ($request->hasFile('foto')) {
            // 1. Borramos la foto anterior del servidor para ahorrar espacio
            if ($trabajador->foto && Storage::disk('public')->exists($trabajador->foto)) {
                Storage::disk('public')->delete($trabajador->foto);
            }
            
            // 2. Guardamos la nueva foto
            $archivo = $request->file('foto');
            $nombreFoto = $request->dni . '_' . time() . '.' . $archivo->getClientOriginalExtension();
            $rutaFoto = $archivo->storeAs('fotos_personal', $nombreFoto, 'public');
            $datos['foto'] = $rutaFoto;
        }

        // Actualizamos en la base de datos
        $trabajador->update($datos);

        return response()->json([
            'status' => 'success',
            'message' => 'Trabajador actualizado correctamente.',
            'data' => $trabajador
        ], 200);
    }

    // ==========================================
    // 🗑️ 5. ELIMINAR
    // ==========================================
    public function destroy($id)
    {
        $trabajador = Trabajador::find($id);

        if (!$trabajador) {
            return response()->json(['status' => 'error', 'message' => 'Trabajador no encontrado.'], 404);
        }

        // 🔥 OPTIMIZACIÓN: Si el trabajador tiene foto, la borramos del servidor
        if ($trabajador->foto && Storage::disk('public')->exists($trabajador->foto)) {
            Storage::disk('public')->delete($trabajador->foto);
        }

        // Eliminamos de la base de datos
        $trabajador->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Trabajador eliminado correctamente.'
        ], 200);
    }
}