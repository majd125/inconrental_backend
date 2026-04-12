<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehiculeController;
use App\Http\Controllers\Api\ExcursionController;
use App\Http\Controllers\Api\DocumentVehiculeController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\ReservationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

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
    Route::get('/vehicules/{id}', [VehiculeController::class, 'show']);
    Route::put('/vehicules/{id}', [VehiculeController::class, 'update']);
    Route::delete('/vehicules/{id}', [VehiculeController::class, 'destroy']);

    // Gestion des documents des véhicules
    Route::get('/documents/all', [DocumentVehiculeController::class, 'allDocuments']);
    Route::post('/documents/{id}/renew', [DocumentVehiculeController::class, 'renew']);
    Route::get('/vehicules/{vehiculeId}/documents', [DocumentVehiculeController::class, 'index']);
    Route::post('/vehicules/{vehiculeId}/documents', [DocumentVehiculeController::class, 'store']);
    Route::put('/documents/{id}', [DocumentVehiculeController::class, 'update']);
    Route::delete('/documents/{id}', [DocumentVehiculeController::class, 'destroy']);

    // Routes pour les excursions (Actions d'écriture nécessitent un token)
    Route::post('/excursions', [ExcursionController::class, 'store']);
    Route::put('/excursions/{excursion}', [ExcursionController::class, 'update']);
    Route::delete('/excursions/{excursion}', [ExcursionController::class, 'destroy']);
    
    // Gestion de la maintenance des véhicules
    Route::get('/maintenances/all', [MaintenanceController::class, 'allMaintenances']);
    Route::get('/vehicules/{vehiculeId}/maintenances', [MaintenanceController::class, 'index']);
    Route::post('/vehicules/{vehiculeId}/maintenances', [MaintenanceController::class, 'store']);
    Route::post('/maintenances/{id}/renew', [MaintenanceController::class, 'renew']);
    Route::post('/maintenances/{id}/receive', [MaintenanceController::class, 'receive']);
    Route::put('/maintenances/{id}', [MaintenanceController::class, 'update']);
    // Gestion des réservations
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/admin/reservations', [ReservationController::class, 'all']);
    Route::patch('/reservations/{id}/status', [ReservationController::class, 'updateStatus']);
});
