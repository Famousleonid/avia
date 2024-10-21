<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->tinyInteger('is_admin')->default(0)->unsigned();
            $table->string('phone', 15)->nullable();
            $table->string('stamp', 10)->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('role_id')->nullable();
            $table->foreignId('team_id')->nullable();

        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
