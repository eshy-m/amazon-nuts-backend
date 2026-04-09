<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trabajador;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TrabajadorController extends Controller
{
    // 📊 1. ESTADÍSTICAS POR ÁREA
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

    // 📋 2. LISTAR TODOS
    public function index()
    {
        $trabajadores = Trabajador::orderBy('id', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $trabajadores], 200);
    }

    // ➕ 3. CREAR NUEVO TRABAJADOR
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dni' => 'required|unique:trabajadores',
            'nombres' => 'required',
            'apellidos' => 'required',
            'condicion_laboral' => 'required',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048' 
        ]);
        
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
        }

        $datos = $request->except('foto');

        // 🔥 MAGIA PARA HOSTINGER: Guardar directo en la carpeta 'public/fotos_personal'
        if ($request->hasFile('foto')) {
            $archivo = $request->file('foto');
            $nombreFoto = $request->dni . '_' . time() . '.' . $archivo->getClientOriginalExtension();
            
            // Movemos el archivo físicamente a la carpeta public
            $archivo->move(public_path('fotos_personal'), $nombreFoto);
            
            // Guardamos la ruta relativa en la base de datos
            $datos['foto'] = 'fotos_personal/' . $nombreFoto;
        }

        $trabajador = Trabajador::create($datos);

        // 🔥 Generar QR directo en la carpeta 'public/qrcodes'
        try {
            if (!file_exists(public_path('qrcodes'))) {
                mkdir(public_path('qrcodes'), 0777, true);
            }
            QrCode::format('svg')
                  ->size(300)
                  ->generate($trabajador->dni, public_path('qrcodes/' . $trabajador->dni . '.svg'));
        } catch (\Exception $e) {
            // Ignorar error de QR
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Trabajador registrado correctamente.',
            'data' => $trabajador
        ], 201);
    }

    // ✏️ 4. ACTUALIZAR / EDITAR
    public function update(Request $request, $id)
    {
        $trabajador = Trabajador::find($id);

        if (!$trabajador) {
            return response()->json(['status' => 'error', 'message' => 'Trabajador no encontrado.'], 404);
        }

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

        $datos = $request->except('foto');
        
        if (empty($datos['fecha_inicio'])) $datos['fecha_inicio'] = null;
        if (empty($datos['celular'])) $datos['celular'] = null;
        if (empty($datos['direccion'])) $datos['direccion'] = null;

        // 🔥 MAGIA PARA HOSTINGER AL EDITAR
        if ($request->hasFile('foto')) {
            // 1. Borramos la foto física anterior de la carpeta public
            if ($trabajador->foto && file_exists(public_path($trabajador->foto))) {
                unlink(public_path($trabajador->foto));
            }
            
            // 2. Guardamos la nueva foto
            $archivo = $request->file('foto');
            $nombreFoto = $request->dni . '_' . time() . '.' . $archivo->getClientOriginalExtension();
            $archivo->move(public_path('fotos_personal'), $nombreFoto);
            $datos['foto'] = 'fotos_personal/' . $nombreFoto;
        }

        $trabajador->update($datos);

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

        // 🔥 Borramos la foto de la carpeta public
        if ($trabajador->foto && file_exists(public_path($trabajador->foto))) {
            unlink(public_path($trabajador->foto));
        }
        
        // Borramos el QR de la carpeta public
        $qrPath = 'qrcodes/' . $trabajador->dni . '.svg';
        if (file_exists(public_path($qrPath))) {
            unlink(public_path($qrPath));
        }

        $trabajador->delete();

        return response()->json(['status' => 'success', 'message' => 'Trabajador eliminado'], 200);
    }
}