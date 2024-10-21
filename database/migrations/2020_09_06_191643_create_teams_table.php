<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('team_id')->references('id')->on('teams')
                ->onDelete('set null');
        });

        DB::table('teams')->insert([
            ['name' => 'Management'],
            ['name' => 'Akimov`s team'],
            ['name' => 'Blinov`s team'],
            ['name' => 'Steblyk`s team'],
            ['name' => 'Tchalyi`s team'],
            ['name' => 'Barysevich`s team'],
            ['name' => 'Volker`s team'],
            ['name' => 'Never stop`s team'],
            ['name' => 'Lipikhin`s team'],
        ]);

        $dataUser = [
            ['name' => 'admin', 'stamp' => '01', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'admin@admin.com', 'email_verified_at' => '2024-01-21 11:44:05', 'created_at' => '2024-01-21 11:44:05','updated_at'=> '2024-01-21 11:44:05' , 'is_admin' => '1'],
            ['name' => 'user', 'stamp' => '77', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'user@user.com', 'email_verified_at' => '2024-01-21 11:44:05', 'created_at' => '2024-01-21 11:44:05','updated_at'=> '2024-01-21 11:44:05' ,  'is_admin' => '0'],
            ['name' => 'Leo', 'stamp' => '09', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'famousleonid@gmail.com', 'email_verified_at' => '2024-01-21 11:44:05', 'created_at' => '2024-01-21 11:44:05','updated_at'=> '2024-01-21 11:44:05' ,  'is_admin' => '0'],
            ['name' => 'Taras St.', 'stamp' => '22', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'taras1@taras.com', 'email_verified_at' => null, 'created_at' => '2024-01-21 11:44:05','updated_at'=> '2024-01-21 11:44:05' ,  'is_admin' => '0'],
            ['name' => 'Yury B.', 'stamp' => '14', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all1@aviatechnik.ca', 'email_verified_at' => null, 'created_at' => '2024-01-21 11:44:05','updated_at'=> '2024-01-21 11:44:05' ,  'is_admin' => '0'],
            ['name' => 'Roman', 'stamp' => '19', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all2@aviatechnik.ca', 'email_verified_at' => null, 'created_at' => '2024-01-21 11:44:05','updated_at'=> '2024-01-21 11:44:05' ,  'is_admin' => '0'],
            ['name' => 'Alex B.', 'stamp' => '12', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all3@aviatechnik.ca', 'email_verified_at' => null, 'created_at' => '2024-01-21 11:44:05','updated_at'=> '2024-01-21 11:44:05' ,  'is_admin' => '0'],
            ['name' => 'Dinmukhamed', 'stamp' => '21', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all4@aviatechnik.ca', 'email_verified_at' => null, 'created_at' => '2024-01-21 11:44:05','updated_at'=> '2024-01-21 11:44:05' ,  'is_admin' => '0'],
            ['name' => 'Dmitry', 'stamp' => '15', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all5@aviatechnik.ca', 'email_verified_at' => null, 'created_at' => '2024-01-21 11:44:05','updated_at'=> '2024-01-21 11:44:05' ,  'is_admin' => '0'],
            ['name' => 'A.F.', 'stamp' => '06', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all6@aviatechnik.ca', 'email_verified_at' => null, 'created_at' => '2024-01-21 11:44:05','updated_at'=> '2024-01-21 11:44:05' ,  'is_admin' => '0'],
            ['name' => 'Andrey L.', 'stamp' => '05', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all7@aviatechnik.ca', 'email_verified_at' => null, 'created_at' => '2024-01-21 11:44:05','updated_at'=> '2024-01-21 11:44:05',  'is_admin' => '0'],
        ];
        User::insert($dataUser);
    }

    public function down()
    {
        Schema::dropIfExists('teams');
    }
};
