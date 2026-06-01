<?php

namespace Tests\Feature;

use App\Models\QuantumRoLine;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class QuantumRoSyncStateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_state_returns_ro_numbers_that_already_have_bom_ref(): void
    {
        config()->set('services.quantum_sync.token', 'test-quantum-token');

        $suffix = (string) random_int(100000, 999999);
        $trackedRo = 'RTRACK' . $suffix;
        $emptyRo = 'REMPTY' . $suffix;
        $blankRo = 'RBLANK' . $suffix;

        QuantumRoLine::query()->create([
            'source_uid' => 'rod:tracked-ref-state-test-' . $suffix,
            'ro_number' => $trackedRo,
            'wo_number' => 'W107616',
            'bom_ref' => 'CP',
            'source_hash' => str_repeat('a', 64),
        ]);
        QuantumRoLine::query()->create([
            'source_uid' => 'rod:empty-ref-state-test-' . $suffix,
            'ro_number' => $emptyRo,
            'wo_number' => 'W107617',
            'bom_ref' => null,
            'source_hash' => str_repeat('b', 64),
        ]);
        QuantumRoLine::query()->create([
            'source_uid' => 'rod:blank-ref-state-test-' . $suffix,
            'ro_number' => $blankRo,
            'wo_number' => 'W107618',
            'bom_ref' => '   ',
            'source_hash' => str_repeat('c', 64),
        ]);

        $response = $this
            ->withToken('test-quantum-token')
            ->getJson('/api/quantum/ro-sync/state');

        $response->assertOk();

        $tracked = $response->json('state.tracked_ref_ro_numbers');

        $this->assertContains($trackedRo, $tracked);
        $this->assertNotContains($emptyRo, $tracked);
        $this->assertNotContains($blankRo, $tracked);
    }
}
