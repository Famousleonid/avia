<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\ManualProcess;
use App\Models\Process;
use App\Models\ProcessName;
use App\Models\WoBushing;
use App\Models\WoBushingBatch;
use App\Models\WoBushingLine;
use App\Models\WoBushingProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class WoBushingSortingTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_group_members_without_flags_are_available_and_share_original_quantity(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $original = Component::create(['manual_id' => $wo->unit->manual_id,
            'ipl_num' => '3-430D', 'part_number' => '52120-11', 'name' => 'Original',
            'is_bush' => false, 'units_assy' => 2]);
        $oversize = Component::create(['manual_id' => $wo->unit->manual_id,
            'ipl_num' => '3-430E', 'part_number' => '52120-11SR', 'name' => 'Oversize',
            'is_bush' => false, 'units_assy' => 'AR']);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version, 'password_hash_web' => $admin->getAuthPassword()])->postJson(route('manuals.part-groups.store', $wo->unit->manual_id), [
            'name' => 'Bushing 430', 'type' => 'oversize', 'applies_to' => ['ndt'],
            'component_ids' => [$original->id, $oversize->id],
        ])->assertSuccessful();
        $this->assertFalse($original->fresh()->is_bush);
        $this->assertFalse($oversize->fresh()->is_bush);
        $group = \App\Models\ManualPartGroup::where('manual_id', $wo->unit->manual_id)->where('type', 'oversize')->firstOrFail();
        $key = 'part-group|'.$wo->unit->manual_id.'|'.$group->id;
        $form = $this->get(route('wo_bushings.partial', $wo))->assertOk();
        $form->assertSee('52120-11SR')->assertSee('data-max-order-qty="2"', false);
        $this->assertCount(2, Component::whereKey([$original->id, $oversize->id])->bushingCandidates()->get());
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $payload = fn ($qty) => ['group_bushings' => [$key => ['items' => [
            $oversize->id => ['selected' => '1', 'qty' => $qty, 'need_processes' => '0'],
        ]]]];
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->putJson(route('wo_bushings.update', $bushing), $payload(2))->assertOk();
        $this->assertSame(2, (int) WoBushingLine::where('wo_bushing_id', $bushing->id)->value('qty'));
        $this->putJson(route('wo_bushings.update', $bushing), $payload(3))->assertStatus(422);
        $this->assertSame(2, (int) WoBushingLine::where('wo_bushing_id', $bushing->id)->value('qty'));
        $this->get(route('wo_bushings.edit', $bushing))->assertOk()->assertSee('52120-11SR');
        $this->assertFalse($original->fresh()->is_bush);
        $this->assertFalse($oversize->fresh()->is_bush);
        $this->assertSame('AR', $oversize->fresh()->units_assy);
        $group->delete();
        $this->assertCount(0, Component::whereKey([$original->id, $oversize->id])->bushingCandidates()->get());
    }

    public function test_bushing_tab_lists_is_bush_components_by_natural_ipl_before_grouping(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;

        foreach ([
            ['ipl_num' => '6-500', 'part_number' => 'PN-6500', 'bush_ipl_num' => 'GRP-B'],
            ['ipl_num' => '6-490', 'part_number' => 'PN-6490', 'bush_ipl_num' => 'GRP-A'],
            ['ipl_num' => '9A-300', 'part_number' => 'PN-9A300', 'bush_ipl_num' => 'GRP-D'],
            ['ipl_num' => '9A-30', 'part_number' => 'PN-9A030', 'bush_ipl_num' => 'GRP-C'],
            ['ipl_num' => '6-470', 'part_number' => 'NOT-BUSH', 'bush_ipl_num' => 'GRP-Z', 'is_bush' => false],
        ] as $row) {
            Component::query()->create([
                'manual_id' => $manualId,
                'ipl_num' => $row['ipl_num'],
                'part_number' => $row['part_number'],
                'name' => 'Bushing '.$row['ipl_num'],
                'bush_ipl_num' => $row['bush_ipl_num'],
                'is_bush' => $row['is_bush'] ?? true,
            ]);
        }

        $machining = $this->attachProcessToManual($manualId, 'Machining', 'Machining create option');

        $response = $this->actingAs($admin)->get(route('wo_bushings.partial', $workorder->id));

        $response->assertOk();
        $response->assertSeeInOrder(['6-490', '6-500', '9A-30', '9A-300'], false);
        $response->assertDontSee('NOT-BUSH', false);
        $response->assertSee('data-itemized="1"', false);
        $response->assertSee('dir-table bushing-create-table bushing-itemized-table', false);
        $response->assertSee('bushing-col-process', false);
        $response->assertSee('bushing-col-ndt', false);
        $response->assertSee('>Qty</th>', false);
        $response->assertSee('>Do Not Order</th>', false);
        $response->assertSee('[do_not_order]', false);
        $response->assertDontSee('WO Qty', false);
        $response->assertSee('group_bushings[GRP-A][items]', false);
        $response->assertDontSee('[components][]', false);
        $response->assertSee('data-bushing-add-process', false);
        $response->assertSee(route('processes.create', ['manual_id' => $manualId, 'context' => 'bushing', 'workorder_id' => $workorder->id]));
        $response->assertSee('data-process-name-ids="'.$machining->process_names_id.'"', false);
        $response->assertSee('window.addBushingProcessOption', false);
        $response->assertSee('data-bushing-part-label', false);
        $response->assertSee('--bushing-col-bushing-width', false);
        $response->assertSee("delay: { show: 500, hide: 0 }", false);
        $response->assertSee('label.scrollWidth <= label.clientWidth + 1', false);
    }

    public function test_update_can_save_selected_bushing_without_processes_for_prl(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $component = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-230',
            'part_number' => '1840-0302',
            'name' => 'Bushing without process route',
            'bush_ipl_num' => '8-230',
            'is_bush' => true,
            'units_assy' => 2,
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->put(route('wo_bushings.update', $woBushing->id), [
                'group_bushings' => [
                    '8-230' => [
                        'items' => [
                            $component->id => [
                                'selected' => '1',
                                'qty' => '2',
                                'need_processes' => '0',
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $line = WoBushingLine::query()
            ->where('wo_bushing_id', $woBushing->id)
            ->where('component_id', $component->id)
            ->first();

        $this->assertNotNull($line);
        $this->assertSame(2, $line->qty);
        $this->assertSame(0, WoBushingProcess::query()->where('wo_bushing_line_id', $line->id)->count());
    }

    public function test_standalone_bushing_without_group_uses_own_ipl_and_manual_quantity_limit(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);
        $component = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '1-160',
            'part_number' => '52167-59',
            'name' => 'Standalone bushing',
            'bush_ipl_num' => null,
            'is_bush' => true,
            'units_assy' => 2,
        ]);
        Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '1-170',
            'part_number' => '52167-60',
            'name' => 'Another standalone bushing',
            'bush_ipl_num' => null,
            'is_bush' => true,
            'units_assy' => 1,
        ]);

        $partial = $this->actingAs($admin)->get(route('wo_bushings.partial', $workorder->id));
        $partial->assertOk();
        $partial->assertSee('data-group-key="1-160"', false);
        $partial->assertSee('data-group-key="1-170"', false);
        $partial->assertDontSee('data-group-key="no_ipl"', false);

        $headers = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];
        $payload = fn (int $qty): array => [
            'group_bushings' => [
                '1-160' => [
                    'items' => [
                        $component->id => [
                            'selected' => '1',
                            'qty' => (string) $qty,
                            'need_processes' => '0',
                        ],
                    ],
                ],
            ],
        ];

        $this->actingAs($admin)
            ->withHeaders($headers)
            ->put(route('wo_bushings.update', $woBushing->id), $payload(2))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(2, (int) WoBushingLine::query()
            ->where('wo_bushing_id', $woBushing->id)
            ->where('component_id', $component->id)
            ->value('qty'));

        $this->actingAs($admin)
            ->withHeaders($headers)
            ->put(route('wo_bushings.update', $woBushing->id), $payload(3))
            ->assertStatus(422)
            ->assertJsonValidationErrors('group_bushings')
            ->assertJsonPath(
                'errors.group_bushings.0',
                'Bushing group 1-160: total ordered QTY is 3. Maximum allowed QTY is 2.'
            );
    }

    public function test_bushing_group_accepts_mixed_original_and_oversize_up_to_original_qty_and_rejects_excess(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);
        $original = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-250',
            'part_number' => 'BUSH-ORIGINAL',
            'name' => 'Original bushing',
            'bush_ipl_num' => '8-250',
            'is_bush' => true,
            'units_assy' => 2,
        ]);
        $oversize = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-251',
            'part_number' => 'BUSH-OVERSIZE',
            'name' => 'Oversize bushing',
            'bush_ipl_num' => '8-250',
            'is_bush' => true,
            'units_assy' => 1,
        ]);
        $headers = ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];
        $form = $this->actingAs($admin)->get(route('wo_bushings.edit', $woBushing))->assertOk();
        $this->assertMatchesRegularExpression('/name="group_bushings\[8-250\]\[items\]\['.$oversize->id.'\]\[qty\]"[^>]*value="2"/s', $form->getContent());
        $this->withHeaders($headers)->put(route('wo_bushings.update', $woBushing), [
            'group_bushings' => ['8-250' => ['items' => [
                $oversize->id => ['selected' => '1', 'qty' => '2', 'need_processes' => '0'],
            ]]],
        ])->assertOk();
        $this->assertSame(2, (int) WoBushingLine::where('wo_bushing_id', $woBushing->id)->where('component_id', $oversize->id)->value('qty'));
        $payload = fn (int $originalQty, int $oversizeQty): array => [
            'group_bushings' => [
                '8-250' => [
                    'items' => [
                        $original->id => ['selected' => '1', 'qty' => (string) $originalQty, 'need_processes' => '0'],
                        $oversize->id => ['selected' => '1', 'qty' => (string) $oversizeQty, 'need_processes' => '0'],
                    ],
                ],
            ],
        ];

        $this->actingAs($admin)
            ->withHeaders($headers)
            ->put(route('wo_bushings.update', $woBushing->id), $payload(1, 1))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(2, WoBushingLine::query()->where('wo_bushing_id', $woBushing->id)->count());
        $this->assertSame(2, (int) WoBushingLine::query()->where('wo_bushing_id', $woBushing->id)->sum('qty'));

        $this->actingAs($admin)
            ->withHeaders($headers)
            ->put(route('wo_bushings.update', $woBushing->id), $payload(2, 1))
            ->assertStatus(422)
            ->assertJsonValidationErrors('group_bushings')
            ->assertJsonPath(
                'errors.group_bushings.0',
                'Bushing group 8-250: total ordered QTY is 3. Maximum allowed QTY is 2.'
            );

        $this->assertSame(2, WoBushingLine::query()->where('wo_bushing_id', $woBushing->id)->count());
        $this->assertSame(2, (int) WoBushingLine::query()->where('wo_bushing_id', $woBushing->id)->sum('qty'));
    }

    public function test_do_not_order_bushing_keeps_process_assignment_and_edit_state(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $component = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-231',
            'part_number' => '1840-0302-OS',
            'name' => 'Reused bushing with process route',
            'bush_ipl_num' => '8-230',
            'is_bush' => true,
            'units_assy' => 1,
        ]);
        $machining = $this->attachProcessToManual($manualId, 'Machining', 'Machine reused bushing');

        $response = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->put(route('wo_bushings.update', $woBushing->id), [
                'group_bushings' => [
                    '8-230' => [
                        'items' => [
                            $component->id => [
                                'selected' => '1',
                                'do_not_order' => '1',
                                'qty' => '1',
                                'need_processes' => '1',
                                'machining' => (string) $machining->id,
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $line = WoBushingLine::query()
            ->where('wo_bushing_id', $woBushing->id)
            ->where('component_id', $component->id)
            ->firstOrFail();

        $this->assertTrue($line->do_not_order);
        $this->assertDatabaseHas('wo_bushing_processes', [
            'wo_bushing_line_id' => $line->id,
            'process_id' => $machining->id,
            'qty' => 1,
        ]);

        $edit = $this->actingAs($admin)->get(route('wo_bushings.edit', [
            'wo_bushing' => $woBushing->id,
            'fragment' => 1,
        ]));

        $edit->assertOk();
        $this->assertMatchesRegularExpression(
            '/name="group_bushings\[8-230\]\[items\]\['.$component->id.'\]\[do_not_order\]"[^>]*checked/s',
            $edit->getContent()
        );
    }

    public function test_bushing_create_save_is_logged_on_workorder(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;

        $component = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-240',
            'part_number' => '1840-0402',
            'name' => 'Logged bushing',
            'bush_ipl_num' => '8-240',
            'is_bush' => true,
            'units_assy' => 2,
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->post(route('wo_bushings.store'), [
                'workorder_id' => $workorder->id,
                'group_bushings' => [
                    '8-240' => [
                        'items' => [
                            $component->id => [
                                'selected' => '1',
                                'qty' => '2',
                                'need_processes' => '0',
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('line_count', 1)
            ->assertJsonPath('process_count', 0);

        $woBushing = WoBushing::query()
            ->where('workorder_id', $workorder->id)
            ->first();

        $this->assertNotNull($woBushing);

        $line = WoBushingLine::query()
            ->where('wo_bushing_id', $woBushing->id)
            ->where('component_id', $component->id)
            ->first();

        $this->assertNotNull($line);
        $this->assertSame(2, $line->qty);
        $this->assertSame(0, WoBushingProcess::query()->where('wo_bushing_line_id', $line->id)->count());

        $activity = Activity::query()
            ->where('log_name', 'workorder')
            ->where('subject_type', $workorder::class)
            ->where('subject_id', $workorder->id)
            ->where('description', 'Bushing data created')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $props = $activity->properties->toArray();
        $this->assertSame('wo_bushings', $props['source'] ?? null);
        $this->assertSame('success', $props['status'] ?? null);
        $this->assertSame(1, $props['snapshot_after']['line_count'] ?? null);
        $this->assertSame(2, $props['snapshot_after']['total_qty'] ?? null);
        $this->assertSame('1840-0402', $props['snapshot_after']['rows'][0]['part_number'] ?? null);
        $this->assertArrayHasKey('bushing_save', $props['attributes'] ?? []);

        $this->actingAs($admin)
            ->get(route('workorders.logs-json', $workorder))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Bushing Save']);
    }

    public function test_edit_bushing_form_limits_ndt_choices_to_ndt_1_and_ndt_4(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-230',
            'part_number' => '1840-0302',
            'name' => 'Bushing',
            'bush_ipl_num' => '8-230',
            'is_bush' => true,
        ]);

        $this->attachProcessToManual($manualId, 'NDT-1', 'NDT one');
        $this->attachProcessToManual($manualId, 'NDT-4', 'NDT four');
        $this->attachProcessToManual($manualId, 'NDT-7', 'NDT seven');

        $response = $this->actingAs($admin)->get(route('wo_bushings.edit', [
            'wo_bushing' => $woBushing->id,
            'fragment' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('NDT-1', false);
        $response->assertSee('NDT-4', false);
        $response->assertDontSee('NDT-7', false);
    }

    public function test_add_process_from_bushing_form_limits_process_name_picker_to_bushing_columns(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $allowedNames = [
            'Machining',
            'Stress Relief',
            'NDT-1',
            'NDT-4',
            'Passivation',
            'Cad plate',
            'Anodizing',
            'Xylan coating',
        ];

        foreach (array_merge($allowedNames, ['Bake (Stress relief)', 'Paint']) as $name) {
            ProcessName::query()->updateOrCreate(
                ['name' => $name],
                [
                    'process_sheet_name' => $name,
                    'form_number' => $name,
                    'print_form' => true,
                    'show_in_process_picker' => true,
                ]
            );
        }

        $bushingForm = $this->actingAs($admin)->get(route('processes.create', [
            'manual_id' => $workorder->unit->manual_id,
            'context' => 'bushing',
            'workorder_id' => $workorder->id,
        ]));

        $bushingForm->assertOk();
        $bushingForm->assertSeeInOrder($allowedNames, false);
        $bushingForm->assertDontSee('>Bake (Stress relief)</option>', false);
        $bushingForm->assertDontSee('>Paint</option>', false);
    }

    public static function machiningNames(): array
    {
        return [['Machining'], ['Machining (AT)'], ['In-house metalwork']];
    }

    /** @dataProvider machiningNames */
    public function test_technician_without_cmm_permission_can_add_bushing_process_for_matching_workorder_with_log(string $machiningName): void
    {
        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $technician->id]);
        $manual = $workorder->unit->manual;
        $processName = ProcessName::query()->updateOrCreate(
            ['name' => $machiningName === 'In-house metalwork' ? 'Machining (AT)' : $machiningName],
            [
                'process_sheet_name' => 'MACHINING',
                'form_number' => '013',
                'print_form' => true,
                'show_in_process_picker' => true,
            ]
        );
        $processName->update(['name' => $machiningName]);
        $existing = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'Existing machining process for this CMM',
        ]);
        ManualProcess::query()->create([
            'manual_id' => $manual->id,
            'processes_id' => $existing->id,
        ]);

        $editorUrl = route('processes.create', [
            'manual_id' => $manual->id,
            'context' => 'bushing',
            'workorder_id' => $workorder->id,
            'modal' => 1,
        ]);

        $this->actingAs($technician)
            ->withSession(['auth.version' => (int) $technician->auth_version,
                'password_hash_web' => $technician->getAuthPassword()])
            ->get($editorUrl)
            ->assertOk()
            ->assertSee('Add Bushing Process')
            ->assertSee('>'.$machiningName.'</option>', false)
            ->assertSee('W'.$workorder->number)
            ->assertSee($manual->number)
            ->assertSee('name="context" value="bushing"', false)
            ->assertSee('name="workorder_id" value="'.$workorder->id.'"', false)
            ->assertDontSee('Manage CMMs');

        $this->actingAs($technician)
            ->getJson(route('processes.getProcesses', [
                'manualId' => $manual->id,
                'processNameId' => $processName->id,
                'context' => 'bushing',
                'workorder_id' => $workorder->id,
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('canCreateProcess', true)
            ->assertJsonFragment(['process' => 'Existing machining process for this CMM']);

        $this->actingAs($technician)
            ->postJson(route('processes.store'), [
                'manual_id' => $manual->id,
                'process_names_id' => $processName->id,
                'process' => 'Technician custom bushing machining process',
                'context' => 'bushing',
                'workorder_id' => $workorder->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('process.process', 'Technician custom bushing machining process');

        $process = Process::query()
            ->where('process_names_id', $processName->id)
            ->where('process', 'Technician custom bushing machining process')
            ->firstOrFail();

        $this->assertDatabaseHas('manual_processes', [
            'manual_id' => $manual->id,
            'processes_id' => $process->id,
        ]);

        $component = Component::create(['manual_id' => $manual->id, 'ipl_num' => '3-430D',
            'part_number' => 'MACH-TEST', 'name' => 'Bushing', 'is_bush' => true, 'units_assy' => 2]);
        $partial = $this->get(route('wo_bushings.partial', $workorder))->assertOk();
        $this->assertContains($processName->id, $partial->viewData('bushingProcessNameIdsByField')['machining']);
        $partial->assertSee('Technician custom bushing machining process');
        $bushing = WoBushing::create(['workorder_id' => $workorder->id]);
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->putJson(route('wo_bushings.update', $bushing), ['group_bushings' => ['3-430D' => ['items' => [
                $component->id => ['selected' => '1', 'qty' => 2, 'need_processes' => '1', 'machining' => $process->id],
            ]]]])->assertOk();
        $edit = $this->get(route('wo_bushings.edit', $bushing))->assertOk();
        $this->assertMatchesRegularExpression('/value="'.$process->id.'"\\s+selected/', $edit->getContent());
        $this->assertDatabaseHas('wo_bushing_processes', ['process_id' => $process->id]);

        $activity = Activity::query()
            ->where('log_name', 'workorder')
            ->where('subject_type', $workorder::class)
            ->where('subject_id', $workorder->id)
            ->where('description', 'Bushing process added to CMM')
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($technician->id, (int) $activity->causer_id);
        $this->assertSame('bushing_process_catalog', $activity->properties->get('source'));
        $this->assertSame($manual->number, $activity->properties->get('manual_number'));
        $this->assertSame($machiningName, $activity->properties->get('process_name'));
        $this->assertTrue((bool) $activity->properties->get('created_definition'));

        $this->actingAs($technician)
            ->get(route('workorders.logs-json', $workorder))
            ->assertOk()
            ->assertJsonFragment([
                'label' => 'Bushing Process CMM',
                'new' => $machiningName.': Technician custom bushing machining process',
            ]);
    }

    public function test_bushing_process_technician_access_is_bound_to_workorder_cmm_and_allowed_names(): void
    {
        $technician = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder(['user_id' => $technician->id]);
        $otherWorkorder = $this->createWorkorder(['user_id' => $technician->id]);
        $manual = $workorder->unit->manual;
        $paint = ProcessName::query()->updateOrCreate(
            ['name' => 'Paint'],
            [
                'process_sheet_name' => 'PAINT',
                'form_number' => '016',
                'print_form' => true,
                'show_in_process_picker' => true,
            ]
        );

        $this->actingAs($technician)
            ->get(route('processes.create', ['manual_id' => $manual->id]))
            ->assertRedirect(route('manuals.index'));

        $this->actingAs($technician)
            ->get(route('processes.create', [
                'manual_id' => $manual->id,
                'context' => 'bushing',
                'workorder_id' => $otherWorkorder->id,
            ]))
            ->assertStatus(422);

        $this->actingAs($technician)
            ->postJson(route('processes.store'), [
                'manual_id' => $manual->id,
                'process_names_id' => $paint->id,
                'process' => 'Paint must not enter the bushing process editor',
                'context' => 'bushing',
                'workorder_id' => $workorder->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('process_names_id');

        $this->assertDatabaseMissing('processes', [
            'process_names_id' => $paint->id,
            'process' => 'Paint must not enter the bushing process editor',
        ]);
    }

    public function test_bushing_processes_allow_machining_without_ndt_and_reset_spinner_on_validation_error(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $component = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-235',
            'part_number' => '1840-0352',
            'name' => 'Machining only bushing',
            'bush_ipl_num' => '8-235',
            'is_bush' => true,
            'units_assy' => 1,
        ]);
        $machining = $this->attachProcessToManual($manualId, 'Machining', 'Machining only');

        $editForm = $this->actingAs($admin)->get(route('wo_bushings.edit', [
            'wo_bushing' => $woBushing->id,
            'fragment' => 1,
        ]));

        $editForm->assertOk();
        $editForm->assertSee('Machining only', false);
        $editForm->assertSee('data-bushing-add-process', false);
        $editForm->assertSee(route('processes.create', ['manual_id' => $manualId, 'context' => 'bushing', 'workorder_id' => $workorder->id]));
        $editForm->assertSee('data-process-name-ids="'.$machining->process_names_id.'"', false);
        $editForm->assertSee('window.addBushingProcessOption', false);
        $editForm->assertSee('data-bushing-part-label', false);
        $editForm->assertSee('--bushing-col-bushing-width', false);
        $editForm->assertSee("delay: { show: 500, hide: 0 }", false);
        $editForm->assertSee('label.scrollWidth <= label.clientWidth + 1', false);
        $editForm->assertSee("typeof window.safeHideSpinner === 'function'", false);
        $editForm->assertSee("form.addEventListener('invalid'", false);
        $editForm->assertSee('Please enter Qty for selected bushings.', false);
        $editForm->assertDontSee('Please enter Qty and NDT for selected bushings with processes.', false);
        $editForm->assertDontSee('ndt.options.length > 1 && !ndt.value', false);

        $showForm = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));

        $showForm->assertOk();
        $showForm->assertSee("typeof window.safeHideSpinner === 'function'", false);
        $showForm->assertSee("bushingTabBody.addEventListener('invalid'", false);
        $showForm->assertSee('Please enter Qty for selected bushings.', false);
        $showForm->assertDontSee('Please enter Qty and NDT for selected bushings with processes.', false);
        $showForm->assertDontSee('ndt.options.length > 1 && !ndt.value', false);

        $save = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->put(route('wo_bushings.update', $woBushing->id), [
                'group_bushings' => [
                    '8-235' => [
                        'items' => [
                            $component->id => [
                                'selected' => '1',
                                'qty' => '1',
                                'need_processes' => '1',
                                'machining' => (string) $machining->id,
                                'ndt' => '',
                            ],
                        ],
                    ],
                ],
            ]);

        $save->assertOk()->assertJson(['success' => true]);

        $line = WoBushingLine::query()
            ->where('wo_bushing_id', $woBushing->id)
            ->where('component_id', $component->id)
            ->first();

        $this->assertNotNull($line);
        $this->assertDatabaseHas('wo_bushing_processes', [
            'wo_bushing_line_id' => $line->id,
            'process_id' => $machining->id,
        ]);
        $this->assertSame(1, WoBushingProcess::query()->where('wo_bushing_line_id', $line->id)->count());
    }

    public function test_bushing_process_form_uses_batches_and_b_labels_only(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $processName = ProcessName::query()->firstOrCreate(
            ['name' => 'Cad plate'],
            [
                'process_sheet_name' => 'CAD',
                'form_number' => '014',
                'print_form' => true,
                'show_in_process_picker' => true,
            ]
        );
        $processName->forceFill(['print_form' => true])->save();
        $process = Process::query()->create([
            'process_names_id' => $processName->id,
            'process' => 'CAD plating',
        ]);
        ManualProcess::query()->create([
            'manual_id' => $manualId,
            'processes_id' => $process->id,
        ]);

        $batchedComponent = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-230',
            'part_number' => 'BATCH-PN',
            'name' => 'Batched bushing',
            'bush_ipl_num' => '8-230',
            'is_bush' => true,
        ]);
        $looseComponent = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-240',
            'part_number' => 'LOOSE-PN',
            'name' => 'Loose bushing',
            'bush_ipl_num' => '8-240',
            'is_bush' => true,
        ]);
        $secondBatchedComponent = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-250',
            'part_number' => 'BATCH2-PN',
            'name' => 'Second batched bushing',
            'bush_ipl_num' => '8-250',
            'is_bush' => true,
        ]);

        $batchedLine = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $batchedComponent->id,
            'qty' => 2,
            'qty_remaining' => 2,
            'group_key' => '8-230',
            'sort_order' => 1,
        ]);
        $looseLine = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $looseComponent->id,
            'qty' => 1,
            'qty_remaining' => 1,
            'group_key' => '8-240',
            'sort_order' => 2,
        ]);
        $secondBatchedLine = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $secondBatchedComponent->id,
            'qty' => 1,
            'qty_remaining' => 1,
            'group_key' => '8-250',
            'sort_order' => 3,
        ]);
        $batch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $process->id,
            'process_column_key' => 'cad',
        ]);
        $secondBatch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $process->id,
            'process_column_key' => 'cad',
        ]);

        WoBushingProcess::query()->create([
            'wo_bushing_line_id' => $batchedLine->id,
            'process_id' => $process->id,
            'batch_id' => $batch->id,
            'qty' => 2,
        ]);
        WoBushingProcess::query()->create([
            'wo_bushing_line_id' => $secondBatchedLine->id,
            'process_id' => $process->id,
            'batch_id' => $secondBatch->id,
            'qty' => 1,
        ]);
        WoBushingProcess::query()->create([
            'wo_bushing_line_id' => $looseLine->id,
            'process_id' => $process->id,
            'qty' => 1,
        ]);

        $partial = $this->actingAs($admin)->get(route('wo_bushings.partial', $workorder->id));
        $partial->assertOk();
        $partial->assertSee('B1', false);
        $partial->assertDontSee('Grp', false);
        $partial->assertSee(route('wo_bushings.processesForm', ['id' => $woBushing->id, 'processNameId' => $processName->id]), false);
        $partial->assertDontSee('bushing_component_ids[]=', false);

        $processFormWithoutSelection = $this->actingAs($admin)->get(route('wo_bushings.processesForm', [
            'id' => $woBushing->id,
            'processNameId' => $processName->id,
        ]));
        $processFormWithoutSelection->assertOk();
        $processFormWithoutSelection->assertDontSee('BATCH-PN', false);
        $processFormWithoutSelection->assertDontSee('LOOSE-PN', false);

        $processForm = $this->actingAs($admin)->get(route('wo_bushings.processesForm', [
            'id' => $woBushing->id,
            'processNameId' => $processName->id,
            'bushing_batch_ids' => [$batch->id],
        ]));
        $processForm->assertOk();
        $processForm->assertSee('B1', false);
        $processForm->assertSee('BATCH-PN', false);
        $processForm->assertDontSee('LOOSE-PN', false);
        $processForm->assertDontSee('BATCH2-PN', false);

        $processFormWithTwoBatches = $this->actingAs($admin)->get(route('wo_bushings.processesForm', [
            'id' => $woBushing->id,
            'processNameId' => $processName->id,
            'bushing_batch_ids' => [$batch->id, $secondBatch->id],
        ]));
        $processFormWithTwoBatches->assertOk();
        $processFormWithTwoBatches->assertSee('BATCH-PN', false);
        $processFormWithTwoBatches->assertDontSee('BATCH2-PN', false);

        $form = $this->actingAs($admin)->get(route('wo_bushings.specProcessForm', $woBushing->id));
        $form->assertOk();
        $form->assertDontSee('BATCH-PN', false);
        $form->assertDontSee('LOOSE-PN', false);
        $form->assertDontSee('BATCH2-PN', false);
    }

    public function test_bushing_tab_shows_cad_checkbox_when_cad_instruction_contains_bake(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);
        $cad = $this->attachProcessToManual(
            $manualId,
            'Cad plate',
            'MIL-STD-870. Bake for 23 hours at 350-400 F.'
        );
        $component = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '3-1260',
            'part_number' => '49001-115',
            'name' => 'Bushing',
            'bush_ipl_num' => '3-1260',
            'is_bush' => true,
        ]);
        $line = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'qty' => 2,
            'qty_remaining' => 2,
            'group_key' => '3-1260',
            'sort_order' => 1,
        ]);
        $woProcess = WoBushingProcess::query()->create([
            'wo_bushing_line_id' => $line->id,
            'process_id' => $cad->id,
            'qty' => 2,
        ]);

        $partial = $this->actingAs($admin)->get(route('wo_bushings.partial', $workorder->id));

        $partial->assertOk();
        $partial->assertSee('data-process-key="cad" data-wo-process-id="'.$woProcess->id.'"', false);
        $partial->assertDontSee('data-process-key="stress_relief" data-wo-process-id="'.$woProcess->id.'"', false);
    }

    public function test_bushing_spec_process_form_prints_part_number_without_qty_across_batches(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $machining = $this->attachProcessToManual($manualId, 'Machining', 'Machine bushings');
        $ndt = $this->attachProcessToManual($manualId, 'NDT-1', 'NDT bushings');
        $cad = $this->attachProcessToManual($manualId, 'Cad plate', 'CAD plating');

        $component = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '1-561',
            'part_number' => 'MERGED-PN',
            'name' => 'Grouped bushing',
            'bush_ipl_num' => '1-561',
            'is_bush' => true,
        ]);
        $line = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'qty' => 2,
            'qty_remaining' => 2,
            'group_key' => '1-561',
            'sort_order' => 1,
        ]);

        foreach ([
            ['process' => $machining, 'key' => 'machining'],
            ['process' => $ndt, 'key' => 'ndt'],
            ['process' => $cad, 'key' => 'cad'],
        ] as $row) {
            $batch = WoBushingBatch::query()->create([
                'workorder_id' => $workorder->id,
                'process_id' => $row['process']->id,
                'process_column_key' => $row['key'],
                'date_start' => '2026-09-01',
            ]);

            WoBushingProcess::query()->create([
                'wo_bushing_line_id' => $line->id,
                'process_id' => $row['process']->id,
                'batch_id' => $batch->id,
                'qty' => 2,
            ]);
        }

        $form = $this->actingAs($admin)->get(route('wo_bushings.specProcessForm', $woBushing->id));

        $form->assertOk();
        $html = $form->getContent();
        // Three operations of the same physical line form one SP column.
        $this->assertSame(1, substr_count($html, 'MERGED-PN'));
        $this->assertStringContainsString('>MERGED-PN</div>', $html);
        $this->assertStringContainsString('QTY: 2', $html);
        $this->assertStringNotContainsString('MERGED-PN : 2', $html);
        $this->assertStringNotContainsString('MERGED-PN : 6', $html);
        $this->assertStringNotContainsString('spec-group-label-box">B1</span>', $html);
    }

    public function test_bushing_spec_process_form_groups_six_part_numbers_per_cell_by_process_route(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $machining = $this->attachProcessToManual($manualId, 'Machining', 'Machine bushings');

        foreach (range(1, 7) as $index) {
            $component = Component::query()->create([
                'manual_id' => $manualId,
                'ipl_num' => '9-'.$index,
                'part_number' => sprintf('PACK-PN-%02d', $index),
                'name' => 'Packed bushing '.$index,
                'bush_ipl_num' => '9-'.$index,
                'is_bush' => true,
            ]);

            $line = WoBushingLine::query()->create([
                'wo_bushing_id' => $woBushing->id,
                'workorder_id' => $workorder->id,
                'component_id' => $component->id,
                'qty' => 1,
                'qty_remaining' => 1,
                'group_key' => '9-'.$index,
                'sort_order' => $index,
            ]);

            WoBushingProcess::query()->create([
                'wo_bushing_line_id' => $line->id,
                'process_id' => $machining->id,
                'qty' => 1,
            ]);
        }

        $sentBatch = WoBushingBatch::create(['workorder_id' => $workorder->id, 'process_id' => $machining->id, 'process_column_key' => 'machining', 'date_start' => '2026-09-01']);
        WoBushingProcess::whereHas('line', fn ($q) => $q->where('wo_bushing_id', $woBushing->id))
            ->whereHas('process', fn ($q) => $q->where('process_names_id', $machining->process_names_id))->update(['batch_id' => $sentBatch->id]);

        $form = $this->actingAs($admin)->get(route('wo_bushings.specProcessForm', $woBushing->id));

        $form->assertOk();
        $html = $form->getContent();

        $this->assertSame(2, substr_count($html, 'spec-part-no-row'));
        $this->assertStringContainsString('data-process-table-rows-max="13"', $html);
        $this->assertStringContainsString('PACK-PN-01', $html);
        $this->assertStringContainsString('PACK-PN-06', $html);
        $this->assertStringContainsString('PACK-PN-07', $html);
        $this->assertStringContainsString('QTY: 7', $html);
        $this->assertStringNotContainsString('PACK-PN-01 : ', $html);
    }

    public function test_bushing_spec_process_print_settings_allow_fourteen_process_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $response = $this->actingAs($admin)->get(route('wo_bushings.specProcessForm', $woBushing->id));

        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('id="processTableRows" name="processTableRows"', $html);
        $this->assertStringContainsString('max="14"', $html);
        $this->assertStringContainsString('const PROCESS_TABLE_ROWS_MAX = 14;', $html);
        $this->assertStringContainsString('clampNumber(merged.processTableRows, 1, PROCESS_TABLE_ROWS_MAX, 14)', $html);
        $this->assertSame(7, substr_count($html, 'spec-extra-process-row'));
    }

    public function test_bushing_spec_process_form_separates_sent_batches_with_same_part_number(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $machining = $this->attachProcessToManual($manualId, 'Machining', 'Machine bushings');
        $ndt = $this->attachProcessToManual($manualId, 'NDT-4', 'NDT bushings');
        $cad = $this->attachProcessToManual($manualId, 'Cad plate', 'CAD plating');

        $first = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-231',
            'part_number' => '1840-0302RS01',
            'name' => 'First bushing',
            'bush_ipl_num' => '8-230',
            'is_bush' => true,
        ]);
        $second = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '8-361',
            'part_number' => '1840-0302RS01',
            'name' => 'Second bushing',
            'bush_ipl_num' => '8-360',
            'is_bush' => true,
        ]);

        $firstLine = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $first->id,
            'qty' => 2,
            'qty_remaining' => 2,
            'group_key' => '8-230',
            'sort_order' => 1,
        ]);
        $secondLine = WoBushingLine::query()->create([
            'wo_bushing_id' => $woBushing->id,
            'workorder_id' => $workorder->id,
            'component_id' => $second->id,
            'qty' => 2,
            'qty_remaining' => 2,
            'group_key' => '8-360',
            'sort_order' => 2,
        ]);

        $machiningBatch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $machining->id,
            'process_column_key' => 'machining',
            'date_start' => '2026-09-01',
        ]);
        $firstNdtBatch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $ndt->id,
            'process_column_key' => 'ndt',
            'date_start' => '2026-09-01',
        ]);
        $secondNdtBatch = WoBushingBatch::query()->create([
            'workorder_id' => $workorder->id,
            'process_id' => $ndt->id,
            'process_column_key' => 'ndt',
            'date_start' => '2026-09-01',
        ]);

        foreach ([
            [$firstLine, $machining, $machiningBatch],
            [$secondLine, $machining, $machiningBatch],
            [$firstLine, $ndt, $firstNdtBatch],
            [$secondLine, $ndt, $secondNdtBatch],
            [$firstLine, $cad, null],
            [$secondLine, $cad, null],
        ] as [$line, $process, $batch]) {
            WoBushingProcess::query()->create([
                'wo_bushing_line_id' => $line->id,
                'process_id' => $process->id,
                'batch_id' => $batch?->id,
                'qty' => 2,
            ]);
        }

        $form = $this->actingAs($admin)->get(route('wo_bushings.specProcessForm', $woBushing->id));

        $form->assertOk();
        $html = $form->getContent();
        // Distinct NDT shipments stay separate; their shared machining is not counted again.
        $this->assertSame(2, substr_count($html, '1840-0302RS01'));
        $this->assertSame(2, substr_count($html, 'QTY: 2'));
        $this->assertStringNotContainsString('QTY: 4', $html);
        $this->assertStringNotContainsString('1840-0302RS01 : 4', $html);
        $this->assertStringNotContainsString('1840-0302RS01 : 2', $html);
    }

    public function test_bushing_spec_process_form_groups_same_process_names_even_with_different_process_rows(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $woBushing = WoBushing::query()->create(['workorder_id' => $workorder->id]);

        $machining = $this->attachProcessToManual($manualId, 'Machining', 'Machine bushings 1');
        $ndt = $this->attachProcessToManual($manualId, 'NDT-1', 'NDT bushings 1');
        $cad = $this->attachProcessToManual($manualId, 'Cad plate', 'CAD plating 1');

        $processNamesByKey = [
            'machining' => $machining->process_names_id,
            'ndt' => $ndt->process_names_id,
            'cad' => $cad->process_names_id,
        ];

        foreach (range(1, 4) as $index) {
            $component = Component::query()->create([
                'manual_id' => $manualId,
                'ipl_num' => '7-'.$index,
                'part_number' => sprintf('ROUTE-PN-%02d', $index),
                'name' => 'Route bushing '.$index,
                'bush_ipl_num' => '7-'.$index,
                'is_bush' => true,
            ]);

            $line = WoBushingLine::query()->create([
                'wo_bushing_id' => $woBushing->id,
                'workorder_id' => $workorder->id,
                'component_id' => $component->id,
                'qty' => 1,
                'qty_remaining' => 1,
                'group_key' => '7-'.$index,
                'sort_order' => $index,
            ]);

            foreach ($processNamesByKey as $key => $processNameId) {
                $process = Process::query()->create([
                    'process_names_id' => $processNameId,
                    'process' => strtoupper($key).' process copy '.$index,
                ]);

                ManualProcess::query()->create([
                    'manual_id' => $manualId,
                    'processes_id' => $process->id,
                ]);

                WoBushingProcess::query()->create([
                    'wo_bushing_line_id' => $line->id,
                    'process_id' => $process->id,
                    'qty' => 1,
                ]);
            }
        }

        $sentBatch = WoBushingBatch::create(['workorder_id' => $workorder->id, 'process_id' => $ndt->id, 'process_column_key' => 'ndt', 'date_start' => '2026-09-01']);
        WoBushingProcess::whereHas('line', fn ($q) => $q->where('wo_bushing_id', $woBushing->id))
            ->whereHas('process', fn ($q) => $q->where('process_names_id', $ndt->process_names_id))->update(['batch_id' => $sentBatch->id]);

        $form = $this->actingAs($admin)->get(route('wo_bushings.specProcessForm', $woBushing->id));

        $form->assertOk();
        $html = $form->getContent();

        $this->assertSame(1, substr_count($html, '<span class="spec-batch-title">Bush '));
        $this->assertSame(1, substr_count($html, '<div class="part-no-data">'));
        $this->assertStringContainsString('ROUTE-PN-01', $html);
        $this->assertStringContainsString('ROUTE-PN-04', $html);
        $this->assertStringContainsString('QTY: 4', $html);
        $this->assertStringNotContainsString('QTY: 1', $html);
    }

    public function test_ndt4_bushing_batch_prints_when_ndt1_printing_is_disabled(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $wo = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $wo->unit->manual_id;
        $ndt1 = $this->attachProcessToManual($manualId, 'NDT-1', 'Unused NDT one');
        $ndt1->process_name->update(['print_form' => false]);
        $ndt4 = $this->attachProcessToManual($manualId, 'NDT-4', 'ASTM-E-1417 Level 3 or 4');
        $bushing = WoBushing::create(['workorder_id' => $wo->id]);
        $batch = WoBushingBatch::create(['workorder_id' => $wo->id, 'process_id' => $ndt4->id, 'process_column_key' => 'ndt']);
        foreach (['52120-5', '52120-7'] as $index => $pn) {
            $component = Component::create(['manual_id' => $manualId, 'ipl_num' => '3-'.(180 + $index * 5), 'part_number' => $pn, 'name' => 'Bushing', 'is_bush' => true]);
            $line = WoBushingLine::create(['wo_bushing_id' => $bushing->id, 'workorder_id' => $wo->id, 'component_id' => $component->id, 'qty' => 2, 'qty_remaining' => 2, 'group_key' => $component->ipl_num, 'sort_order' => $index]);
            WoBushingProcess::create(['wo_bushing_line_id' => $line->id, 'process_id' => $ndt4->id, 'batch_id' => $batch->id, 'qty' => 2]);
        }
        $url = route('wo_bushings.processesForm', ['id' => $bushing->id, 'processNameId' => $ndt4->process_names_id]);
        $this->actingAs($admin)->get(route('wo_bushings.partial', $wo->id))->assertOk()->assertSee($url, false);
        $this->get($url.'?'.http_build_query(['bushing_batch_ids' => [$batch->id]]))
            ->assertOk()->assertSee('52120-5')->assertSee('52120-7')->assertSee('B1')->assertSee('ASTM-E-1417');
        $ndt4->process_name->update(['print_form' => false]);
        $this->get(route('wo_bushings.partial', $wo->id))->assertOk()->assertDontSee($url, false);
        $this->get($url)->assertRedirect();
    }

    public function test_bushing_pickers_use_comments_from_the_current_manual(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        Component::query()->create([
            'manual_id' => $manualId, 'ipl_num' => '1-10', 'part_number' => 'COMMENT-BUSH',
            'name' => 'Bushing', 'bush_ipl_num' => '1-10', 'is_bush' => true, 'units_assy' => 1,
        ]);
        $process = $this->attachProcessToManual($manualId, 'Cad plate', 'CAD with comment');
        ManualProcess::where('manual_id', $manualId)->where('processes_id', $process->id)
            ->update(['process_comment' => 'Bushings <only> & steel']);
        $otherWorkorder = $this->createWorkorder(['user_id' => $admin->id]);
        ManualProcess::create([
            'manual_id' => $otherWorkorder->unit->manual_id, 'processes_id' => $process->id,
            'process_comment' => 'Other manual comment',
        ]);
        $this->actingAs($admin)->get(route('wo_bushings.partial', $workorder->id))
            ->assertOk()->assertSee('data-process-comment="Bushings &lt;only&gt; &amp; steel"', false)
            ->assertDontSee('Other manual comment');
        $bushing = WoBushing::create(['workorder_id' => $workorder->id]);
        $this->get(route('wo_bushings.edit', $bushing->id).'?fragment=1')
            ->assertOk()->assertSee('data-process-comment="Bushings &lt;only&gt; &amp; steel"', false)
            ->assertDontSee('Other manual comment');
    }

    private function attachProcessToManual(int $manualId, string $processName, string $processText): Process
    {
        $name = ProcessName::query()->firstOrCreate(
            ['name' => $processName],
            [
                'process_sheet_name' => 'NDT',
                'form_number' => 'NDT',
                'print_form' => true,
                'show_in_process_picker' => true,
            ]
        );
        $name->forceFill(['print_form' => true])->save();

        $process = Process::query()->create([
            'process_names_id' => $name->id,
            'process' => $processText,
        ]);

        ManualProcess::query()->create([
            'manual_id' => $manualId,
            'processes_id' => $process->id,
        ]);

        return $process;
    }
}
