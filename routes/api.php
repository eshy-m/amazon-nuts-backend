<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\AsistenciaController;

// ==========================================
// 🌐 RUTAS PÚBLICAS GENERALES
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::get('/pages/{slug}', [PageContentController::class, 'getPageBySlug']);
Route::post('/contact', [ContactController::class, 'store']);

// MÓDULO ASISTENCIA: Registro por QR (Público para los trabajadores)
Route::post('/asistencias/registrar', [AsistenciaController::class, 'registrar']);

// ==========================================
// 👥 MÓDULO PERSONAL: Rutas CRUD (Temporalmente públicas para pruebas Angular)
// ==========================================
// ⚠️ MUY IMPORTANTE: La ruta "estadisticas" SIEMPRE debe ir ANTES que la ruta "{id}".
Route::get('/trabajadores/estadisticas', [TrabajadorController::class, 'estadisticas']);
Route::get('/trabajadores', [TrabajadorController::class, 'index']);           // Listar todos
Route::post('/trabajadores', [TrabajadorController::class, 'store']);          // Crear nuevo
Route::get('/trabajadores/{id}', [TrabajadorController::class, 'show']);       // Ver uno
Route::put('/trabajadores/{id}', [TrabajadorController::class, 'update']);     // Editar
Route::delete('/trabajadores/{id}', [TrabajadorController::class, 'destroy']); // Eliminar

// ==========================================
// 🔐 RUTAS PROTEGIDAS (Panel Admin)
// ==========================================
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

// ==========================================
// 🛠️ UTILIDADES DEL SERVIDOR
// ==========================================
Route::get('/generar-tunel', function () {
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return '¡Túnel creado exitosamente con Laravel!';
});

Route::get('/limpiar-todo', function() {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    return 'Caché de configuración, vistas y rutas limpiada con éxito.';
});