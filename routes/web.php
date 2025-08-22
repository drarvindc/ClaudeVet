<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/admin');
});

// Test route to check if Laravel is working
Route::get('/test', function () {
    return 'Laravel is working!';
});

// Patient intake routes (for your vet system)
Route::get('/patient/intake', function () {
    return view('patient.intake');
})->name('patient.intake');

// Basic login route for Filament (if needed)
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');