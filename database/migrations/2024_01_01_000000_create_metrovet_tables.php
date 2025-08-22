<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create species table
        Schema::create('species', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('common_name', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create breeds table
        Schema::create('breeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('species_id')->constrained()->onDelete('cascade');
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['species_id', 'name']);
        });

        // Create owners table
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('locality', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('created_via', ['web', 'mobile', 'provisional'])->default('web');
            $table->boolean('is_sample_data')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Create owner_mobiles table
        Schema::create('owner_mobiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->onDelete('cascade');
            $table->string('mobile', 20);
            $table->string('mobile_e164', 20);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_whatsapp')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        // Create pets table
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id', 10)->unique(); // YY#### format (allow up to 10 chars)
            $table->foreignId('owner_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->foreignId('species_id')->constrained();
            $table->foreignId('breed_id')->constrained();
            $table->enum('gender', ['male', 'female']);
            $table->integer('age_years')->nullable();
            $table->integer('age_months')->nullable();
            $table->string('color')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->text('distinguishing_marks')->nullable();
            $table->string('microchip_number')->nullable();
            $table->enum('sterilization_status', ['intact', 'neutered', 'spayed'])->nullable();
            $table->enum('status', ['active', 'inactive', 'deceased'])->default('active');
            $table->boolean('is_sample_data')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Create visits table
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->date('visit_date');
            $table->integer('visit_number')->default(1);
            $table->integer('sequence')->default(1);
            $table->text('chief_complaint')->nullable();
            $table->text('examination_notes')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('prescription')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->enum('visit_type', ['consultation', 'vaccination', 'surgery', 'checkup', 'emergency'])->default('consultation');
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])->default('open');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance_amount', 10, 2)->default(0);
            $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('pending');
            $table->timestamps();
            
            $table->unique(['pet_id', 'visit_date', 'sequence']);
        });

        // Create documents table
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->foreignId('pet_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['prescription', 'lab', 'xray', 'usg', 'photo', 'certificate', 'report']);
            $table->string('filename');
            $table->integer('filesize')->nullable();
            $table->string('mime', 100)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Create year_counters table for UID generation
        Schema::create('year_counters', function (Blueprint $table) {
            $table->id();
            $table->string('year_two', 2)->unique(); // Last 2 digits of year
            $table->integer('last_seq')->default(0);
            $table->timestamps();
        });

        // Create visit_seq_counters table
        Schema::create('visit_seq_counters', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();
            $table->integer('last_seq')->default(0);
            $table->timestamps();
        });

        // Create api_tokens table for mobile app
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('owner_mobiles');
        Schema::dropIfExists('owners');
        Schema::dropIfExists('breeds');
        Schema::dropIfExists('species');
        Schema::dropIfExists('year_counters');
        Schema::dropIfExists('visit_seq_counters');
        Schema::dropIfExists('api_tokens');
    }
};