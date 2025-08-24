<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\Pet;
use App\Models\OwnerMobile;
use App\Models\Species;
use App\Models\Breed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    /**
     * Show the patient intake search form
     */
    public function intake()
    {
        return view('patient.intake');
    }

    /**
     * Search for patient by mobile or UID
     */
    public function search(Request $request)
    {
        $request->validate([
            'search' => 'required|string|min:6|max:10'
        ]);

        $search = preg_replace('/[^0-9]/', '', $request->search);

        // Validate input length
        if (strlen($search) === 6) {
            return $this->searchByUid($search);
        } elseif (strlen($search) === 10) {
            return $this->searchByMobile($search);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Please enter exactly 6 digits for UID or exactly 10 digits for mobile number'
            ]);
        }
    }

    /**
     * Search by UID (6 digits)
     */
    private function searchByUid(string $uid)
    {
        $pet = Pet::findByUid($uid);

        if (!$pet) {
            return response()->json([
                'success' => false,
                'message' => "Pet with UID {$uid} not found",
                'action' => 'not_found',
                'search_value' => $uid
            ]);
        }

        // Get all pets from same owner (siblings)
        $allPets = Pet::where('owner_id', $pet->owner_id)
                      ->active()
                      ->with(['species', 'breed'])
                      ->get();

        return response()->json([
            'success' => true,
            'message' => 'Pet found',
            'action' => 'pet_found',
            'pet' => $pet,
            'siblings' => $allPets->where('id', '!=', $pet->id)->values(),
            'owner' => $pet->owner,
            'all_mobiles' => $pet->owner->all_mobile_numbers,
            'has_duplicates' => $pet->isDuplicate() || $pet->hasDuplicates()
        ]);
    }

    /**
     * Search by Mobile (10 digits)
     */
    private function searchByMobile(string $mobile)
    {
        // Validate mobile number format
        if (!OwnerMobile::validateMobile($mobile)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9'
            ]);
        }

        $normalizedMobile = OwnerMobile::normalizeMobile($mobile);
        $pets = Owner::findPetsByMobile($normalizedMobile);

        if ($pets->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "No pets found for mobile number {$mobile}",
                'action' => 'not_found',
                'search_value' => $mobile
            ]);
        }

        // If only one pet found, return it directly
        if ($pets->count() === 1) {
            $pet = $pets->first();
            return response()->json([
                'success' => true,
                'message' => 'Pet found',
                'action' => 'single_pet_found',
                'pet' => $pet,
                'owner' => $pet->owner,
                'all_mobiles' => $pet->owner->all_mobile_numbers,
                'has_duplicates' => $pet->isDuplicate() || $pet->hasDuplicates()
            ]);
        }

        // Multiple pets found, show selection interface
        return response()->json([
            'success' => true,
            'message' => 'Multiple pets found for this mobile number',
            'action' => 'multiple_pets_found',
            'pets' => $pets,
            'mobile' => $mobile,
            'owner_name' => $pets->first()->owner->name
        ]);
    }

    /**
     * Create provisional patient record
     */
    public function createProvisional(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10'
        ]);

        $mobile = $request->mobile;

        // Validate mobile number
        if (!OwnerMobile::validateMobile($mobile)) {
            throw ValidationException::withMessages([
                'mobile' => 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9'
            ]);
        }

        // Check if mobile already exists
        $existingPets = Owner::findPetsByMobile($mobile);
        if ($existingPets->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'This mobile number already exists in the system. Please search instead.'
            ]);
        }

        return DB::transaction(function () use ($mobile) {
            // Generate new UID
            $uid = Pet::generateUniqueId();

            // Create provisional owner
            $owner = Owner::create([
                'name' => 'Incomplete Owner',
                'status' => 'active',
                'created_via' => 'provisional',
                'is_complete' => false
            ]);

            // Add mobile number
            OwnerMobile::create([
                'owner_id' => $owner->id,
                'mobile' => $mobile,
                'is_primary' => true,
                'is_whatsapp' => false,
                'is_verified' => false
            ]);

            // Get default species and breed for provisional pet
            $defaultSpecies = Species::where('name', 'Canine')->first();
            $defaultBreed = Breed::where('species_id', $defaultSpecies->id)
                                 ->where('name', 'Mixed Breed')
                                 ->first();

            // Create provisional pet
            $pet = Pet::create([
                'unique_id' => $uid,
                'owner_id' => $owner->id,
                'name' => 'Incomplete Pet',
                'species_id' => $defaultSpecies->id,
                'breed_id' => $defaultBreed->id,
                'gender' => 'male', // Default value
                'status' => 'active',
                'created_via' => 'provisional',
                'is_complete' => false
            ]);

            // Generate QR and Barcode
            $qrCode = $this->generateQRCode($uid);
            $barcode = $this->generateBarcode($uid);

            return response()->json([
                'success' => true,
                'message' => 'Provisional patient created successfully',
                'action' => 'provisional_created',
                'uid' => $uid,
                'pet' => $pet->load(['owner', 'species', 'breed']),
                'owner' => $owner,
                'mobile' => $mobile,
                'qr_code' => $qrCode,
                'barcode' => $barcode
            ]);
        });
    }

    /**
     * Show letterhead for a pet
     */
    public function letterhead(string $uid)
    {
        $pet = Pet::findByUid($uid);

        if (!$pet) {
            abort(404, 'Pet not found');
        }

        // Generate QR and Barcode
        $qrCode = $this->generateQRCode($uid);
        $barcode = $this->generateBarcode($uid);

        return view('patient.letterhead', compact('pet', 'qrCode', 'barcode'));
    }

    /**
     * Show pet selection interface for multi-pet families
     */
    public function selectPet(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10'
        ]);

        $pets = Owner::findPetsByMobile($request->mobile);

        if ($pets->isEmpty()) {
            return redirect()->route('patient.intake')->with('error', 'No pets found for this mobile number');
        }

        return view('patient.select-pet', compact('pets'));
    }

    /**
     * Generate QR Code for UID
     */
    private function generateQRCode(string $uid): string
    {
        try {
            // Use QR code generation service (assuming existing implementation)
            $url = url("/media/qr-uid?uid={$uid}");
            $imageData = @file_get_contents($url);
            
            if ($imageData) {
                return base64_encode($imageData);
            }
        } catch (\Exception $e) {
            // Fallback implementation if service fails
        }
        
        return $this->createPlaceholderImage("QR\n{$uid}");
    }

    /**
     * Generate Barcode for UID
     */
    private function generateBarcode(string $uid): string
    {
        try {
            // Use barcode generation service (assuming existing implementation)
            $url = url("/media/barcode-uid?uid={$uid}");
            $imageData = @file_get_contents($url);
            
            if ($imageData) {
                return base64_encode($imageData);
            }
        } catch (\Exception $e) {
            // Fallback implementation if service fails
        }
        
        return $this->createPlaceholderImage($uid);
    }

    /**
     * Create placeholder image when QR/Barcode generation fails
     */
    private function createPlaceholderImage(string $text): string
    {
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

    /**
     * Add new pet to existing family
     */
    public function addPetToFamily(Request $request)
    {
        $request->validate([
            'owner_id' => 'required|exists:owners,id'
        ]);

        $owner = Owner::findOrFail($request->owner_id);

        return DB::transaction(function () use ($owner) {
            // Generate new UID for the additional pet
            $uid = Pet::generateUniqueId();

            // Get default species and breed
            $defaultSpecies = Species::where('name', 'Canine')->first();
            $defaultBreed = Breed::where('species_id', $defaultSpecies->id)
                                 ->where('name', 'Mixed Breed')
                                 ->first();

            // Create new pet for existing owner
            $pet = Pet::create([
                'unique_id' => $uid,
                'owner_id' => $owner->id,
                'name' => 'Additional Pet',
                'species_id' => $defaultSpecies->id,
                'breed_id' => $defaultBreed->id,
                'gender' => 'male',
                'status' => 'active',
                'created_via' => 'provisional',
                'is_complete' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'New pet added to family',
                'action' => 'pet_added',
                'uid' => $uid,
                'pet' => $pet->load(['owner', 'species', 'breed'])
            ]);
        });
    }
}