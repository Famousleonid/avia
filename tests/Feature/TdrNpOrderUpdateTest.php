<?php

namespace Tests\Feature;

use App\Models\{MachiningWorkStep, Necessary, Tdr, TdrProcess};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class TdrNpOrderUpdateTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public static function orderCases(): array
    {
        return [
            'NP inspected part becomes replacement' => [true, null, false, false, true],
            'existing NP replacement wins over inspected part' => [false, true, false, false, true],
            'new NP component selected during edit' => [false, null, true, false, true],
            'archived NP replacement still protected' => [false, true, false, true, true],
            'NP inspected part with orderable replacement' => [true, false, false, false, false],
            'ordinary replacement' => [false, null, false, false, false],
        ];
    }

    /** @dataProvider orderCases */
    public function test_order_new_checks_actual_replacement_before_any_side_effect(
        bool $sourceNp, ?bool $storedReplacementNp, bool $replaceWithNp, bool $archived, bool $reject
    ): void {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id, 'new_parts' => false]);
        $manual = $wo->unit->manual;
        $source = $this->createComponent($manual, ['np' => $sourceNp, 'units_assy' => 1]);
        $stored = $storedReplacementNp !== null
            ? $this->createComponent($manual, ['np' => $storedReplacementNp]) : null;
        if ($archived) $stored->delete();
        $repair = Necessary::firstOrCreate(['name' => 'Repair']);
        $orderNew = Necessary::firstOrCreate(['name' => 'Order New']);
        $tdr = Tdr::create([
            'workorder_id' => $wo->id, 'component_id' => $source->id,
            'order_component_id' => $stored?->id, 'necessaries_id' => $repair->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR, 'qty' => 1,
            'use_tdr' => true, 'use_process_forms' => true, 'description' => 'Original',
        ]);
        $process = TdrProcess::create(['tdrs_id' => $tdr->id, 'processes' => [], 'description' => 'Repair process']);
        $step = MachiningWorkStep::create(['tdr_process_id' => $process->id, 'step_index' => 1, 'description' => 'Keep on rejection']);
        $payload = ['workorder_id' => $wo->id, 'necessaries_id' => $orderNew->id, 'description' => 'Changed'];
        if ($replaceWithNp) {
            $this->grantFeatureAccess($admin, 'tdrs.replace_component');
            $payload['component_id'] = $this->createComponent($manual, ['np' => true])->id;
        }
        $before = $tdr->fresh()->getAttributes();
        $this->actingAs($admin)->withSession([
            'auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword(),
        ]);
        $response = $this->putJson(route('tdrs.update', $tdr->id), $payload);
        if ($reject) {
            $response->assertUnprocessable()->assertJsonValidationErrors('order_component_id');
            $this->assertSame($before, $tdr->fresh()->getAttributes());
            $this->assertFalse((bool) $wo->fresh()->new_parts);
            $this->assertDatabaseHas('tdr_processes', ['id' => $process->id]);
            $this->assertDatabaseHas('machining_work_steps', ['id' => $step->id]);

            // NP still permits ordinary Repair edits.
            $this->putJson(route('tdrs.update', $tdr->id), [
                'workorder_id' => $wo->id, 'necessaries_id' => $repair->id, 'description' => 'Repair edit',
            ])->assertOk();
            $this->assertSame('Repair edit', $tdr->fresh()->description);
        } else {
            $response->assertOk()->assertJsonPath('success', true);
            $this->assertSame($stored?->id ?? $source->id, $tdr->fresh()->order_component_id);
            $this->assertSame($orderNew->id, $tdr->fresh()->necessaries_id);
            $this->assertTrue((bool) $wo->fresh()->new_parts);
            $this->assertDatabaseMissing('tdr_processes', ['id' => $process->id]);
            $this->assertDatabaseMissing('machining_work_steps', ['id' => $step->id]);
        }
    }
}
