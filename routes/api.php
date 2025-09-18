<?php

use App\Http\Controllers\ActivityController;
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
});
