<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // Función para Iniciar Sesión
   public function login(Request $request)
{
    // 1. Validamos que envíen las credenciales (ya no pedimos estrictamente 'email')
    $request->validate([
        'login'    => 'required', // Este campo recibirá el nombre o el correo
        'password' => 'required'
    ]);

    // 2. Buscamos al usuario por correo O por nombre (username)
    $user = User::where('email', $request->login)
                ->orWhere('name', $request->login)
                ->first();

    // 3. Verificación de contraseña y estado
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Usuario o contraseña incorrectos.'], 401);
    }

    if (!$user->is_active) {
        return response()->json(['message' => 'Cuenta desactivada.'], 403);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'access_token' => $token,
        'token_type'   => 'Bearer',
        'user' => [
            'name'    => $user->name,
            'email'   => $user->email,
            'role_id' => $user->role_id // Importante para la redirección en Angular
        ]
    ], 200);
}

    // Función para Cerrar Sesión
    public function logout(Request $request)
    {
        // Eliminamos el token actual para que ya no pueda ser usado
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.'
        ], 200);
    }
}