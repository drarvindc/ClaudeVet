@extends('layouts.app')

@section('title', 'Patient Not Found')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
        
        <!-- Icon -->
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-gray-900">No Match Found</h1>
            <p class="text-gray-600 mt-2">
                @if($search_type === 'uid')
                    Unique ID <strong>{{ $search_input }}</strong> not found
                @else
                    Mobile number <strong>{{ $search_input }}</strong> not found
                @endif
            </p>
        </div>

        <!-- Options -->
        <div class="space-y-4">
            
            <!-- Try Another Search -->
            <a href="{{ route('patient.intake') }}" 
               class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span>Try Another Search</span>
            </a>

            @if($search_type === 'mobile')
                <!-- Create New Record -->
                <div class="border-t pt-4">
                    <p class="text-sm text-gray-600 mb-3">
                        This mobile number is not in our system. Create a new provisional record?
                    </p>
                    
                    <form action="{{ route('patient.create-provisional') }}" method="POST">
                        @csrf
                        <input type="hidden" name="mobile" value="{{ $search_input }}">
                        
                        <button type="submit" 
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>Create New Patient Record</span>
                        </button>
                    </form>
                </div>
            @endif

            @if($search_type === 'uid')
                <!-- UID Not Found - Suggest Mobile Search -->
                <div class="border-t pt-4">
                    <p class="text-sm text-gray-600 mb-3">
                        This Unique ID doesn't exist. Try searching by mobile number instead?
                    </p>
                    
                    <a href="{{ route('patient.intake') }}" 
                       class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <span>Search by Mobile Number</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- Admin Link -->
        @if(auth()->check())
            <div class="mt-6 pt-4 border-t text-center">
                <a href="/admin" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    ← Back to Admin Panel
                </a>
            </div>
        @endif
    </div>
</div>
@endsection