<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cargo;
use App\Models\Area;

class MaestrosController extends Controller
{
    // --- CARGOS ---
    public function getCargos() {
        return response()->json(['status' => true, 'data' => Cargo::orderBy('nombre')->get()]);
    }

    public function storeCargo(Request $request) {
        $request->validate(['nombre' => 'required|string|unique:cargos']);
        $cargo = Cargo::create($request->all());
        return response()->json(['status' => true, 'message' => 'Cargo creado exitosamente', 'data' => $cargo]);
    }

    // --- ÁREAS ---
    public function getAreas() {
        return response()->json(['status' => true, 'data' => Area::orderBy('nombre')->get()]);
    }

    public function storeArea(Request $request) {
        $request->validate(['nombre' => 'required|string|unique:areas']);
        $area = Area::create($request->all());
        return response()->json(['status' => true, 'message' => 'Área creada exitosamente', 'data' => $area]);
    }
}