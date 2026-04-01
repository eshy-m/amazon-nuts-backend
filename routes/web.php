<?php

use Illuminate\Support\Facades\Route;

// Ruta principal de la API (opcional, solo para verificar que vive)
Route::get('/crear-admin-maestro', function () {
    // 1. Crear el rol si no existe (solo si usas el paquete de roles)
    $rol = Role::firstOrCreate(['name' => 'admin']);

    // 2. Crear el usuario
    $user = User::create([
        'name' => 'Erick Sandro',
        'email' => 'ericksandrillo5@gmail.com',
        'password' => Hash::make('TuClaveSegura123'), // Cambia esto por tu contraseña real
    ]);

    // 3. Asignar rol
    $user->assignRole($rol);

    return "Administrador creado con éxito. Ya puedes loguearte.";
});