<?php

namespace Tests\Feature;

use App\Models\{Code, Component, Necessary, Tdr};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class PrlManualHeaderTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    /** @dataProvider manualCases */
    public function test_cmm_header_uses_the_manuals_of_printed_parts(array $numbers, string $expected): void
    {
        $admin = $this->createUserWithRole('Admin');
        $main = $this->createManual(['number' => '32-28-15']);
        $other = $this->createManual(['number' => '32-28-21']);
        $wo = $this->createWorkorder([
            'user_id' => $admin->id,
            'unit_id' => $this->createUnit(['manual_id' => $main->id])->id,
            'instruction_id' => $this->createOverhaulInstruction()->id,
        ]);
        $order = Necessary::firstOrCreate(['name' => 'Order New']);
        $code = Code::firstOrCreate(['name' => 'Damage'], ['code' => 'D']);
        foreach ($numbers as $index => $number) {
            $part = Component::firstOrCreate([
                'manual_id' => $number === $main->number ? $main->id : $other->id,
                'ipl_num' => ['3-120A', '3-140A', '3-150A', '3-160A', '3-160A'][$index],
            ], [
                'part_number' => ['NAS1102-4-10', 'AN4-5A', 'NAS1149F0423P', 'AN4-5A', 'AN4-5A'][$index],
                'name' => 'PRL part', 'units_assy' => 1,
            ]);
            Tdr::create([
                'workorder_id' => $wo->id, 'component_id' => $part->id,
                'order_component_id' => $part->id, 'qty' => $index === 4 ? 1 : 2,
                'necessaries_id' => $order->id, 'codes_id' => $code->id,
            ]);
        }
        $response = $this->actingAs($admin)->withSession([
            'auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword(),
        ])->get(route('tdrs.prlForm', $wo))->assertOk();
        $this->assertCount(count($numbers), $response->viewData('ordersParts'));
        $this->assertSame(count(array_unique($numbers)) > 1, $response->viewData('hasMultipleManuals'));

        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $cmm = $xpath->query('//h6[strong[normalize-space(.)="CMM:"]]/parent::div/following-sibling::div[1]');
        $this->assertCount(1, $cmm);
        $this->assertSame($expected, trim($cmm->item(0)->textContent));
        $headings = $xpath->query('//div[contains(concat(" ",normalize-space(@class)," ")," manual-row ")]');
        $this->assertCount(count(array_unique($numbers)) > 1 ? 2 : 0, $headings);
        if (count($numbers) === 5) {
            $this->assertSame(9, (int) collect($response->viewData('ordersParts'))->sum('qty'));
            file_put_contents(sys_get_temp_dir().'/prl-w108091-manual-qa.html', $response->getContent());
        }
    }

    public static function manualCases(): array
    {
        return [
            'W108091 all orders from covering manual' => [array_fill(0, 5, '32-28-21'), '32-28-21'],
            'only main manual' => [['32-28-15'], '32-28-15'],
            'mixed manuals' => [['32-28-21', '32-28-15'], '32-28-15; 32-28-21'],
            'empty form' => [[], '32-28-15'],
        ];
    }
}
