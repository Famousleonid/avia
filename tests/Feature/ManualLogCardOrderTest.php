<?php

namespace Tests\Feature;

use App\Models\{Component, LogCard};
use App\Services\ManualLogCardOrder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\{Spreadsheet, Writer\Xlsx};
use Tests\{BuildsDomainData, TestCase};

class ManualLogCardOrderTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function parts(): array
    {
        $manual = $this->createManual();
        $parts = collect(['1-10'=>'AAA', '1-10A'=>'AAA-R', '1-20'=>'ZZZ'])->map(
            fn ($pn, $ipl) => Component::create(['manual_id'=>$manual->id, 'ipl_num'=>$ipl,
                'part_number'=>$pn, 'name'=>'Test part', 'units_assy'=>1, 'log_card'=>true])
        )->values();
        return [$manual, $parts];
    }

    public function test_shared_order_permissions_validation_revision_and_audit(): void
    {
        [$manual, $parts] = $this->parts();
        $service = app(ManualLogCardOrder::class);
        $payload = ['ids'=>[$parts[2]->id,$parts[0]->id,$parts[1]->id], 'revision'=>$service->revision($manual)];
        $manager=$this->createUserWithRole('Manager');
        $this->actingAs($manager);
        $this->withSession(['auth.version'=>(int)$manager->auth_version]);
        $this->putJson(route('manuals.log-card-order.update',$manual),array_merge($payload,['ids'=>[$parts[0]->id,$parts[2]->id,$parts[1]->id]]))->assertUnprocessable();
        $this->putJson(route('manuals.log-card-order.update',$manual),$payload)->assertOk();
        $this->assertSame($payload['ids'],$manual->fresh()->log_card_order);
        $this->assertDatabaseHas('activity_log',['subject_id'=>$manual->id,'subject_type'=>get_class($manual),'causer_id'=>$manager->id]);
        $this->putJson(route('manuals.log-card-order.update',$manual),$payload)->assertStatus(409);
    }

    public function test_technician_cannot_change_shared_order(): void
    {
        [$manual,$parts]=$this->parts();
        $this->actingAs($this->createUserWithRole('Technician'));
        $this->putJson(route('manuals.log-card-order.update',$manual),[
            'ids'=>$parts->pluck('id')->all(),'revision'=>app(ManualLogCardOrder::class)->revision($manual),
        ])->assertForbidden();
    }

    public function test_workbook_preview_keeps_variants_together_and_does_not_save(): void
    {
        [$manual,$parts]=$this->parts();
        $book=new Spreadsheet();$book->getActiveSheet()->setTitle('LLP');
        $book->getActiveSheet()->fromArray([['Description','Part number'],['Second','ZZZ'],['First','AAA-R']]);
        $dir=storage_path('app/codex-test-runtime');if(!is_dir($dir))mkdir($dir,0777,true);
        $path=$dir.'/lc-order-'.uniqid().'.xlsx';
        try {
            (new Xlsx($book))->save($path);
            $result=app(ManualLogCardOrder::class)->preview($manual,$path);
            $this->assertSame([[$parts[2]->id],[$parts[0]->id,$parts[1]->id]],array_column($result['groups'],'ids'));
            $this->assertNull($manual->fresh()->log_card_order);
            $book->getActiveSheet()->setCellValue('B3','UNKNOWN');(new Xlsx($book))->save($path);
            $this->expectException(\Illuminate\Validation\ValidationException::class);
            app(ManualLogCardOrder::class)->preview($manual,$path);
        } finally { @unlink($path);$book->disconnectWorksheets(); }
    }

    public function test_active_saved_cards_follow_manual_order_without_changing_row_identity(): void
    {
        [$manual,$parts]=$this->parts();
        $admin=$this->createUserWithRole('Admin');$this->actingAs($admin);
        $wo=$this->createWorkorder(['user_id'=>$admin->id,'unit_id'=>$this->createUnit(['manual_id'=>$manual->id])->id]);
        $manual->log_card_order=[$parts[2]->id,$parts[0]->id,$parts[1]->id];$manual->save();
        $context=(new \ReflectionMethod(\App\Http\Controllers\Admin\LogCardController::class,'prepareGroupedLogCardComponents'))
            ->invoke(app(\App\Http\Controllers\Admin\LogCardController::class),$wo);
        $this->assertSame($parts[2]->id,$context['orderedComponents']->first()['component']->id);
        $this->getJson(route('mobile.log-card.template',$wo->id))->assertOk()->assertJsonPath('data.order_ranks.'.$parts[2]->id,0);
        $rows=[$parts[2],$parts[0]];
        $this->postJson(route('log_card.store'),['workorder_id'=>$wo->id,'component_data'=>json_encode(array_map(fn($p)=>['component_id'=>(string)$p->id,'manual_id'=>(string)$manual->id,'serial_number'=>'SN-'.$p->part_number],$rows))])->assertOk();
        $manual->log_card_order=$parts->pluck('id')->all();$manual->save();
        $card=LogCard::where('workorder_id',$wo->id)->firstOrFail();
        $stored=$card->component_data;
        $this->get(route('log_card.partial',$wo))->assertOk()->assertSeeInOrder(['AAA','ZZZ']);
        $this->get(route('log_card.logCardForm',$wo))->assertOk()->assertSeeInOrder(['AAA','ZZZ']);
        $this->getJson(route('mobile.log-card.data',$wo->id))->assertOk()
            ->assertJsonPath('data.rows.0.component.part_number','AAA')
            ->assertJsonPath('data.rows.0.index',1)
            ->assertJsonPath('data.rows.0.serial_number','SN-AAA')
            ->assertJsonPath('data.rows.1.index',0);
        $this->assertSame($stored,$card->fresh()->component_data);
        $this->patchJson(route('mobile.log-card.rows.update',[$card,1]),['field'=>'serial_number','value'=>'UPDATED-AAA'])->assertOk();
        $updated=json_decode($card->fresh()->component_data,true);
        $this->assertSame('SN-ZZZ',$updated[0]['serial_number']);
        $this->assertSame('UPDATED-AAA',$updated[1]['serial_number']);
        $general=\App\Models\GeneralTask::create(['name'=>'QA lock order test','sort_order'=>1]);
        $task=\App\Models\Task::create(['name'=>'Post Disassembly inspection','general_task_id'=>$general->id,'task_has_start_date'=>0]);
        $inspection=\App\Models\Main::create(['workorder_id'=>$wo->id,'general_task_id'=>$general->id,'task_id'=>$task->id,'date_finish'=>'2026-10-01']);
        $lockedStored=$card->fresh()->component_data;
        // QA lock freezes order even for Admin, who can still edit the card.
        $this->get(route('log_card.partial',$wo))->assertOk()->assertSeeInOrder(['ZZZ','AAA']);
        $this->get(route('log_card.logCardForm',$wo))->assertOk()->assertSeeInOrder(['ZZZ','AAA']);
        $this->getJson(route('mobile.log-card.data',$wo->id))->assertOk()
            ->assertJsonPath('data.rows.0.component.part_number','ZZZ')->assertJsonPath('data.rows.0.index',0);
        $context=(new \ReflectionMethod(\App\Http\Controllers\Admin\LogCardController::class,'prepareGroupedLogCardComponents'))
            ->invoke(app(\App\Http\Controllers\Admin\LogCardController::class),$wo,null,true,[$parts[2]->id,$parts[0]->id]);
        $this->assertSame($parts[2]->id,$context['orderedComponents']->first()['component']->id);
        $this->assertSame($lockedStored,$card->fresh()->component_data);
        $inspection->date_finish=null;$inspection->save();
        $this->get(route('log_card.partial',$wo))->assertOk()->assertSeeInOrder(['AAA','ZZZ']);
        $wo->done_at=now();$wo->save();
        $this->get(route('log_card.partial',$wo))->assertOk()->assertSeeInOrder(['ZZZ','AAA']);
        $this->get(route('log_card.logCardForm',$wo))->assertOk()->assertSeeInOrder(['ZZZ','AAA']);
    }
}
