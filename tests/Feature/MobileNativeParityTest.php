<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\LogCard;
use App\Models\ManualPartGroup;
use App\Models\UserUiSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MobileNativeParityTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    private function token($user): array
    {
        $token=$this->postJson(route('api.android.auth.login'),['email'=>$user->email,'password'=>'password'])->assertOk()->json('data.token');
        return ['Authorization'=>'Bearer '.$token];
    }

    public function test_native_draft_shares_web_storage_and_is_isolated_by_user_and_workorder(): void
    {
        $user=$this->createUserWithRole('Technician');
        $other=$this->createUserWithRole('Technician');
        $wo=$this->createWorkorder(['user_id'=>$user->id]);
        $next=$this->createWorkorder(['user_id'=>$user->id]);
        $headers=$this->token($user);
        $url=route('api.android.workorders.log-card.draft.store',$wo->id);
        $draft=['version'=>1,'rows'=>[['key'=>'one','included'=>true,'componentId'=>123,'serial'=>'S1','assemblies'=>[['componentId'=>123,'value'=>'']]]]];
        $this->putJson($url,['value'=>$draft],$headers)->assertOk();
        $setting=UserUiSetting::where('user_id',$user->id)->where('scope','mobile.log-card.draft')->where('key','wo:'.$wo->id)->firstOrFail();
        $this->assertNull($setting->value['rows'][0]['assemblies'][0]['value']);
        $this->getJson(route('api.android.workorders.log-card.draft.show',$wo->id),$headers)->assertOk()->assertJsonPath('data.draft.rows.0.serial','S1');
        $this->getJson(route('api.android.workorders.log-card.draft.show',$next->id),$headers)->assertOk()->assertJsonPath('data.draft',null);
        $otherHeaders=$this->token($other);
        $this->getJson(route('api.android.workorders.log-card.draft.show',$wo->id),$otherHeaders)->assertOk()->assertJsonPath('data.draft',null);
        $this->putJson($url,['value'=>['version'=>2,'rows'=>[]]],$headers)->assertUnprocessable();
        $this->putJson($url,['value'=>[]],$headers)->assertUnprocessable();
        $this->putJson($url,['value'=>['version'=>1,'rows'=>[]]],$headers)->assertOk();
        $this->putJson($url,['value'=>null],$headers)->assertOk();
        $this->assertFalse(UserUiSetting::whereKey($setting->id)->exists());
    }

    public function test_native_log_card_additions_require_authentication_and_allowed_role(): void
    {
        $wo=$this->createWorkorder();
        $this->getJson(route('api.android.workorders.log-card.draft.show',$wo->id))->assertUnauthorized();
        $headers=$this->token($this->createUserWithRole('Shipping'));
        $this->getJson(route('api.android.workorders.log-card.draft.show',$wo->id),$headers)->assertForbidden();
        $this->postJson(route('api.android.workorders.log-card.photo.store',$wo->id),[],$headers)->assertForbidden();
    }

    public function test_native_creation_requires_unit_identity_and_preserves_both_serials(): void
    {
        $user=$this->createUserWithRole('Technician');
        $wo=$this->createWorkorder(['user_id'=>$user->id]);
        $part=Component::create(['manual_id'=>$wo->unit->manual_id,'name'=>'Spring','ipl_num'=>'20-20','part_number'=>'NATIVE-SPRING','units_assy'=>2,'log_card'=>true]);
        $headers=$this->token($user);
        $url=route('api.android.workorders.log-card.store',$wo->id);
        $row=['component_id'=>$part->id,'ipl_group'=>'separate','included'=>true];
        $this->postJson($url,['rows'=>[$row,$row]],$headers)->assertUnprocessable();
        $this->assertFalse(LogCard::where('workorder_id',$wo->id)->exists());
        $rows=[];
        foreach([1,2] as $i) $rows[]=array_merge($row,['unit_index'=>$i,'units_assy'=>'2','serial_number'=>'SN-'.$i,'assy_selection_explicit'=>true,'manual_part_group_choice'=>'component']);
        $this->postJson($url,['rows'=>$rows],$headers)->assertOk();
        $saved=collect(json_decode(LogCard::where('workorder_id',$wo->id)->firstOrFail()->component_data,true))->where('row_type','!=','manual')->keyBy('unit_index');
        $this->assertCount(2,$saved);
        $this->assertSame('SN-1',$saved[1]['serial_number']);
        $this->assertSame('SN-2',$saved[2]['serial_number']);
    }

    public function test_native_row_assy_edit_and_explicit_none_affect_only_the_selected_instance(): void
    {
        $user=$this->createUserWithRole('Technician');
        $wo=$this->createWorkorder(['user_id'=>$user->id,'scope_type'=>'full_unit']);
        $part=Component::create(['manual_id'=>$wo->unit->manual_id,'name'=>'Spring','ipl_num'=>'20-20','part_number'=>'P-SPRING','units_assy'=>2,'log_card'=>true]);
        $options=[];
        foreach(['A','B'] as $letter){
            $head=Component::create(['manual_id'=>$part->manual_id,'name'=>'Parent','ipl_num'=>'1-1'.$letter,'part_number'=>'P-ASSY-'.$letter]);
            $group=ManualPartGroup::create(['manual_id'=>$part->manual_id,'code'=>'NATIVE-'.$letter,'name'=>'Parent '.$letter,'type'=>'assy','behavior'=>'bundle','applies_to'=>['prl']]);
            $option=$group->options()->create(['component_id'=>$head->id,'part_number'=>$head->part_number,'ipl_num'=>$head->ipl_num,'option_kind'=>'assy']);
            $option->coverages()->create(['component_id'=>$part->id,'qty'=>2]);
            $options[]=$option;
        }
        $headers=$this->token($user);
        $rows=[];
        foreach([1,2] as $i) $rows[]=['component_id'=>$part->id,'unit_index'=>$i,'units_assy'=>'2','serial_number'=>'S-'.$i,'manual_part_group_choice'=>'component','assy_selection_explicit'=>true];
        $this->postJson(route('api.android.workorders.log-card.store',$wo->id),['rows'=>$rows],$headers)->assertOk();
        $card=LogCard::where('workorder_id',$wo->id)->firstOrFail();
        $this->getJson(route('api.android.workorders.log-card.show',$wo->id),$headers)->assertOk()->assertJsonCount(2,'data.rows.1.assembly_choices');
        $url=route('api.android.log-card.rows.assembly.update',[$card->id,1]);
        $this->patchJson($url,['assy_selection_explicit'=>true,'assy_option_id'=>$options[1]->id],$headers)->assertOk();
        $saved=json_decode($card->fresh()->component_data,true);
        $this->assertSame('P-ASSY-B',$saved[1]['assy_part_number']);
        $this->assertSame('',$saved[2]['assy_part_number']);
        $this->patchJson($url,['assy_selection_explicit'=>true,'assy_option_id'=>999999],$headers)->assertUnprocessable();
        $this->patchJson($url,['assy_selection_explicit'=>true,'assy_option_id'=>null],$headers)->assertOk();
        $saved=json_decode($card->fresh()->component_data,true);
        $this->assertSame('',$saved[1]['assy_part_number']);
        $this->assertSame('1',$saved[1]['assy_selection_explicit']);
        $this->assertSame('S-2',$saved[2]['serial_number']);
    }

    public function test_native_photo_recognition_does_not_save_before_confirmation_and_rejects_portrait(): void
    {
        Bus::fake();
        config(['services.openai.api_key'=>'test-key','services.openai.retry_attempts'=>1]);
        Http::fake(['https://api.openai.com/v1/responses'=>Http::response(['output'=>[['type'=>'function_call','name'=>'extract_nameplate_identifiers',
            'arguments'=>json_encode(['part_numbers'=>['P-1'],'serial_numbers'=>['S-1'],'other_identifiers'=>[],'confidence'=>'high','notes'=>'QA'])]]])]);
        $user=$this->createUserWithRole('Technician');
        $wo=$this->createWorkorder(['user_id'=>$user->id]);
        $headers=array_merge($this->token($user),['Accept'=>'application/json']);
        $url=route('api.android.workorders.log-card.photo.store',$wo->id);
        $this->post($url,['photo'=>UploadedFile::fake()->image('portrait.jpg',80,120)],$headers)->assertUnprocessable();
        Http::assertNothingSent();
        $this->post($url,['photo'=>UploadedFile::fake()->image('plate.jpg',120,80),'expected_part_number'=>'P-1'],$headers)
            ->assertOk()->assertJsonPath('data.pending_confirmation',true)->assertJsonPath('data.recognition.serial_numbers.0','S-1')->assertJsonPath('data.photo_count',0);
        $this->assertCount(0,$wo->fresh()->getMedia('logs'));
        $this->post($url,['photo'=>UploadedFile::fake()->image('confirmed.jpg',120,80),'recognize'=>0],$headers)
            ->assertOk()->assertJsonPath('data.pending_confirmation',false)->assertJsonPath('data.photo_count',1)
            ->assertJsonPath('data.photo.url', fn ($value) => str_contains($value, '/api/mobile/media/'));
        Http::assertSentCount(1);
    }
}
