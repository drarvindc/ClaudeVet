@extends('layouts.app')

@section('title', 'Prescription - ' . $pet->name)

@section('content')
<div class="max-w-4xl mx-auto p-8 bg-white" id="letterhead">
    <!-- Header -->
    <div class="border-b-2 border-gray-800 pb-4 mb-6">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-800">MetroVet Clinic</h1>
            <p class="text-sm text-gray-600 mt-2">
                304, Popular Nagar Shopping Complex, Warje, Pune<br>
                Phone: 9867999773 | Email: info@metrovet.in
            </p>
        </div>
    </div>

    <!-- Patient Info -->
    <div class="grid grid-cols-2 gap-6 mb-6">
        <div>
            <p><strong>Date:</strong> {{ now()->format('d/m/Y') }}</p>
            <p><strong>UID:</strong> {{ $pet->unique_id }}</p>
            <p><strong>Visit #:</strong> V{{ str_pad($visit->visit_seq, 4, '0', STR_PAD_LEFT) }}</p>
        </div>
        <div>
            <p><strong>Owner:</strong> {{ $pet->owner->name }}</p>
            <p><strong>Pet:</strong> {{ $pet->name }}</p>
            <p><strong>Species:</strong> {{ $pet->species->common_name ?? 'N/A' }}</p>
        </div>
    </div>

    <!-- QR and Barcode -->
    <div class="flex justify-between mb-6">
        <div class="text-center">
            <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code" class="w-24 h-24">
            <p class="text-xs mt-1">QR Code</p>
        </div>
        <div class="text-center">
            <img src="data:image/png;base64,{{ $barcode }}" alt="Barcode" class="h-16">
            <p class="text-xs mt-1">{{ $pet->unique_id }}</p>
        </div>
    </div>

    <!-- Prescription Area -->
    <div class="min-h-[400px] border-2 border-dashed border-gray-300 p-4 mb-6">
        <p class="text-gray-400 text-center">Prescription Area</p>
    </div>

    <!-- Footer -->
    <div class="border-t-2 border-gray-800 pt-4 mt-8">
        <div class="flex justify-between">
            <div>
                <p class="text-sm text-gray-600">Doctor Signature</p>
                <div class="w-48 border-b border-gray-400 mt-8"></div>
            </div>
            <div>
                <p class="text-sm text-gray-600">Next Visit</p>
                <div class="w-48 border-b border-gray-400 mt-8"></div>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4 no-print">
    <button onclick="window.print()" class="bg-blue-500 text-white px-6 py-2 rounded">
        Print Letterhead
    </button>
</div>

<style>
@media print {
    .no-print { display: none; }
    body { background: white; }
    #letterhead { box-shadow: none; }
}
</style>
@endsection