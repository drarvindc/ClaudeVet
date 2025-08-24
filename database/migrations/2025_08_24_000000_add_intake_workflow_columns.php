<?php
// Create this file: database/migrations/2025_08_24_000000_add_intake_workflow_columns.php

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
        // Add columns to owners table
        Schema::table('owners', function (Blueprint $table) {
            if (!Schema::hasColumn('owners', 'is_complete')) {
                $table->boolean('is_complete')->default(true)->after('created_via');
            }
        });

        // Add columns to pets table
        Schema::table('pets', function (Blueprint $table) {
            if (!Schema::hasColumn('pets', 'created_via')) {
                $table->enum('created_via', ['web', 'mobile', 'provisional'])->default('web')->after('status');
            }
            if (!Schema::hasColumn('pets', 'is_complete')) {
                $table->boolean('is_complete')->default(true)->after('created_via');
            }
            if (!Schema::hasColumn('pets', 'duplicate_of_uid')) {
                $table->string('duplicate_of_uid', 10)->nullable()->after('is_complete');
            }
            if (!Schema::hasColumn('pets', 'is_duplicate')) {
                $table->boolean('is_duplicate')->default(false)->after('duplicate_of_uid');
            }
        });

        // Remove mobile_e164 column if it exists
        Schema::table('owner_mobiles', function (Blueprint $table) {
            if (Schema::hasColumn('owner_mobiles', 'mobile_e164')) {
                $table->dropColumn('mobile_e164');
            }
        });

        // Add indexes
        Schema::table('owner_mobiles', function (Blueprint $table) {
            $table->index(['mobile', 'owner_id', 'is_primary'], 'idx_owner_mobiles_search');
            $table->index(['mobile'], 'idx_owner_mobiles_mobile_lookup');
        });

        Schema::table('pets', function (Blueprint $table) {
            $table->index(['owner_id', 'status', 'unique_id'], 'idx_pets_owner_status');
            $table->index(['unique_id'], 'idx_pets_uid_lookup');
            $table->index(['is_complete', 'created_via', 'created_at'], 'idx_pets_incomplete_profiles');
            $table->index(['is_duplicate', 'duplicate_of_uid'], 'idx_pets_duplicate_tracking');
        });

        // Create audit log table
        Schema::create('duplicate_audit_log', function (Blueprint $table) {
            $table->id();
            $table->enum('action', ['mark_duplicate', 'unmark_duplicate', 'cross_reference']);
            $table->string('source_uid', 10);
            $table->string('target_uid', 10)->nullable();
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['source_uid'], 'idx_duplicate_audit_source');
            $table->index(['target_uid'], 'idx_duplicate_audit_target');
            $table->index(['admin_user_id'], 'idx_duplicate_audit_admin');
            
            $table->foreign('admin_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('duplicate_audit_log');
        
        Schema::table('pets', function (Blueprint $table) {
            $table->dropIndex('idx_pets_duplicate_tracking');
            $table->dropIndex('idx_pets_incomplete_profiles');
            $table->dropIndex('idx_pets_uid_lookup');
            $table->dropIndex('idx_pets_owner_status');
            $table->dropColumn(['created_via', 'is_complete', 'duplicate_of_uid', 'is_duplicate']);
        });

        Schema::table('owner_mobiles', function (Blueprint $table) {
            $table->dropIndex('idx_owner_mobiles_mobile_lookup');
            $table->dropIndex('idx_owner_mobiles_search');
        });

        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn('is_complete');
        });
    }
};