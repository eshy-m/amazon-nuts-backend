<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\AsistenciaController; 

// 🌐 RUTAS PÚBLICAS
Route::post('/login', [AuthController::class, 'login']);
Route::get('/pages/{slug}', [PageContentController::class, 'getPageBySlug']);
Route::post('/contact', [ContactController::class, 'store']);
// MÓDULO ASISTENCIA
Route::get('/asistencias/hoy', [AsistenciaController::class, 'hoy']);
Route::post('/asistencias/registrar', [AsistenciaController::class, 'registrar']);

// 👨‍🔧 MÓDULO TRABAJADORES (Público temporalmente para pruebas)
// 🔥 IMPORTANTE: La ruta específica ('estadisticas') debe ir SIEMPRE antes que la dinámica ('{id}')
Route::get('/trabajadores/estadisticas', [TrabajadorController::class, 'estadisticas']);
Route::get('/trabajadores', [TrabajadorController::class, 'index']);
Route::post('/trabajadores', [TrabajadorController::class, 'store']);
Route::put('/trabajadores/{id}', [TrabajadorController::class, 'update']);
Route::delete('/trabajadores/{id}', [TrabajadorController::class, 'destroy']);


// 🔐 RUTAS PROTEGIDAS (Panel Admin)
Route::middleware('auth:sanctum')->group(function () {
    
    // Sesión
    Route::get('/user', function (Request $request) { return $request->user(); });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gestión de Contenidos
    Route::put('/contents/{id}', [PageContentController::class, 'update']);
    Route::post('/contents/upload-image', [PageContentController::class, 'uploadImage']);

    // Gestión de Mensajes
    Route::get('/messages', [ContactController::class, 'index']);
    Route::delete('/messages/{id}', [ContactController::class, 'destroy']);
    Route::put('/messages/{id}/status', [ContactController::class, 'updateStatus']);
    Route::post('/messages/{id}/reply', [ContactController::class, 'reply']);
});

// 🛠️ Utilidades del servidor
Route::get('/generar-tunel', function () {
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return '¡Túnel creado exitosamente con Laravel!';
});

Route::get('/limpiar-todo', function() {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    return '¡Caché limpiada exitosamente!';
});