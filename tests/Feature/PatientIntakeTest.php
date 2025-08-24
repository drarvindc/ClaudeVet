<?php
// tests/Feature/PatientIntakeTest.php - Fixed for new database structure

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Pet;
use App\Models\OwnerMobile;
use App\Models\Species;
use App\Models\Breed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PatientIntakeTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user for authentication
        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);
        
        // Authenticate all requests
        $this->actingAs($admin);
        
        // Create default species and breeds for testing
        $species = Species::create([
            'name' => 'Canine',
            'common_name' => 'Dog',
            'is_active' => true
        ]);
        
        Breed::create([
            'species_id' => $species->id,
            'name' => 'Mixed Breed'
        ]);
    }

    /** @test */
    public function it_can_search_by_valid_uid()
    {
        // Arrange: Create a test pet with owner
        $owner = Owner::create([
            'name' => 'John Doe',
            'status' => 'active',
            'created_via' => 'web',
            'is_complete' => true
        ]);
        
        $pet = Pet::create([
            'unique_id' => '251001',
            'owner_id' => $owner->id,
            'name' => 'Buddy',
            'species_id' => 1,
            'breed_id' => 1,
            'gender' => 'male',
            'status' => 'active',
            'created_via' => 'web',
            'is_complete' => true
        ]);

        // Act: Search by UID
        $response = $this->postJson('/patient/search', [
            'search' => '251001'
        ]);

        // Assert: Should find the pet
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'action' => 'pet_found',
                     'pet' => [
                         'unique_id' => '251001',
                         'name' => 'Buddy'
                     ]
                 ]);
    }

    /** @test */
    public function it_can_search_by_valid_mobile()
    {
        // Arrange: Create owner with mobile number
        $owner = Owner::create([
            'name' => 'Jane Smith',
            'status' => 'active',
            'created_via' => 'web',
            'is_complete' => true
        ]);
        
        OwnerMobile::create([
            'owner_id' => $owner->id,
            'mobile' => '9876543210',
            'is_primary' => true
        ]);
        
        $pet = Pet::create([
            'unique_id' => '251002',
            'owner_id' => $owner->id,
            'name' => 'Max',
            'species_id' => 1,
            'breed_id' => 1,
            'gender' => 'male',
            'status' => 'active',
            'created_via' => 'web',
            'is_complete' => true
        ]);

        // Act: Search by mobile
        $response = $this->postJson('/patient/search', [
            'search' => '9876543210'
        ]);

        // Assert: Should find the pet
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'action' => 'single_pet_found',
                     'pet' => [
                         'unique_id' => '251002',
                         'name' => 'Max'
                     ]
                 ]);
    }

    /** @test */
    public function it_returns_multiple_pets_for_family_mobile()
    {
        // Arrange: Create owner with multiple pets
        $owner = Owner::create([
            'name' => 'Family Johnson',
            'status' => 'active',
            'created_via' => 'web',
            'is_complete' => true
        ]);
        
        OwnerMobile::create([
            'owner_id' => $owner->id,
            'mobile' => '9123456789',
            'is_primary' => true
        ]);
        
        $pet1 = Pet::create([
            'unique_id' => '251003',
            'owner_id' => $owner->id,
            'name' => 'Rex',
            'species_id' => 1,
            'breed_id' => 1,
            'gender' => 'male',
            'status' => 'active',
            'created_via' => 'web',
            'is_complete' => true
        ]);
        
        $pet2 = Pet::create([
            'unique_id' => '251004',
            'owner_id' => $owner->id,
            'name' => 'Luna',
            'species_id' => 1,
            'breed_id' => 1,
            'gender' => 'female',
            'status' => 'active',
            'created_via' => 'web',
            'is_complete' => true
        ]);

        // Act: Search by mobile
        $response = $this->postJson('/patient/search', [
            'search' => '9123456789'
        ]);

        // Assert: Should return multiple pets
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'action' => 'multiple_pets_found'
                 ])
                 ->assertJsonCount(2, 'pets');
    }

    /** @test */
    public function it_can_create_provisional_patient()
    {
        // Act: Create provisional patient
        $response = $this->postJson('/patient/create-provisional', [
            'mobile' => '8765432109'
        ]);

        // Assert: Should create provisional patient
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'action' => 'provisional_created',
                     'mobile' => '8765432109'
                 ]);
        
        // Verify database records
        $this->assertDatabaseHas('owners', [
            'name' => 'Incomplete Owner',
            'created_via' => 'provisional',
            'is_complete' => false
        ]);
        
        $this->assertDatabaseHas('owner_mobiles', [
            'mobile' => '8765432109',
            'is_primary' => true
        ]);
        
        $this->assertDatabaseHas('pets', [
            'name' => 'Incomplete Pet',
            'created_via' => 'provisional',
            'is_complete' => false
        ]);
    }

    /** @test */
    public function it_rejects_invalid_input_formats()
    {
        // Test invalid UID (too short)
        $response = $this->postJson('/patient/search', [
            'search' => '12345'
        ]);
        
        $response->assertStatus(422);
        
        // Test invalid mobile (too long)
        $response = $this->postJson('/patient/search', [
            'search' => '12345678901'
        ]);
        
        $response->assertStatus(422);
    }

    /** @test */
    public function it_validates_mobile_number_format()
    {
        // Test mobile not starting with valid digits
        $response = $this->postJson('/patient/search', [
            'search' => '1234567890'
        ]);
        
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9'
                 ]);
    }

    /** @test */
    public function it_prevents_duplicate_mobile_in_provisional_creation()
    {
        // Arrange: Create existing owner with mobile
        $owner = Owner::create([
            'name' => 'Existing Owner',
            'status' => 'active',
            'created_via' => 'web',
            'is_complete' => true
        ]);
        
        OwnerMobile::create([
            'owner_id' => $owner->id,
            'mobile' => '9988776655',
            'is_primary' => true
        ]);

        // Act: Try to create provisional with same mobile
        $response = $this->postJson('/patient/create-provisional', [
            'mobile' => '9988776655'
        ]);

        // Assert: Should reject
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => false,
                     'message' => 'This mobile number already exists in the system. Please search instead.'
                 ]);
    }

    /** @test */
    public function it_generates_unique_uid_for_provisional_patients()
    {
        $currentYear = date('y');
        
        // Create first provisional patient
        $response1 = $this->postJson('/patient/create-provisional', [
            'mobile' => '9111111111'
        ]);
        
        $response1->assertStatus(200);
        $uid1 = $response1->json('uid');
        
        // Create second provisional patient
        $response2 = $this->postJson('/patient/create-provisional', [
            'mobile' => '9222222222'
        ]);
        
        $response2->assertStatus(200);
        $uid2 = $response2->json('uid');
        
        // Assert: UIDs should be different and follow format
        $this->assertNotEquals($uid1, $uid2);
        $this->assertStringStartsWith($currentYear, $uid1);
        $this->assertStringStartsWith($currentYear, $uid2);
        $this->assertEquals(6, strlen($uid1));
        $this->assertEquals(6, strlen($uid2));
    }

    /** @test */
    public function it_can_access_letterhead_with_valid_uid()
    {
        // Arrange: Create test pet
        $owner = Owner::create([
            'name' => 'Test Owner',
            'status' => 'active',
            'created_via' => 'web',
            'is_complete' => true
        ]);
        
        $pet = Pet::create([
            'unique_id' => '251999',
            'owner_id' => $owner->id,
            'name' => 'Test Pet',
            'species_id' => 1,
            'breed_id' => 1,
            'gender' => 'male',
            'status' => 'active',
            'created_via' => 'web',
            'is_complete' => true
        ]);

        // Act: Access letterhead
        $response = $this->get('/patient/letterhead/251999');

        // Assert: Should load successfully
        $response->assertStatus(200)
                 ->assertViewIs('patient.letterhead')
                 ->assertViewHas('pet', $pet);
    }

    /** @test */
    public function it_returns_404_for_invalid_uid_letterhead()
    {
        $response = $this->get('/patient/letterhead/999999');
        $response->assertStatus(404);
    }

    /** @test */
    public function mobile_normalization_works_correctly()
    {
        // Test the OwnerMobile normalization method
        $this->assertEquals('9876543210', OwnerMobile::normalizeMobile('9876543210'));
        $this->assertEquals('9876543210', OwnerMobile::normalizeMobile('+919876543210'));
        $this->assertEquals('9876543210', OwnerMobile::normalizeMobile('91-9876543210'));
        $this->assertEquals('9876543210', OwnerMobile::normalizeMobile(' 98 765 43210 '));
    }

    /** @test */
    public function mobile_validation_works_correctly()
    {
        // Valid mobile numbers
        $this->assertTrue(OwnerMobile::validateMobile('9876543210'));
        $this->assertTrue(OwnerMobile::validateMobile('8123456789'));
        $this->assertTrue(OwnerMobile::validateMobile('7987654321'));
        $this->assertTrue(OwnerMobile::validateMobile('6123456789'));
        
        // Invalid mobile numbers
        $this->assertFalse(OwnerMobile::validateMobile('1234567890')); // Doesn't start with 6,7,8,9
        $this->assertFalse(OwnerMobile::validateMobile('98765432')); // Too short
        $this->assertFalse(OwnerMobile::validateMobile('98765432100')); // Too long
        $this->assertFalse(OwnerMobile::validateMobile('abcdefghij')); // Non-numeric
    }
}