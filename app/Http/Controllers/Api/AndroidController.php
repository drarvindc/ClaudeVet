<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Models\Visit;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AndroidController extends Controller
{
    /**
     * Open or ensure today's visit exists for a pet
     */
    public function openVisit(Request $request): JsonResponse
    {
        $request->validate([
            'uid' => 'required|string|size:6'
        ]);

        try {
            $uid = $request->uid;
            
            // Find the pet
            $pet = Pet::where('unique_id', $uid)
                ->with(['owner.mobiles', 'species', 'breed'])
                ->first();

            if (!$pet) {
                return response()->json([
                    'ok' => false,
                    'error' => 'uid_not_found'
                ], 404);
            }

            // Get or create today's visit
            $today = now()->format('Y-m-d');
            $visit = Visit::where('pet_id', $pet->id)
                ->where('visit_date', $today)
                ->first();

            $wasCreated = false;
            if (!$visit) {
                // Create new visit with sequence number
                $visitSeq = $this->getNextVisitSequence($pet->id, $today);
                
                $visit = Visit::create([
                    'uuid' => Str::uuid(),
                    'pet_id' => $pet->id,
                    'visit_date' => $today,
                    'visit_number' => $visitSeq,
                    'sequence' => 1,
                    'status' => 'open',
                    'visit_type' => 'consultation'
                ]);
                $wasCreated = true;
            }

            return response()->json([
                'ok' => true,
                'visit' => [
                    'id' => $visit->id,
                    'uid' => $uid,
                    'date' => $visit->visit_date->format('Y-m-d'),
                    'sequence' => $visit->sequence,
                    'wasCreated' => $wasCreated
                ],
                'pet' => [
                    'unique_id' => $pet->unique_id,
                    'name' => $pet->name,
                    'species' => $pet->species->common_name ?? 'Unknown',
                    'breed' => $pet->breed->name ?? 'Unknown'
                ],
                'owner' => [
                    'name' => $pet->owner->name,
                    'mobile' => $pet->owner->mobiles->where('is_primary', true)->first()?->mobile ?? ''
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => 'server_error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload file for a visit
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'uid' => 'required|string|size:6',
            'type' => 'required|in:prescription,lab,xray,usg,photo,certificate,report',
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,webp',
            'note' => 'nullable|string|max:500',
            'forceNewVisit' => 'boolean'
        ]);

        try {
            $uid = $request->uid;
            
            // Find the pet
            $pet = Pet::where('unique_id', $uid)->first();
            if (!$pet) {
                return response()->json([
                    'ok' => false,
                    'error' => 'uid_not_found'
                ], 404);
            }

            // Get or create visit
            $today = now()->format('Y-m-d');
            $visit = null;

            if ($request->boolean('forceNewVisit')) {
                // Create new visit with incremented sequence
                $maxSequence = Visit::where('pet_id', $pet->id)
                    ->where('visit_date', $today)
                    ->max('sequence') ?? 0;
                
                $visitSeq = $this->getNextVisitSequence($pet->id, $today);
                
                $visit = Visit::create([
                    'uuid' => Str::uuid(),
                    'pet_id' => $pet->id,
                    'visit_date' => $today,
                    'visit_number' => $visitSeq,
                    'sequence' => $maxSequence + 1,
                    'status' => 'open',
                    'visit_type' => 'consultation'
                ]);
            } else {
                // Get existing or create first visit
                $visit = Visit::where('pet_id', $pet->id)
                    ->where('visit_date', $today)
                    ->first();

                if (!$visit) {
                    $visitSeq = $this->getNextVisitSequence($pet->id, $today);
                    
                    $visit = Visit::create([
                        'uuid' => Str::uuid(),
                        'pet_id' => $pet->id,
                        'visit_date' => $today,
                        'visit_number' => $visitSeq,
                        'sequence' => 1,
                        'status' => 'open',
                        'visit_type' => 'consultation'
                    ]);
                }
            }

            // Handle file upload
            $file = $request->file('file');
            $type = $request->type;
            
            // Generate filename with convention: DDMMYY-type-uid-seq.ext
            $dateStr = now()->format('dmy');
            $extension = $file->getClientOriginalExtension();
            
            // Get next sequence number for this type today
            $sequenceNum = Document::where('visit_id', $visit->id)
                ->where('type', $type)
                ->count() + 1;
            
            $filename = sprintf(
                '%s-%s-%s-%02d.%s',
                $dateStr,
                $type,
                $uid,
                $sequenceNum,
                $extension
            );

            // Store file in pets/{YYYY}/{UID}/ directory
            $year = now()->format('Y');
            $storagePath = "patients/{$year}/{$uid}/{$filename}";
            
            // Ensure directory exists
            $fullPath = storage_path("app/public/{$storagePath}");
            $directory = dirname($fullPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Store the file
            $path = $file->storeAs("public/patients/{$year}/{$uid}", $filename);

            // Calculate file checksum
            $checksum = hash_file('sha1', $fullPath);

            // Check for duplicates
            $duplicate = Document::where('visit_id', $visit->id)
                ->where('filename', $filename)
                ->orWhere(function($query) use ($checksum) {
                    $query->where('checksum_sha1', $checksum);
                })
                ->exists();

            if ($duplicate) {
                Storage::delete($path);
                return response()->json([
                    'ok' => false,
                    'error' => 'duplicate_file'
                ], 409);
            }

            // Save document record
            $document = Document::create([
                'visit_id' => $visit->id,
                'pet_id' => $pet->id,
                'type' => $type,
                'filename' => $filename,
                'filesize' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'note' => $request->note,
                'checksum_sha1' => $checksum
            ]);

            return response()->json([
                'ok' => true,
                'visitId' => $visit->id,
                'attachment' => [
                    'id' => $document->id,
                    'type' => $type,
                    'filename' => $filename,
                    'url' => "/storage/patients/{$year}/{$uid}/{$filename}",
                    'size' => $file->getSize(),
                    'created_at' => $document->created_at->format('Y-m-d\TH:i:sP')
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => 'upload_failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

/**
 * Get today's visit with attachments
 */
public function getTodayVisit(Request $request): JsonResponse
{
    $request->validate([
        'uid' => 'required|string|size:6'
    ]);

    try {
        $uid = $request->uid;
        $pet = Pet::where('unique_id', $uid)->first();
        
        if (!$pet) {
            return response()->json([
                'ok' => false,
                'error' => 'uid_not_found'
            ], 404);
        }

        $today = now()->format('Y-m-d');
        $visits = Visit::where('pet_id', $pet->id)
            ->where('visit_date', $today)
            ->with('documents')
            ->orderBy('sequence')
            ->get();

        if ($visits->isEmpty()) {
            return response()->json([
                'ok' => false,
                'error' => 'no_visit_today'
            ], 404);
        }

        $visitData = [];
        foreach ($visits as $visit) {
            // Fix: Use $visit directly instead of in closure
            $year = $visit->visit_date->format('Y');
            
            $attachments = $visit->documents->map(function ($doc) use ($uid, $year) {
                return [
                    'id' => $doc->id,
                    'type' => $doc->type,
                    'filename' => $doc->filename,
                    'url' => "/storage/patients/{$year}/{$uid}/{$doc->filename}",
                    'size' => $doc->filesize,
                    'note' => $doc->note
                ];
            });

            $visitData[] = [
                'id' => $visit->id,
                'sequence' => $visit->sequence,
                'date' => $visit->visit_date->format('Y-m-d'),
                'status' => $visit->status,
                'attachments' => $attachments
            ];
        }

        return response()->json([
            'ok' => true,
            'visits' => $visitData
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'ok' => false,
            'error' => 'server_error',
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Get next visit sequence number for a pet
     */
    private function getNextVisitSequence(int $petId, string $date): int
    {
        $maxVisitNumber = Visit::where('pet_id', $petId)->max('visit_number') ?? 0;
        return $maxVisitNumber + 1;
    }
}