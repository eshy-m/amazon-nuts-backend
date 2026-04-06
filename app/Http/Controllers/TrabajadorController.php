<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trabajador;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Carbon; 

class TrabajadorController extends Controller
{
    public function index()
    {
        $trabajadores = Trabajador::orderBy('apellidos', 'asc')->get();
        return response()->json($trabajadores, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'dni' => 'required|string|size:8|unique:trabajadores,dni',
            'area' => 'required|string',
            'celular' => 'nullable|string|max:15',
            'direccion' => 'nullable|string',
            'experiencia' => 'boolean',
            'observaciones' => 'nullable|string',
            'fecha_inicio' => 'nullable|date'
        ]);

        try {
            // Generamos el QR en SVG
            $qrImage = QrCode::format('svg')->size(300)->generate($request->dni);
            $qrFileName = 'qrcodes/' . $request->dni . '.svg';
            
            // 👇 LA MAGIA: Guardamos físicamente en public/qrcodes/
            $path = public_path('qrcodes');
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            file_put_contents(public_path($qrFileName), $qrImage);

            $trabajador = Trabajador::create([
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'dni' => $request->dni,
                'area' => $request->area,
                'celular' => $request->celular,
                'direccion' => $request->direccion,
                'experiencia' => $request->experiencia ?? false,
                'observaciones' => $request->observaciones,
                'fecha_inicio' => $request->fecha_inicio ?? Carbon::today()->toDateString(),
                'qr_code' => $qrFileName, 
                'activo' => true
            ]);

            return response()->json([
                'message' => '¡Trabajador registrado y QR generado con éxito!',
                'data' => $trabajador,
                'qr_url' => asset($qrFileName) 
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar al trabajador: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $trabajador = Trabajador::findOrFail($id);
        $trabajador->qr_url = asset($trabajador->qr_code);
        return response()->json($trabajador, 200);
    }
}