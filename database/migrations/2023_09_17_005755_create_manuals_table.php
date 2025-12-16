<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Manual;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('manuals', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('title')->nullable();
            $table->string('lib')->nullable();
            $table->date('revision_date')->nullable();
            $table->string('unit_name')->nullable();
            $table->string('unit_name_training')->nullable();
            $table->string('training_hours')->nullable();
            $table->foreignId('planes_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('builders_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('scopes_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

        });

    }

    public function down(): void
    {
        Schema::dropIfExists('manuals');
    }
};
