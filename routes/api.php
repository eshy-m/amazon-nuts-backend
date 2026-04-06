<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\AsistenciaController; // <-- 1. IMPORTANTE: Importamos el controlador

// 🌐 RUTAS PÚBLICAS
Route::post('/login', [AuthController::class, 'login']);
Route::get('/pages/{slug}', [PageContentController::class, 'getPageBySlug']);
Route::post('/contact', [ContactController::class, 'store']);

// ✅ RUTAS TEMPORALMENTE PÚBLICAS (Para facilitar pruebas sin token)
Route::get('/trabajadores', [TrabajadorController::class, 'index']);

// 👇 2. AQUÍ ESTABA EL ERROR 404. FALTABA DECLARAR ESTA RUTA:
Route::post('/asistencias/registrar', [AsistenciaController::class, 'registrar']);


// 🔐 RUTAS PROTEGIDAS (Panel Admin)
Route::middleware('auth:sanctum')->group(function () {
    
    // Sesión
    Route::get('/user', function (Request $request) { return $request->user(); });
    Route::post('/logout', [AuthController::class, 'logout']);

    // MÓDULO ASISTENCIA: Trabajadores
    Route::post('/trabajadores', [TrabajadorController::class, 'store']);
    Route::get('/trabajadores/{id}', [TrabajadorController::class, 'show']);

    // Gestión de Contenidos
    Route::put('/contents/{id}', [PageContentController::class, 'update']);
    Route::post('/contents/upload-image', [PageContentController::class, 'uploadImage']);

    // Gestión de Mensajes
    Route::get('/messages', [ContactController::class, 'index']);
    Route::delete('/messages/{id}', [ContactController::class, 'destroy']);
    Route::put('/messages/{id}/status', [ContactController::class, 'updateStatus']);
    Route::post('/messages/{id}/reply', [ContactController::class, 'reply']);
});

// Utilidades del servidor
Route::get('/generar-tunel', function () {
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return '¡Túnel creado exitosamente con Laravel!';
});

Route::get('/limpiar-todo', function() {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear'); // <-- Añadí limpieza de rutas por si acaso
    return "Memoria caché y rutas limpiadas correctamente.";
});