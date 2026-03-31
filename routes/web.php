<?php

use Illuminate\Support\Facades\Route;

Route::get('/instalar-bd', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
    return '¡Magia pura! Tablas creadas y usuario administrador sembrado con éxito.';
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
