<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Controllers\Api\DocumentController;

/*
|--------------------------------------------------------------------------
| API Routes for Android App
|--------------------------------------------------------------------------
*/

// API Version 1
Route::prefix('v1')->group(function () {
    
    // Public endpoints
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/health', function () {
        return response()->json(['status' => 'ok', 'version' => '1.0.0']);
    });
    
    // Protected endpoints (require API token)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);
        
        // Patient/Pet
        Route::post('/patient/search', [PatientController::class, 'search']);
        Route::get('/patient/{uid}', [PatientController::class, 'getByUid']);
        Route::post('/patient/provisional', [PatientController::class, 'createProvisional']);
        
        // Visit
        Route::post('/visit/open', [VisitController::class, 'open']);
        Route::post('/visit/ensure', [VisitController::class, 'ensure']);
        Route::get('/visit/today', [VisitController::class, 'today']);
        Route::get('/visit/{uuid}', [VisitController::class, 'show']);
        Route::post('/visit/{uuid}/close', [VisitController::class, 'close']);
        
        // Document Upload
        Route::post('/visit/upload', [DocumentController::class, 'upload']);
        Route::get('/document/{uuid}', [DocumentController::class, 'show']);
        Route::delete('/document/{uuid}', [DocumentController::class, 'delete']);
        
        // Sync
        Route::post('/sync/visits', [VisitController::class, 'syncBatch']);
        Route::post('/sync/documents', [DocumentController::class, 'syncBatch']);
    });
});