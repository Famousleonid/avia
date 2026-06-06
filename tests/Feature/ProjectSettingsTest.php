<?php

namespace Tests\Feature;

use App\Models\PrintMark;
use App\Models\ProjectSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ProjectSettingsTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_system_admin_can_open_and_update_project_settings(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.project-settings.index'))
            ->assertOk()
            ->assertSee('Project Settings')
            ->assertSee('QR code mark');

        $this->actingAs($admin)
            ->post(route('admin.project-settings.update'), [
                'print_forms_qr_enabled' => '0',
            ])
            ->assertRedirect(route('admin.project-settings.index'));

        $this->assertFalse(ProjectSetting::boolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true));
    }

    public function test_admin_role_without_is_admin_cannot_open_project_settings(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => false]);

        $this->actingAs($roleOnlyAdmin)
            ->get(route('admin.project-settings.index'))
            ->assertForbidden();
    }

    public function test_qr_partial_does_not_create_print_mark_when_setting_is_disabled(): void
    {
        ProjectSetting::setBoolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, false);

        $html = view('shared.print-mark.qr', [
            'printMarkWorkorder' => '107736',
            'printMarkPrintedBy' => 'ADMIN',
            'printMarkPrintedAt' => Carbon::create(2026, 5, 26),
            'printMarkFormName' => 'NDT',
        ])->render();

        $this->assertStringNotContainsString('system-print-qr', $html);
        $this->assertSame(0, PrintMark::query()->count());
    }
}
