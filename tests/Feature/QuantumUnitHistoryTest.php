<?php
namespace Tests\Feature;

use App\Services\QuantumUnitHistory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\{BuildsDomainData, TestCase};

class QuantumUnitHistoryTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;
    private QuantumUnitHistory $history;
    private string $snapshotPath;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-11-04 12:00:00');
        $this->snapshotPath = storage_path('framework/testing/unit-history-' . uniqid() . '.json');
        $this->history = new class($this->snapshotPath) extends QuantumUnitHistory {
            public function __construct(private string $snapshotPath) {}
            public function path(): string { return $this->snapshotPath; }
        };
        $this->app->instance(QuantumUnitHistory::class, $this->history);
    }

    protected function tearDown(): void
    {
        foreach ([$this->snapshotPath, $this->snapshotPath . '.lock'] as $path) { if (is_file($path)) unlink($path); }
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function payload(): array
    {
        $base = ['customer' => 'Test customer', 'closed_at' => null, 'status' => 'WIP', 'quantity' => 1,
            'serials' => 'SN1', 'quantity_review' => false, 'parent_id' => null];
        return ['version' => 1, 'exported_at' => '2026-11-04T12:00:00Z', 'row_count' => 4, 'rows' => [
            array_merge($base, ['source_id' => 1, 'wo_number' => 'W100001', 'part_number' => '190-70745-401', 'opened_at' => '2025-01-01', 'closed_at' => '2025-03-01']),
            array_merge($base, ['source_id' => 2, 'wo_number' => 'W100002', 'part_number' => '190-70745-403', 'opened_at' => '2026-09-01', 'quantity' => 2]),
            array_merge($base, ['source_id' => 3, 'wo_number' => 'W100003', 'part_number' => '190-70744-403', 'opened_at' => '2026-10-01']),
            array_merge($base, ['source_id' => 4, 'wo_number' => 'W100004', 'part_number' => '190-70745-403', 'opened_at' => '2026-10-01', 'parent_id' => 2]),
        ]];
    }

    public function test_history_includes_closed_wo_counts_quantity_and_zero_months_without_child_duplicates(): void
    {
        $this->history->import($this->payload());
        $r = $this->history->report('190-70745');
        $this->assertSame('190-70745', $r['group']);
        $this->assertSame(2, $r['rows']->count());
        $this->assertEquals(3, $r['quantity']);
        $this->assertEquals(2, $r['last12']);
        $this->assertEquals(0, collect($r['months'])->firstWhere('month', 'Oct 2026')['quantity']);
        $this->assertSame('Nov 2026', end($r['months'])['month']);
        $this->assertEquals(2, $this->history->report('190-70745', '2026-01-01')['quantity']);
        $this->assertSame(0, $this->history->report('190-70745-NOTFOUND')['rows']->count());
        $this->assertSame('ABC', QuantumUnitHistory::group('ABC'));
    }

    public function test_part_number_prefix_keeps_suffix_and_filters_totals(): void
    {
        $payload = $this->payload();
        $base = $payload['rows'][0];
        $payload['rows'] = [];
        foreach (['55000-1', '55000-2', '55000-3', '55000-3A', '55000-3-1', '55000-30', '55000-4'] as $i => $pn) {
            $payload['rows'][] = array_merge($base, ['source_id' => $i + 1, 'wo_number' => 'W' . (100001 + $i),
                'part_number' => $pn, 'opened_at' => '2026-10-01']);
        }
        $payload['row_count'] = count($payload['rows']);
        $this->history->import($payload);
        $this->assertCount(7, $this->history->report('55000')['rows']);
        $r = $this->history->report(' 55000-3 ');
        $this->assertEqualsCanonicalizing(['55000-3', '55000-3A', '55000-3-1', '55000-30'], $r['rows']->pluck('part_number')->all());
        $this->assertEquals(4, $r['quantity']);
        $this->assertEquals(4, $r['last12']);
        $this->assertCount(1, $this->history->report('55000-3a')['rows']);
        $this->assertCount(0, $this->history->report('55000-9')['rows']);
    }

    public function test_atomic_import_rejects_duplicates_and_preserves_existing_history(): void
    {
        $payload = $this->payload();
        $this->history->import($payload);
        $payload['rows'][1]['source_id'] = 1;
        try { $this->history->import($payload); $this->fail('Duplicate accepted'); }
        catch (ValidationException $e) { $this->assertSame(4, count($this->history->read()['rows'])); }
        $payload = $this->payload();
        $payload['exported_at'] = '2026-10-01T12:00:00Z';
        $this->expectException(ValidationException::class);
        $this->history->import($payload);
    }

    public function test_page_access_search_date_filter_and_sync_authentication(): void
    {
        $this->history->import($this->payload());
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin)->get(route('workorders.status.unit-history', ['group' => '190-70745', 'from' => '01/Jan/2026']))
            ->assertOk()->assertSee('W100002')->assertDontSee('W100001')->assertSee('Unit History');
        $this->actingAs($admin)->get(route('workorders.status.unit-history', ['group' => '190-70745', 'to' => '01/Jan/2026']))
            ->assertOk()->assertSee('W100001')->assertDontSee('W100002');
    }

    public function test_technician_cannot_access_history(): void
    {
        $tech = $this->createUserWithRole('Technician');
        $this->actingAs($tech)->getJson(route('workorders.status.unit-history'))->assertForbidden();
    }

    public function test_snapshot_ingestion_requires_bridge_token(): void
    {
        config(['services.quantum_sync.token' => 'test-history-token']);
        $this->postJson('/api/quantum/unit-history', $this->payload())->assertUnauthorized();
        $this->withToken('test-history-token')->postJson('/api/quantum/unit-history', $this->payload())->assertOk();
    }

    public function test_description_survives_api_import_and_is_escaped_next_to_part_number(): void
    {
        config(['services.quantum_sync.token' => 'test-history-token']);
        $payload = $this->payload();
        $payload['rows'][0]['description'] = 'MAIN GEAR <script>test</script>';
        $this->withToken('test-history-token')->postJson('/api/quantum/unit-history', $payload)->assertOk();
        $this->assertSame($payload['rows'][0]['description'], $this->history->read()['rows'][0]['description']);
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin)->get(route('workorders.status.unit-history', ['group' => '190-70745']))
            ->assertOk()->assertSee('<th>Unit P/N</th><th>Description</th>', false)
            ->assertSee('MAIN GEAR &lt;script&gt;test&lt;/script&gt;', false)
            ->assertDontSee('<script>test</script>', false);
    }

    public function test_suggestions_include_unique_groups_and_full_parts_with_descriptions_across_all_dates(): void
    {
        $payload = $this->payload();
        $payload['rows'][0]['description'] = 'NLG Shock Strut';
        $payload['rows'][1]['description'] = 'NLG Assembly';
        $payload['rows'][3]['description'] = 'Child only';
        $this->history->import($payload);
        $items = collect($this->history->report('not-found', '2026-11-01')['suggestions'])->keyBy('value');
        $this->assertCount(5, $items);
        $this->assertSame('Group · NLG Assembly / NLG Shock Strut', $items['190-70745']['label']);
        $this->assertSame('P/N · NLG Shock Strut', $items['190-70745-401']['label']);
        $this->assertSame('P/N · NLG Assembly', $items['190-70745-403']['label']);
        $this->assertSame('P/N', $items['190-70744-403']['label']);
    }
}
