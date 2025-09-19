<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication Routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/auth/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Activity API Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/activities', [ActivityController::class, 'apiIndex']);
    Route::get('/activities/{activity}', [ActivityController::class, 'apiShow']);
    Route::post('/activities', [ActivityController::class, 'store']);
    Route::put('/activities/{activity}', [ActivityController::class, 'update']);
    Route::post('/activities/{activity}/status', [ActivityController::class, 'updateStatus']);
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy']);
    
    // Dashboard API Routes
    Route::get('/dashboard/daily', [DashboardController::class, 'getActivities']);
    Route::get('/dashboard/updates', [DashboardController::class, 'getUpdates']);
    
    // Reports API Routes
    Route::post('/reports/generate', [ReportController::class, 'generate']);
    Route::get('/reports/trends', [ReportController::class, 'trends']);
    Route::get('/reports/department-stats', [ReportController::class, 'departmentStats']);
    Route::get('/reports/summary', [ReportController::class, 'summary']);
});
