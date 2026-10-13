<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualPartGroup;
use App\Models\WoBushing;
use App\Models\WoBushingLine;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class BushingCrossoutIsolationTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    /** @dataProvider forms */
    public function test_unselected_size_does_not_cross_out_same_pn_at_another_position(bool $kit): void
    {
        // WO108022: 8-230 uses two originals; 8-360 uses one original and one oversize.
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id,
            'instruction_id' => $this->createOverhaulInstruction()->id]);
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $parts = [];
        foreach ([230, 360] as $position) {
            $group = ManualPartGroup::create(['manual_id' => $wo->unit->manual_id,
                'code' => 'BUSH-'.$position, 'name' => 'Bushing '.$position,
                'type' => 'oversize', 'behavior' => 'choose_one', 'applies_to' => ['prl']]);
            foreach (['1840-0302', '1840-0302RS01', '1840-0350', '1840-0351'] as $index => $pn) {
                $part = Component::create(['manual_id' => $wo->unit->manual_id,
                    'ipl_num' => '8-'.($position + $index), 'bush_ipl_num' => '8-'.$position,
                    'part_number' => $pn, 'name' => 'BUSHING', 'is_bush' => true,
                    'kit' => $kit && $index < 2, 'units_assy' => $index === 0 ? 2 : 1]);
                $parts[$position][$index] = $part;
                $group->options()->create(['component_id' => $part->id, 'part_number' => $pn,
                    'ipl_num' => $part->ipl_num, 'option_kind' => $index === 0 ? 'original' : 'oversize',
                    'sort_order' => $index]);
                $qty = $index === 0 ? ($position === 230 ? 2 : 1) : ($position === 360 && $index === 3 ? 1 : 0);
                if ($qty > 0) {
                    WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id,
                        'component_id' => $part->id, 'qty' => $qty, 'qty_remaining' => $qty,
                        'do_not_order' => false]);
                }
            }
        }

        $response = $this->actingAs($admin)->withSession([
            'auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword(),
        ])->get(route('tdrs.kitForm', $wo))->assertOk();
        $options = collect($response->viewData('ordersParts'))
            ->flatMap(fn ($row) => $row['prl_part_numbers'])->keyBy('component_id');
        foreach ([230 => [false, true, true, true], 360 => [false, true, true, false]] as $position => $expected) {
            foreach ($expected as $index => $crossedOut) {
                $this->assertSame($crossedOut, $options[$parts[$position][$index]->id]['crossed_out'],
                    'Unexpected crossout at '.$parts[$position][$index]->ipl_num);
            }
        }
        $this->assertSame(1, $options[$parts[360][0]->id]['qty']);
        $this->assertSame(1, $options[$parts[360][3]->id]['qty']);
    }

    public static function forms(): array
    {
        return ['KIT' => [true], 'Extra parts' => [false]];
    }
}
