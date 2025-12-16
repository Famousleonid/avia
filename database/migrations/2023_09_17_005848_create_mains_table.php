<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('workorder_id')->constrained()->onDelete('cascade');
            $table->foreignId('general_task_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('task_id')->nullable()->after('workorder_id');
            $table->foreign('task_id')->references('id')->on('tasks')->nullOnDelete();
            $table->date('date_start')->nullable();
            $table->date('date_finish')->nullable();;
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mains');
    }
};
