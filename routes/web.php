<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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

// Basic auth routes that Filament expects
Auth::routes();

Route::get('/', function () {
    return redirect('/admin');
});

// Test route to check if Laravel is working
Route::get('/test', function () {
    return 'Laravel is working!';
});