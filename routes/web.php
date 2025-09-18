<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard Routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/activities', [DashboardController::class, 'getActivities'])->name('dashboard.activities');
    Route::get('/dashboard/handover', [DashboardController::class, 'handover'])->name('dashboard.handover');
    Route::get('/dashboard/updates', [DashboardController::class, 'getUpdates'])->name('dashboard.updates');
    
    // Activity Routes
    Route::resource('activities', ActivityController::class);
    Route::post('activities/{activity}/status', [ActivityController::class, 'updateStatus'])
        ->name('activities.update-status');
    
    // Report Routes
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/generate', [App\Http\Controllers\ReportController::class, 'generate'])->name('reports.generate');
    Route::get('/reports/trends', [App\Http\Controllers\ReportController::class, 'trends'])->name('reports.trends');
    Route::get('/reports/department-stats', [App\Http\Controllers\ReportController::class, 'departmentStats'])->name('reports.department-stats');
    Route::post('/reports/export', [App\Http\Controllers\ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/summary', [App\Http\Controllers\ReportController::class, 'summary'])->name('reports.summary');
});

Route::get('/test', function () {
    return view('test');
})->name('test');
