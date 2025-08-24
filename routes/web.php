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

// QR Code endpoint - FIXED
Route::get('/media/qr-uid', function () {
    $uid = request('uid');
    if (!$uid) {
        abort(400, 'UID required');
    }
    
    try {
        // Use your UidGenerator service
        $qrCodeBase64 = \App\Services\UidGenerator::generateQrCode($uid);
        $qrCodeBinary = base64_decode($qrCodeBase64);
        
        return response($qrCodeBinary)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Content-Disposition', 'inline; filename="qr-' . $uid . '.png"');
        
    } catch (Exception $e) {
        // Emergency fallback - create simple placeholder
        $width = 200;
        $height = 200;
        $image = imagecreate($width, $height);
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $gray = imagecolorallocate($image, 128, 128, 128);
        
        imagefill($image, 0, 0, $white);
        
        // Draw border
        imagerectangle($image, 5, 5, $width-6, $height-6, $black);
        imagerectangle($image, 10, 10, $width-11, $height-11, $gray);
        
        // Add text
        imagestring($image, 5, 80, 85, 'QR', $black);
        imagestring($image, 4, 70, 105, $uid, $black);
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);
        
        return response($imageData)
            ->header('Content-Type', 'image/png');
    }
})->name('media.qr');

// Barcode endpoint - FIXED
Route::get('/media/barcode-uid', function () {
    $uid = request('uid');
    if (!$uid) {
        abort(400, 'UID required');
    }
    
    try {
        // Use your UidGenerator service
        $barcodeBase64 = \App\Services\UidGenerator::generateBarcode($uid);
        $barcodeBinary = base64_decode($barcodeBase64);
        
        return response($barcodeBinary)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Content-Disposition', 'inline; filename="barcode-' . $uid . '.png"');
        
    } catch (Exception $e) {
        // Emergency fallback
        $width = 300;
        $height = 60;
        $image = imagecreate($width, $height);
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        imagefill($image, 0, 0, $white);
        
        // Simple barcode pattern
        for ($i = 0; $i < 50; $i++) {
            $x = 20 + ($i * 4);
            if ($i % 3 != 0) {
                imagefilledrectangle($image, $x, 10, $x + 2, 40, $black);
            }
        }
        
        // Add UID text
        imagestring($image, 3, ($width - strlen($uid) * 10) / 2, 45, $uid, $black);
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);
        
        return response($imageData)
            ->header('Content-Type', 'image/png');
    }
})->name('media.barcode');

// Test routes to verify QR and Barcode generation
Route::get('/test/qr/{uid}', function ($uid) {
    echo '<h2>QR Code Test for UID: ' . $uid . '</h2>';
    echo '<img src="/media/qr-uid?uid=' . $uid . '" alt="QR Code" style="border: 1px solid #ccc;">';
    echo '<br><br>';
    echo '<a href="/media/qr-uid?uid=' . $uid . '" target="_blank">Direct QR Link</a>';
})->where('uid', '[0-9]+');

Route::get('/test/barcode/{uid}', function ($uid) {
    echo '<h2>Barcode Test for UID: ' . $uid . '</h2>';
    echo '<img src="/media/barcode-uid?uid=' . $uid . '" alt="Barcode" style="border: 1px solid #ccc;">';
    echo '<br><br>';
    echo '<a href="/media/barcode-uid?uid=' . $uid . '" target="_blank">Direct Barcode Link</a>';
})->where('uid', '[0-9]+');

// Fallback login route for Laravel (required for some middleware)
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');