<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\OwnerMobile;
use App\Models\Pet;
use App\Models\Species;
use App\Models\Breed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PatientController extends Controller
{
    public function intake(): View
    {
        return view('patient.intake');
    }

    public function search(Request $request)
    {
        $input = $request->input('search', '');
        $input = trim($input);

        if (empty($input)) {
            return back()->with('error', 'Please enter a mobile number or unique ID');
        }

        // Determine if input is UID (6 digits) or mobile
        if (preg_match('/^\d{6}$/', $input)) {
            return $this->searchByUID($input);
        } else {
            return $this->searchByMobile($input);
        }
    }

    private function searchByUID(string $uid)
    {
        $pet = Pet::where('unique_id', $uid)
            ->with(['owner.mobiles', 'species', 'breed'])
            ->first();

        if (!$pet) {
            return view('patient.not-found', [
                'search_input' => $uid,
                'search_type' => 'uid'
            ]);
        }

        // Get all pets for the same owner (siblings)
        $allPets = Pet::where('owner_id', $pet->owner_id)
            ->with(['species', 'breed'])
            ->get();

        return view('patient.found', [
            'search_type' => 'uid',
            'matched_pet' => $pet,
            'owner' => $pet->owner,
            'all_pets' => $allPets,
        ]);
    }

    private function searchByMobile(string $mobile)
    {
        // Normalize mobile number
        $normalizedMobile = OwnerMobile::normalizeMobile($mobile);
        
        // Find owner by mobile
        $owner = Owner::whereHas('mobiles', function ($query) use ($normalizedMobile) {
            $query->where('mobile_e164', $normalizedMobile);
        })->with(['mobiles', 'pets.species', 'pets.breed'])->first();

        if (!$owner) {
            return view('patient.not-found', [
                'search_input' => $mobile,
                'search_type' => 'mobile'
            ]);
        }

        return view('patient.found', [
            'search_type' => 'mobile',
            'matched_pet' => null,
            'owner' => $owner,
            'all_pets' => $owner->pets,
        ]);
    }

    public function createProvisional(Request $request): RedirectResponse
    {
        $request->validate([
            'mobile' => 'required|string|min:10',
        ]);

        try {
            DB::beginTransaction();

            $mobile = OwnerMobile::normalizeMobile($request->mobile);

            // Generate unique ID
            $uniqueId = $this->generateUniqueId();

            // Create provisional owner
            $owner = Owner::create([
                'name' => 'Unknown Owner',
                'status' => 'active',
                'created_via' => 'provisional',
            ]);

            // Add mobile number
            OwnerMobile::create([
                'owner_id' => $owner->id,
                'mobile' => $request->mobile,
                'mobile_e164' => $mobile,
                'is_primary' => true,
            ]);

            // Create provisional pet
            $pet = Pet::create([
                'unique_id' => $uniqueId,
                'owner_id' => $owner->id,
                'name' => 'Unknown Pet',
                'species_id' => 1, // Default to Canine
                'breed_id' => 13, // Default to Mixed Breed
                'gender' => 'male', // Default
                'status' => 'active',
            ]);

            DB::commit();

            return redirect()->route('patient.letterhead', ['uid' => $uniqueId])
                ->with('success', 'Provisional record created successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Failed to create provisional record. Please try again.');
        }
    }

    public function letterhead(string $uid): View
    {
        $pet = Pet::where('unique_id', $uid)
            ->with(['owner.mobiles', 'species', 'breed'])
            ->firstOrFail();

        // Generate QR and Barcode (base64 encoded)
        $qrCode = $this->generateQRCode($uid);
        $barcode = $this->generateBarcode($uid);

        return view('patient.letterhead', [
            'pet' => $pet,
            'qrCode' => $qrCode,
            'barcode' => $barcode,
        ]);
    }

    private function generateQRCode(string $uid): string
    {
        // Simple QR code generation - you can enhance this
        $data = "UID:{$uid}";
        $size = 150;
        
        // Using Google Charts API as fallback
        $url = "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl=" . urlencode($data);
        
        // Get the image and convert to base64
        $imageData = @file_get_contents($url);
        if ($imageData) {
            return base64_encode($imageData);
        }
        
        // Fallback: create a simple placeholder
        return $this->createPlaceholderImage("QR\n{$uid}");
    }

    private function generateBarcode(string $uid): string
    {
        // Simple barcode generation - you can enhance this with a proper library
        // For now, using Google Charts API
        $url = "https://chart.googleapis.com/chart?chs=200x50&cht=qr&chl={$uid}";
        
        $imageData = @file_get_contents($url);
        if ($imageData) {
            return base64_encode($imageData);
        }
        
        // Fallback
        return $this->createPlaceholderImage($uid);
    }

    private function createPlaceholderImage(string $text): string
    {
        // Create a simple text-based placeholder image
        $width = 200;
        $height = 100;
        $image = imagecreate($width, $height);
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        imagefill($image, 0, 0, $white);
        imagestring($image, 5, 50, 40, $text, $black);
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);
        
        return base64_encode($imageData);
    }
}