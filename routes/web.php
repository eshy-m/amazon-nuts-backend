<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

Route::get('/crear-admin-maestro', function () {
    try {
        // 1. Crear el usuario
        $user = User::firstOrCreate(
            ['email' => 'admin@amazonnuts.com'],
            [
                'name' => 'Erick Sandro',
                'password' => Hash::make('admin123')
            ]
        );

        // 2. Crear el rol (Modo seguro directo a la base de datos)
        DB::table('roles')->insertOrIgnore([
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $rol = DB::table('roles')->where('name', 'admin')->first();

        // 3. Asignarle el rol al usuario
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