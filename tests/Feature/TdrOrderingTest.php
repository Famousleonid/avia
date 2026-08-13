<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Tdr;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TdrOrderingTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_saved_tdr_order_is_used_by_tdr_table_and_both_sp_forms(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $tdrs = collect([
            ['ipl' => '1-10', 'part_number' => 'ORDER-PART-A', 'serial' => 'ORDER-SN-A'],
            ['ipl' => '1-20', 'part_number' => 'ORDER-PART-B', 'serial' => 'ORDER-SN-B'],
            ['ipl' => '1-30', 'part_number' => 'ORDER-PART-C', 'serial' => 'ORDER-SN-C'],
        ])->map(function (array $part) use ($workorder): Tdr {
            $component = Component::query()->create([
                'manual_id' => $workorder->unit->manual_id,
                'ipl_num' => $part['ipl'],
                'part_number' => $part['part_number'],
                'name' => $part['part_number'].' name',
            ]);

            return Tdr::query()->create([
                'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
                'workorder_id' => $workorder->id,
                'component_id' => $component->id,
                'serial_number' => $part['serial'],
                'qty' => 1,
                'use_tdr' => true,
                'use_process_forms' => true,
            ]);
        });

        $expectedIds = [$tdrs[2]->id, $tdrs[0]->id, $tdrs[1]->id];

        $this->actingAs($admin)
            ->postJson(route('tdrs.reorder', ['workorder' => $workorder->id]), [
                'tdr_ids' => $expectedIds,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('tdr_ids', $expectedIds);

        $this->assertSame(
            $expectedIds,
            Tdr::query()
                ->where('workorder_id', $workorder->id)
                ->where('use_process_forms', true)
                ->inDisplayOrder()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all()
        );

        $this->actingAs($admin)
            ->get(route('tdrs.show', ['id' => $workorder->id]))
            ->assertOk()
            ->assertViewHas('tdrs', function ($orderedTdrs) use ($expectedIds): bool {
                return $orderedTdrs
                    ->where('use_tdr', true)
                    ->where('use_process_forms', true)
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->values()
                    ->all() === $expectedIds;
            });

        foreach (['tdrs.specProcessForm', 'tdrs.specProcessFormEmp'] as $routeName) {
            $this->actingAs($admin)
                ->get(route($routeName, ['id' => $workorder->id]))
                ->assertOk()
                ->assertViewHas('componentChunks', function ($chunks) use ($expectedIds): bool {
                    return $chunks
                        ->flatten(1)
                        ->pluck('component.id')
                        ->map(fn ($id): int => (int) $id)
                        ->values()
                        ->all() === $expectedIds;
                });
        }
    }

    public function test_reorder_rejects_an_incomplete_or_foreign_tdr_list(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $otherWorkorder = $this->createWorkorder(['user_id' => $admin->id]);

        $createTdr = function ($targetWorkorder, string $partNumber, string $ipl): Tdr {
            $component = Component::query()->create([
                'manual_id' => $targetWorkorder->unit->manual_id,
                'ipl_num' => $ipl,
                'part_number' => $partNumber,
                'name' => $partNumber.' name',
            ]);

            return Tdr::query()->create([
                'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
                'workorder_id' => $targetWorkorder->id,
                'component_id' => $component->id,
                'qty' => 1,
                'use_tdr' => true,
                'use_process_forms' => true,
            ]);
        };

        $first = $createTdr($workorder, 'ORDER-LOCAL-A', '2-10');
        $createTdr($workorder, 'ORDER-LOCAL-B', '2-20');
        $foreign = $createTdr($otherWorkorder, 'ORDER-FOREIGN', '2-10');

        $this->actingAs($admin)
            ->postJson(route('tdrs.reorder', ['workorder' => $workorder->id]), [
                'tdr_ids' => [$first->id, $foreign->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tdr_ids');
    }
}
