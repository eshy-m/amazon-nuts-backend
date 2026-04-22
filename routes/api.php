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
use App\Http\Controllers\OperacionesController;

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
// 🕒 MÓDULO ASISTENCIAS Y REPORTES
// ==========================================
Route::prefix('asistencias')->group(function () {
    Route::get('/hoy', [AsistenciaController::class, 'registrosHoy']); // Unificado
    Route::get('/reportes', [AsistenciaController::class, 'reportes']);
    Route::get('/dashboard/metricas', [AsistenciaController::class, 'dashboardMetricas']);
    Route::post('/registrar', [AsistenciaController::class, 'registrar']);
    Route::post('/qr', [AsistenciaController::class, 'registrarQR']);
});

// Reportes globales
Route::prefix('reportes')->group(function () {
    Route::get('/general/pdf', [AsistenciaController::class, 'exportarPDF']);
    Route::get('/general/excel', [AsistenciaController::class, 'exportarExcel']);
    Route::get('/detallado/pdf', [AsistenciaController::class, 'exportarDetalladoPDF']);
    Route::get('/detallado/excel', [AsistenciaController::class, 'exportarDetalladoExcel']);
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
// 🏭 CENTRO DE OPERACIONES (PLANTA)
// ==========================================
Route::prefix('operaciones')->group(function () {
    Route::get('/lotes/activo', [OperacionesController::class, 'getLoteActivo']);
    Route::post('/lotes', [OperacionesController::class, 'iniciarLote']);
    Route::post('/muestreos', [OperacionesController::class, 'registrarMuestreo']);
    Route::post('/pesajes', [OperacionesController::class, 'registrarPesaje']);
    Route::get('/lotes/{id}/metricas', [OperacionesController::class, 'metricasEnVivo']);
});

// ==========================================
// 📱 KIOSCO (TABLET OPERARIOS)
// ==========================================
Route::prefix('kiosco')->group(function () {
    Route::post('/pesajes/sincronizar', [OperacionesController::class, 'sincronizarPesajes']);
    Route::delete('/pesajes/deshacer/{id}', [OperacionesController::class, 'deshacerPesaje']);
});

// ==========================================
// ⚙️ UTILIDADES DEL SISTEMA
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