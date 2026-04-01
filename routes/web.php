<?php

use Illuminate\Support\Facades\Route;

Route::get('/conectar-mysql', function () {
    // Comando 1: Limpiar la memoria caché para que lea las nuevas variables
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    
    // Comando 2: Crear las tablas de cero y plantar los datos
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
    
    return '¡Éxito! Memoria limpiada. Laravel ahora está conectado a MySQL de Railway.';
});
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
