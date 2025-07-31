<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\api\AuthController;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\api\ObraController;
use App\Http\Controllers\AreasController;

// Ruta de prueba
Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
});

// Áreas (CRUD básico)
Route::apiResource('areas', AreasController::class);

// Autenticación y usuarios
Route::post('/register', [AuthController::class, 'register']);           // Registro de usuario normal
Route::post('/artist-register', [AuthController::class, 'artistRegister']); // Autoregistro de artista
Route::post('/login', [AuthController::class, 'login']);                 // Login

// Rutas protegidas (requieren token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);                // Obtener usuario autenticado
    Route::get('/admin-only', [AuthController::class, 'adminOnly']);     // Solo para admin

    //RUTAS PARA LA GESTIÓN DE OBRAS
    Route::post('/obras', [ObraController::class, 'store']); // Artista sube obra
    Route::get('/obras', [ObraController::class, 'index']); // Listar obras (admin o artista)
    
    // ✅ PRIMERO las rutas específicas
    Route::get('/obras/pendientes', [ObraController::class, 'pendientes']); // Admin ve pendientes
    Route::get('/obras/aceptadas', [ObraController::class, 'aceptadas']);
    
    // ✅ DESPUÉS las rutas con parámetros
    Route::get('/obras/{id}', [ObraController::class, 'show']); // Ver obra
    Route::put('/obras/{id}', [ObraController::class, 'update']); // Admin acepta/rechaza

    // Aquí puedes agregar más rutas protegidas
    Route::apiResource('users', UserController::class);                  // CRUD de usuarios
    Route::post('/logout', [AuthController::class, 'logout']);            // Logout
});