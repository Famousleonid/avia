<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class RetiredLegacySchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.legacy_schema_test', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        DB::setDefaultConnection('legacy_schema_test');
        Schema::clearResolvedInstance('db.schema');
        Schema::create('components', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('manual_id');
            $table->string('part_number');
        });
        $this->migration()->down();
    }

    protected function tearDown(): void
    {
        DB::purge('legacy_schema_test');
        DB::setDefaultConnection('mysql');
        Schema::clearResolvedInstance('db.schema');
        parent::tearDown();
    }

    private function migration()
    {
        return require database_path('migrations/2026_09_17_100000_drop_retired_kit_prl_choice_group.php');
    }

    public function test_unmigrated_links_block_removal_without_losing_data(): void
    {
        DB::table('components')->insert(['manual_id' => 91, 'part_number' => 'BEARING', 'kit_prl_choice_group' => 'old-link']);
        try {
            $this->migration()->up();
            $this->fail('Unmigrated links must block column removal.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Legacy links remain', $e->getMessage());
        }
        $this->assertTrue(Schema::hasColumn('components', 'kit_prl_choice_group'));
        $this->assertSame('old-link', DB::table('components')->value('kit_prl_choice_group'));
    }

    public function test_empty_column_is_removed_and_rollback_restores_only_empty_schema(): void
    {
        DB::table('components')->insert(['manual_id' => 91, 'part_number' => 'BEARING']);
        $this->migration()->up();
        $this->migration()->up();
        $this->assertFalse(Schema::hasColumn('components', 'kit_prl_choice_group'));
        $this->assertSame('BEARING', DB::table('components')->value('part_number'));
        $this->assertSame(0, Artisan::call('part-groups:retire-legacy', ['--apply' => true]));
        $this->assertStringContainsString('already removed', Artisan::output());
        $this->migration()->down();
        $this->assertTrue(Schema::hasColumn('components', 'kit_prl_choice_group'));
        $this->assertNull(DB::table('components')->value('kit_prl_choice_group'));
    }
}
