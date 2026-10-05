<?php

namespace Tests\Feature;

use App\Models\ProjectSetting;
use App\Models\PrintMark;
use App\Services\PrintFormQrSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class PrintFormQrSettingsTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;

    public function test_admin_can_enable_previously_unmarked_forms_and_disable_existing_ones(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        ProjectSetting::where('key', PrintFormQrSettings::KEY)->delete();
        $settings = app(PrintFormQrSettings::class);
        ProjectSetting::setBoolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true);
        $this->assertTrue($settings->enabled('ndt_std'));
        $this->assertFalse($settings->enabled('log_card'));
        $this->actingAs($admin)->post(route('admin.project-settings.update'), [
            'settings_section' => 'printed-forms', 'print_forms_qr_enabled' => 1,
            'qr_forms_present' => 1, 'qr_forms' => ['log_card' => 1, 'kit' => 1, 'training112' => 1],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue($settings->enabled('kit'));
        $this->assertFalse($settings->enabled('ndt_std'));
        $this->actingAs($admin)->get(route('admin.project-settings.index'))->assertOk()->assertSee('qr_forms[kit]', false);
        foreach (['log_card' => true, 'kit' => true, 'ndt_std' => false] as $key => $enabled) {
            $html = view('shared.print-mark.qr', ['printMarkFormKey' => $key, 'printMarkWorkorder' => '108022'])->render();
            $this->assertSame($enabled, str_contains($html, 'class="system-print-qr"'));
        }
        $html = view('shared.print-mark.qr', ['printMarkFormKey' => 'training112', 'printMarkAllowWithoutWorkorder' => true])->render();
        $this->assertStringContainsString('class="system-print-qr"', $html);
        $mark = PrintMark::latest('id')->first();
        $this->assertSame('—', $mark->workorder_number);
        $this->get('/p/'.$mark->token)->assertOk()->assertDontSee('Work Order');
        ProjectSetting::setBoolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, false);
        $this->assertFalse($settings->enabled('log_card'));
        $this->assertTrue($settings->selections()['log_card']);
    }

    public function test_non_system_admin_cannot_change_form_qr_settings(): void
    {
        $user = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $this->actingAs($user)->post(route('admin.project-settings.update'), [
            'settings_section' => 'printed-forms', 'qr_forms_present' => 1,
        ])->assertForbidden();
    }
}
