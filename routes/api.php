<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Importación de Controladores
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\TurnoPlanificadoController;
use App\Http\Controllers\MaestrosController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// 🌐 RUTAS PÚBLICAS
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::get('/pages/{slug}', [PageContentController::class, 'getPageBySlug']);
Route::post('/contact', [ContactController::class, 'store']);

// ==========================================
// 👨‍🔧 MÓDULO TRABAJADORES
// ==========================================
Route::prefix('trabajadores')->group(function () {
    Route::get('/estadisticas', [TrabajadorController::class, 'estadisticas']);
    Route::get('/', [TrabajadorController::class, 'index']);
    Route::post('/', [TrabajadorController::class, 'store']);
    Route::put('/{id}', [TrabajadorController::class, 'update']);
    Route::delete('/{id}', [TrabajadorController::class, 'destroy']);
});

// ==========================================
// 🕒 MÓDULO ASISTENCIAS
// ==========================================
Route::prefix('asistencias')->group(function () {
    Route::get('/hoy', [AsistenciaController::class, 'hoy']);
    Route::get('/reporte', [AsistenciaController::class, 'reporte']);
    Route::post('/registrar', [AsistenciaController::class, 'registrar']);
    Route::post('/qr', [AsistenciaController::class, 'registrarQR']);
});

// ==========================================
// 📅 MÓDULO PLANIFICACIÓN (TURNOS)
// ==========================================
Route::prefix('turnos')->group(function () {
    Route::get('/', [TurnoPlanificadoController::class, 'index']);
    Route::post('/', [TurnoPlanificadoController::class, 'store']);
    Route::put('/{id}', [TurnoPlanificadoController::class, 'update']);
    Route::delete('/{id}', [TurnoPlanificadoController::class, 'destroy']);
    Route::put('/{id}/cerrar', [TurnoPlanificadoController::class, 'cerrarTurno']);
    Route::post('/auto-cierre', [TurnoPlanificadoController::class, 'autoCierre']);
    Route::get('/reporte/general/pdf', [TurnoPlanificadoController::class, 'descargarGeneralPDF']);
});

// ==========================================
// 📊 DASHBOARD
// ==========================================
Route::get('/dashboard/kpis', [DashboardController::class, 'getKpis']);

// ==========================================
// 🛠️ TABLAS MAESTRAS
// ==========================================
Route::prefix('maestros')->group(function () {
    Route::get('/cargos', [MaestrosController::class, 'getCargos']);
    Route::post('/cargos', [MaestrosController::class, 'storeCargo']);
    Route::get('/areas', [MaestrosController::class, 'getAreas']);
    Route::post('/areas', [MaestrosController::class, 'storeArea']);
});

// ==========================================
// ⚙️ UTILIDADES DEL SISTEMA (CORREGIDO)
// ==========================================
Route::get('/limpiar-todo', function () {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    return response()->json(['message' => 'Caché y rutas limpiadas exitosamente']);
});

Route::get('/generar-storage-link', function () {
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return 'Enlace simbólico creado';
});
// ==========================================
// 🕒 MÓDULO ASISTENCIAS
// ==========================================
Route::prefix('asistencias')->group(function () {
    Route::get('/hoy', [AsistenciaController::class, 'hoy']);
    
    // 🔥 ESTA ES LA RUTA QUE FALTABA (En plural)
    Route::get('/reportes', [AsistenciaController::class, 'reportes']); 
    
    Route::post('/registrar', [AsistenciaController::class, 'registrar']);
    Route::post('/qr', [AsistenciaController::class, 'registrarQR']);
});
// En routes/api.php
Route::get('/reportes/general/pdf', [AsistenciaController::class, 'exportarPDF']);
Route::get('/reportes/general/excel', [AsistenciaController::class, 'exportarExcel']);
Route::get('/reportes/general/pdf', [AsistenciaController::class, 'exportarPDF']); // Consolidado PDF
Route::get('/reportes/general/excel', [AsistenciaController::class, 'exportarExcel']); // Consolidado Excel
Route::get('/reportes/detallado/pdf', [AsistenciaController::class, 'exportarDetalladoPDF']); // Detallado PDF
Route::get('/reportes/detallado/excel', [AsistenciaController::class, 'exportarDetalladoExcel']); // Detallado Excel