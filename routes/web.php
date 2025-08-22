<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/admin');
});

// Test route
Route::get('/test', function () {
    return 'Laravel is working! <a href="/admin">Go to Admin</a> | <a href="/patient/intake">Patient Intake</a>';
});

// Patient Intake Routes
Route::get('/patient/intake', [PatientController::class, 'intake'])->name('patient.intake');
Route::post('/patient/search', [PatientController::class, 'search'])->name('patient.search');
Route::post('/patient/create-provisional', [PatientController::class, 'createProvisional'])->name('patient.create-provisional');
Route::get('/patient/letterhead/{uid}', [PatientController::class, 'letterhead'])->name('patient.letterhead');

// Fallback login route for Laravel (required for some middleware)
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');