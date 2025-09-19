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

// Authentication Routes with Rate Limiting
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:5,15'); // 5 attempts per 15 minutes
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

    // Report Routes with Rate Limiting
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/generate', [App\Http\Controllers\ReportController::class, 'generate'])
        ->middleware('throttle:10,60') // 10 reports per hour
        ->name('reports.generate');
    Route::get('/reports/trends', [App\Http\Controllers\ReportController::class, 'trends'])->name('reports.trends');
    Route::get('/reports/department-stats', [App\Http\Controllers\ReportController::class, 'departmentStats'])->name('reports.department-stats');
    Route::post('/reports/export', [App\Http\Controllers\ReportController::class, 'export'])
        ->middleware('throttle:5,60') // 5 exports per hour
        ->name('reports.export');
    Route::get('/reports/summary', [App\Http\Controllers\ReportController::class, 'summary'])->name('reports.summary');

    // Audit Log Routes (Admin/Manager only)
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [App\Http\Controllers\AuditLogController::class, 'index'])->name('index');
        Route::get('/security', [App\Http\Controllers\AuditLogController::class, 'security'])->name('security');
        Route::get('/export', [App\Http\Controllers\AuditLogController::class, 'export'])->name('export');
        Route::get('/user/{user}', [App\Http\Controllers\AuditLogController::class, 'userActivity'])->name('user-activity');
        Route::get('/{auditLog}', [App\Http\Controllers\AuditLogController::class, 'show'])->name('show');
    });
});

Route::get('/test', function () {
    return view('test');
})->name('test');

// Metrics endpoint for Prometheus (no auth required for scraping)
Route::get('/metrics', [App\Http\Controllers\MetricsController::class, 'index'])
    ->name('metrics')
    ->middleware('throttle:60,1'); // Rate limit to prevent abuse
