<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehiculeController;
use App\Http\Controllers\Api\ExcursionController;

// Routes PUBLIQUES (sans token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/vehicules', [VehiculeController::class, 'index']);
Route::get('/vehicules/{vehicule}', [VehiculeController::class, 'show']);
Route::get('/excursions', [ExcursionController::class, 'index']);
Route::get('/excursions/{excursion}', [ExcursionController::class, 'show']);

// Routes PROTÉGÉES (avec token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Routes pour les véhicules (Actions d'écriture nécessitent un token)
    Route::post('/vehicules', [VehiculeController::class, 'store']);
    Route::put('/vehicules/{vehicule}', [VehiculeController::class, 'update']);
    Route::delete('/vehicules/{vehicule}', [VehiculeController::class, 'destroy']);

    // Routes pour les excursions (Actions d'écriture nécessitent un token)
    Route::post('/excursions', [ExcursionController::class, 'store']);
    Route::put('/excursions/{excursion}', [ExcursionController::class, 'update']);
    Route::delete('/excursions/{excursion}', [ExcursionController::class, 'destroy']);
});


