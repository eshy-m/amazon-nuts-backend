<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// 1. Limpiar caché (siempre útil)
Route::get('/limpiar-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    return "✅ Caché limpiada.";
});

// 2. LA PRUEBA DEFINITIVA DE RED
Route::get('/diagnostico-red', function () {
    $host = 'smtp.gmail.com';
    $port = 587;
    $timeout = 5; // Solo esperamos 5 segundos, no 60.

    // Intentamos abrir una conexión directa al servidor de Google
    $conexion = @fsockopen($host, $port, $errno, $errstr, $timeout);
    
    if (!$conexion) {
        return "<h1>🚨 BLOQUEO DE RED DETECTADO 🚨</h1>
                <p>Railway <b>NO</b> puede comunicarse con Gmail.</p>
                <p><b>Razón técnica:</b> $errstr ($errno)</p>
                <br>
                <p><b>¿Qué significa esto?</b> Railway (como muchos servidores en la nube) bloquea los puertos de correo SMTP (465/587) por seguridad para evitar el spam.</p>";
    } else {
        fclose($conexion);
        return "<h1>✅ LA RED ESTÁ BIEN</h1>
                <p>Railway SÍ tiene acceso al puerto 587 de Gmail. El bloqueo es de contraseñas, no de red.</p>";
    }
});
Route::get('/login', function () {
    return response()->json([
        'message' => 'No estás autenticado. Inicia sesión en la aplicación.'
    ], 401);
})->name('login');