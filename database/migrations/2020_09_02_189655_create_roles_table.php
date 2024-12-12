<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Component Technician')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')
                ->onDelete('set null');
        });

        $dataRoles = [
            ['name' => 'Component Technician'],
            ['name' => 'Team Leader'],
            ['name' => 'Shop Certifying Authority (SCA)'],
        ];

        Role::insert($dataRoles);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};


