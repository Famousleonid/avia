<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();

        });

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
    }


    public function down()
    {
        Schema::dropIfExists('customers');
    }
};
