@extends('layouts.app')

@section('title', 'Prescription - ' . $pet->display_name)

@section('content')
<div class="max-w-4xl mx-auto p-8 bg-white" id="letterhead">
    <!-- Header -->
    <div class="border-b-2 border-gray-800 pb-4 mb-6">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-800">MetroVet Clinic</h1>
            <p class="text-sm text-gray-600 mt-2">
                304, Popular Nagar Shopping Complex, Warje, Pune<br>
                Phone: 7020241565 | Email: info@metrovet.in
            </p>
        </div>
    </div>

    <!-- Patient Info -->
    <div class="grid grid-cols-2 gap-6 mb-6">
        <div>
            <p><strong>Date:</strong> {{ now()->format('d/m/Y') }}</p>
            <p><strong>UID:</strong> {{ $pet->unique_id }}</p>
            @if($pet->visits->count() > 0)
                <p><strong>Visit #:</strong> V{{ str_pad($pet->visits->first()->visit_seq, 4, '0', STR_PAD_LEFT) }}</p>
            @endif
        </div>
        <div>
            <p><strong>Owner:</strong> {{ $pet->owner->full_name }}</p>
            <p><strong>Pet:</strong> {{ $pet->display_name }}</p>
            @if($pet->species)
                <p><strong>Species:</strong> {{ $pet->species->display_name }}</p>
            @endif
            @if($pet->breed)
                <p><strong>Breed:</strong> {{ $pet->breed->name }}</p>
            @endif
            @if($pet->formatted_age !== 'Unknown')
                <p><strong>Age:</strong> {{ $pet->formatted_age }}</p>
            @endif
        </div>
    </div>

    <!-- QR and Barcode -->
    <div class="flex justify-between items-center mb-6">
        <div class="text-center">
            <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code" class="w-24 h-24 mx-auto">
            <p class="text-xs mt-1">QR Code</p>
        </div>
        
        <div class="text-center flex-1 mx-8">
            @if($pet->isProvisional())
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-2 rounded">
                    <p class="font-semibold">PROVISIONAL RECORD</p>
                    <p class="text-xs">Complete details in admin panel</p>
                </div>
            @endif
        </div>
        
        <div class="text-center">
            <img src="data:image/png;base64,{{ $barcode }}" alt="Barcode" class="h-16 mx-auto">
            <p class="text-xs mt-1 font-mono">{{ $pet->unique_id }}</p>
        </div>
    </div>

    <!-- Prescription Area -->
    <div class="min-h-[400px] border-2 border-dashed border-gray-300 p-4 mb-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Clinical Findings & Prescription</h3>
        <div class="space-y-4 text-gray-400">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="font-medium">Temperature: _______________</p>
                    <p class="font-medium">Weight: ___________________</p>
                    <p class="font-medium">Heart Rate: _______________</p>
                </div>
                <div>
                    <p class="font-medium">Respiratory Rate: _________</p>
                    <p class="font-medium">Blood Pressure: __________</p>
                    <p class="font-medium">Body Condition: __________</p>
                </div>
            </div>
            
            <div class="mt-6">
                <p class="font-medium mb-2">Clinical Findings:</p>
                <div class="space-y-2">
                    <p>____________________________________________________________________________</p>
                    <p>____________________________________________________________________________</p>
                    <p>____________________________________________________________________________</p>
                </div>
            </div>
            
            <div class="mt-6">
                <p class="font-medium mb-2">Prescription:</p>
                <div class="space-y-2">
                    <p>____________________________________________________________________________</p>
                    <p>____________________________________________________________________________</p>
                    <p>____________________________________________________________________________</p>
                    <p>____________________________________________________________________________</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="border-t-2 border-gray-800 pt-4 mt-8">
        <div class="flex justify-between">
            <div>
                <p class="text-sm text-gray-600">Doctor Signature</p>
                <div class="w-48 border-b border-gray-400 mt-8"></div>
                <p class="text-xs text-gray-500 mt-1">Dr. ________________________</p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Next Visit</p>
                <div class="w-48 border-b border-gray-400 mt-8"></div>
                <p class="text-xs text-gray-500 mt-1">Date: ______________________</p>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4 space-x-4 no-print">
    <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg transition duration-200">
        🖨️ Print Letterhead
    </button>
    
    <a href="{{ route('patient.intake') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg transition duration-200">
        ← New Search
    </a>
    
    @if(auth()->check())
        <a href="/admin" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg transition duration-200">
            Manage in Admin
        </a>
    @endif
</div>

<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
    #letterhead { box-shadow: none !important; margin: 0 !important; }
    .bg-yellow-100 { background: #fffbeb !important; }
    .border-yellow-400 { border-color: #fbbf24 !important; }
    .text-yellow-800 { color: #92400e !important; }
}
</style>
@endsection