<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\Customer;
use App\Models\GeneralTask;
use App\Models\Instruction;
use App\Models\Task;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;


class InstructionSeeder extends Seeder
{

    public function run()
    {

        $dataInstruction = [
            ['name' => 'overhaul'],
            ['name' => 'repair'],
            ['name' => 'test inspect'],
            ['name' => 'inspect 120 months'],
        ];
        Instruction::insert($dataInstruction);

        $dataTask = [
            ['name' => 'machining'],
            ['name' => 'NDT'],
            ['name' => 'CAD'],
            ['name' => 'rechrome'],
            ['name' => 'shot peen'],
            ['name' => 'anodaizing'],
            ['name' => 'nickel'],
            ['name' => 'stress relief'],
            ['name' => 'paint'],
        ];
        Task::insert($dataTask);

        $dataGeneralTask = [
            ['name' => 'start'],
            ['name' => 'clean'],
            ['name' => 'disassembly'],
            ['name' => 'NDT List'],
            ['name' => 'CAD List'],
            ['name' => 'stress relief'],
            ['name' => 'check bushing'],
            ['name' => 'promote'],
            ['name' => 'assembly'],
            ['name' => 'paint'],
            ['name' => 'test'],
            ['name' => 'done'],
        ];
        GeneralTask::insert($dataGeneralTask);

        $dataUser = [
            ['name' => 'admin', 'stamp' => '01', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'admin@admin.com', 'email_verified_at' => '2024-01-21 11:44:05', 'is_admin' => '1'],
            ['name' => 'user', 'stamp' => '77', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'user@user.com', 'email_verified_at' => '2024-01-21 11:44:05', 'is_admin' => '0'],
            ['name' => 'Leo', 'stamp' => '09', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'famousleonid@gmail.com', 'email_verified_at' => '2024-01-21 11:44:05', 'is_admin' => '0'],
            ['name' => 'Taras St.', 'stamp' => '22', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'taras1@taras.com', 'email_verified_at' => null, 'is_admin' => '0'],
            ['name' => 'Yury B.', 'stamp' => '14', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all1@aviatechnik.ca', 'email_verified_at' => null, 'is_admin' => '0'],
            ['name' => 'Roman', 'stamp' => '19', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all2@aviatechnik.ca', 'email_verified_at' => null, 'is_admin' => '0'],
            ['name' => 'Alex B.', 'stamp' => '12', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all3@aviatechnik.ca', 'email_verified_at' => null, 'is_admin' => '0'],
            ['name' => 'Dinmukhamed', 'stamp' => '21', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all4@aviatechnik.ca', 'email_verified_at' => null, 'is_admin' => '0'],
            ['name' => 'Dmitry', 'stamp' => '15', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all5@aviatechnik.ca', 'email_verified_at' => null, 'is_admin' => '0'],
            ['name' => 'A.F.', 'stamp' => '06', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all6@aviatechnik.ca', 'email_verified_at' => null, 'is_admin' => '0'],
            ['name' => 'Andrey L.', 'stamp' => '05', 'password' => '$2y$10$L/Z93dqMPuaejkSGAnq1LuCfmJbYx2TABpmOikebg5Z/tq2Q9nY1W', 'email' => 'all7@aviatechnik.ca', 'email_verified_at' => null, 'is_admin' => '0'],
        ];
        User::insert($dataUser);

        $dataUnit = [
            ['partnumber' => '2309-3002-516', 'lib' => '145', 'description' => 'ERJ-145'],
            ['partnumber' => '49200-19', 'lib' => '158', 'description' => 'CRJ-900'],
            ['partnumber' => '2842A0000-026', 'lib' => '295', 'description' => 'ERJ-190'],
            ['partnumber' => '2900344-90', 'lib' => '73', 'description' => 'ATR-72'],
            ['partnumber' => 'A1800-12', 'lib' => '77', 'description' => 'A77'],
        ];
        Unit::insert($dataUnit);

        $dataCustomer = [
            ['name' => 'AERGO Capital'],
            ['name' => 'AeroVision Aircraft Ser'],
            ['name' => 'Africa World Airlines'],
            ['name' => 'Air Hamburg Technik'],
            ['name' => 'Aircraft Propeller Serv'],
            ['name' => 'ALS Limited'],
            ['name' => 'Amaszonas S.A.'],
            ['name' => 'Atavis'],
            ['name' => 'Aviatechnik Corporation'],
            ['name' => 'Aviation Inventory Reso'],
            ['name' => 'Be Aero Havacilik A.S.'],
            ['name' => 'C&L Aerospace'],
            ['name' => 'ExecuJet MRO Svcs Middle'],
            ['name' => 'Flightexec'],
            ['name' => 'Fokker Servise BV'],
            ['name' => 'General Atomics Aero'],
            ['name' => 'Jazz Aviation LP'],
            ['name' => 'Kingman Aviation Parts'],
            ['name' => 'Liebherr Aerospace Lind'],
            ['name' => 'Loganair Limited'],
            ['name' => 'LUXAIR S.A.'],
            ['name' => 'Nordic Aviation Capital'],
            ['name' => 'Polskie Linie Lotnicze'],
            ['name' => 'Porter'],
            ['name' => 'Reginal One'],
            ['name' => 'Rheinland Air Service'],
            ['name' => 'Short Brothers Plc'],
            ['name' => 'Summit Air'],
            ['name' => 'Swiftair S.A.'],
            ['name' => 'VistaJet Ltd'],

        ];
        Customer::insert($dataCustomer);

        $dataComponent = [
            ['name' => 'Pin'],
            ['name' => 'Bracket'],
            ['name' => 'Axle'],
            ['name' => 'Main Fitting'],
            ['name' => 'Pintle Pin'],
        ];
        Component::insert($dataComponent);

    }
}
