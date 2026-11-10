<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('user_ui_settings', 'device_key')) {
            Schema::table('user_ui_settings', fn (Blueprint $table) => $table->string('device_key', 64)->default(''));
        }
        // Create the replacement first: the FK needs an index beginning with user_id.
        Schema::table('user_ui_settings', function (Blueprint $table) {
            $table->unique(['user_id', 'scope', 'key', 'device_key'], 'ui_settings_user_scope_key_device_unique');
        });
        Schema::table('user_ui_settings', fn (Blueprint $table) => $table->dropUnique(['user_id', 'scope', 'key']));
        Schema::create('user_ui_setting_devices', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_key', 64);
            $table->timestamp('created_at');
            $table->primary(['user_id', 'device_key']);
        });
    }

    public function down(): void
    {
        // Keep original account preferences when reverting the browser-profile feature.
        DB::table('user_ui_settings')->where('device_key', '!=', '')->delete();
        Schema::dropIfExists('user_ui_setting_devices');
        Schema::table('user_ui_settings', fn (Blueprint $table) => $table->unique(['user_id', 'scope', 'key']));
        Schema::table('user_ui_settings', function (Blueprint $table) {
            $table->dropUnique('ui_settings_user_scope_key_device_unique');
            $table->dropColumn('device_key');
        });
    }
};
