@extends('layouts.app')

@section('title', 'Patient Found')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Patient Found</h1>
                    <p class="text-gray-600 mt-1">
                        @if($search_type === 'uid')
                            Found by Unique ID • Showing pet and family
                        @else
                            Found by Mobile Number • Showing all family pets
                        @endif
                    </p>
                </div>
                <a href="{{ route('patient.intake') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition duration-200">
                    ← New Search
                </a>
            </div>
        </div>

        <!-- Owner Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Owner Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Name</label>
                    <p class="text-gray-900">{{ $owner->full_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-500">Mobile Numbers</label>
                    <div class="space-y-1">
                        @foreach($owner->mobiles as $mobile)
                            <p class="text-gray-900">
                                {{ $mobile->formatted_mobile }}
                                @if($mobile->is_primary)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 ml-2">
                                        Primary
                                    </span>
                                @endif
                            </p>
                        @endforeach
                    </div>
                </div>
                @if($owner->locality)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-500">Locality</label>
                    <p class="text-gray-900">{{ $owner->locality }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Pets List -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                Family Pets ({{ $all_pets->count() }})
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($all_pets as $pet)
                    <div class="border rounded-lg p-4 transition duration-200 hover:shadow-md
                        {{ $matched_pet && $matched_pet->id === $pet->id ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                        
                        @if($matched_pet && $matched_pet->id === $pet->id)
                            <div class="flex items-center mb-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    ✓ Matched Pet
                                </span>
                            </div>
                        @endif
                        
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-900">
                                    {{ $pet->display_name }}
                                </h3>
                                <span class="text-sm font-mono bg-gray-100 px-2 py-1 rounded">
                                    {{ $pet->unique_id }}
                                </span>
                            </div>
                            
                            <div class="text-sm text-gray-600 space-y-1">
                                @if($pet->species)
                                    <p>
                                        <span class="font-medium">Species:</span> 
                                        {{ $pet->species->display_name }}
                                        @if($pet->breed)
                                            - {{ $pet->breed->name }}
                                        @endif
                                    </p>
                                @endif
                                
                                @if($pet->gender !== 'unknown')
                                    <p>
                                        <span class="font-medium">Gender:</span> 
                                        {{ ucfirst($pet->gender) }}
                                    </p>
                                @endif
                                
                                @if($pet->age_years || $pet->age_months)
                                    <p>
                                        <span class="font-medium">Age:</span> 
                                        {{ $pet->formatted_age }}
                                    </p>
                                @endif
                                
                                @if($pet->isProvisional())
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Needs Completion
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mt-4 flex space-x-2">
                            <a href="{{ route('patient.letterhead', ['uid' => $pet->unique_id]) }}" 
                               class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2 px-3 rounded text-center transition duration-200">
                                Print Letterhead
                            </a>
                            
                            @if(auth()->check())
                                <a href="/admin" 
                                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 px-3 rounded transition duration-200">
                                    Manage
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="mt-6 flex justify-center space-x-4">
            <a href="{{ route('patient.intake') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-lg transition duration-200">
                Search Another Patient
            </a>
            @if(auth()->check())
                <a href="/admin" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    Go to Admin Panel
                </a>
            @endif
        </div>
    </div>
</div>
@endsection