<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Intake - MetroVet Clinic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen" x-data="patientIntake()">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold text-gray-900">MetroVet Clinic</h1>
                        <span class="ml-3 text-sm text-gray-500">Patient Intake System</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        <span x-text="currentTime"></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto py-12 px-4">
            <!-- Search Section -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-6">
                <h2 class="text-xl font-semibold mb-6 text-gray-800">Find Patient</h2>
                
                <div class="space-y-4">
                    <div class="relative">
                        <input
                            type="text"
                            x-model="searchInput"
                            @keyup.enter="searchPatient"
                            @input="clearError"
                            class="w-full px-4 py-3 text-lg border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:outline-none"
                            placeholder="Enter 10-digit Mobile Number or 6-digit UID"
                            autofocus
                            maxlength="10"
                            pattern="[0-9]*"
                        >
                        <div class="absolute right-3 top-3">
                            <button 
                                @click="searchPatient"
                                class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600 transition"
                            >
                                Search
                            </button>
                        </div>
                    </div>
                    
                    <div x-show="error" class="bg-red-50 text-red-600 p-3 rounded-md" x-text="error"></div>
                    
                    <div class="flex gap-2 text-sm text-gray-600">
                        <button @click="setExample('9867999773')" class="hover:text-blue-600">
                            Example Mobile: 9867999773
                        </button>
                        <span>|</span>
                        <button @click="setExample('250001')" class="hover:text-blue-600">
                            Example UID: 250001
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Section -->
            <div x-show="showResults" class="bg-white rounded-lg shadow-md p-8">
                <!-- Patient Found -->
                <div x-show="patientFound">
                    <h3 class="text-lg font-semibold mb-4 text-green-600">✓ Patient Found</h3>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="text-sm text-gray-600">Owner Name</label>
                            <p class="font-medium" x-text="owner?.name"></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">Mobile</label>
                            <p class="font-medium" x-text="owner?.display_mobile"></p>
                        </div>
                    </div>

                    <!-- Single Pet -->
                    <div x-show="pets.length === 1" class="border-t pt-4">
                        <h4 class="font-medium mb-3">Pet Details</h4>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="text-sm text-gray-600">Name</label>
                                <p class="font-medium" x-text="pets[0]?.name"></p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-600">UID</label>
                                <p class="font-medium" x-text="pets[0]?.unique_id"></p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-600">Species</label>
                                <p class="font-medium" x-text="pets[0]?.species?.common_name"></p>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex gap-3">
                            <button 
                                @click="printLetterhead(pets[0].id)"
                                class="bg-green-500 text-white px-6 py-2 rounded-md hover:bg-green-600"
                            >
                                Print Letterhead
                            </button>
                            <button 
                                @click="openVisit(pets[0].id)"
                                class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600"
                            >
                                Open Visit
                            </button>
                        </div>
                    </div>

                    <!-- Multiple Pets -->
                    <div x-show="pets.length > 1" class="border-t pt-4">
                        <h4 class="font-medium mb-3">Select Pet</h4>
                        <div class="space-y-3">
                            <template x-for="pet in pets" :key="pet.id">
                                <div class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer" @click="selectPet(pet)">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium" x-text="pet.name"></p>
                                            <p class="text-sm text-gray-600">
                                                <span x-text="pet.species?.common_name"></span> • 
                                                UID: <span x-text="pet.unique_id"></span>
                                            </p>
                                        </div>
                                        <button class="text-blue-600 hover:text-blue-800">
                                            Select →
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Not Found - Create Provisional -->
                <div x-show="!patientFound && showProvisional">
                    <h3 class="text-lg font-semibold mb-4 text-orange-600">Patient Not Found</h3>
                    <p class="text-gray-600 mb-6">Create a provisional record to proceed with the visit.</p>
                    
                    <div class="space-y-4">
                        <input
                            type="text"
                            x-model="provisional.owner_name"
                            class="w-full px-4 py-2 border rounded-md"
                            placeholder="Owner Name (Optional)"
                        >
                        <input
                            type="text"
                            x-model="provisional.pet_name"
                            class="w-full px-4 py-2 border rounded-md"
                            placeholder="Pet Name (Optional)"
                        >
                        <input
                            type="text"
                            x-model="provisional.mobile"
                            class="w-full px-4 py-2 border rounded-md"
                            placeholder="Mobile Number (Optional)"
                            maxlength="10"
                        >
                        
                        <div class="flex gap-3">
                            <button 
                                @click="createProvisional"
                                class="bg-orange-500 text-white px-6 py-2 rounded-md hover:bg-orange-600"
                            >
                                Create Provisional & Print
                            </button>
                            <button 
                                @click="resetSearch"
                                class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div x-show="loading" class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <p class="mt-2 text-gray-600">Searching...</p>
            </div>
        </main>
    </div>

    <script>
        function patientIntake() {
            return {
                searchInput: '',
                error: '',
                loading: false,
                showResults: false,
                patientFound: false,
                showProvisional: false,
                owner: null,
                pets: [],
                currentTime: '',
                provisional: {
                    owner_name: '',
                    pet_name: '',
                    mobile: ''
                },
                
                init() {
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);
                },
                
                updateTime() {
                    const now = new Date();
                    this.currentTime = now.toLocaleString('en-IN', {
                        weekday: 'short',
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                },
                
                setExample(value) {
                    this.searchInput = value;
                    this.searchPatient();
                },
                
                clearError() {
                    this.error = '';
                },
                
                resetSearch() {
                    this.searchInput = '';
                    this.error = '';
                    this.showResults = false;
                    this.patientFound = false;
                    this.showProvisional = false;
                    this.owner = null;
                    this.pets = [];
                    this.provisional = { owner_name: '', pet_name: '', mobile: '' };
                },
                
                async searchPatient() {
                    if (!this.searchInput) {
                        this.error = 'Please enter a mobile number or UID';
                        return;
                    }
                    
                    this.loading = true;
                    this.error = '';
                    
                    try {
                        const response = await fetch('/patient/search', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: JSON.stringify({ search: this.searchInput })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.showResults = true;
                            this.patientFound = true;
                            this.owner = data.owner;
                            this.pets = data.pets || (data.pet ? [data.pet] : []);
                        } else {
                            this.showResults = true;
                            this.patientFound = false;
                            this.showProvisional = true;
                            if (data.mobile) {
                                this.provisional.mobile = data.mobile;
                            }
                        }
                    } catch (error) {
                        this.error = 'Network error. Please try again.';
                    } finally {
                        this.loading = false;
                    }
                },
                
                async createProvisional() {
                    this.loading = true;
                    
                    try {
                        const response = await fetch('/patient/provisional', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: JSON.stringify(this.provisional)
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Open letterhead in new window for printing
                            window.open(`/patient/letterhead/${data.pet.id}`, '_blank');
                            this.resetSearch();
                        }
                    } catch (error) {
                        this.error = 'Failed to create provisional record';
                    } finally {
                        this.loading = false;
                    }
                },
                
                selectPet(pet) {
                    this.printLetterhead(pet.id);
                },
                
                printLetterhead(petId) {
                    window.open(`/patient/letterhead/${petId}`, '_blank');
                },
                
                openVisit(petId) {
                    window.location.href = `/admin/resources/pets/${petId}`;
                }
            }
        }
    </script>
</body>
</html>