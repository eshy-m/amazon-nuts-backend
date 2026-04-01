<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

Route::get('/crear-admin-maestro', function () {
    try {
        // 1. PRIMERO: Crear el rol de administrador para obtener su ID
        DB::table('roles')->insertOrIgnore([
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Obtenemos el ID del rol que acabamos de crear
        $rol = DB::table('roles')->where('name', 'admin')->first();

        // 2. SEGUNDO: Crear el usuario pasándole el role_id obligatorio
        $user = User::where('email', 'admin@amazonnuts.com')->first();
        
        if (!$user) {
            $user = new User();
            $user->name = 'Erick Sandro';
            $user->email = 'admin@amazonnuts.com';
            $user->password = Hash::make('admin123');
            $user->role_id = $rol->id; // ¡Aquí está la solución al error 1364!
            $user->save();
        }

        // 3. (Opcional) Guardar también en la tabla pivote de roles por si tu código la usa
        DB::table('model_has_roles')->insertOrIgnore([
            'role_id' => $rol->id,
            'model_type' => 'App\Models\User',
            'model_id' => $user->id
        ]);

        return "✅ Administrador creado con éxito. Correo: admin@amazonnuts.com | Clave: admin123";
    } catch (\Throwable $e) {
        return "🚨 Error real: " . $e->getMessage();
    }
});