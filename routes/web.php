<?php

use Illuminate\Support\Facades\Route;

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
    return 'Laravel is working! <a href="/admin">Go to Admin</a>';
});

// Fallback login route for Laravel (required for some middleware)
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');