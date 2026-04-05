<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trabajador;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon; // 🔥 Importante: Agregamos Carbon para manejar las fechas automáticamente

class TrabajadorController extends Controller
{
    // 📋 Obtener la lista de todos los trabajadores
    public function index()
    {
        $trabajadores = Trabajador::orderBy('apellidos', 'asc')->get();
        return response()->json($trabajadores, 200);
    }

    // ➕ Registrar un nuevo trabajador y generarle su Fotocheck (QR)
    public function store(Request $request)
    {
        // 1. Validamos que nos envíen todos los datos de la hoja física
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dni' => 'required|string|size:8|unique:trabajadores,dni',
            'area' => 'required|string', // Obligatorio elegir un área
            'celular' => 'nullable|string|max:15',
            'direccion' => 'nullable|string',
            'experiencia' => 'boolean', // Espera un true o false
            'observaciones' => 'nullable|string',
            'fecha_inicio' => 'nullable|date'
        ]);

        try {
            // 2. Generamos el Código QR con el DNI
            //$qrImage = QrCode::format('png')->size(300)->generate($request->dni);
            
            // 3. Guardamos la imagen del QR en el servidor
            //$qrFileName = 'qrcodes/' . $request->dni . '.png';
            // ✅ AGREGA ESTAS DOS LÍNEAS USANDO 'svg':
$qrImage = QrCode::format('svg')->size(300)->generate($request->dni);
$qrFileName = 'qrcodes/' . $request->dni . '.svg';
            Storage::disk('public')->put($qrFileName, $qrImage);

            // 4. Guardamos al trabajador en la Base de Datos con todos sus datos
            $trabajador = Trabajador::create([
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'dni' => $request->dni,
                'area' => $request->area,
                'celular' => $request->celular,
                'direccion' => $request->direccion,
                'experiencia' => $request->experiencia ?? false, // Si no marcan nada, es false (No)
                'observaciones' => $request->observaciones,
                // 🔥 FECHA AUTOMÁTICA: Si escribes una fecha, la usa. Si la dejas en blanco, pone la de hoy.
                'fecha_inicio' => $request->fecha_inicio ?? Carbon::today()->toDateString(),
                'qr_code' => $qrFileName, 
                'activo' => true
            ]);

            return response()->json([
                'message' => '¡Trabajador registrado y QR generado con éxito!',
                'data' => $trabajador,
                'qr_url' => asset('storage/' . $qrFileName)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar al trabajador: ' . $e->getMessage()
            ], 500);
        }
    }

    // 👁️ Ver los datos de un solo trabajador
    public function show($id)
    {
        $trabajador = Trabajador::findOrFail($id);
        $trabajador->qr_url = asset('storage/' . $trabajador->qr_code);

        return response()->json($trabajador, 200);
    }
}