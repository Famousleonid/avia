<?php

namespace Tests\Feature;

use App\Models\MarketingWoEstimateNotification;
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
        $customer = $this->createCustomer(['name' => 'Settings Reminder Customer']);
        $workorder = $this->createWorkorder([
            'customer_id' => $customer->id,
            'number' => 108001,
            'wo_estimate_date' => '2026-07-01',
        ]);
        $notification = MarketingWoEstimateNotification::query()->create([
            'workorder_id' => $workorder->id,
            'customer_id' => $customer->id,
            'estimate_date' => '2026-07-01',
            'triggered_at' => '2026-07-01 08:00:00',
            'due_at' => '2026-07-02 08:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.project-settings.index'))
            ->assertOk()
            ->assertSee('Project Settings')
            ->assertSee('QR code mark')
            ->assertSee('WO Estimate Date email recipients');

        $this->actingAs($admin)
            ->post(route('admin.project-settings.update'), [
                'print_forms_qr_enabled' => '0',
                'marketing_wo_estimate_email_recipients' => "sales@example.test\nManager@Example.Test",
                'marketing_wo_estimate_email_delay_days' => '3',
            ])
            ->assertRedirect(route('admin.project-settings.index'));

        $this->assertFalse(ProjectSetting::boolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true));
        $this->assertSame(['sales@example.test', 'manager@example.test'], ProjectSetting::marketingWoEstimateEmailRecipients());
        $this->assertSame(3, ProjectSetting::marketingWoEstimateEmailDelayDays());
        $this->assertDatabaseHas('marketing_wo_estimate_notifications', [
            'id' => $notification->id,
            'due_at' => '2026-07-04 00:00:00',
        ]);
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
