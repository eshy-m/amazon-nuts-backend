<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Route::get('/limpiar-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    return "✅ Caché limpiada. Laravel ahora leerá las nuevas variables de Railway.";

});