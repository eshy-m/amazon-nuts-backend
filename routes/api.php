<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Importaciones de Controladores
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\AsistenciaController; 
use App\Http\Controllers\TurnoPlanificadoController; // 🔥 AGREGADO: Faltaba esta importación vital

// ==========================================
// 🌐 RUTAS PÚBLICAS
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::get('/pages/{slug}', [PageContentController::class, 'getPageBySlug']);
Route::post('/contact', [ContactController::class, 'store']);


// ==========================================
// 👨‍🔧 MÓDULO TRABAJADORES Y RRHH
// ==========================================
// IMPORTANTE: Rutas específicas deben ir antes de las dinámicas con {id}
Route::get('/trabajadores/estadisticas', [TrabajadorController::class, 'estadisticas']);
Route::get('/trabajadores', [TrabajadorController::class, 'index']);
Route::post('/trabajadores', [TrabajadorController::class, 'store']);
Route::put('/trabajadores/{id}', [TrabajadorController::class, 'update']);
Route::delete('/trabajadores/{id}', [TrabajadorController::class, 'destroy']);


// ==========================================
// ⏱️ MÓDULO ASISTENCIA Y TURNOS
// ==========================================
// 1. Lógica del Escáner QR en la puerta
Route::post('/asistencias/qr', [AsistenciaController::class, 'registrarQR']);
//cerrar turno automatico
Route::get('/turnos/auto-cerrar', [TurnoPlanificadoController::class, 'autoCerrarTurnos']);

// 2. Reportes y manuales (Dashboard)
Route::get('/asistencias/hoy', [AsistenciaController::class, 'hoy']);
Route::post('/asistencias/registrar', [AsistenciaController::class, 'registrar']); // Registro manual por DNI
Route::get('/asistencias/reportes', [AsistenciaController::class, 'reportes']);

// 3. Planificación de Turnos (Ingeniero).
Route::get('/turnos', [TurnoPlanificadoController::class, 'index']);
Route::post('/turnos', [TurnoPlanificadoController::class, 'store']);
Route::put('/turnos/{id}/cerrar', [TurnoPlanificadoController::class, 'cerrarTurno']);


// ==========================================
// 🔐 RUTAS PROTEGIDAS (Panel Admin)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // Sesión de Usuario
    Route::get('/user', function (Request $request) { return $request->user(); });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gestión de Contenidos (Web)
    Route::put('/contents/{id}', [PageContentController::class, 'update']);
    Route::post('/contents/upload-image', [PageContentController::class, 'uploadImage']);

    // Gestión de Mensajes (Buzón de Contacto)
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
    // ... tus otros comandos de limpieza
    return '¡Caché limpiada exitosamente!';
});