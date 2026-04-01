<?php

use Illuminate\Support\Facades\Route;

// Ruta principal de la API (opcional, solo para verificar que vive)
Route::get('/', function () {
    return ['Laravel' => app()->version(), 'Estado' => 'Conectado a MySQL'];
});

// No necesitas poner las rutas de los controladores aquí si usas api.php
// Route::get('/instalar-bd', function () {
//     \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
//     return '¡Tablas creadas con éxito, eres un hacker!';
// });

//como crear usuario y contraseña
// Route::get('/crear-admin-seguro', function () {
//     \App\Models\User::updateOrCreate(
//         ['email' => 'admin@amazonnuts.com'],
//         [
//             'name' => 'Administrador',
//             'password' => \Illuminate\Support\Facades\Hash::make('tu_contraseña_aqui')
//         ]
//     );
//     return 'Usuario admin@amazonnuts.com creado o actualizado con éxito.';
// });

