<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_company_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('marketing_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('customer_marketing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('lifecycle_status', 20)->default('existing');
            $table->string('country')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('company_type_id')->nullable()->constrained('marketing_company_types')->nullOnDelete();
            $table->foreignId('segment_id')->nullable()->constrained('marketing_segments')->nullOnDelete();
            $table->string('terms_label')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('last_contact_at')->nullable();
            $table->date('next_follow_up_at')->nullable();
            $table->timestamps();

            $table->unique('customer_id');
            $table->index(['lifecycle_status', 'country']);
            $table->index(['company_type_id', 'segment_id']);
            $table->index('next_follow_up_at');
        });

        Schema::create('customer_aircraft', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plane_id')->constrained('planes')->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'plane_id'], 'customer_aircraft_unique');
            $table->index('plane_id');
        });

        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('position')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'is_active', 'sort_order']);
            $table->index('email');
        });

        Schema::create('customer_interaction_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('customer_contacts')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->date('interaction_at');
            $table->date('follow_up_at')->nullable();
            $table->string('follow_up_status', 20)->default('open');
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'interaction_at']);
            $table->index(['follow_up_status', 'follow_up_at', 'reminder_sent_at'], 'customer_notes_followup_idx');
        });

        $now = now();

        DB::table('marketing_company_types')->insert([
            ['name' => 'MRO', 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'OEM', 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Airline', 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Operator', 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Broker', 'sort_order' => 50, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Leasing', 'sort_order' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'MRO/Operator', 'sort_order' => 70, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('marketing_segments')->insert([
            ['name' => 'Regional', 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Business', 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Business/Regional', 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_interaction_notes');
        Schema::dropIfExists('customer_contacts');
        Schema::dropIfExists('customer_aircraft');
        Schema::dropIfExists('customer_marketing_profiles');
        Schema::dropIfExists('marketing_segments');
        Schema::dropIfExists('marketing_company_types');
    }
};
