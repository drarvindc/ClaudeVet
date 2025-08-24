{{-- resources/views/patient/intake.blade.php --}}
@extends('layouts.app')

@section('title', 'Patient Intake')

@section('content')
<!-- Header -->
<div class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-4xl mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800">Patient Intake</h1>
            <div class="text-sm text-gray-500">
                {{ now()->format('d M Y, h:i A') }}
            </div>
        </div>
    </div>
</div>

<!-- Main Interface -->
<div class="max-w-2xl mx-auto px-4 py-8">
    <!-- Search Card -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-6">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Search Patient</h2>
            <p class="text-gray-600">Enter 6-digit UID or 10-digit mobile number</p>
        </div>

        <!-- Search Form -->
        <form id="searchForm" class="space-y-6">
            @csrf
            <div>
                <input 
                    type="text" 
                    id="searchInput" 
                    name="search"
                    class="w-full text-2xl text-center border-2 border-gray-300 rounded-xl px-6 py-4 focus:border-blue-500 focus:ring-4 focus:ring-blue-200 focus:outline-none transition-all duration-200"
                    placeholder="Enter UID or Mobile"
                    maxlength="10"
                    autocomplete="off"
                    autofocus
                >
                <div class="text-center mt-2">
                    <small class="text-gray-500">
                        <span id="inputHint">Enter 6 digits for UID or 10 digits for mobile</span>
                    </small>
                </div>
            </div>

            <button 
                type="submit" 
                id="searchBtn"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-8 rounded-xl transition-all duration-200 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-blue-300"
            >
                <span id="searchBtnText">Search Patient</span>
                <span id="searchBtnLoader" class="hidden">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Searching...
                </span>
            </button>
        </form>
    </div>

    <!-- Results Container -->
    <div id="resultsContainer" class="hidden"></div>

    <!-- No Match Options -->
    <div id="noMatchOptions" class="hidden bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.19 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">No Match Found</h3>
            <p class="text-gray-600 mb-6">
                <span id="noMatchMessage"></span>
            </p>
        </div>

        <div class="space-y-4">
            <button 
                onclick="tryAnotherSearch()" 
                class="w-full bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200"
            >
                Try Another Number
            </button>
            
            <button 
                id="createProvisionalBtn"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200"
            >
                Create New Patient Record
            </button>
        </div>
    </div>

    <!-- Pet Selection Interface -->
    <div id="petSelectionInterface" class="hidden bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-6">
            <h3 class="text-xl font-bold text-gray-800 mb-2">Select Pet</h3>
            <p class="text-gray-600">Multiple pets found for this mobile number</p>
        </div>
        <div id="petsList" class="space-y-3"></div>
        <div class="mt-6 text-center">
            <button 
                onclick="tryAnotherSearch()" 
                class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-6 rounded-xl transition-all duration-200"
            >
                New Search
            </button>
        </div>
    </div>

    <!-- Single Pet Found -->
    <div id="singlePetFound" class="hidden bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Pet Found</h3>
        </div>
        
        <div id="petDetails" class="mb-6"></div>
        
        <div class="space-y-4">
            <button 
                id="printLetterheadBtn"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200"
            >
                Print Letterhead
            </button>
            
            <button 
                onclick="tryAnotherSearch()" 
                class="w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200"
            >
                New Search
            </button>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md mx-4">
        <div class="text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Success!</h3>
            <p id="successMessage" class="text-gray-600 mb-6"></p>
            <button 
                onclick="closeSuccessModal()" 
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-xl transition-all duration-200"
            >
                OK
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const searchBtnText = document.getElementById('searchBtnText');
    const searchBtnLoader = document.getElementById('searchBtnLoader');
    const inputHint = document.getElementById('inputHint');
    const resultsContainer = document.getElementById('resultsContainer');
    const noMatchOptions = document.getElementById('noMatchOptions');
    const noMatchMessage = document.getElementById('noMatchMessage');
    const createProvisionalBtn = document.getElementById('createProvisionalBtn');
    const petSelectionInterface = document.getElementById('petSelectionInterface');
    const singlePetFound = document.getElementById('singlePetFound');
    const successModal = document.getElementById('successModal');
    const successMessage = document.getElementById('successMessage');

    // Input formatting and hints
    searchInput.addEventListener('input', function() {
        const value = this.value.replace(/[^0-9]/g, '');
        this.value = value;
        
        if (value.length === 6) {
            inputHint.textContent = 'UID format detected';
            inputHint.className = 'text-blue-600 font-medium';
        } else if (value.length === 10) {
            inputHint.textContent = 'Mobile format detected';
            inputHint.className = 'text-green-600 font-medium';
        } else {
            inputHint.textContent = 'Enter 6 digits for UID or 10 digits for mobile';
            inputHint.className = 'text-gray-500';
        }
    });

    // Form submission
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        performSearch();
    });

    // Search function
    async function performSearch() {
        const searchValue = searchInput.value.trim();
        
        if (searchValue.length !== 6 && searchValue.length !== 10) {
            alert('Please enter exactly 6 digits for UID or 10 digits for mobile number');
            return;
        }

        // Show loading state
        setLoadingState(true);
        hideAllResults();

        try {
            const response = await fetch('{{ route("patient.search") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ search: searchValue })
            });

            const data = await response.json();
            handleSearchResponse(data);
        } catch (error) {
            console.error('Search error:', error);
            alert('Search failed. Please try again.');
        } finally {
            setLoadingState(false);
        }
    }

    // Handle search response
    function handleSearchResponse(data) {
        hideAllResults();
        
        if (!data.success) {
            // No match found
            noMatchMessage.textContent = data.message;
            noMatchOptions.classList.remove('hidden');
            
            // Setup provisional creation for mobile searches
            if (data.search_value && data.search_value.length === 10) {
                createProvisionalBtn.onclick = () => createProvisional(data.search_value);
                createProvisionalBtn.classList.remove('hidden');
            } else {
                createProvisionalBtn.classList.add('hidden');
            }
            return;
        }

        // Handle different success scenarios
        switch (data.action) {
            case 'single_pet_found':
            case 'pet_found':
                showSinglePetResult(data.pet, data.owner);
                break;
                
            case 'multiple_pets_found':
                showPetSelection(data.pets, data.mobile);
                break;
                
            case 'provisional_created':
                showProvisionalSuccess(data);
                break;
        }
    }

    // Show single pet result
    function showSinglePetResult(pet, owner) {
        const petDetails = document.getElementById('petDetails');
        petDetails.innerHTML = `
            <div class="bg-gray-50 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-800">${pet.display_name || pet.name}</h4>
                    <span class="bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full">UID: ${pet.unique_id}</span>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Owner:</span>
                        <span class="font-medium text-gray-800">${owner.name}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Mobile:</span>
                        <span class="font-medium text-gray-800">${owner.primary_mobile_number || 'Not available'}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Species:</span>
                        <span class="font-medium text-gray-800">${pet.species?.common_name || 'Unknown'}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Age:</span>
                        <span class="font-medium text-gray-800">${pet.formatted_age || 'Unknown'}</span>
                    </div>
                </div>
                ${pet.is_complete === false ? '<div class="mt-4 text-center"><span class="bg-yellow-100 text-yellow-800 text-sm px-3 py-1 rounded-full">Incomplete Profile</span></div>' : ''}
            </div>
        `;
        
        // Setup print button
        document.getElementById('printLetterheadBtn').onclick = () => {
            window.open(`{{ url('/patient/letterhead') }}/${pet.unique_id}`, '_blank');
        };
        
        singlePetFound.classList.remove('hidden');
    }

    // Show pet selection interface
    function showPetSelection(pets, mobile) {
        const petsList = document.getElementById('petsList');
        petsList.innerHTML = '';
        
        pets.forEach(pet => {
            const petCard = document.createElement('div');
            petCard.className = 'border-2 border-gray-200 rounded-xl p-4 cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all duration-200';
            petCard.onclick = () => selectPet(pet);
            
            petCard.innerHTML = `
                <div class="flex items-center justify-between">
                    <div>
                        <h5 class="font-semibold text-gray-800">${pet.display_name || pet.name}</h5>
                        <p class="text-sm text-gray-600">${pet.species?.common_name || 'Unknown'} • ${pet.formatted_age || 'Unknown age'}</p>
                    </div>
                    <div class="text-right">
                        <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">${pet.unique_id}</span>
                        ${pet.is_complete === false ? '<div class="mt-1"><span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded">Incomplete</span></div>' : ''}
                    </div>
                </div>
            `;
            
            petsList.appendChild(petCard);
        });
        
        petSelectionInterface.classList.remove('hidden');
    }

    // Select a pet from multiple options
    function selectPet(pet) {
        showSinglePetResult(pet, pet.owner);
    }

    // Create provisional patient
    async function createProvisional(mobile) {
        setLoadingState(true);
        
        try {
            const response = await fetch('{{ route("patient.create-provisional") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ mobile: mobile })
            });

            const data = await response.json();
            
            if (data.success) {
                showProvisionalSuccess(data);
            } else {
                alert(data.message || 'Failed to create provisional patient');
            }
        } catch (error) {
            console.error('Create provisional error:', error);
            alert('Failed to create provisional patient. Please try again.');
        } finally {
            setLoadingState(false);
        }
    }

    // Show provisional creation success
    function showProvisionalSuccess(data) {
        hideAllResults();
        successMessage.textContent = `Provisional patient created with UID: ${data.uid}. You can now print the letterhead.`;
        successModal.classList.remove('hidden');
        
        // Auto-open letterhead after success modal
        setTimeout(() => {
            window.open(`{{ url('/patient/letterhead') }}/${data.uid}`, '_blank');
        }, 2000);
    }

    // Utility functions
    function setLoadingState(loading) {
        if (loading) {
            searchBtn.disabled = true;
            searchBtnText.classList.add('hidden');
            searchBtnLoader.classList.remove('hidden');
        } else {
            searchBtn.disabled = false;
            searchBtnText.classList.remove('hidden');
            searchBtnLoader.classList.add('hidden');
        }
    }

    function hideAllResults() {
        resultsContainer.classList.add('hidden');
        noMatchOptions.classList.add('hidden');
        petSelectionInterface.classList.add('hidden');
        singlePetFound.classList.add('hidden');
    }

    window.tryAnotherSearch = function() {
        hideAllResults();
        searchInput.value = '';
        searchInput.focus();
        inputHint.textContent = 'Enter 6 digits for UID or 10 digits for mobile';
        inputHint.className = 'text-gray-500';
    }

    window.closeSuccessModal = function() {
        successModal.classList.add('hidden');
        tryAnotherSearch();
    }

    // Auto-focus search input
    searchInput.focus();
});
</script>
@endpush
@endsection