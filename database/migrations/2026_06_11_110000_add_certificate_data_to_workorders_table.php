<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->json('certificate_data')->nullable()->after('rm_report');
        });

        $allowedKeys = [
            'certificate_item_source',
            'certificate_tracking_mode',
            'certificate_manager_id',
            'certificate_date',
            'include_landing_gear_log_card',
            'include_royco_service',
        ];

        DB::table('user_ui_settings')
            ->where('scope', 'like', 'quality.certificate.wo.%')
            ->whereIn('key', $allowedKeys)
            ->orderBy('updated_at')
            ->orderBy('id')
            ->get(['scope', 'key', 'value'])
            ->groupBy(function ($setting): int {
                return (int) preg_replace('/^quality\.certificate\.wo\./', '', (string) $setting->scope);
            })
            ->each(function ($settings, int $workorderId): void {
                $certificateData = [];

                foreach ($settings as $setting) {
                    $decodedValue = json_decode((string) $setting->value, true);
                    $value = json_last_error() === JSON_ERROR_NONE
                        ? $decodedValue
                        : $setting->value;

                    if (in_array((string) $setting->key, ['certificate_manager_id', 'certificate_date', 'include_landing_gear_log_card', 'include_royco_service'], true)) {
                        $certificateData['item_settings']['main'][(string) $setting->key] = $value;
                    } else {
                        $certificateData[(string) $setting->key] = $value;
                    }
                }

                if ($certificateData !== []) {
                    DB::table('workorders')
                        ->where('id', $workorderId)
                        ->update(['certificate_data' => json_encode($certificateData)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('workorders', function (Blueprint $table) {
            $table->dropColumn('certificate_data');
        });
    }
};
