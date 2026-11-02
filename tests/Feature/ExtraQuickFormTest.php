<?php
namespace Tests\Feature;
use App\Models\{Component, ExtraProcess, Process, ProcessName, ManualProcess, Tdr, TdrProcess};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{TestCase, BuildsDomainData};
class ExtraQuickFormTest extends TestCase {
 use DatabaseTransactions, BuildsDomainData;
 public function test_quick_forms_save_separately_and_do_not_create_tdr_processes(): void {
  $user=$this->createUserWithRole('Admin'); $wo=$this->createWorkorder(['user_id'=>$user->id]);
  $component=Component::create(['manual_id'=>$wo->unit->manual_id,'part_number'=>'QUICK-TEST','name'=>'Test part','ipl_num'=>'1-1']);
  $name=ProcessName::create(['name'=>'Quick test','process_sheet_name'=>'OTHER','form_number'=>'018','print_form'=>true,'show_in_process_picker'=>true]);
  $process=Process::create(['process_names_id'=>$name->id,'process'=>'Quick specification']);
  ManualProcess::create(['manual_id'=>$component->manual_id,'processes_id'=>$process->id]);
  $tdrCount=Tdr::count(); $processCount=TdrProcess::count();
  $body=['workorder_id'=>$wo->id,'component_id'=>$component->id,'qty'=>1,'quick_form'=>true,'processes'=>[['process_names_id'=>$name->id,'processes'=>[$process->id],'description'=>'FIG 123']]];
  foreach(['SERIAL-A','SERIAL-B'] as $sn) {
   $response=$this->actingAs($user)->postJson(route('extra_processes.store'),$body+['serial_num'=>$sn]);
   $response->assertOk()->assertJsonPath('success',true)->assertJsonStructure(['form_url']);
   $this->assertDatabaseHas('extra_processes',['workorder_id'=>$wo->id,'component_id'=>$component->id,'serial_num'=>$sn]);
  }
  $this->assertSame(2,ExtraProcess::where('workorder_id',$wo->id)->count());
  $this->assertSame($tdrCount,Tdr::count()); $this->assertSame($processCount,TdrProcess::count());
  ManualProcess::where('manual_id',$component->manual_id)->where('processes_id',$process->id)->delete();
  $this->actingAs($user)->postJson(route('extra_processes.store'),$body+['serial_num'=>'INVALID'])->assertStatus(422);
  $this->assertSame(2,ExtraProcess::where('workorder_id',$wo->id)->count());
 }

 public function test_inline_edit_updates_exact_record_preserving_other_processes_and_legacy_data(): void {
  $user=$this->createUserWithRole('Admin'); $wo=$this->createWorkorder(['user_id'=>$user->id]);
  $otherWo=$this->createWorkorder(['user_id'=>$user->id]);
  $component=Component::create(['manual_id'=>$wo->unit->manual_id,'part_number'=>'EDIT-TEST','name'=>'Edit part','ipl_num'=>'1-1']);
  $name=ProcessName::create(['name'=>'Edit test','process_sheet_name'=>'OTHER','form_number'=>'018','print_form'=>true,'show_in_process_picker'=>true]);
  $process=Process::create(['process_names_id'=>$name->id,'process'=>'Edit specification']);
  ManualProcess::create(['manual_id'=>$component->manual_id,'processes_id'=>$process->id]);
  $item=['process_name_id'=>$name->id,'process_id'=>$process->id,'description'=>'Original','notes'=>'Preserve notes'];
  $record=ExtraProcess::create(['workorder_id'=>$wo->id,'component_id'=>$component->id,'qty'=>1,'serial_num'=>'OLD','sort_order'=>7,'processes'=>[$item,$item]]);
  $sibling=ExtraProcess::create(['workorder_id'=>$wo->id,'component_id'=>$component->id,'qty'=>1,'serial_num'=>'OTHER','processes'=>[$item]]);
  $count=ExtraProcess::count(); $tdrs=Tdr::count(); $tdrProcesses=TdrProcess::count();
  $body=['workorder_id'=>$wo->id,'component_id'=>$component->id,'serial_num'=>'NEW','qty'=>3,'process_index'=>0,'process_name_id'=>$name->id,'process_id'=>$process->id,'description'=>'Updated  description'];
  $this->actingAs($user)->get(route('extra_process.create',['id'=>$wo->id,'modal'=>1,'quick'=>1,'edit_id'=>$record->id]))->assertOk()->assertSee('Saved process');
  $this->putJson(route('extra_processes.update',$record->id),$body)->assertOk()->assertJsonPath('success',true);
  $record->refresh(); $this->assertSame('NEW',$record->serial_num); $this->assertEquals(3,$record->qty);
  $this->assertSame('Updated  description',$record->processes[0]['description']);
  $this->assertSame('Preserve notes',$record->processes[0]['notes']); $this->assertEquals($item,$record->processes[1]);
  $this->assertSame('OTHER',$sibling->fresh()->serial_num); $this->assertEquals(7,$record->sort_order);
  $this->assertSame($count,ExtraProcess::count()); $this->assertSame($tdrs,Tdr::count()); $this->assertSame($tdrProcesses,TdrProcess::count());
  $this->putJson(route('extra_processes.update',$record->id),array_replace($body,['workorder_id'=>$otherWo->id]))->assertNotFound();
  $this->putJson(route('extra_processes.update',$record->id),array_replace($body,['qty'=>0]))->assertUnprocessable();
  $this->putJson(route('extra_processes.update',$record->id),array_replace($body,['process_index'=>20]))->assertUnprocessable();
  $foreign=Process::create(['process_names_id'=>$name->id,'process'=>'Not in manual']);
  $this->putJson(route('extra_processes.update',$record->id),array_replace($body,['process_id'=>$foreign->id]))->assertUnprocessable();
  $this->assertSame($process->id,$record->fresh()->processes[0]['process_id']);
  $record->update(['processes'=>[$name->id=>$process->id]]);
  $this->putJson(route('extra_processes.update',$record->id),$body)->assertOk();
  $this->assertSame('Updated  description',$record->fresh()->processes[0]['description']);

 }

 public function test_only_live_extra_process_routes_remain(): void {
  $routes=collect(app('router')->getRoutes())->filter(fn($route)=>str_starts_with($route->uri(),'extra_processes'))->map(fn($route)=>$route->getName())->sort()->values()->all();
  $expected=['extra_process.create','extra_processes.partial','extra_processes.show_form','extra_processes.show_group_forms','extra_processes.store','extra_processes.update']; sort($expected);
  $this->assertSame($expected,$routes);
  $this->assertFalse(app('router')->has('components.storeFromExtra'));
  foreach(['show','processes','create_processes','form','partials.processes-body','edit','edit_component'] as $view) {
   $this->assertFalse(view()->exists('admin.extra_processes.'.$view));
  }
 }
}

