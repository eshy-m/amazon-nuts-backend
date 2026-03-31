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
        // 1. Validamos que nos envíen correo y contraseña
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // 2. Buscamos al usuario en la base de datos
        $user = User::where('email', $request->email)->first();

        // 3. Verificamos si existe y si la contraseña es correcta
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Las credenciales son incorrectas.'
            ], 401); // 401 significa "No Autorizado"
        }

        // 4. Verificamos si el usuario está activo
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Esta cuenta ha sido desactivada.'
            ], 403); // 403 significa "Prohibido"
        }

        // 5. Si todo está bien, generamos el Token de seguridad (Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Devolvemos el token y los datos del usuario al Frontend (Angular)
        return response()->json([
            'message' => '¡Bienvenido a Amazon Nuts!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id
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