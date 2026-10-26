<?php

namespace Tests\Feature;

use App\Models\{Component, Necessary, Tdr, Unit};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class PrlBadgeCountTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    /** @dataProvider scopes */
    public function test_badge_matches_printed_quantities_with_kit_e(string $instruction, string $scope, int $expected): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id,
            'instruction_id' => $this->createInstruction(['name' => $instruction])->id,
            'scope_type' => $scope]);
        $orderNew = Necessary::firstOrCreate(['name' => 'Order New']);
        // W108090: three PRL positions (4+4+1), two KIT parts and three Kit_E parts.
        foreach ([
            ['1-50', '170-70303-001', 4, false, false],
            ['1-60', '170-70310-401', 4, false, false],
            ['1-130', '170-70267-001', 1, false, false],
            ['1-110', 'PE24030-K1-05-220-20', 1, true, false],
            ['1-120', 'NAS6705DG20', 1, true, false],
            ['1-210', 'MS17986C616', 1, false, true],
            ['1-220', '2301-0101-001', 1, false, true],
            ['1-580', '170-70266-001', 1, false, true],
        ] as [$ipl, $pn, $qty, $kit, $extra]) {
            $part = Component::create(['manual_id' => $wo->unit->manual_id,
                'ipl_num' => $ipl, 'part_number' => $pn, 'name' => 'PRL badge part',
                'units_assy' => 1, 'kit' => $kit, 'kit_e' => $extra]);
            Tdr::create(['workorder_id' => $wo->id, 'component_id' => $part->id,
                'order_component_id' => $part->id, 'qty' => $qty, 'necessaries_id' => $orderNew->id]);
        }
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword()]);
        $print = $this->get(route('tdrs.prlForm', $wo))->assertOk();
        $show = $this->get(route('tdrs.show', $wo))->assertOk();
        $this->assertSame($expected, (int) collect($print->viewData('ordersParts'))->sum('qty'));
        $this->assertSame($expected, $show->viewData('prlPartsCount'));
    }

    public static function scopes(): array
    {
        return [
            'overhaul excludes KIT and Kit_E' => ['Overhaul', Unit::SCOPE_FULL_UNIT, 9],
            'repair keeps orders' => ['Repair', Unit::SCOPE_FULL_UNIT, 14],
            'individual component has no automatic KIT' => ['Overhaul', Unit::SCOPE_COMPONENT, 14],
        ];
    }
}
