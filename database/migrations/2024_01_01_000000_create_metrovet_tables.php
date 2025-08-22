<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Users table (for admin/staff)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'doctor', 'staff'])->default('staff');
            $table->string('degree')->nullable();
            $table->string('registration_no')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // Year counters for UID generation
        Schema::create('year_counters', function (Blueprint $table) {
            $table->id();
            $table->char('year_two', 2)->unique();
            $table->unsignedInteger('last_seq')->default(0);
            $table->timestamp('updated_at');
        });

        // Owners
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('locality')->nullable();
            $table->string('city')->default('Pune');
            $table->string('pincode', 6)->nullable();
            $table->enum('status', ['active', 'provisional'])->default('provisional');
            $table->enum('created_via', ['web', 'android', 'visit_entry'])->default('web');
            $table->boolean('is_sample_data')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status');
            $table->index('is_sample_data');
        });

        // Owner mobiles (multiple per owner)
        Schema::create('owner_mobiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->onDelete('cascade');
            $table->string('mobile', 10);
            $table->string('mobile_e164', 13)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_whatsapp')->default(true);
            $table->timestamps();
            
            $table->unique(['owner_id', 'mobile']);
            $table->index('mobile');
            $table->index('mobile_e164');
        });

        // Species
        Schema::create('species', function (Blueprint $table) {
            $table->id();
            $table->string('name', 30)->unique();
            $table->string('common_name', 30)->nullable();
            $table->timestamps();
        });

        // Breeds
        Schema::create('breeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('species_id')->constrained()->onDelete('cascade');
            $table->string('name', 50);
            $table->timestamps();
            
            $table->unique(['species_id', 'name']);
        });

        // Pets
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->char('unique_id', 7)->unique(); // YY####C format with checksum
            $table->foreignId('owner_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->foreignId('species_id')->nullable()->constrained();
            $table->foreignId('breed_id')->nullable()->constrained();
            $table->enum('gender', ['male', 'female', 'unknown'])->default('unknown');
            $table->date('dob')->nullable();
            $table->unsignedSmallInteger('age_years')->nullable();
            $table->unsignedSmallInteger('age_months')->nullable();
            $table->string('color', 30)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('microchip', 20)->nullable();
            $table->text('distinguishing_marks')->nullable();
            $table->enum('status', ['active', 'provisional', 'deceased'])->default('provisional');
            $table->boolean('is_sample_data')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('unique_id');
            $table->index('owner_id');
            $table->index('status');
            $table->index('is_sample_data');
        });

        // Visit sequence counters
        Schema::create('visit_seq_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->unique()->constrained()->onDelete('cascade');
            $table->unsignedInteger('last_visit_seq')->default(0);
            $table->timestamp('updated_at');
        });

        // Visits
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->date('visit_date');
            $table->unsignedInteger('visit_seq');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->enum('source', ['web', 'android', 'pos', 'ingest', 'email', 'whatsapp'])->default('web');
            $table->string('reason', 200)->nullable();
            $table->text('chief_complaint')->nullable();
            $table->text('examination_notes')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment')->nullable();
            $table->text('prescription')->nullable();
            $table->text('remarks')->nullable();
            $table->date('next_visit')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('doctor_id')->nullable()->constrained('users');
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->json('vitals')->nullable();
            $table->boolean('is_sample_data')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['pet_id', 'visit_date', 'visit_seq']);
            $table->index(['visit_date', 'status']);
            $table->index('is_sample_data');
        });

        // Documents
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->char('patient_unique_id', 7);
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->foreignId('visit_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', [
                'photo', 'prescription', 'report', 'xray', 'lab', 'usg', 
                'invoice', 'vaccine', 'deworm', 'tick', 'consent', 
                'referral', 'qrcode', 'barcode', 'certificate'
            ]);
            $table->string('subtype', 60)->nullable();
            $table->string('path');
            $table->string('filename');
            $table->string('original_name')->nullable();
            $table->enum('source', ['web', 'android', 'pos', 'ingest', 'email', 'whatsapp']);
            $table->string('mime_type', 80)->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->datetime('captured_at');
            $table->string('checksum_sha1', 40)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['pet_id', 'captured_at']);
            $table->index('visit_id');
            $table->index(['type', 'captured_at']);
        });

        // Preventive care templates
        Schema::create('preventive_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('species_id')->nullable()->constrained();
            $table->enum('type', ['vaccine', 'deworm', 'tick_flea']);
            $table->string('name', 80);
            $table->string('description')->nullable();
            $table->json('schedule_rules');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Preventive care plans
        Schema::create('preventive_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['vaccine', 'deworm', 'tick_flea']);
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            $table->timestamps();
            
            $table->unique(['pet_id', 'type']);
        });

        // Preventive care items
        Schema::create('preventive_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('preventive_plans')->onDelete('cascade');
            $table->foreignId('pet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['vaccine', 'deworm', 'tick_flea']);
            $table->string('name', 80);
            $table->date('due_date');
            $table->date('window_start')->nullable();
            $table->date('window_end')->nullable();
            $table->enum('status', ['scheduled', 'overdue', 'done', 'skipped'])->default('scheduled');
            $table->foreignId('visit_id')->nullable()->constrained();
            $table->date('given_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['due_date', 'status']);
            $table->index('pet_id');
        });

        // Certificate templates
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->enum('category', ['certificate', 'report', 'letter']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->longText('html_template');
            $table->json('available_tokens')->nullable();
            $table->timestamps();
        });

        // Generated certificates
        Schema::create('generated_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('certificate_templates');
            $table->foreignId('pet_id')->constrained();
            $table->foreignId('visit_id')->nullable()->constrained();
            $table->string('path');
            $table->string('filename');
            $table->json('token_values')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Activity log
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
            
            $table->index('log_name');
        });

        // System settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // Insert default settings
        DB::table('settings')->insert([
            ['key' => 'clinic_name', 'value' => 'MetroVet', 'group' => 'clinic'],
            ['key' => 'clinic_address', 'value' => '304, Popular Nagar Shopping Complex, Warje, Pune', 'group' => 'clinic'],
            ['key' => 'clinic_phone', 'value' => '9867999773', 'group' => 'clinic'],
            ['key' => 'clinic_email', 'value' => 'info@metrovet.in', 'group' => 'clinic'],
            ['key' => 'auto_close_time', 'value' => '00:00', 'group' => 'system'],
            ['key' => 'uid_prefix', 'value' => date('y'), 'group' => 'system'],
            ['key' => 'enable_sms', 'value' => 'false', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'enable_whatsapp', 'value' => 'false', 'type' => 'boolean', 'group' => 'notifications'],
        ]);

        // Insert default species
        DB::table('species')->insert([
            ['name' => 'Canine', 'common_name' => 'Dog'],
            ['name' => 'Feline', 'common_name' => 'Cat'],
            ['name' => 'Avian', 'common_name' => 'Bird'],
            ['name' => 'Rabbit', 'common_name' => 'Rabbit'],
            ['name' => 'Guinea Pig', 'common_name' => 'Guinea Pig'],
            ['name' => 'Hamster', 'common_name' => 'Hamster'],
            ['name' => 'Turtle', 'common_name' => 'Turtle'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('generated_certificates');
        Schema::dropIfExists('certificate_templates');
        Schema::dropIfExists('preventive_items');
        Schema::dropIfExists('preventive_plans');
        Schema::dropIfExists('preventive_templates');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('visits');
        Schema::dropIfExists('visit_seq_counters');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('breeds');
        Schema::dropIfExists('species');
        Schema::dropIfExists('owner_mobiles');
        Schema::dropIfExists('owners');
        Schema::dropIfExists('year_counters');
        Schema::dropIfExists('users');
    }
};