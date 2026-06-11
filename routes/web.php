<?php

use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\DocumentController;
use App\Http\Controllers\App\ExportController;
use App\Http\Controllers\App\ActivityLogController;
use App\Http\Controllers\App\LansiaController;
use App\Http\Controllers\App\SurveyController;
use App\Http\Controllers\App\VerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HealthController;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

// Health check (for load balancers / uptime monitors)
Route::get('/health', HealthController::class)->name('health');

// Welcome page (publik)
Route::get('/', function () {
    $stats = Cache::remember('welcome.stats', now()->addMinutes(15), function () {
        try {
            return [
                'lansia_count' => \App\Models\SurveyResponse::where('status', 'verified')->count(),
                'village_count' => \App\Models\SurveyResponse::distinct('region_id')->count('region_id'),
                'surveyor_count' => \App\Models\User::role('surveyor')->where('is_active', true)->count(),
            ];
        } catch (\Throwable) {
            return [
                'lansia_count' => 0,
                'village_count' => 0,
                'surveyor_count' => 0,
            ];
        }
    });

    return view('welcome', $stats);
})->name('home');

// Auth routes (hanya untuk guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware(['throttle:5,1', \App\Http\Middleware\LoginThrottle::class]);
});

// Logout
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ═══════════════════════════════════════
// FRONTEND APP (authenticated users)
// ═══════════════════════════════════════
Route::middleware(['auth', 'throttle:120,1'])->prefix('app')->name('app.')->group(function () {

    // Dashboard (semua role) — rate limited lebih ketat karena heavy query
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('throttle:30,1')
        ->name('dashboard');

    // Data Lansia (semua role — scoping per role di controller)
    Route::get('/lansia', [LansiaController::class, 'index'])->name('lansia.index');
    Route::get('/lansia/{id}', [LansiaController::class, 'show'])->name('lansia.show');

    // Input Survey (Administrator + Surveyor only)
    Route::middleware(CheckRole::class . ':administrator,surveyor')->group(function () {
        Route::get('/survey/create', [SurveyController::class, 'create'])->name('survey.create');
        Route::post('/survey', [SurveyController::class, 'store'])->name('survey.store');
        Route::get('/survey/{id}/edit', [SurveyController::class, 'edit'])->name('survey.edit');
        Route::put('/survey/{id}', [SurveyController::class, 'update'])->name('survey.update');
    });

    // Verifikasi (Administrator + Verifikator only)
    Route::middleware(CheckRole::class . ':administrator,verifikator')->prefix('verification')->name('verification.')->group(function () {
        Route::get('/', [VerificationController::class, 'index'])->name('index');
        Route::get('/{id}', [VerificationController::class, 'show'])->name('show');
        Route::post('/{id}/verify', [VerificationController::class, 'verify'])->name('verify');
    });

    // Dokumen pendukung (authorized file serving)
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/respondents/{respondent}/photo', [DocumentController::class, 'photo'])->name('respondents.photo');

    // Export (Administrator + Surveyor)
    Route::get('/export', [ExportController::class, 'export'])
        ->middleware([CheckRole::class . ':administrator,surveyor', 'throttle:10,1'])
        ->name('export');

    Route::get('/export/download', [ExportController::class, 'download'])
        ->name('export.download');

    // Admin tools
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware(CheckRole::class . ':administrator,super admin,super_admin')
        ->name('activity-logs.index');

    // AJAX: Wilayah cascade
    Route::get('/wilayah/districts', [SurveyController::class, 'getDistricts'])->name('wilayah.districts');
    Route::get('/wilayah/villages', [SurveyController::class, 'getVillages'])->name('wilayah.villages');
    Route::get('/wilayah/villages/search', [SurveyController::class, 'searchVillages'])->name('wilayah.villages.search');
});

// Legacy redirect
Route::get('/dashboard', function () {
    return redirect()->route('app.dashboard');
})->middleware('auth');
