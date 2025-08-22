<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Pet;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function today()
    {
        $visits = Visit::with(['pet.owner', 'doctor'])
            ->whereDate('visit_date', today())
            ->latest('created_at')
            ->get();
            
        return view('visits.today', compact('visits'));
    }
    
    public function show(Visit $visit)
    {
        $visit->load(['pet.owner', 'documents', 'doctor']);
        return view('visits.show', compact('visit'));
    }
    
    public function close(Visit $visit)
    {
        $visit->update(['status' => 'closed']);
        return redirect()->route('visit.show', $visit)
            ->with('success', 'Visit closed successfully');
    }
    
    public function upload(Request $request, Visit $visit)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'type' => 'required|string'
        ]);
        
        // Handle file upload
        $path = $request->file('file')->store('visits/' . $visit->id);
        
        $visit->documents()->create([
            'uuid' => \Str::uuid(),
            'patient_unique_id' => $visit->pet->unique_id,
            'pet_id' => $visit->pet_id,
            'type' => $request->type,
            'path' => $path,
            'filename' => $request->file('file')->getClientOriginalName(),
            'mime_type' => $request->file('file')->getMimeType(),
            'size_bytes' => $request->file('file')->getSize(),
            'captured_at' => now(),
            'source' => 'web'
        ]);
        
        return back()->with('success', 'Document uploaded successfully');
    }
}