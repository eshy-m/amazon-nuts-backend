<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==========================================
// 📥 IMPORTACIÓN DE CONTROLADORES
// ==========================================
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\TurnoPlanificadoController;
use App\Http\Controllers\MaestrosController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OperacionesController;
use App\Http\Controllers\SecadoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// 🌐 1. RUTAS PÚBLICAS Y AUTENTICACIÓN
// ==========================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/pages/{slug}', [PageContentController::class, 'getPageBySlug']);

// ==========================================
// 👨‍🔧 2. RECURSOS HUMANOS (Trabajadores, Asistencia y Turnos)
// ==========================================
Route::prefix('trabajadores')->group(function () {
    Route::get('/estadisticas', [TrabajadorController::class, 'estadisticas']);
    Route::get('/', [TrabajadorController::class, 'index']);
    Route::post('/', [TrabajadorController::class, 'store']);
    Route::put('/{id}', [TrabajadorController::class, 'update']);
    Route::delete('/{id}', [TrabajadorController::class, 'destroy']);
});

Route::prefix('asistencia')->group(function () {
    Route::get('/hoy', [AsistenciaController::class, 'asistenciaHoy']);
    Route::post('/registrar', [AsistenciaController::class, 'registrarAsistencia']);
});

Route::prefix('turnos')->group(function () {
    Route::get('/', [TurnoPlanificadoController::class, 'index']);
    Route::post('/', [TurnoPlanificadoController::class, 'store']);
    Route::put('/{id}', [TurnoPlanificadoController::class, 'update']);
    Route::delete('/{id}', [TurnoPlanificadoController::class, 'destroy']);
});

// ==========================================
// 📊 3. DATOS GENERALES (Dashboard Admin y Maestros)
// ==========================================
Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
Route::get('/maestros', [MaestrosController::class, 'index']);

// ==========================================
// 🏭 CENTRO DE OPERACIONES (PLANTA E INGENIERÍA)
// ==========================================
Route::prefix('operaciones')->group(function () {
    
    // Rutas para el Lote
    Route::get('/lotes/activo', [OperacionesController::class, 'getLoteActivo']); 
    Route::post('/iniciar-lote', [OperacionesController::class, 'iniciarLote']);
    Route::put('/lotes/{id}/cerrar', [OperacionesController::class, 'cerrarLote']);

    // Métricas y Muestreos (Panel Admin)
    Route::get('/metricas', [OperacionesController::class, 'getMetricas']);
    Route::post('/muestreos', [OperacionesController::class, 'registrarMuestreo']);
    
    // Pesajes (Tablet y Admin)
    Route::post('/pesajes', [OperacionesController::class, 'guardarPesaje']);
});

// ✅ COMODÍN PARA LA TABLET (Si tu Angular de la tablet no usa el prefijo /operaciones)
Route::get('/lotes/activo', [OperacionesController::class, 'getLoteActivo']);
Route::post('/pesajes', [OperacionesController::class, 'guardarPesaje']);
// ==========================================
// 📱 5. RUTAS DE LA TABLET (Área de Selección / Kiosco)
// ==========================================
// A. Rutas directas (Sin prefijo, tal como lo pide Angular)
Route::get('/lotes/activo', [OperacionesController::class, 'getLoteActivo']);
Route::post('/pesajes', [OperacionesController::class, 'guardarPesaje']);

// B. Rutas secundarias del Kiosco (Deshacer pesajes y modo offline)
Route::prefix('terminales')->group(function () {
    Route::post('/pesajes/sincronizar', [OperacionesController::class, 'sincronizarPesajes']);
    Route::delete('/pesajes/deshacer/{id}', [OperacionesController::class, 'deshacerPesaje']);
});

// ==========================================
// 🌡️ 6. ÁREA DE SECADO (Hornos)
// ==========================================
Route::prefix('secado')->group(function () {
    Route::get('/activos', [SecadoController::class, 'getProcesosActivos']);
    Route::post('/iniciar', [SecadoController::class, 'iniciarSecado']);
    Route::put('/finalizar/{id}', [SecadoController::class, 'finalizarSecado']);
});

// ==========================================
// 🛠️ 7. UTILIDADES DE MANTENIMIENTO DEL SISTEMA
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
// 🛠️ DATOS MAESTROS (Áreas, Cargos, etc.)
// ==========================================
Route::prefix('maestros')->group(function () {
    Route::get('/areas', [MaestrosController::class, 'getAreas']);
    
    // Si más adelante necesitas cargos, la ruta sería esta:
    Route::get('/cargos', [MaestrosController::class, 'getCargos']);
});
