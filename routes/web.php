<?php

use Illuminate\Support\Facades\Route;

Route::get('/crear-admin', function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
    return '¡Semillas plantadas! Base de datos poblada con éxito.';
});
// Route::get('/instalar-bd', function () {
//     \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
//     return '¡Tablas creadas con éxito, eres un hacker!';
// });
