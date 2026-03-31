<?php

use Illuminate\Support\Facades\Route;

Route::get('/instalar-bd', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    return '¡Tablas creadas con éxito, eres un hacker!';
});
