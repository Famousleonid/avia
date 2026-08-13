<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ComponentAssembly;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ComponentCsvImportTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_existing_ipl_is_skipped_without_overwriting_component_or_assembly(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $component = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => 'ORIGINAL-PN',
            'name' => 'Original Part',
            'ipl_num' => '8-10',
            'assy_part_number' => 'ORIGINAL-ASSY',
            'assy_ipl_num' => '8-1',
            'units_assy' => '1',
            'log_card' => true,
            'kit' => true,
            'paint_list' => true,
        ]);
        $assembly = ComponentAssembly::query()->create([
            'component_id' => $component->id,
            'assy_part_number' => 'ORIGINAL-ASSY',
            'assy_ipl_num' => '8-1',
            'units_assy' => '1',
            'sort_order' => 0,
        ]);

        $csv = implode("\n", [
            'ipl_num,part_number,assy_part_number,name,assy_ipl_num,units_assy,log_card,kit,paint_list,bush_ipl_num',
            '8-10,CHANGED-PN,CHANGED-ASSY,Changed Part,8-2,9,0,0,0,8-99',
        ]);

        $response = $this->actingAs($admin)->postJson(route('components.upload-csv'), [
            'manual_id' => $manual->id,
            'csv_file' => $this->makeUploadedFile('parts.csv', $csv, 'text/csv'),
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'success_count' => 0,
            'create_count' => 0,
            'update_count' => 0,
            'skip_count' => 1,
            'error_count' => 0,
        ]);

        $component->refresh();
        $this->assertSame('ORIGINAL-PN', $component->part_number);
        $this->assertSame('Original Part', $component->name);
        $this->assertSame('ORIGINAL-ASSY', $component->assy_part_number);
        $this->assertSame('8-1', $component->assy_ipl_num);
        $this->assertSame('1', (string) $component->units_assy);
        $this->assertTrue($component->log_card);
        $this->assertTrue($component->kit);
        $this->assertTrue($component->paint_list);

        $assembly->refresh();
        $this->assertSame('ORIGINAL-ASSY', $assembly->assy_part_number);
        $this->assertSame('8-1', $assembly->assy_ipl_num);
        $this->assertSame('1', (string) $assembly->units_assy);
        $this->assertSame(1, ComponentAssembly::query()->where('component_id', $component->id)->count());
    }

    public function test_new_ipl_creates_basic_part_and_assembly_but_ignores_process_flags(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $csv = implode("\n", [
            'ipl_num,part_number,assy_part_number,name,assy_ipl_num,units_assy,eff_code,log_card,is_bush,kit,np,kit_e,ndt_list,cad_list,stress_relief_list,paint_list,bush_ipl_num',
            '8-20,NEW-PN,NEW-ASSY,New Part,8-2,4,SHOULD-NOT-IMPORT,1,1,1,1,1,1,1,1,1,8-99',
        ]);

        $response = $this->actingAs($admin)->postJson(route('components.upload-csv'), [
            'manual_id' => $manual->id,
            'csv_file' => $this->makeUploadedFile('parts.csv', $csv, 'text/csv'),
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'success_count' => 1,
            'create_count' => 1,
            'update_count' => 0,
            'skip_count' => 0,
            'error_count' => 0,
        ]);

        $component = Component::query()
            ->where('manual_id', $manual->id)
            ->where('ipl_num', '8-20')
            ->firstOrFail();

        $this->assertSame('NEW-PN', $component->part_number);
        $this->assertSame('New Part', $component->name);
        $this->assertSame('NEW-ASSY', $component->assy_part_number);
        $this->assertSame('8-2', $component->assy_ipl_num);
        $this->assertSame('4', (string) $component->units_assy);
        $this->assertNull($component->eff_code);
        $this->assertFalse($component->log_card);
        $this->assertFalse($component->is_bush);
        $this->assertFalse($component->kit);
        $this->assertFalse($component->np);
        $this->assertFalse($component->kit_e);
        $this->assertFalse($component->ndt_list);
        $this->assertFalse($component->cad_list);
        $this->assertFalse($component->stress_relief_list);
        $this->assertFalse($component->paint_list);
        $this->assertNull($component->bush_ipl_num);

        $this->assertDatabaseHas('component_assemblies', [
            'component_id' => $component->id,
            'assy_part_number' => 'NEW-ASSY',
            'assy_ipl_num' => '8-2',
            'units_assy' => '4',
            'sort_order' => 0,
        ]);
    }

    public function test_downloaded_template_contains_only_basic_parts_columns(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $response = $this->actingAs($admin)->get(route('components.download-csv-template'));

        $response->assertOk();
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $response->streamedContent());
        $header = str_getcsv(strtok($content, "\r\n"));

        $this->assertSame([
            'ipl_num',
            'part_number',
            'assy_part_number',
            'name',
            'assy_ipl_num',
            'units_assy',
        ], $header);
    }
}
