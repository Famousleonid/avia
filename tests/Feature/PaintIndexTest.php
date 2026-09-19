<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Workorder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class PaintIndexTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_paint_index_defaults_to_date_start_filter_and_marks_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $withDateStart = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 880001,
            'approve_at' => '2026-06-01',
            'open_at' => null,
        ]);
        $withoutDateStart = $this->createWorkorder([
            'user_id' => $admin->id,
            'number' => 880002,
            'approve_at' => '2026-06-01',
            'open_at' => '2026-05-28',
        ]);

        $this->attachPaintDetail($withDateStart, 'PAINT-DATE-START', '2026-06-03');
        $this->attachPaintDetail($withoutDateStart, 'PAINT-NO-DATE-START');

        $response = $this->actingAs($admin)
            ->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()])
            ->get(route('paint.index'));

        $response->assertOk();
        $response->assertSee('id="paintTableState" class="paint-table-state is-loading"', false);
        $response->assertSee('Loading', false);
        $response->assertSee('paint-loading-dots', false);
        $response->assertSee('id="paintOnlyDateStartRows" checked', false);
        $response->assertSee('Date start only', false);
        $response->assertSee('data-paint-has-date-start="1"', false);
        $response->assertSee('data-paint-has-date-start="0"', false);
        $response->assertDontSee('paintOnlyArrivalRows', false);
        $response->assertDontSee('data-paint-has-arrival', false);
        $response->assertSee('PAINT-DATE-START', false);
        $response->assertSee('PAINT-NO-DATE-START', false);
    }

    /** @dataProvider finishDateUsers */
    public function test_finish_date_access_is_enforced_in_table_and_endpoint(string $role, int $teamId, bool $allowed): void
    {
        $user = $this->createUserWithRole($role, ['team_id' => $teamId]);
        $this->grantFeatureAccess($user, 'paint');
        $wo = $this->createWorkorder(['user_id' => $user->id, 'approve_at' => '2026-06-01']);
        $process = $this->attachPaintDetail($wo, 'PAINT-ACCESS', '2026-06-03');
        $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()]);

        $page = $this->get(route('paint.index'))->assertOk();
        $page->assertSee('data-date-kind="date_start"', false);
        if ($allowed) {
            $page->assertSee('data-date-kind="date_finish"', false);
        } else {
            $page->assertDontSee('data-date-kind="date_finish"', false);
        }

        $this->patchJson(route('tdrprocesses.updateDate', $process), ['date_start' => '2026-06-02'])->assertOk();
        $response = $this->patchJson(route('tdrprocesses.updateDate', $process), ['date_finish' => '2026-06-04', 'from_paint_index' => 1]);
        if ($allowed) {
            $response->assertOk();
            $this->assertSame('2026-06-04', $process->fresh()->date_finish->format('Y-m-d'));
            $this->assertSame($user->id, (int) $process->fresh()->date_finish_user_id);
        } else {
            $response->assertForbidden();
            $this->assertNull($process->fresh()->date_finish);
            // Omitting the UI marker must not bypass the permission.
            $this->patchJson(route('tdrprocesses.updateDate', $process), ['date_finish' => '2026-06-04'])->assertForbidden();
            $process->update(['date_finish' => '2026-06-05']);
            $this->patchJson(route('tdrprocesses.updateDate', $process), ['date_finish' => null])->assertForbidden();
            $this->assertSame('2026-06-05', $process->fresh()->date_finish->format('Y-m-d'));
        }
    }

    public static function finishDateUsers(): array
    {
        return [
            'Admin' => ['Admin', 1, true],
            'Paint in Never stop' => ['Paint', 8, true],
            'Technician in Never stop' => ['Technician', 8, true],
            'Paint in another team' => ['Paint', 2, false],
            'Manager in Never stop' => ['Manager', 8, false],
            'Technician in another team' => ['Technician', 2, false],
        ];
    }

    public function test_manager_cannot_change_paint_finish_through_traveler_group(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $wo = $this->createWorkorder(['user_id' => $manager->id]);
        $paint = $this->attachPaintDetail($wo, 'PAINT-TRAVELER', '2026-06-03');
        $paint->update(['in_traveler' => true, 'traveler_group' => 1, 'date_finish' => '2026-06-04']);
        $this->actingAs($manager)->withSession(['auth.version' => (int) $manager->auth_version, 'password_hash_web' => $manager->getAuthPassword()]);
        $this->patchJson(route('tdrprocesses.updateDate', $paint), ['date_finish' => '2026-06-05'])->assertForbidden();
        $this->patchJson(route('tdrprocesses.updateTravelerGroupDates', $paint->tdrs_id), ['traveler_group' => 1, 'date_finish' => null])->assertForbidden();
        $this->patchJson(route('tdrprocesses.updateTravelerGroupDates', $paint->tdrs_id), ['traveler_group' => 1, 'date_start' => null])->assertForbidden();
        $this->assertSame('2026-06-04', $paint->fresh()->date_finish->format('Y-m-d'));
    }

    private function attachPaintDetail(Workorder $workorder, string $partNumber, ?string $dateStart = null): TdrProcess
    {
        $processName = ProcessName::query()->firstOrCreate(
            ['name' => 'Paint'],
            [
                'process_sheet_name' => 'PAINT',
                'form_number' => 'PAINT',
                'print_form' => true,
                'show_in_process_picker' => true,
            ]
        );

        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => $partNumber,
            'name' => 'Paint detail',
            'ipl_num' => $partNumber,
            'eff_code' => 'ALL',
        ]);

        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'SN-' . $partNumber,
            'assy_serial_number' => '',
            'qty' => 1,
            'use_tdr' => true,
            'use_process_forms' => true,
        ]);

        return TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'date_start' => $dateStart,
        ]);
    }
}
