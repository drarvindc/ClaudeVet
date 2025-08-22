<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientIntakeController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SetupController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', function () {
    return redirect('/admin');
});

// Setup route (first time only)
Route::get('/setup', [SetupController::class, 'index'])->name('setup');
Route::post('/setup', [SetupController::class, 'install'])->name('setup.install');

// Patient Intake (public access for reception)
Route::prefix('patient')->name('patient.')->group(function () {
    Route::get('/intake', [PatientIntakeController::class, 'index'])->name('intake');
    Route::post('/search', [PatientIntakeController::class, 'search'])->name('search');
    Route::post('/provisional', [PatientIntakeController::class, 'createProvisional'])->name('provisional');
    Route::get('/letterhead/{pet}', [PatientIntakeController::class, 'printLetterhead'])->name('letterhead');
});

// QR and Barcode generation endpoints
Route::get('/qr/{uid}', [DocumentController::class, 'generateQr'])->name('qr');
Route::get('/barcode/{uid}', [DocumentController::class, 'generateBarcode'])->name('barcode');

// Visit management (requires auth)
Route::middleware(['auth'])->prefix('visit')->name('visit.')->group(function () {
    Route::get('/today', [VisitController::class, 'today'])->name('today');
    Route::get('/{visit}', [VisitController::class, 'show'])->name('show');
    Route::post('/{visit}/close', [VisitController::class, 'close'])->name('close');
    Route::post('/{visit}/upload', [VisitController::class, 'upload'])->name('upload');
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'version' => config('app.version', '1.0.0')
    ]);
});