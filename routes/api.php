<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TrabajadorController;

// 🌐 RUTAS PÚBLICAS
Route::post('/login', [AuthController::class, 'login']);
Route::get('/pages/{slug}', [PageContentController::class, 'getPageBySlug']);
Route::post('/contact', [ContactController::class, 'store']);

// ✅ MOVIMOS ESTA RUTA AQUÍ TEMPORALMENTE PARA LA PRUEBA
// Ahora podrás entrar a eshypro.com/api/trabajadores sin que te pida contraseña


// 🔐 RUTAS PROTEGIDAS (Panel Admin)
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/trabajadores', [TrabajadorController::class, 'index']);
    // Sesión
    Route::get('/user', function (Request $request) { return $request->user(); });
    Route::post('/logout', [AuthController::class, 'logout']);

    // MÓDULO ASISTENCIA: Trabajadores
    // Route::get('/trabajadores', [TrabajadorController::class, 'index']); // (La movimos arriba)
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

    //crear tunel para código qr
    Route::get('/generar-tunel', function () {
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return '¡Túnel creado exitosamente con Laravel!';
});
});