<?php

namespace Tests\Feature;

use App\Models\QuantumRoLine;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MainQuantumRoTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_ro_details_are_scoped_to_the_exact_wo_and_keep_their_own_dates(): void
    {
        $wo = $this->createWorkorder(['number' => 887321]);
        foreach ([
            ['W887321', 'applied', 'PART-A', '2026-09-20'],
            ['  w887321 ', 'unresolved', 'PART-B', null],
            ['887321', 'dismissed', 'PART-C', '2026-09-22'],
            ['W8873210', 'applied', 'OTHER-WO', '2026-09-25'],
        ] as [$number, $status, $pn, $returned]) {
            QuantumRoLine::create([
                'source_uid' => 'main-ro-test:' . $pn, 'source_hash' => str_repeat('a', 64),
                'wo_number' => $number, 'ro_number' => 'R-TEST', 'pn' => $pn,
                'vendor_name' => '<script>vendor</script>', 'apply_status' => $status,
                'out_date' => '2026-09-10', 'returned_date' => $returned,
                'last_seen_at' => '2026-09-28 09:30:00',
            ]);
        }
        $user = $this->createUserWithRole('Manager');
        $response = $this->actingAs($user)
            ->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()])
            ->getJson(route('mains.quantum-ro-lines', $wo))->assertOk();
        $html = $response->json('html');
        foreach (['PART-A', 'PART-B', 'PART-C', '10/Sep/2026', '20/Sep/2026', '22/Sep/2026', 'Part No.', '&lt;script&gt;'] as $value) {
            $this->assertStringContainsString($value, $html);
        }
        $this->assertStringNotContainsString('OTHER-WO', $html);
        $this->assertStringNotContainsString('<script>', $html);
        preg_match('/<td data-ro-pn="PART-B"[^>]*>.*?<\/td>/s', $html, $row);
        $this->assertNotEmpty($row);
        $this->assertStringNotContainsString('20/Sep/2026', $row[0]);
        $this->assertStringContainsString('Returned: <span>-</span>', $row[0]);
        $this->assertSame(3, substr_count($html, 'class="main-ro-entry"'));
    }

    public function test_matrix_keeps_serials_and_multiple_ro_details_separate(): void
    {
        \App\Models\ProcessName::create(['name' => 'Matrix process', 'process_sheet_name' => 'Matrix process', 'code' => 'QATEST', 'sp_sort_order' => 1]);
        $lines = collect([
            new QuantumRoLine(['pn' => 'PART-1', 'serial_number' => 'SN1', 'bom_ref' => 'QATEST', 'ro_number' => 'R1']),
            new QuantumRoLine(['pn' => 'PART-1', 'serial_number' => 'SN2', 'bom_ref' => 'QATEST', 'ro_number' => 'R2']),
            new QuantumRoLine(['pn' => 'PART-1', 'serial_number' => 'SN1', 'bom_ref' => 'QATEST', 'ro_number' => 'R3']),
            new QuantumRoLine(['pn' => 'PART-1', 'serial_number' => 'SN1', 'bom_ref' => 'T2', 'ro_number' => 'R4']),
        ]);
        $matrix = app(\App\Services\QuantumRoMatrix::class)->build($lines);
        $this->assertCount(2, $matrix['columns']);
        $this->assertCount(2, $matrix['rows']);
        $this->assertSame('Matrix process', $matrix['rows'][0]['label']);
        $cells = array_values($matrix['rows'][0]['cells']);
        $this->assertSame(['R1', 'R3'], array_map(fn ($line) => $line->ro_number, $cells[0]));
        $this->assertSame('R2', $cells[1][0]->ro_number);
        $this->assertSame('Traveler 2', $matrix['rows'][1]['label']);
    }

    public function test_empty_state_and_authentication(): void
    {
        $wo = $this->createWorkorder(['number' => 887322]);
        $this->getJson(route('mains.quantum-ro-lines', $wo))->assertUnauthorized();
        $user = $this->createUserWithRole('Admin');
        $response = $this->actingAs($user)
            ->withSession(['auth.version' => (int) $user->auth_version, 'password_hash_web' => $user->getAuthPassword()])
            ->getJson(route('mains.quantum-ro-lines', $wo))->assertOk();
        $this->assertStringContainsString('No synchronized RO lines', $response->json('html'));
    }

    public function test_only_managers_and_admins_can_see_the_tab_and_read_ro_data(): void
    {
        $wo = $this->createWorkorder(['number' => 887323]);
        foreach (['Admin', 'Manager', 'Technician', 'Team Leader', 'Paint'] as $role) {
            $user = $this->createUserWithRole($role);
            $this->actingAs($user)->withSession([
                'auth.version' => (int) $user->auth_version,
                'password_hash_web' => $user->getAuthPassword(),
            ]);
            $allowed = in_array($role, ['Admin', 'Manager'], true);
            $this->getJson(route('mains.quantum-ro-lines', $wo))->assertStatus($allowed ? 200 : 403);
            $page = $this->get(route('mains.show', $wo))->assertOk();
            if ($allowed) {
                $page->assertSee('id="main-ro-panel"', false)->assertSee("allowedTabs.push('ro')", false);
            } else {
                $page->assertDontSee('id="main-ro-panel"', false)
                    ->assertDontSee('data-main-tab="ro" role="tab"', false)
                    ->assertDontSee("allowedTabs.push('ro')", false);
            }
        }
    }
}
