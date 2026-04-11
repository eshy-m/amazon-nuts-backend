<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\TurnoPlanificado;

class TurnoPlanificadoController extends Controller
{
    // Listar los turnos (podemos filtrar por fecha luego)
    public function index()
    {
        // Traemos los turnos más recientes
        $turnos = TurnoPlanificado::orderBy('fecha', 'desc')->get();
        return response()->json($turnos);
    }

    // Guardar un nuevo turno (Lo hará el ingeniero)
    public function store(Request $request)
    {
        $request->validate([
            'area' => 'required|string',
            'fecha' => 'required|date',
            'hora_entrada' => 'required',
            'hora_salida' => 'required'
        ]);

        $turno = TurnoPlanificado::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Turno programado correctamente',
            'data' => $turno
        ]);
    }

    // Cerrar un turno al final del día
    public function cerrarTurno($id)
    {
        $turno = TurnoPlanificado::find($id);
        if($turno) {
            $turno->estado = 'Cerrado';
            $turno->save();
        }
        return response()->json(['success' => true, 'message' => 'Turno cerrado']);
    }
}