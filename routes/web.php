<?php
// routes/web.php - Updated for complete intake workflow

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\AdminController;

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

// Patient Intake Routes (Admin-authenticated but simplified UI)
Route::middleware(['auth'])->group(function () {
    // Main intake interface
    Route::get('/patient/intake', [PatientController::class, 'intake'])->name('patient.intake');
    
    // Search functionality
    Route::post('/patient/search', [PatientController::class, 'search'])->name('patient.search');
    
    // Provisional patient creation
    Route::post('/patient/create-provisional', [PatientController::class, 'createProvisional'])->name('patient.create-provisional');
    
    // Pet selection for multi-pet families
    Route::get('/patient/select-pet', [PatientController::class, 'selectPet'])->name('patient.select-pet');
    
    // Add new pet to existing family
    Route::post('/patient/add-pet-to-family', [PatientController::class, 'addPetToFamily'])->name('patient.add-pet-to-family');
    
    // Letterhead generation
    Route::get('/patient/letterhead/{uid}', [PatientController::class, 'letterhead'])->name('patient.letterhead');
    
    // Admin completion interfaces
    Route::prefix('admin/incomplete')->name('admin.incomplete.')->group(function () {
        Route::get('/', [AdminController::class, 'incompleteProfiles'])->name('index');
        Route::get('/{id}', [AdminController::class, 'editIncompleteProfile'])->name('edit');
        Route::put('/{id}', [AdminController::class, 'updateIncompleteProfile'])->name('update');
        Route::post('/{id}/complete', [AdminController::class, 'markComplete'])->name('complete');
    });
    
    // Duplicate management routes
    Route::prefix('admin/duplicates')->name('admin.duplicates.')->group(function () {
        Route::get('/', [AdminController::class, 'duplicateManagement'])->name('index');
        Route::post('/mark', [AdminController::class, 'markDuplicate'])->name('mark');
        Route::post('/unmark', [AdminController::class, 'unmarkDuplicate'])->name('unmark');
        Route::get('/compare/{uid1}/{uid2}', [AdminController::class, 'comparePets'])->name('compare');
    });
});

// Replace your existing /media/qr-uid route with this:

Route::get('/media/qr-uid', function () {
    $uid = request('uid');
    if (!$uid) {
        abort(400, 'UID required');
    }
    
    try {
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(200)
            ->margin(2)
            ->generate($uid);
        
        return response($qrCode)->header('Content-Type', 'image/png');
        
    } catch (Exception $e) {
        // Create simple placeholder on failure
        $width = 200;
        $height = 200;
        $image = imagecreate($width, $height);
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        imagefill($image, 0, 0, $white);
        imagestring($image, 5, 70, 80, 'QR', $black);
        imagestring($image, 4, 60, 100, $uid, $black);
        imagerectangle($image, 10, 10, 190, 190, $black);
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);
        
        return response($imageData)->header('Content-Type', 'image/png');
    }
});

Route::get('/media/barcode-uid', function () {
    $uid = request('uid');
    if (!$uid) abort(400, 'UID required');
    
    // Generate barcode using existing library
    try {
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $barcode = $generator->getBarcode($uid, $generator::TYPE_CODE_128, 3, 50);
        
        header('Content-Type: image/png');
        echo $barcode;
        exit;
    } catch (Exception $e) {
        abort(500, 'Barcode generation failed');
    }
});

// Fallback login route for Laravel (required for some middleware)
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');