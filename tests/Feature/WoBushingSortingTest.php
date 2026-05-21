<?php

namespace Tests\Feature;

use App\Models\Component;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WoBushingSortingTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_bushing_tab_lists_is_bush_components_by_natural_ipl_before_grouping(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;

        foreach ([
            ['ipl_num' => '6-500', 'part_number' => 'PN-6500', 'bush_ipl_num' => 'GRP-B'],
            ['ipl_num' => '6-490', 'part_number' => 'PN-6490', 'bush_ipl_num' => 'GRP-A'],
            ['ipl_num' => '9A-300', 'part_number' => 'PN-9A300', 'bush_ipl_num' => 'GRP-D'],
            ['ipl_num' => '9A-30', 'part_number' => 'PN-9A030', 'bush_ipl_num' => 'GRP-C'],
            ['ipl_num' => '6-470', 'part_number' => 'NOT-BUSH', 'bush_ipl_num' => 'GRP-Z', 'is_bush' => false],
        ] as $row) {
            Component::query()->create([
                'manual_id' => $manualId,
                'ipl_num' => $row['ipl_num'],
                'part_number' => $row['part_number'],
                'name' => 'Bushing '.$row['ipl_num'],
                'bush_ipl_num' => $row['bush_ipl_num'],
                'is_bush' => $row['is_bush'] ?? true,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('wo_bushings.partial', $workorder->id));

        $response->assertOk();
        $response->assertSeeInOrder(['6-490', '6-500', '9A-30', '9A-300'], false);
        $response->assertDontSee('NOT-BUSH', false);
    }
}
