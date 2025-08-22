<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Owner;
use App\Models\OwnerMobile;
use App\Models\Pet;
use App\Models\Species;
use App\Models\Breed;
use App\Models\Visit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $this->createAdminUser();
        
        // Create species and breeds
        $this->createSpeciesAndBreeds();
        
        // Create sample data if enabled
        if (env('ENABLE_SAMPLE_DATA', true)) {
            $this->createSampleData();
        }
    }
    
    private function createAdminUser(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@metrovet.in')],
            [
                'name' => 'Admin',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'MetroVet@2025')),
                'role' => 'admin',
                'is_active' => true,
            ]
        );
        
        // Create a sample doctor
        User::firstOrCreate(
            ['email' => 'doctor@metrovet.in'],
            [
                'name' => 'Dr. Sharma',
                'password' => Hash::make('doctor123'),
                'role' => 'doctor',
                'degree' => 'BVSc & AH',
                'registration_no' => 'MVC-2024-001',
                'phone' => '9876543210',
                'is_active' => true,
            ]
        );
    }
    
    private function createSpeciesAndBreeds(): void
    {
        $speciesData = [
            'Canine' => [
                'common_name' => 'Dog',
                'breeds' => [
                    'Labrador Retriever', 'German Shepherd', 'Golden Retriever',
                    'Beagle', 'Pug', 'Rottweiler', 'Cocker Spaniel',
                    'Siberian Husky', 'Shih Tzu', 'Pomeranian', 'Indian Spitz',
                    'Indie (Indian Pariah)', 'Mixed Breed'
                ]
            ],
            'Feline' => [
                'common_name' => 'Cat',
                'breeds' => [
                    'Persian', 'Siamese', 'Maine Coon', 'British Shorthair',
                    'Indian Cat', 'Mixed Breed'
                ]
            ],
            'Avian' => [
                'common_name' => 'Bird',
                'breeds' => [
                    'Budgerigar', 'Cockatiel', 'Parrot', 'Lovebird', 'Canary'
                ]
            ],
            'Rabbit' => [
                'common_name' => 'Rabbit',
                'breeds' => [
                    'Dutch', 'Lionhead', 'Rex', 'Angora', 'Mixed Breed'
                ]
            ],
        ];
        
        foreach ($speciesData as $name => $data) {
            $species = Species::firstOrCreate(
                ['name' => $name],
                ['common_name' => $data['common_name']]
            );
            
            foreach ($data['breeds'] as $breedName) {
                Breed::firstOrCreate([
                    'species_id' => $species->id,
                    'name' => $breedName
                ]);
            }
        }
    }
    
    private function createSampleData(): void
    {
        $indianNames = [
            'Rajesh Kumar', 'Priya Sharma', 'Amit Patel', 'Sneha Desai',
            'Vikram Singh', 'Anjali Mehta', 'Suresh Reddy', 'Kavita Joshi',
            'Arjun Nair', 'Pooja Gupta', 'Ravi Verma', 'Neha Kulkarni',
            'Sanjay Iyer', 'Divya Rao', 'Manoj Pandey', 'Sunita Bhatt',
            'Arun Kapoor', 'Meera Saxena', 'Rohit Agarwal', 'Anita Chopra'
        ];
        
        $petNames = [
            'dogs' => ['Bruno', 'Max', 'Charlie', 'Buddy', 'Rocky', 'Duke', 'Cooper', 'Bear', 'Tucker', 'Leo'],
            'cats' => ['Luna', 'Bella', 'Lucy', 'Kitty', 'Cleo', 'Simba', 'Milo', 'Oliver', 'Smokey', 'Shadow'],
            'birds' => ['Tweety', 'Polly', 'Sunny', 'Blue', 'Kiwi', 'Mango', 'Rio', 'Sky'],
            'rabbits' => ['Bunny', 'Cotton', 'Snowball', 'Oreo', 'Pepper']
        ];
        
        $localities = [
            'Koregaon Park', 'Baner', 'Kothrud', 'Viman Nagar', 'Hadapsar',
            'Kalyani Nagar', 'Aundh', 'Wakad', 'Hinjewadi', 'Shivajinagar',
            'Deccan', 'Camp', 'Pimpri', 'Chinchwad', 'Warje'
        ];
        
        $dogSpecies = Species::where('name', 'Canine')->first();
        $catSpecies = Species::where('name', 'Feline')->first();
        
        foreach (array_slice($indianNames, 0, 10) as $index => $name) {
            // Create owner
            $owner = Owner::create([
                'name' => $name,
                'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                'address' => 'Flat ' . rand(1, 500) . ', Building ' . chr(65 + rand(0, 5)),
                'locality' => $localities[array_rand($localities)],
                'city' => 'Pune',
                'pincode' => '4110' . str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT),
                'status' => 'active',
                'created_via' => 'web',
                'is_sample_data' => true,
            ]);
            
            // Add mobile numbers
            OwnerMobile::create([
                'owner_id' => $owner->id,
                'mobile' => '98' . rand(10000000, 99999999),
                'mobile_e164' => '+9198' . rand(10000000, 99999999),
                'is_primary' => true,
                'is_whatsapp' => true,
            ]);
            
            // Create 1-2 pets per owner
            $numPets = rand(1, 2);
            for ($p = 0; $p < $numPets; $p++) {
                $isDog = rand(0, 1); // Dog or cat
                $species = $isDog ? $dogSpecies : $catSpecies;
                $breeds = $species->breeds;
                
                $uid = $this->generateUid();
                
                $pet = Pet::create([
                    'unique_id' => $uid,
                    'owner_id' => $owner->id,
                    'name' => $isDog ? 
                        $petNames['dogs'][array_rand($petNames['dogs'])] : 
                        $petNames['cats'][array_rand($petNames['cats'])],
                    'species_id' => $species->id,
                    'breed_id' => $breeds->random()->id,
                    'gender' => ['male', 'female'][rand(0, 1)],
                    'age_years' => rand(1, 10),
                    'age_months' => rand(0, 11),
                    'color' => ['Black', 'White', 'Brown', 'Golden', 'Mixed'][rand(0, 4)],
                    'weight' => $isDog ? rand(5, 40) : rand(3, 8),
                    'status' => 'active',
                    'is_sample_data' => true,
                ]);
                
                // Create 1-3 visits for each pet
                $numVisits = rand(1, 3);
                for ($v = 0; $v < $numVisits; $v++) {
                    $visitDate = now()->subDays(rand(0, 90));
                    
                    Visit::create([
                        'uuid' => Str::uuid(),
                        'pet_id' => $pet->id,
                        'visit_date' => $visitDate,
                        'visit_number' => $v + 1,
                        'chief_complaint' => $this->getRandomComplaint(),
                        'examination_notes' => 'Physical examination completed.',
                        'diagnosis' => $this->getRandomDiagnosis(),
                        'treatment_plan' => 'Treatment plan as per diagnosis.',
                        'prescription' => $this->getRandomPrescription(),
                        'follow_up_date' => $visitDate->addDays(rand(7, 30)),
                        'visit_type' => ['consultation', 'vaccination', 'surgery', 'checkup'][rand(0, 3)],
                        'status' => 'completed',
                        'total_amount' => rand(500, 5000),
                        'paid_amount' => rand(500, 5000),
                        'balance_amount' => 0,
                        'payment_status' => 'paid',
                        'created_at' => $visitDate,
                        'updated_at' => $visitDate,
                    ]);
                }
            }
        }
    }
    
    private function getRandomComplaint(): string
    {
        $complaints = [
            'Loss of appetite and lethargy',
            'Vomiting and diarrhea',
            'Skin allergies and itching',
            'Ear infection and discharge',
            'Limping and joint pain',
            'Routine vaccination',
            'General health checkup',
            'Dental cleaning required',
            'Eye discharge and redness',
            'Respiratory issues and coughing'
        ];
        
        return $complaints[array_rand($complaints)];
    }
    
    private function getRandomDiagnosis(): string
    {
        $diagnoses = [
            'Gastroenteritis',
            'Allergic dermatitis',
            'Otitis externa',
            'Arthritis',
            'Healthy - routine vaccination',
            'Conjunctivitis',
            'Upper respiratory infection',
            'Dental tartar buildup',
            'Parasitic infection',
            'Nutritional deficiency'
        ];
        
        return $diagnoses[array_rand($diagnoses)];
    }
    
    private function getRandomPrescription(): string
    {
        $prescriptions = [
            'Antibiotic course for 7 days, probiotics',
            'Antihistamine tablets, medicated shampoo',
            'Ear cleaning solution, topical antibiotics',
            'Pain relief medication, joint supplements',
            'Vaccination administered successfully',
            'Eye drops, antibiotic ointment',
            'Cough suppressant, rest',
            'Dental scaling recommended',
            'Deworming medication',
            'Nutritional supplements, diet change'
        ];
        
        return $prescriptions[array_rand($prescriptions)];
    }
    
    private function generateUid(): string
    {
        // Generate UID in YY#### format (6 characters total)
        $year = date('y'); // Last 2 digits of current year
        $sequence = rand(1, 9999); // Random 4-digit sequence
        
        return $year . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}