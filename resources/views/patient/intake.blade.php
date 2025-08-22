@extends('layouts.app')

@section('title', 'Patient Intake')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Patient Search</h1>
            <p class="text-gray-600 mt-2">Enter mobile number or scan/type Unique ID</p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('patient.search') }}" method="POST" class="space-y-6">
            @csrf
            
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                    Mobile Number or Unique ID
                </label>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    placeholder="9876543210 or 250001"
                    autofocus
                    autocomplete="off"
                    class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    value="{{ old('search') }}"
                    required
                >
                <div class="mt-2 text-sm text-gray-500">
                    <div class="flex items-center space-x-4">
                        <span>📱 10-digit mobile</span>
                        <span>🏷️ 6-digit UID</span>
                    </div>
                </div>
            </div>

            <button 
                type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span>Search Patient</span>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="text-center text-sm text-gray-500">
                <p>For barcode/QR scanners: just scan directly into the input field</p>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="/admin" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                ← Back to Admin Panel
            </a>
        </div>
    </div>
</div>

<script>
// Auto-focus and handle scanner input
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    
    // Focus the input
    searchInput.focus();
    
    // Handle barcode scanner input (typically ends with Enter)
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            // Small delay to ensure scanner has finished input
            setTimeout(() => {
                this.form.submit();
            }, 100);
        }
    });
    
    // Auto-submit for 6-digit UIDs after slight delay
    searchInput.addEventListener('input', function() {
        const value = this.value.replace(/\D/g, ''); // Remove non-digits
        if (value.length === 6) {
            setTimeout(() => {
                if (this.value.replace(/\D/g, '').length === 6) {
                    this.form.submit();
                }
            }, 500);
        }
    });
});
</script>
@endsection