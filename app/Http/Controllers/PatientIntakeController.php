<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use App\Models\Pet;
use App\Models\OwnerMobile;
use App\Services\UidGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class PatientIntakeController extends Controller
{
    /**
     * Show the patient intake search form
     */
    public function index()
    {
        return view('patient.intake');
    }
    
    /**
     * Search for patient by mobile or UID
     */
    public function search(Request $request)
    {
        $request->validate([
            'search' => 'required|string'
        ]);
        
        $search = preg_replace('/[^0-9]/', '', $request->search);
        
        // Check if it's a UID (6 or 7 digits)
        if (strlen($search) === 6 || strlen($search) === 7) {
            return $this->searchByUid($search);
        }
        
        // Check if it's a mobile (10 digits)
        if (strlen($search) === 10) {
            return $this->searchByMobile($search);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Please enter a valid 10-digit mobile number or 6-digit UID'
        ]);
    }
    
    /**
     * Search by UID
     */
    private function searchByUid($uid)
    {
        // Handle both with and without checksum
        if (strlen($uid) === 6) {
            $baseUid = $uid;
        } else {
            if (!UidGenerator::validate($uid)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid UID checksum'
                ]);
            }
            $baseUid = UidGenerator::extractBase($uid);
        }
        
        $pet = Pet::where('unique_id', 'LIKE', $baseUid . '%')
            ->with(['owner.mobiles', 'species', 'breed'])
            ->first();
        
        if (!$pet) {
            return response()->json([
                'success' => false,
                'message' => 'No patient found with this UID',
                'action' => 'create_provisional'
            ]);
        }
        
        // Get all pets of the same owner (siblings)
        $siblings = Pet::where('owner_id', $pet->owner_id)
            ->with(['species', 'breed'])
            ->get();
        
        return response()->json([
            'success' => true,
            'pet' => $pet,
            'siblings' => $siblings,
            'owner' => $pet->owner,
            'action' => 'show_patient'
        ]);
    }
    
    /**
     * Search by mobile number
     */
    private function searchByMobile($mobile)
    {
        $ownerMobile = OwnerMobile::where('mobile', $mobile)
            ->with('owner')
            ->first();
        
        if (!$ownerMobile) {
            return response()->json([
                'success' => false,
                'message' => 'No patient found with this mobile number',
                'action' => 'create_provisional',
                'mobile' => $mobile
            ]);
        }
        
        // Get all pets for this owner
        $pets = Pet::where('owner_id', $ownerMobile->owner_id)
            ->with(['species', 'breed'])
            ->get();
        
        if ($pets->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Owner found but no pets registered',
                'action' => 'add_pet',
                'owner' => $ownerMobile->owner
            ]);
        }
        
        return response()->json([
            'success' => true,
            'pets' => $pets,
            'owner' => $ownerMobile->owner,
            'action' => $pets->count() === 1 ? 'show_patient' : 'choose_pet'
        ]);
    }
    
    /**
     * Create provisional patient record
     */
    public function createProvisional(Request $request)
    {
        $request->validate([
            'mobile' => 'nullable|digits:10',
            'owner_name' => 'nullable|string|max:100',
            'pet_name' => 'nullable|string|max:50'
        ]);
        
        return DB::transaction(function () use ($request) {
            // Generate new UID with checksum
            $uid = UidGenerator::generate();
            
            // Create provisional owner
            $owner = Owner::create([
                'name' => $request->owner_name ?? 'Provisional Owner',
                'status' => 'provisional',
                'created_via' => 'visit_entry'
            ]);
            
            // Add mobile if provided
            if ($request->mobile) {
                OwnerMobile::create([
                    'owner_id' => $owner->id,
                    'mobile' => $request->mobile,
                    'mobile_e164' => '+91' . $request->mobile,
                    'is_primary' => true
                ]);
            }
            
            // Create provisional pet
            $pet = Pet::create([
                'unique_id' => $uid,
                'owner_id' => $owner->id,
                'name' => $request->pet_name ?? 'Provisional Pet',
                'status' => 'provisional'
            ]);
            
            // Generate QR and Barcode
            $qrCode = UidGenerator::generateQrCode($uid);
            $barcode = UidGenerator::generateBarcode($uid);
            
            // Create visit for today
            $visit = $pet->visits()->create([
                'uuid' => \Str::uuid(),
                'visit_date' => now()->toDateString(),
                'visit_seq' => 1,
                'status' => 'open',
                'source' => 'web'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Provisional patient created',
                'uid' => $uid,
                'pet' => $pet,
                'owner' => $owner,
                'visit' => $visit,
                'qr_code' => $qrCode,
                'barcode' => $barcode,
                'action' => 'print_letterhead'
            ]);
        });
    }
    
    /**
     * Print letterhead for existing patient
     */
    public function printLetterhead($petId)
    {
        $pet = Pet::with(['owner.mobiles', 'species', 'breed'])->findOrFail($petId);
        
        // Get or create today's visit
        $visit = $pet->visits()
            ->whereDate('visit_date', now())
            ->first();
        
        if (!$visit) {
            $lastSeq = $pet->visits()
                ->whereDate('visit_date', now())
                ->max('visit_seq') ?? 0;
                
            $visit = $pet->visits()->create([
                'uuid' => \Str::uuid(),
                'visit_date' => now()->toDateString(),
                'visit_seq' => $lastSeq + 1,
                'status' => 'open',
                'source' => 'web'
            ]);
        }
        
        $qrCode = UidGenerator::generateQrCode($pet->unique_id);
        $barcode = UidGenerator::generateBarcode($pet->unique_id);
        
        return view('patient.letterhead', compact('pet', 'visit', 'qrCode', 'barcode'));
    }
}