<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\Manual;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ComponentUniquenessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_component_ipl_is_unique_inside_one_manual(): void
    {
        $manual = Manual::query()->create([
            'number' => 'TEST-MANUAL-IPL-'.uniqid(),
        ]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '13-70',
            'part_number' => 'PN-1',
            'name' => 'First component',
        ]);

        $this->expectException(ValidationException::class);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '13-70',
            'part_number' => 'PN-2',
            'name' => 'Second component',
        ]);
    }

    public function test_same_component_ipl_is_allowed_in_different_manuals(): void
    {
        $manualA = Manual::query()->create([
            'number' => 'TEST-MANUAL-A-'.uniqid(),
        ]);
        $manualB = Manual::query()->create([
            'number' => 'TEST-MANUAL-B-'.uniqid(),
        ]);

        Component::query()->create([
            'manual_id' => $manualA->id,
            'ipl_num' => '13-70',
            'part_number' => 'PN-1',
            'name' => 'First component',
        ]);

        $component = Component::query()->create([
            'manual_id' => $manualB->id,
            'ipl_num' => '13-70',
            'part_number' => 'PN-2',
            'name' => 'Second component',
        ]);

        $this->assertTrue($component->exists);
    }

    public function test_database_blocks_duplicate_active_component_ipl_inside_one_manual(): void
    {
        $manual = Manual::query()->create([
            'number' => 'TEST-MANUAL-DB-'.uniqid(),
        ]);

        Component::query()->create([
            'manual_id' => $manual->id,
            'ipl_num' => '13-70',
            'part_number' => 'PN-1',
            'name' => 'First component',
        ]);

        $this->expectException(QueryException::class);

        DB::table('components')->insert([
            'manual_id' => $manual->id,
            'ipl_num' => '13-70',
            'part_number' => 'PN-2',
            'name' => 'Second component',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
