<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\Owner;
use App\Models\OwnerMobile;
use App\Models\Species;
use App\Models\Breed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Show incomplete profiles dashboard
     */
    public function incompleteProfiles(Request $request)
    {
        $query = Pet::incomplete()
                    ->with(['owner.mobiles', 'species', 'breed'])
                    ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('created_via')) {
            $query->where('created_via', $request->created_via);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('unique_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('owner', function($ownerQuery) use ($search) {
                      $ownerQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('owner.mobiles', function($mobileQuery) use ($search) {
                      $mobileQuery->where('mobile', 'like', "%{$search}%");
                  });
            });
        }

        $incompleteProfiles = $query->paginate(20);
        
        $stats = [
            'total_incomplete' => Pet::incomplete()->count(),
            'provisional_count' => Pet::where('created_via', 'provisional')->incomplete()->count(),
            'oldest_incomplete' => Pet::incomplete()->oldest()->first()?->created_at,
        ];

        return view('admin.incomplete.index', compact('incompleteProfiles', 'stats'));
    }

    /**
     * Show edit form for incomplete profile
     */
    public function editIncompleteProfile($id)
    {
        $pet = Pet::with(['owner.mobiles', 'species', 'breed'])->findOrFail($id);
        
        $species = Species::where('is_active', true)->orderBy('name')->get();
        $breeds = Breed::orderBy('name')->get();

        return view('admin.incomplete.edit', compact('pet', 'species', 'breeds'));
    }

    /**
     * Update incomplete profile
     */
    public function updateIncompleteProfile(Request $request, $id)
    {
        $pet = Pet::with('owner')->findOrFail($id);

        $request->validate([
            // Owner details
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'nullable|email|max:255',
            'owner_address' => 'nullable|string',
            'owner_locality' => 'nullable|string|max:100',
            'owner_city' => 'nullable|string|max:100',
            'owner_pincode' => 'nullable|string|max:10',
            
            // Pet details
            'pet_name' => 'required|string|max:255',
            'species_id' => 'required|exists:species,id',
            'breed_id' => 'required|exists:breeds,id',
            'gender' => 'required|in:male,female',
            'age_years' => 'nullable|integer|min:0|max:30',
            'age_months' => 'nullable|integer|min:0|max:11',
            'color' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0|max:200',
            'distinguishing_marks' => 'nullable|string',
            'microchip_number' => 'nullable|string|max:255',
            'sterilization_status' => 'nullable|in:intact,neutered,spayed',
            
            // Additional mobile numbers
            'additional_mobiles' => 'nullable|array',
            'additional_mobiles.*' => 'digits:10',
        ]);

        return DB::transaction(function () use ($request, $pet) {
            // Update owner details
            $pet->owner->update([
                'name' => $request->owner_name,
                'email' => $request->owner_email,
                'address' => $request->owner_address,
                'locality' => $request->owner_locality,
                'city' => $request->owner_city,
                'pincode' => $request->owner_pincode,
                'is_complete' => true,
            ]);

            // Update pet details
            $pet->update([
                'name' => $request->pet_name,
                'species_id' => $request->species_id,
                'breed_id' => $request->breed_id,
                'gender' => $request->gender,
                'age_years' => $request->age_years,
                'age_months' => $request->age_months,
                'color' => $request->color,
                'weight' => $request->weight,
                'distinguishing_marks' => $request->distinguishing_marks,
                'microchip_number' => $request->microchip_number,
                'sterilization_status' => $request->sterilization_status,
                'is_complete' => true,
            ]);

            // Handle additional mobile numbers
            if ($request->filled('additional_mobiles')) {
                foreach ($request->additional_mobiles as $mobile) {
                    $normalizedMobile = OwnerMobile::normalizeMobile($mobile);
                    
                    // Check if mobile already exists for this owner
                    $existingMobile = OwnerMobile::where('owner_id', $pet->owner_id)
                                                ->where('mobile', $normalizedMobile)
                                                ->first();
                    
                    if (!$existingMobile) {
                        OwnerMobile::create([
                            'owner_id' => $pet->owner_id,
                            'mobile' => $normalizedMobile,
                            'is_primary' => false,
                            'is_whatsapp' => false,
                            'is_verified' => false,
                        ]);
                    }
                }
            }

            return redirect()->route('admin.incomplete.index')
                           ->with('success', "Profile for {$pet->name} (UID: {$pet->unique_id}) completed successfully.");
        });
    }

    /**
     * Mark profile as complete without detailed updates
     */
    public function markComplete($id)
    {
        $pet = Pet::with('owner')->findOrFail($id);

        DB::transaction(function () use ($pet) {
            $pet->update(['is_complete' => true]);
            $pet->owner->update(['is_complete' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Profile marked as complete'
        ]);
    }

    /**
     * Show duplicate management interface
     */
    public function duplicateManagement(Request $request)
    {
        $query = Pet::with(['owner.mobiles', 'species', 'breed'])
                    ->where(function($q) {
                        $q->where('is_duplicate', true)
                          ->orWhereNotNull('duplicate_of_uid');
                    });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('unique_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('duplicate_of_uid', 'like', "%{$search}%");
            });
        }

        $duplicates = $query->paginate(20);

        return view('admin.duplicates.index', compact('duplicates'));
    }

    /**
     * Mark pet as duplicate
     */
    public function markDuplicate(Request $request)
    {
        $request->validate([
            'source_uid' => 'required|exists:pets,unique_id',
            'target_uid' => 'required|exists:pets,unique_id|different:source_uid',
            'reason' => 'nullable|string|max:500'
        ]);

        $sourcePet = Pet::where('unique_id', $request->source_uid)->first();
        $targetPet = Pet::where('unique_id', $request->target_uid)->first();

        return DB::transaction(function () use ($request, $sourcePet, $targetPet) {
            // Mark source as duplicate
            $sourcePet->update([
                'is_duplicate' => true,
                'duplicate_of_uid' => $request->target_uid
            ]);

            // Log the action
            DB::table('duplicate_audit_log')->insert([
                'action' => 'mark_duplicate',
                'source_uid' => $request->source_uid,
                'target_uid' => $request->target_uid,
                'admin_user_id' => auth()->id(),
                'reason' => $request->reason,
                'created_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => "UID {$request->source_uid} marked as duplicate of {$request->target_uid}"
            ]);
        });
    }

    /**
     * Unmark pet as duplicate
     */
    public function unmarkDuplicate(Request $request)
    {
        $request->validate([
            'uid' => 'required|exists:pets,unique_id'
        ]);

        $pet = Pet::where('unique_id', $request->uid)->first();

        return DB::transaction(function () use ($request, $pet) {
            $oldTargetUid = $pet->duplicate_of_uid;
            
            $pet->update([
                'is_duplicate' => false,
                'duplicate_of_uid' => null
            ]);

            // Log the action
            DB::table('duplicate_audit_log')->insert([
                'action' => 'unmark_duplicate',
                'source_uid' => $request->uid,
                'target_uid' => $oldTargetUid,
                'admin_user_id' => auth()->id(),
                'created_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => "UID {$request->uid} is no longer marked as duplicate"
            ]);
        });
    }

    /**
     * Compare two pets for duplicate analysis
     */
    public function comparePets($uid1, $uid2)
    {
        $pet1 = Pet::where('unique_id', $uid1)->with(['owner.mobiles', 'species', 'breed', 'visits'])->firstOrFail();
        $pet2 = Pet::where('unique_id', $uid2)->with(['owner.mobiles', 'species', 'breed', 'visits'])->firstOrFail();

        // Get audit log for these UIDs
        $auditLog = DB::table('duplicate_audit_log')
                     ->where(function($q) use ($uid1, $uid2) {
                         $q->where('source_uid', $uid1)->orWhere('target_uid', $uid1)
                           ->orWhere('source_uid', $uid2)->orWhere('target_uid', $uid2);
                     })
                     ->orderBy('created_at', 'desc')
                     ->get();

        return view('admin.duplicates.compare', compact('pet1', 'pet2', 'auditLog'));
    }

    /**
     * Get breeds for a species (AJAX endpoint)
     */
    public function getBreedsForSpecies(Request $request)
    {
        $speciesId = $request->species_id;
        $breeds = Breed::where('species_id', $speciesId)->orderBy('name')->get();
        
        return response()->json($breeds);
    }
}