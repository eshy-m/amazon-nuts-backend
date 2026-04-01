<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\ContactController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// 🌐 RUTAS PÚBLICAS (No requieren Token)
// ==========================================
//para responder mensajes
Route::get('/messages/{id}/reply', [ContactController::class, 'reply']);
// Autenticación
Route::post('/login', [AuthController::class, 'login']);
//para eliminar
Route::delete('/messages/{id}', [ContactController::class, 'destroy']);
// Contenidos Web
Route::get('/pages/{slug}', [PageContentController::class, 'getPageBySlug']);

// Contacto
Route::post('/contact', [ContactController::class, 'store']);


// ==========================================
// 🔐 RUTAS PROTEGIDAS (Requieren Token de Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // Perfil y Sesión
    Route::get('/user', function (Request $request) { return $request->user(); });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gestión de Contenidos (Panel Admin)
    Route::put('/contents/{id}', [PageContentController::class, 'update']);
    Route::post('/contents/upload-image', [PageContentController::class, 'uploadImage']);

    // Gestión de Mensajes (Panel Admin)
    Route::get('/messages', [ContactController::class, 'index']);
    Route::patch('/messages/{id}/status', [ContactController::class, 'updateStatus']);
    Route::delete('/messages/{id}', [ContactController::class, 'destroy']);
    Route::put('/messages/{id}/status', [ContactController::class, 'updateStatus']);
    
});