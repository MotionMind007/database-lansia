<?php

use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\DocumentController;
use App\Http\Controllers\App\ExportController;
use App\Http\Controllers\App\LansiaController;
use App\Http\Controllers\App\SurveyController;
use App\Http\Controllers\App\VerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;

// Welcome page (publik)
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Auth routes (hanya untuk guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});

// Logout
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ═══════════════════════════════════════
// FRONTEND APP (authenticated users)
// ═══════════════════════════════════════
Route::middleware('auth')->prefix('app')->name('app.')->group(function () {

    // Dashboard (semua role)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

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

    // Export (Administrator + Surveyor)
    Route::get('/export', [ExportController::class, 'export'])
        ->middleware(CheckRole::class . ':administrator,surveyor')
        ->name('export');

    // AJAX: Wilayah cascade
    Route::get('/wilayah/districts', [SurveyController::class, 'getDistricts'])->name('wilayah.districts');
    Route::get('/wilayah/villages', [SurveyController::class, 'getVillages'])->name('wilayah.villages');
});

// Legacy redirect
Route::get('/dashboard', function () {
    return redirect()->route('app.dashboard');
})->middleware('auth');
