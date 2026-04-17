<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==========================================
// 📦 IMPORTACIÓN DE CONTROLADORES
// ==========================================
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\TurnoPlanificadoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\DashboardController;


// ==========================================
// 🌐 RUTAS PÚBLICAS
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::get('/pages/{slug}', [PageContentController::class, 'getPageBySlug']);
Route::post('/contact', [ContactController::class, 'store']);


// ==========================================
// 👨‍🔧 MÓDULO TRABAJADORES Y RRHH
// ==========================================
// IMPORTANTE: Rutas específicas antes de dinámicas ({id})
Route::get('/trabajadores/estadisticas', [TrabajadorController::class, 'estadisticas']);
Route::get('/trabajadores', [TrabajadorController::class, 'index']);
Route::post('/trabajadores', [TrabajadorController::class, 'store']);
Route::put('/trabajadores/{id}', [TrabajadorController::class, 'update']);
Route::delete('/trabajadores/{id}', [TrabajadorController::class, 'destroy']);


// ==========================================
// 📊 MÓDULO DE REPORTES (PDF / EXCEL)
// ==========================================
Route::get('/reportes/general/pdf', [ReporteController::class, 'generalPdf']);
Route::get('/reportes/general/excel', [ReporteController::class, 'generalExcel']);
Route::get('/reportes/detallado/excel', [ReporteController::class, 'detalladoExcel']);

Route::get('/reportes/general/pdf', [ReporteController::class, 'generalPdf']);
Route::get('/reportes/general/excel', [ReporteController::class, 'generalExcel']);
// Agrega esta nueva línea:
Route::get('/reportes/detallado/pdf', [ReporteController::class, 'detalladoPdf']);
Route::get('/reportes/detallado/excel', [ReporteController::class, 'detalladoExcel']);
// ==========================================
// ⏱️ MÓDULO ASISTENCIA Y TURNOS
// ==========================================

// 🔹 Turnos planificados
Route::get('/turnos', [TurnoPlanificadoController::class, 'index']);
Route::post('/turnos', [TurnoPlanificadoController::class, 'store']);
Route::put('/turnos/{id}', [TurnoPlanificadoController::class, 'update']);
Route::delete('/turnos/{id}', [TurnoPlanificadoController::class, 'destroy']);
Route::put('/turnos/{id}/cerrar', [TurnoPlanificadoController::class, 'cerrarTurno']);

// 🔹 Automatización de turnos
Route::post('/turnos/auto-cierre', [TurnoPlanificadoController::class, 'autoCierre']); // Robot
Route::get('/turnos/auto-cerrar', [TurnoPlanificadoController::class, 'autoCerrarTurnos']);

// 🔹 Asistencia (QR y manual)
Route::post('/asistencias/qr', [AsistenciaController::class, 'registrarQR']); // Escáner QR
Route::post('/asistencias/registrar', [AsistenciaController::class, 'registrar']); // Manual (DNI)

// 🔹 Consultas y reportes de asistencia
Route::get('/asistencias/hoy', [AsistenciaController::class, 'hoy']);
Route::get('/asistencias/reportes', [AsistenciaController::class, 'reportes']);

//Dashboard 

Route::get('/dashboard/metricas', [DashboardController::class, 'obtenerMetricasDiarias']);
Route::get('/asistencias/dashboard/metricas', [App\Http\Controllers\AsistenciaController::class, 'metricasDashboard']);

// ==========================================
// 🔐 RUTAS PROTEGIDAS (AUTH: SANCTUM)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {

    // 👤 Sesión de usuario
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // 🌐 Gestión de contenidos web
    Route::put('/contents/{id}', [PageContentController::class, 'update']);
    Route::post('/contents/upload-image', [PageContentController::class, 'uploadImage']);

    // 📬 Gestión de mensajes (contacto)
    Route::get('/messages', [ContactController::class, 'index']);
    Route::delete('/messages/{id}', [ContactController::class, 'destroy']);
    Route::put('/messages/{id}/status', [ContactController::class, 'updateStatus']);
    Route::post('/messages/{id}/reply', [ContactController::class, 'reply']);
});


// ==========================================
// 🛠️ UTILIDADES DEL SERVIDOR
// ==========================================

// Crear enlace simbólico (storage)
Route::get('/generar-tunel', function () {
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return '¡Túnel creado exitosamente con Laravel!';
});

// Limpiar caché del sistema
Route::get('/limpiar-todo', function () {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    // Puedes agregar más comandos aquí
    return '¡Caché limpiada exitosamente!';
});
// Rutas para Tablas Maestras (Configuración).
Route::get('/maestros/cargos', [App\Http\Controllers\MaestrosController::class, 'getCargos']);
Route::post('/maestros/cargos', [App\Http\Controllers\MaestrosController::class, 'storeCargo']);
Route::get('/maestros/areas', [App\Http\Controllers\MaestrosController::class, 'getAreas']);
Route::post('/maestros/areas', [App\Http\Controllers\MaestrosController::class, 'storeArea']);