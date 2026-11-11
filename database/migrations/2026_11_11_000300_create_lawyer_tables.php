<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lawyer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('public_code', 20)->unique();   // e.g. MLN-000123, quoted by clients on calls
            $table->string('slug')->unique();

            // Public details
            $table->string('full_name');
            $table->string('gender', 20)->nullable();       // male | female | other | undisclosed
            $table->string('designation')->nullable();      // e.g. Advocate, Senior Advocate
            $table->string('firm_name')->nullable();
            $table->unsignedSmallInteger('practice_since'); // year -> experience is computed from this
            $table->string('education')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedInteger('consultation_fee')->nullable();
            $table->boolean('offers_online_consultation')->default(false);
            $table->string('photo_path')->nullable();

            $table->foreignId('country_id')->constrained();
            $table->foreignId('state_id')->constrained();
            $table->foreignId('city_id')->constrained();

            // PRIVATE details - never sent to public pages
            $table->string('phone', 20);
            $table->string('alternate_phone', 20)->nullable();
            $table->string('office_address', 500)->nullable();
            $table->string('pincode', 12)->nullable();
            $table->string('enrolment_number', 60);
            $table->string('bar_council')->nullable();
            $table->date('date_of_birth')->nullable();

            // Workflow
            $table->string('status', 20)->default('pending'); // pending | approved | rejected | suspended
            $table->string('status_reason', 1000)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('profile_views')->default(0);
            $table->timestamps();

            // Search indexes: status first because public search always filters on it
            $table->index(['status', 'state_id', 'city_id']);
            $table->index(['status', 'city_id']);
            $table->index(['status', 'practice_since']);
            $table->index(['status', 'is_featured', 'approved_at']);
            $table->index('full_name');
            $table->index('enrolment_number');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE lawyer_profiles ADD FULLTEXT ft_lawyer_search (full_name, firm_name)');
        }

        foreach ([
            'lawyer_practice_area' => 'practice_area',
            'language_lawyer' => 'language',
            'court_lawyer' => 'court',
        ] as $pivot => $other) {
            Schema::create($pivot, function (Blueprint $table) use ($other) {
                $table->foreignId('lawyer_profile_id')->constrained()->cascadeOnDelete();
                $table->foreignId($other.'_id')->constrained()->cascadeOnDelete();
                $table->primary(['lawyer_profile_id', $other.'_id']);
                $table->index([$other.'_id', 'lawyer_profile_id']);
            });
        }

        Schema::create('lawyer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);       // enrolment_certificate | id_proof | other
            $table->string('path');           // on the private disk
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
        });

        Schema::create('profile_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lawyer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('changes');           // {field: {old, new}}
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['reviewed_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_change_logs');
        Schema::dropIfExists('lawyer_documents');
        Schema::dropIfExists('court_lawyer');
        Schema::dropIfExists('language_lawyer');
        Schema::dropIfExists('lawyer_practice_area');
        Schema::dropIfExists('lawyer_profiles');
    }
};
