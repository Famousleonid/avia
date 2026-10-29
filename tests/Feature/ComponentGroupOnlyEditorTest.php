<?php
namespace Tests\Feature;
use App\Models\{Component, ComponentAssembly};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};
class ComponentGroupOnlyEditorTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;
    public function test_create_and_edit_without_legacy_fields_preserves_existing_assembly_history(): void
    {
        $this->actingAs($this->createUserWithRole('Admin'));
        $manual=$this->createManual();
        $payload=['manual_id'=>$manual->id,'ipl_num'=>'8-230','part_number'=>'EDITOR-PART','name'=>'Bushing','units_assy'=>'2'];
        $r=$this->postJson(route('components.store'),$payload)->assertOk();
        $part=Component::findOrFail($r->json('component_id'));
        $this->assertSame(0,$part->allAssemblies()->count());
        $part->update(['assy_part_number'=>'HISTORICAL-ASSY','assy_ipl_num'=>'8-200']);
        $legacy=ComponentAssembly::create(['component_id'=>$part->id,'assy_part_number'=>'HISTORICAL-ASSY','assy_ipl_num'=>'8-200','units_assy'=>'2']);
        $before=$legacy->fresh()->getAttributes();
        $payload['name']='Updated bushing';
        $this->putJson(route('components.update',$part),$payload)->assertOk();
        $this->assertSame('Updated bushing',$part->fresh()->name);
        $this->assertSame('HISTORICAL-ASSY',$part->fresh()->assy_part_number);
        $this->assertSame('8-200',$part->fresh()->assy_ipl_num);
        $this->assertSame($before,$legacy->fresh()->getAttributes());

        $this->putJson(route('components.update',$part),$payload + ['assemblies'=>[]])->assertOk();
        $this->assertNull($part->fresh()->assy_part_number);
        $this->assertNull($part->fresh()->assy_ipl_num);
        $this->assertSame(0,$part->allAssemblies()->count());
    }
}
