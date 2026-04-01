<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

Route::get('/crear-admin-maestro', function () {
    try {
        // 1. Crear el rol solo con las columnas que existen en tu base de datos
        DB::table('roles')->insertOrIgnore([
            'name' => 'admin',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Obtenemos el ID del rol
        $rol = DB::table('roles')->where('name', 'admin')->first();

        // 2. Crear el usuario
        $user = User::where('email', 'admin@amazonnuts.com')->first();
        
        if (!$user) {
            $user = new User();
            $user->name = 'Erick Sandro';
            $user->email = 'admin@amazonnuts.com';
            $user->password = Hash::make('admin123');
            $user->role_id = $rol->id; 
            $user->save();
        }

        return "✅ Administrador creado con éxito. Correo: admin@amazonnuts.com | Clave: admin123";
    } catch (\Throwable $e) {
        return "🚨 Error real: " . $e->getMessage();
    }
});