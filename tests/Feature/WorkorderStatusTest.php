<?php
namespace Tests\Feature;

use App\Models\{Customer, Process, ProcessName, Tdr, TdrProcess, WoBushing, WoBushingBatch, WoBushingLine, WoBushingProcess};
use App\Services\{WorkorderPartsList, WorkorderStatus};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\{BuildsDomainData, TestCase};

class WorkorderStatusTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_parallel_forecast_uses_approval_and_actual_completion_dates(): void
    {
        $forecast = app(\App\Services\WorkorderCompletionForecast::class);
        $wo = $this->createWorkorder(['approve_at'=>'2026-09-01']);
        $wo->expected_completion_date = '2026-12-01';
        $wo->save();
        $operations = collect([
            ['days'=>60, 'start'=>'2026-09-20', 'finish'=>null],
            ['days'=>30, 'start'=>null, 'finish'=>null],
        ]);
        $this->assertSame(60, $forecast->calculate($wo, $operations->map(fn ($operation) => collect([$operation])))['days']);
        $operations[0] = ['days'=>60, 'start'=>'2026-09-02', 'finish'=>'2026-09-11'];
        $this->assertSame(30, $forecast->calculate($wo, $operations->map(fn ($operation) => collect([$operation])))['days']);
        $operations[1] = ['days'=>30, 'start'=>null, 'finish'=>'2026-09-06'];
        $this->assertSame('2026-09-11', $forecast->calculate($wo, $operations->map(fn ($operation) => collect([$operation])))['date']->format('Y-m-d'));
        $operations[1] = ['days'=>30, 'start'=>null, 'finish'=>'2026-11-10'];
        $this->assertSame(70, $forecast->calculate($wo, $operations->map(fn ($operation) => collect([$operation])))['days']);
        $this->assertSame('2026-12-01', $wo->fresh()->expected_completion_date->format('Y-m-d'));
        $this->assertTrue($forecast->calculate($wo, collect())['date']->isSameDay(today()));
    }

    public function test_zero_day_forecast_shows_today_with_or_without_processes(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-29 12:00:00'));
        try {
            $forecast = app(\App\Services\WorkorderCompletionForecast::class);
            $wo = $this->createWorkorder(['approve_at'=>'2026-08-01']);
            foreach ([collect(), collect([collect()]), collect([collect([['days'=>0,'finish'=>null]])])] as $routes) {
                $result = $forecast->calculate($wo, $routes);
                $this->assertSame(0, $result['days']);
                $this->assertSame('2026-09-29', $result['date']->format('Y-m-d'));
            }
            // Nonzero overdue estimates still use the approved route calculation.
            $positive = $forecast->calculate($wo, collect([collect([['days'=>22,'finish'=>null]])]));
            $this->assertSame('2026-08-23', $positive['date']->format('Y-m-d'));
            $this->mock(WorkorderPartsList::class, fn ($m) => $m->shouldReceive('rows')->andReturn(collect()));
            $card = app(WorkorderStatus::class)->forWorkorder($wo);
            $html = view('admin.workorders.partials.status-cards', ['cards'=>collect([$card])])->render();
            $this->assertStringContainsString('29/Sep/2026', $html);
        } finally {
            $this->travelBack();
        }
    }

    public function test_traveler_group_uses_longest_member_and_actual_group_completion(): void
    {
        $wo = $this->createWorkorder(['approve_at'=>'2026-09-01']);
        $component = $this->createComponent($wo->unit->manual);
        $tdr = Tdr::create(['workorder_id'=>$wo->id,'component_id'=>$component->id,'qty'=>4]);
        $traveler = ProcessName::firstOrCreate(['name'=>'Traveler'], ['process_sheet_name'=>'Traveler','form_number'=>'TEST']);
        $traveler->update(['std_days'=>null]);
        $rows = collect();
        foreach (['Machining'=>120, 'NDT-1'=>90] as $name=>$days) {
            $type = ProcessName::firstOrCreate(['name'=>$name], ['process_sheet_name'=>$name,'form_number'=>'TEST']);
            $type->update(['std_days'=>$days, 'show_in_process_picker'=>true]);
            $rows->push(TdrProcess::create(['tdrs_id'=>$tdr->id,'process_names_id'=>$type->id,'in_traveler'=>true,'traveler_group'=>1]));
        }
        $this->mock(WorkorderPartsList::class, fn($m)=>$m->shouldReceive('rows')->andReturn(collect()));
        $status = app(WorkorderStatus::class);
        $this->assertSame(120, $status->forWorkorder($wo)['duration_days']);
        $rows[0]->update(['date_finish'=>'2026-09-11']);
        $this->assertSame(120, $status->forWorkorder($wo)['duration_days']);
        $traveler->update(['std_days'=>45]);
        $this->assertSame(120, $status->forWorkorder($wo)['duration_days']);
        $rows[1]->update(['date_finish'=>'2026-09-21']);
        $this->assertSame(20, $status->forWorkorder($wo)['duration_days']);
    }

    public function test_rod_traveler_is_one_current_shipment_followed_by_silver_plating(): void
    {
        // W107912 / 190-70974-005: all seven Traveler members share the same RO and sent date.
        $wo = $this->createWorkorder(['approve_at'=>'2026-09-08']);
        $component = $this->createComponent($wo->unit->manual, ['part_number'=>'190-70974-005', 'ipl_num'=>'1-375B', 'name'=>'ROD']);
        $tdr = Tdr::create(['workorder_id'=>$wo->id, 'component_id'=>$component->id, 'qty'=>1]);
        $vendor = \App\Models\Vendor::firstOrCreate(['name'=>'Airco Plating Co., Inc.']);
        $members = collect();
        $order = 0;
        foreach (['Chrome stripping'=>21, 'NDT-1'=>3, 'Shot peening'=>14, 'Chrome plating'=>45,
            'Chrome plating, Thin Dens'=>45, 'NDT-4'=>3, 'Passivation'=>21, 'Silver plating'=>21, 'Paint'=>5] as $name=>$days) {
            $type = ProcessName::firstOrCreate(['name'=>$name], ['process_sheet_name'=>$name, 'form_number'=>'TEST']);
            $type->update(['std_days'=>$days, 'show_in_process_picker'=>true]);
            $isTraveler = ++$order <= 7;
            $row = TdrProcess::create(['tdrs_id'=>$tdr->id, 'process_names_id'=>$type->id, 'sort_order'=>$order,
                'in_traveler'=>$isTraveler, 'traveler_group'=>$isTraveler ? 1 : null,
                'date_start'=>$isTraveler ? '2026-09-10' : null, 'repair_order'=>$isTraveler ? 'R9345' : null,
                'vendor_id'=>$isTraveler ? $vendor->id : null]);
            if ($isTraveler) $members->push($row);
        }
        $this->mock(WorkorderPartsList::class, fn ($m) => $m->shouldReceive('rows')->andReturn(collect()));
        $status = app(WorkorderStatus::class);
        $card = $status->forWorkorder($wo);
        $part = $card['processes']->first();
        $this->assertSame(68, $card['duration_days']); // max(21,3,14,45,45,3,21) + 2 transfer + 21 Silver.
        $this->assertSame('2026-11-15', $card['isd']->format('Y-m-d'));
        $this->assertSame(['Traveler', 'Silver plating'], $part['pending']->pluck('name')->all());
        $this->assertSame([45,21], $part['pending']->pluck('days')->all());
        $this->assertSame('Traveler', $part['current']['name']);
        $this->assertSame('R9345', $part['current']['ro']);
        $this->assertSame($vendor->name, $part['current']['vendor']);
        $this->assertSame(1, $part['ahead']);
        $this->assertSame(0, $part['earlier_pending']);
        $this->assertSame(1, $card['sent_qty']);
        $html = view('admin.workorders.partials.status-cards', ['cards'=>collect([$card])])->render();
        $this->assertStringContainsString('Traveler', $html);
        $this->assertStringContainsString('Silver plating', $html);
        $this->assertStringNotContainsString('Passivation', $html);
        foreach (['2026-10-14'=>11, '2026-10-25'=>0, '2026-10-26'=>-1] as $today=>$remaining) {
            $this->travelTo(\Illuminate\Support\Carbon::parse($today.' 23:00:00'));
            try {
                $timed = $status->forWorkorder($wo);
                $current = $timed['processes']->first()['current'];
                $this->assertSame($remaining, $current['remaining_days']);
                $this->assertSame('2026-10-25', $current['due']->format('Y-m-d'));
                $this->assertNull($timed['processes']->first()['pending'][1]['remaining_days']);
                $timedHtml = view('admin.workorders.partials.status-cards', ['cards'=>collect([$timed])])->render();
                $this->assertStringContainsString($remaining.' days left', $timedHtml);
                $this->assertSame($remaining < 0, str_contains($timedHtml, 'class="ws-process-remaining ws-process-overdue"'));
            } finally {
                $this->travelBack();
            }
        }
        // A partial return must not release the following standalone operation.
        $members->first()->update(['date_finish'=>'2026-10-01']);
        $this->assertSame('Traveler', $status->forWorkorder($wo)['processes']->first()['current']['name']);
        foreach ($members as $member) $member->update(['date_finish'=>'2026-10-10']);
        $returned = $status->forWorkorder($wo);
        $this->assertSame('Silver plating', $returned['processes']->first()['current']['name']);
        $this->assertFalse($returned['processes']->first()['current']['sent']);
        $this->assertSame('2026-11-02', $returned['isd']->format('Y-m-d'));
    }

    public function test_independent_part_bushing_and_std_routes_use_sequential_steps(): void
    {
        $wo = $this->createWorkorder(['approve_at'=>'2026-09-01']);
        $component = $this->createComponent($wo->unit->manual);
        $types = [];
        foreach (['Machining'=>20,'NDT-1'=>5,'STD NDT List'=>20,'STD CAD List'=>15,'STD Paint List'=>100] as $name=>$days) {
            $type = ProcessName::firstOrCreate(['name'=>$name], ['process_sheet_name'=>$name,'form_number'=>'TEST']);
            $type->update(['std_days'=>$days,'show_in_process_picker'=>!str_starts_with($name,'STD')]);
            $types[$name] = $type;
        }
        $tdr = Tdr::create(['workorder_id'=>$wo->id,'component_id'=>$component->id,'qty'=>4]);
        // Insert out of order: the saved route order must win.
        TdrProcess::create(['tdrs_id'=>$tdr->id,'process_names_id'=>$types['NDT-1']->id,'sort_order'=>2]);
        TdrProcess::create(['tdrs_id'=>$tdr->id,'process_names_id'=>$types['Machining']->id,'sort_order'=>1,'date_finish'=>'2026-09-03']);
        $std = collect();
        foreach (['cad'=>'STD CAD List','ndt'=>'STD NDT List','paint'=>'STD Paint List'] as $key=>$name) {
            $std[$key] = \App\Models\WorkorderStdProcess::create(['workorder_id'=>$wo->id,'std_type'=>$key,'process_name_id'=>$types[$name]->id]);
        }
        $b = WoBushing::create(['workorder_id'=>$wo->id]);
        $line = WoBushingLine::create(['wo_bushing_id'=>$b->id,'workorder_id'=>$wo->id,'component_id'=>$component->id,'qty'=>3,'qty_remaining'=>3]);
        $batches = [];
        foreach (['NDT-1','Machining'] as $name) {
            $p = Process::create(['process_names_id'=>$types[$name]->id,'process'=>'Instruction']);
            $batch = WoBushingBatch::create(['workorder_id'=>$wo->id,'process_id'=>$p->id,'route_number'=>1]);
            WoBushingProcess::create(['wo_bushing_line_id'=>$line->id,'process_id'=>$p->id,'batch_id'=>$batch->id,'qty'=>3]);
            $batches[$name] = $batch;
        }
        $this->mock(WorkorderPartsList::class, fn($m)=>$m->shouldReceive('rows')->andReturn(collect()));
        $status = app(WorkorderStatus::class);
        // Add 2 days between steps: part = 2 + 2 + 5; bushing = 20 + 2 + 5; STD = 20 + 2 + 15.
        $this->assertSame(37, $status->forWorkorder($wo)['duration_days']);
        $std['ndt']->update(['date_finish'=>'2026-09-04']);
        $this->assertSame(27, $status->forWorkorder($wo)['duration_days']);
        $batches['Machining']->update(['date_finish'=>'2026-09-05']);
        $this->assertSame(20, $status->forWorkorder($wo)['duration_days']);
        $std['cad']->update(['ignore_row'=>true]);
        $this->assertSame(11, $status->forWorkorder($wo)['duration_days']);
    }

    public function test_parts_are_sorted_by_next_descending_and_pending_steps_keep_route_order(): void
    {
        $wo = $this->createWorkorder();
        foreach ([1,3,2] as $count) {
            $component = $this->createComponent($wo->unit->manual);
            $tdr = Tdr::create(['workorder_id'=>$wo->id,'component_id'=>$component->id,'qty'=>1]);
            foreach (range($count,1) as $step) {
                $type = ProcessName::firstOrCreate(['name'=>'Route step '.$step], ['process_sheet_name'=>'Test','form_number'=>'TEST','std_days'=>$step,'show_in_process_picker'=>true]);
                TdrProcess::create(['tdrs_id'=>$tdr->id,'process_names_id'=>$type->id,'sort_order'=>$step]);
            }
        }
        $this->mock(WorkorderPartsList::class, fn($m)=>$m->shouldReceive('rows')->andReturn(collect()));
        $card = app(WorkorderStatus::class)->forWorkorder($wo);
        $this->assertSame([2,1,0],$card['processes']->pluck('ahead')->all());
        $this->assertSame(['Route step 1','Route step 2','Route step 3'],$card['processes'][0]['pending']->pluck('name')->all());
        $html = view('admin.workorders.partials.status-cards',['cards'=>collect([$card])])->render();
        $this->assertStringContainsString('aria-haspopup="dialog"',$html);
        $this->assertStringNotContainsString('class="ws-remaining-row"',$html);
    }

    public function test_manual_completion_date_is_shared_validated_authorized_and_logged(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $wo = $this->createWorkorder();
        $url = route('workorders.status.completion-date', $wo);
        $this->actingAs($manager)->patchJson($url, ['expected_completion_date'=>'15/Oct/2026'])
            ->assertOk()->assertJsonPath('date','15/Oct/2026');
        $this->assertSame('2026-10-15', $wo->fresh()->expected_completion_date->format('Y-m-d'));
        $log = \Spatie\Activitylog\Models\Activity::where('subject_type', get_class($wo))->where('subject_id',$wo->id)->latest('id')->first();
        $this->assertSame($manager->id, $log->causer_id);
        $this->assertNotNull($log->properties->get('attributes')['expected_completion_date'] ?? null);
        foreach (['31/Feb/2026','tomorrow','2026-10-15'] as $invalid) {
            $this->patchJson($url,['expected_completion_date'=>$invalid])->assertUnprocessable();
        }
        $this->patchJson($url,[])->assertUnprocessable();
        $this->assertSame('2026-10-15', $wo->fresh()->expected_completion_date->format('Y-m-d'));
        $this->mock(WorkorderPartsList::class, fn($m)=>$m->shouldReceive('rows')->andReturn(collect()));
        $response = $this->getJson(route('workorders.status',['workorder_id'=>$wo->id,'state'=>'all']))->assertOk();
        $this->assertStringContainsString('15/Oct/2026', $response->json('html'));
        $this->patchJson($url,['expected_completion_date'=>null])->assertOk()->assertJsonPath('date',null);
        foreach (['Technician','Team Leader'] as $role) {
            $user = $this->createUserWithRole($role);
            $this->actingAs($user)->withSession(['auth.version'=>(int)$user->auth_version,'password_hash_web'=>$user->getAuthPassword()])
                ->patchJson($url,['expected_completion_date'=>'15/Oct/2026'])->assertForbidden();
        }
        $this->assertNull($wo->fresh()->expected_completion_date);
    }

    public function test_ec_panel_contains_all_workorder_ec_rows_including_completed_only(): void
    {
        $wo = $this->createWorkorder();
        $other = $this->createWorkorder();
        $component = $this->createComponent($wo->unit->manual);
        $type = ProcessName::firstOrCreate(['name'=>'EC'], ['process_sheet_name'=>'EC','form_number'=>'EC']);
        foreach ([$wo,$other] as $owner) {
            $tdr = Tdr::create(['workorder_id'=>$owner->id,'component_id'=>$component->id,'qty'=>1]);
            foreach ([false,true] as $done) {
                TdrProcess::create(['tdrs_id'=>$tdr->id,'process_names_id'=>$type->id,'concession_number'=>'EC-'.$owner->id.'-'.(int)$done,
                    'date_start'=>'2026-09-01','date_finish'=>$done?'2026-09-10':null]);
            }
        }
        $this->mock(WorkorderPartsList::class, fn($m)=>$m->shouldReceive('rows')->andReturn(collect()));
        $card = app(WorkorderStatus::class)->forWorkorder($wo);
        $this->assertCount(2,$card['ec']);
        $html = view('admin.workorders.partials.status-cards',['cards'=>collect([$card])])->render();
        $this->assertStringContainsString('EC-'.$wo->id.'-1',$html);
        $this->assertStringNotContainsString('EC-'.$other->id.'-1',$html);
        $this->assertStringContainsString('10/Sep/2026',$html);
        $this->assertStringContainsString('bi-arrow-right ws-ec-sent',$html);
        $this->assertStringContainsString('bi-box-arrow-in-left ws-ec-returned',$html);
        $this->assertStringContainsString('ws-'.$wo->id.'-ec',$html);
        $this->assertStringContainsString('>w'.$wo->number.'</a>',$html);
    }

    public function test_status_checkboxes_filter_combinations_and_restore_per_user(): void
    {
        $user = $this->createUserWithRole('Manager');
        $customer = Customer::create(['name'=>'Status combinations']);
        $active = $this->createWorkorder(['customer_id'=>$customer->id,'approve_at'=>null,'done_at'=>null]);
        $approved = $this->createWorkorder(['customer_id'=>$customer->id,'approve_at'=>'2026-09-01','done_at'=>null]);
        $completed = $this->createWorkorder(['customer_id'=>$customer->id,'approve_at'=>'2026-09-01','done_at'=>'2026-09-20']);
        $completed->forceFill(['done_at'=>'2026-09-20'])->saveQuietly();
        $this->mock(WorkorderPartsList::class, fn($m)=>$m->shouldReceive('rows')->andReturn(collect()));
        $this->actingAs($user);
        foreach (['active'=>[$active,$approved], 'approved'=>[$approved,$completed], 'completed'=>[$active,$approved,$completed],
            'active,approved'=>[$approved], 'approved,completed'=>[$approved,$completed],
            'active,completed'=>[$active,$approved], 'active,approved,completed'=>[$approved],
            'all'=>[$active,$approved,$completed], ''=>[$active,$approved,$completed]] as $state=>$expected) {
            $response = $this->getJson(route('workorders.status', ['customer_id'=>$customer->id,'state'=>$state]))->assertOk()->assertJsonPath('total',count($expected));
            foreach ($expected as $wo) $this->assertStringContainsString('data-workorder="'.$wo->id.'"', $response->json('html'));
        }
        $this->postJson(route('user-ui-settings.store'), ['scope'=>'wo-status','key'=>'state','value'=>[
            'filters'=>['customer_id'=>(string)$customer->id,'number'=>'','state'=>'approved,completed'],
        ]])->assertSuccessful();
        $this->get(route('workorders.status'))->assertOk()
            ->assertViewHas('uiState', fn($state)=>$state['filters']['state']==='approved')
            ->assertDontSee('data-workorder="'.$active->id.'"',false)
            ->assertSee('data-workorder="'.$approved->id.'"',false)
            ->assertSee('data-workorder="'.$completed->id.'"',false)
            ->assertSee('data-ws-status="active"',false)
            ->assertSee('data-ws-status="approved"',false)
            ->assertDontSee('data-ws-status="all"',false)
            ->assertDontSee('data-ws-status="completed"',false);
    }

    public function test_user_state_is_restored_in_first_html_without_overwriting_it(): void
    {
        $user = $this->createUserWithRole('Manager');
        $otherUser = $this->createUserWithRole('Admin');
        $customer = Customer::create(['name'=>'Remembered customer']);
        for ($i=0; $i<17; $i++) $wo = $this->createWorkorder(['number'=>881100+$i, 'customer_id'=>$customer->id]);
        $this->mock(WorkorderPartsList::class, fn($m)=>$m->shouldReceive('rows')->andReturn(collect()));
        $state = ['filters'=>['customer_id'=>(string)$customer->id,'number'=>'881','state'=>'all'],
            'pages'=>2,'open'=>['ws-'.$wo->id.'-processes'],'lastWorkorder'=>(string)$wo->id,
            'scroll'=>['top'=>420,'left'=>0]];
        $this->actingAs($user)->postJson(route('user-ui-settings.store'), ['scope'=>'wo-status','key'=>'state','value'=>$state])->assertSuccessful();
        $response = $this->get(route('workorders.status'))->assertOk();
        $this->assertSame(16, substr_count($response->getContent(), 'data-workorder='));
        $response->assertSee('aria-controls="ws-'.$wo->id.'-processes" aria-expanded="true"', false)
            ->assertViewHas('selectedNumber', '881')
            ->assertViewHas('nextUrl', fn($url)=>str_contains($url, 'page=3') && str_contains($url, 'customer_id='.$customer->id));
        $this->getJson($response->viewData('nextUrl'))->assertOk()->assertJsonPath('total',17);
        $this->get(route('workorders.status', ['number'=>'881116']))->assertOk()->assertViewHas('selectedNumber','881116');
        $this->getJson(route('workorders.status', ['number'=>'999999999']))->assertJsonPath('total',0);
        $this->assertEquals($state, \App\Models\UserUiSetting::where('user_id',$user->id)->where('scope','wo-status')->value('value'));
        $this->actingAs($otherUser)->withSession(['auth.version'=>(int)$otherUser->auth_version,'password_hash_web'=>$otherUser->getAuthPassword()])
            ->get(route('workorders.status'))->assertOk()->assertViewHas('selectedNumber','')
            ->assertDontSee('aria-controls="ws-'.$wo->id.'-processes" aria-expanded="true"', false);
    }

    public function test_access_filters_and_menu(): void
    {
        $wo = $this->createWorkorder();
        $other = $this->createWorkorder(['customer_id' => Customer::create(['name'=>'Other customer'])->id]);
        $this->mock(WorkorderPartsList::class, fn ($m) => $m->shouldReceive('rows')->andReturn(collect()));
        foreach (['Admin', 'Manager', 'Technician', 'Team Leader'] as $role) {
            $user = $this->createUserWithRole($role);
            $this->actingAs($user)->withSession(['auth.version'=>(int)$user->auth_version,'password_hash_web'=>$user->getAuthPassword()]);
            $response = $this->get(route('workorders.status', ['customer_id'=>$wo->customer_id]));
            if (in_array($role, ['Admin','Manager'])) {
                $response->assertOk()->assertSee('data-workorder="'.$wo->id.'"', false)->assertDontSee('data-workorder="'.$other->id.'"', false);
                $this->assertStringContainsString('WO Status', view('components.admin_menu_sidebar', ['themeToggleId'=>'testTheme'])->render());
                $this->get(route('workorders.status', ['customer_id'=>$wo->customer_id,'workorder_id'=>$other->id]))->assertOk()->assertSee('No workorders match');
            } else {
                $response->assertForbidden();
                $this->assertStringNotContainsString('WO Status', view('components.admin_menu_sidebar', ['themeToggleId'=>'testTheme'])->render());
            }
        }
    }

    public function test_json_pages_and_number_search_preserve_customer_filter(): void
    {
        $user = $this->createUserWithRole('Manager');
        $customer = Customer::create(['name'=>'Status customer']);
        for ($i=0; $i<9; $i++) $this->createWorkorder(['number'=>880100+$i,'customer_id'=>$customer->id]);
        $this->createWorkorder(['number'=>990999,'customer_id'=>Customer::create(['name'=>'Different customer'])->id]);
        $this->mock(WorkorderPartsList::class, fn($m)=>$m->shouldReceive('rows')->andReturn(collect()));
        $this->actingAs($user);
        $first = $this->getJson(route('workorders.status', ['customer_id'=>$customer->id]))->assertOk()->assertJsonPath('total',9);
        $this->assertSame(8, substr_count($first->json('html'), 'data-workorder='));
        $second = $this->getJson($first->json('next'))->assertOk()->assertJsonPath('next',null);
        $this->assertSame(1, substr_count($second->json('html'), 'data-workorder='));
        $this->getJson(route('workorders.status', ['customer_id'=>$customer->id,'number'=>'W880108']))->assertOk()->assertJsonPath('total',1);
        $this->getJson(route('workorders.status', ['customer_id'=>$customer->id,'number'=>'990999']))->assertOk()->assertJsonPath('total',0);
    }

    public function test_esd_date_color_boundaries(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-28 12:00:00'));
        try {
            $wo = $this->createWorkorder(['approve_at'=>'2026-09-01']);
            $this->mock(WorkorderPartsList::class, fn($m)=>$m->shouldReceive('rows')->andReturn(collect()));
            $card = app(WorkorderStatus::class)->forWorkorder($wo);
            foreach ([-1=>'ws-esd-overdue', 0=>'ws-esd-soon', 14=>'ws-esd-soon', 15=>'ws-esd-future'] as $offset=>$class) {
                $card['isd'] = today()->addDays($offset);
                $html = view('admin.workorders.partials.status-cards', ['cards'=>collect([$card])])->render();
                $this->assertStringContainsString('ECD:', $html);
                $this->assertStringContainsString('class="'.$class.'"', $html);
                $this->assertStringNotContainsString('ISD:', $html);
            }
        } finally { $this->travelBack(); }
    }

    public function test_duration_current_step_and_missing_standards(): void
    {
        $wo = $this->createWorkorder(['approve_at'=>'2026-09-01']);
        $component = $this->createComponent($wo->unit->manual);
        $tdr = Tdr::create(['workorder_id'=>$wo->id,'component_id'=>$component->id,'qty'=>3]);
        foreach (['Machining'=>5, 'Stress Relief'=>99, 'Paint'=>88, 'INSPECT'=>77, 'NDT-1'=>7] as $name=>$days) {
            $type = ProcessName::firstOrCreate(['name'=>$name], ['process_sheet_name'=>$name,'form_number'=>'TEST','show_in_process_picker'=>true]);
            $type->update(['std_days'=>$days]);
            TdrProcess::create(['tdrs_id'=>$tdr->id,'process_names_id'=>$type->id,'sort_order'=>$name==='Machining'?1:2,'date_start'=>$name==='Machining'?'2026-09-02':null]);
        }
        $this->mock(WorkorderPartsList::class, fn($m)=>$m->shouldReceive('rows')->andReturn(collect()));
        $result = app(WorkorderStatus::class)->forWorkorder($wo);
        $this->assertSame(14, $result['duration_days']);
        $this->assertSame('2026-09-15', $result['isd']->format('Y-m-d'));
        $this->assertSame(3, $result['process_total_qty']);
        $this->assertSame('Machining', $result['processes'][0]['current']['name']);
        $this->assertSame(1, $result['processes'][0]['ahead']);
        $this->assertSame([5, 7], $result['processes'][0]['pending']->pluck('days')->all());
        ProcessName::where('name','NDT-1')->first()->update(['std_days'=>null]);
        $result = app(WorkorderStatus::class)->forWorkorder($wo);
        $this->assertSame(7, $result['duration_days']);
        $this->assertSame('2026-09-08', $result['isd']->format('Y-m-d'));
        $this->assertSame([5, 0], $result['processes'][0]['pending']->pluck('days')->all());
        $html = view('admin.workorders.partials.status-cards', ['cards'=>collect([$result])])->render();
        $this->assertStringNotContainsString('durations missing', $html);
        $this->assertStringNotContainsString('≥', $html);
        $this->assertStringContainsString('data-ws-remaining=', $html);
        $this->assertStringContainsString('Remaining processes', $html);
        $wo->approve_at = null;
        $result = app(WorkorderStatus::class)->forWorkorder($wo);
        $this->assertNull($result['duration_days']);
        $this->assertNull($result['isd']);
    }

    public function test_remaining_quantities_and_unfinished_processes_exclude_paint_and_ignored_rows(): void
    {
        $wo = $this->createWorkorder();
        $component = $this->createComponent($wo->unit->manual);
        $tdr = Tdr::create(['workorder_id'=>$wo->id,'component_id'=>$component->id,'qty'=>2]);
        foreach (['Paint','INSPECT','NDT-1','Machining','Cad plate'] as $name) {
            $type = ProcessName::firstOrCreate(['name'=>$name], ['process_sheet_name'=>$name,'form_number'=>'TEST','show_in_process_picker'=>true]);
            if ($name === 'Paint') $type->update(['name'=>'Renamed coating']);
            TdrProcess::create(['tdrs_id'=>$tdr->id,'process_names_id'=>$type->id,'date_start'=>$name==='NDT-1'?'2026-09-01':null,'date_finish'=>$name==='Machining'?'2026-09-01':null,'ignore_row'=>$name==='Cad plate']);
        }
        $b = WoBushing::create(['workorder_id'=>$wo->id]);
        $line = WoBushingLine::create(['wo_bushing_id'=>$b->id,'workorder_id'=>$wo->id,'component_id'=>$component->id,'qty'=>3,'qty_remaining'=>3]);
        foreach (['NDT-1','Machining'] as $name) {
            $p = Process::create(['process_names_id'=>ProcessName::where('name',$name)->value('id'),'process'=>'Instruction']);
            $batch = WoBushingBatch::create(['workorder_id'=>$wo->id,'process_id'=>$p->id,'route_number'=>1,'date_finish'=>$name==='Machining'?'2026-09-02':null]);
            WoBushingProcess::create(['wo_bushing_line_id'=>$line->id,'process_id'=>$p->id,'batch_id'=>$batch->id,'qty'=>3]);
        }
        $completed = Tdr::create(['workorder_id'=>$wo->id,'component_id'=>$component->id,'qty'=>4]);
        TdrProcess::create(['tdrs_id'=>$completed->id,'process_names_id'=>ProcessName::where('name','NDT-1')->value('id'),'date_finish'=>'2026-09-02']);
        $parts = collect([[5,2,false],[4,4,false],[8,0,true],[2,null,false]])->map(fn($r)=>(object)[
            'qty'=>$r[0], 'received_qty'=>$r[1], 'crossed_out'=>$r[2],
            'component'=>(object)['ipl_num'=>'1-1', 'part_number'=>'TEST', 'name'=>'Part', 'kit'=>true],
        ]);
        $this->mock(WorkorderPartsList::class, fn($m)=>$m->shouldReceive('rows')->andReturn($parts));
        $result = app(WorkorderStatus::class)->forWorkorder($wo);
        $this->assertSame(9, $result['process_total_qty']);
        $this->assertSame(5, $result['unfinished_qty']);
        $this->assertSame(2, $result['sent_qty']);
        $this->assertSame(11, $result['ordered_qty']);
        $this->assertSame(5, $result['missing_qty']);
    }
}
