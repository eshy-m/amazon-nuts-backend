<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 📥 IMPORTACIÓN DE CONTROLADORES
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
| API Routes - Amazon Nuts System
|--------------------------------------------------------------------------
*/

// ============================================================
// 🌐 1. RUTAS PÚBLICAS Y AUTENTICACIÓN
// ============================================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/pages/{slug}', [PageContentController::class, 'getPageBySlug']);


// ============================================================
// 👨‍🔧 2. RECURSOS HUMANOS (RRHH)
// ============================================================
Route::prefix('trabajadores')->group(function () {
    Route::get('/', [TrabajadorController::class, 'index']);
    Route::post('/', [TrabajadorController::class, 'store']);
    Route::get('/estadisticas', [TrabajadorController::class, 'estadisticas']);
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


// ============================================================
// 📊 3. DASHBOARD Y DATOS MAESTROS
// ============================================================
Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

Route::prefix('maestros')->group(function () {
    Route::get('/', [MaestrosController::class, 'index']);
    Route::get('/areas', [MaestrosController::class, 'getAreas']);
    Route::get('/cargos', [MaestrosController::class, 'getCargos']);
});


// ============================================================
// 🏭 4. OPERACIONES Y PRODUCCIÓN (Selección)
// ============================================================
Route::prefix('operaciones')->group(function () {
    // Gestión de Lotes
    Route::get('/lote-activo', [OperacionesController::class, 'getLoteActivo']); // Para Tablet
    Route::post('/lote', [OperacionesController::class, 'iniciarLote']);         // Iniciar jornada
    Route::put('/lote/{id}/cerrar', [OperacionesController::class, 'cerrarLote']); // Cerrar selección

    // Producción y Calidad
    Route::get('/metricas', [OperacionesController::class, 'getMetricas']);
    Route::post('/muestreos', [OperacionesController::class, 'registrarMuestreo']);
    Route::post('/pesajes', [OperacionesController::class, 'registrarPesaje']);

    // Rutas puente: Flujo de Selección hacia Secado
    Route::post('/enviar-a-secado/{id}', [OperacionesController::class, 'enviarASecado']);
    Route::get('/lote-activo-secado', [OperacionesController::class, 'getLoteActivo_Secado']);
});

// ✅ COMODINES PARA COMPATIBILIDAD (Si Angular usa rutas sin prefijo 'operaciones')
Route::get('/lotes/activo', [OperacionesController::class, 'getLoteActivo']);
Route::post('/pesajes', [OperacionesController::class, 'registrarPesaje']);


// ============================================================
// 📱 5. TERMINALES / KIOSCO (Funciones extra)
// ============================================================
Route::prefix('terminales')->group(function () {
    Route::post('/pesajes/sincronizar', [OperacionesController::class, 'sincronizarPesajes']);
    Route::delete('/pesajes/deshacer/{id}', [OperacionesController::class, 'deshacerPesaje']);
});


// ============================================================
// 🔥 6. ÁREA DE SECADO (Hornos)
// ============================================================
Route::prefix('secado')->group(function () {
    // Obtener hornos encendidos y lote en espera
    Route::get('/procesos-activos', [SecadoController::class, 'getProcesosActivos']);
    
    // Control de procesos
    Route::post('/iniciar', [SecadoController::class, 'iniciarSecado']);           // Meter al horno
    Route::put('/finalizar/{id}', [SecadoController::class, 'finalizarSecado']);    // Sacar del horno
    Route::put('/lote/{id}/cerrar', [SecadoController::class, 'cerrarLoteTotalmente']); // Fin de secado
});


// ============================================================
// 🛠️ 7. UTILIDADES DE MANTENIMIENTO
// ============================================================
Route::get('/limpiar-todo', function () {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    return response()->json(['message' => 'Caché y rutas limpiadas con éxito']);
});

Route::get('/generar-storage-link', function () {
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    return response()->json(['message' => 'Enlace simbólico de almacenamiento creado']);
});