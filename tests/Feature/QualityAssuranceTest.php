<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Code;
use App\Models\Component;
use App\Models\GeneralTask;
use App\Models\LogCard;
use App\Models\Main;
use App\Models\ManualServiceBulletin;
use App\Models\ManualRevisionCheck;
use App\Models\Necessary;
use App\Models\ProcessName;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Workorder;
use App\Models\WorkorderServiceBulletinLog;
use App\Models\WorkorderStdProcess;
use App\Support\LogCardDestructionCertificate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\BuildsDomainData;
use Tests\TestCase;

class QualityAssuranceTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $viewPath = base_path('codex-test-runtime' . DIRECTORY_SEPARATOR . 'quality-test-views');
        File::ensureDirectoryExists($viewPath);
        config()->set('view.compiled', $viewPath);
    }

    public function test_admin_can_open_quality_dashboard(): void
    {
        $admin = $this->createUserWithRole('Admin');

        $response = $this->actingAs($admin)->get(route('quality.index'));

        $response->assertOk();
        $response->assertSee('Quality Assurance');
        $response->assertSee('Enter full workorder number');
        $response->assertSee('qaRepairFilter', false);
        $response->assertSee('repairOrderFilter', false);
    }

    public function test_manager_with_qa_access_can_open_quality_dashboard(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);

        $response = $this->actingAs($manager)->get(route('quality.index'));

        $response->assertOk();
        $response->assertSee('Quality Assurance');
    }

    public function test_admin_with_qa_access_can_open_quality_dashboard_without_is_admin(): void
    {
        $admin = $this->createUserWithRole('Admin', [
            'is_admin' => false,
            'qa_access' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('quality.index'));

        $response->assertOk();
        $response->assertSee('Quality Assurance');
    }

    public function test_manager_without_qa_access_cannot_open_quality_dashboard(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => false,
        ]);

        $response = $this->actingAs($manager)->get(route('quality.index'));

        $response->assertForbidden();
    }

    public function test_personal_qa_flag_does_not_grant_access_to_non_manager(): void
    {
        $technician = $this->createUserWithRole('Technician', [
            'qa_access' => true,
        ]);

        $response = $this->actingAs($technician)->get(route('quality.index'));

        $response->assertForbidden();
    }

    public function test_workorder_lookup_requires_full_normalized_number_and_returns_single_payload(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'name' => 'Current Release Manager',
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder(['number' => 988801]);

        $workorder
            ->addMedia(UploadedFile::fake()->image('qa-photo.jpg', 1, 1))
            ->toMediaCollection('photos');

        $partial = $this->actingAs($manager)->getJson(route('quality.workorder', [
            'q' => '988',
        ]));

        $partial->assertStatus(422);
        $partial->assertJsonPath('message', 'Enter full 6-digit workorder number.');

        $response = $this->actingAs($manager)->getJson(route('quality.workorder', [
            'q' => 'w 988 801',
        ]));

        $response->assertOk();
        $response->assertJsonPath('found', true);
        $response->assertJsonPath('normalized', '988801');
        $response->assertJsonPath('workorder.id', $workorder->id);
        $response->assertJsonPath('workorder.number', '988801');
        $response->assertJsonStructure([
            'workorder' => [
                'top',
                'photos',
                'submitted',
                'std_processes',
                'repair_orders',
                'forms',
            ],
        ]);
        $forms = collect($response->json('workorder.forms'));
        $this->assertSame([
            'log_card',
            'service_bulletin_log',
            'sp_form',
            'certificate',
            'shipment',
            'certificate_of_destruction',
        ], $forms->pluck('key')->all());
        $this->assertSame([
            'Log Card',
            'SB log',
            'SP Form',
            'Form ONE',
            'Shipment',
            'Certificate of destruction',
        ], $forms->pluck('title')->all());
        $this->assertContains('certificate', $forms->pluck('key')->all());
        $this->assertSame(
            route('quality.forms.certificate', ['workorder' => $workorder->id]),
            $forms->firstWhere('key', 'certificate')['url']
        );
        $this->assertContains('sp_form', $forms->pluck('key')->all());
        $this->assertSame(
            route('tdrs.specProcessFormEmp', ['id' => $workorder->id]),
            $forms->firstWhere('key', 'sp_form')['url']
        );
    }

    public function test_shipment_release_form_defaults_shipset_to_no(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'name' => 'Current Release Manager',
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder(['number' => 988802]);

        $response = $this->actingAs($manager)
            ->get(route('quality.forms.shipment_release', $workorder));

        $response->assertOk();
        $response->assertSee('WO part of the', false);
        $response->assertSee('<option value="Yes">Yes</option>', false);
        $response->assertSee('<option value="No" selected>No</option>', false);
        $response->assertSee('<span class="shipset-print-value" id="shipsetPrintValue">No</span>', false);
        $response->assertDontSee('<option value="Yes" selected>Yes</option>', false);
    }

    public function test_certificate_form_opens_from_quality(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
            'can_sign_certificates' => true,
        ]);
        $manual = $this->createManual([
            'number' => '32-11-08',
            'revision_date' => '2025-06-15',
        ]);
        ManualRevisionCheck::query()->create([
            'manual_id' => $manual->id,
            'revision_number' => '12',
            'revision_date' => '2025-06-15',
            'checked_at' => '2025-06-15',
            'status' => ManualRevisionCheck::STATUS_UNCHANGED,
        ]);
        $unit = $this->createUnit([
            'manual_id' => $manual->id,
            'part_number' => '190-70260-407',
            'name' => 'Lower Stay Assy',
        ]);
        $workorder = $this->createWorkorder([
            'number' => 988803,
            'unit_id' => $unit->id,
            'instruction_id' => $this->createOverhaulInstruction()->id,
            'description' => 'Lower Stay Assy',
            'customer_po' => '500013602',
            'serial_number' => '1464362/001',
            'modified' => '190-70262-007',
            'approve_at' => '2025-10-28',
        ]);
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);
        $orderedComponent = Component::query()->create([
            'manual_id' => $manual->id,
            'part_number' => '190-70262-009',
            'name' => 'Ordered Replacement',
            'ipl_num' => '32-1',
        ]);
        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'tdr_type' => Tdr::TYPE_ORDER_NEW,
            'order_component_id' => $orderedComponent->id,
            'necessaries_id' => $orderNew->id,
            'serial_number' => '1464362/001',
            'assy_serial_number' => '1453146/005',
            'qty' => 1,
        ]);
        LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                [
                    'qa_fit_csn' => '13931',
                    'qa_fit_cso' => '0',
                ],
            ]),
        ]);
        $firstBulletin = ManualServiceBulletin::query()->create([
            'manual_id' => $manual->id,
            'sort_order' => 1,
            'ac_mfg_service_bulletin_no' => '170-32-0060 R1',
            'oem_service_bulletin_no' => '2801A-32-09 R2',
            'awd_no' => '11/7/2019',
            'default_requirement' => ManualServiceBulletin::REQUIREMENT_MANDATORY,
            'is_active' => true,
        ]);
        $secondBulletin = ManualServiceBulletin::query()->create([
            'manual_id' => $manual->id,
            'sort_order' => 2,
            'ac_mfg_service_bulletin_no' => '170-32-A94 R2',
            'oem_service_bulletin_no' => 'N/A',
            'awd_no' => 'E2024-05-09 R1',
            'default_requirement' => ManualServiceBulletin::REQUIREMENT_MANDATORY,
            'is_active' => true,
        ]);
        $notCarriedOutBulletin = ManualServiceBulletin::query()->create([
            'manual_id' => $manual->id,
            'sort_order' => 3,
            'ac_mfg_service_bulletin_no' => 'IGNORE-SB',
            'oem_service_bulletin_no' => 'N/A',
            'awd_no' => 'N/A',
            'default_requirement' => ManualServiceBulletin::REQUIREMENT_RECOMMENDED,
            'is_active' => true,
        ]);
        WorkorderServiceBulletinLog::query()->create([
            'workorder_id' => $workorder->id,
            'manual_service_bulletin_id' => $firstBulletin->id,
            'status' => WorkorderServiceBulletinLog::STATUS_PREVIOUSLY_CARRIED_OUT,
        ]);
        WorkorderServiceBulletinLog::query()->create([
            'workorder_id' => $workorder->id,
            'manual_service_bulletin_id' => $secondBulletin->id,
            'status' => WorkorderServiceBulletinLog::STATUS_AT_CARRIED_OUT,
        ]);
        WorkorderServiceBulletinLog::query()->create([
            'workorder_id' => $workorder->id,
            'manual_service_bulletin_id' => $notCarriedOutBulletin->id,
            'status' => WorkorderServiceBulletinLog::STATUS_NOT_CARRIED_OUT,
        ]);

        $response = $this->actingAs($manager)
            ->get(route('quality.forms.certificate', $workorder));

        $response->assertOk();
        $response->assertSee('<title>CERTIFICATE</title>', false);
        $response->assertSee('AUTHORIZED RELEASE CERTIFICATE');
        $response->assertSee('Form One');
        $response->assertSee('W988803');
        $response->assertSee('500013602');
        $response->assertSee($manager->name);
        $response->assertDontSee('Alexey Baydalia');
        $response->assertSee('28/Oct/2025');
        $response->assertDontSee('28/oct/2025');
        $response->assertSee('arc-date-hint');
        $response->assertSee('Date (dd/mmm/yyyy)');
        $response->assertSee('Lower Stay Assy');
        $response->assertSee('190-70260-407');
        $response->assertSee('190-70262-007');
        $response->assertSee('1464362/001');
        $response->assertSee('1453146/005');
        $response->assertSee('Rev # 12 dated');
        $response->assertSee('For the replacement parts refer to Teardown Report.');
        $response->assertSee('Overhauled on ...................');
        $response->assertSee('data-setting-key="include_overhauled_on"', false);
        $response->assertSee('Airworthiness Directives 2019-11-07, E2024-05-09 R1 and Service Bulletins: 170-32-0060 R1, 170-32-A94 R2 incorporated.');
        $response->assertDontSee('IGNORE-SB');
        $response->assertSee('Landing Gear Log Card attached');
        $response->assertSee('CSN-13931; CSO-0');
        $response->assertSee('Serviced with ROYCO LGF (Yellow)');
        $response->assertSee('CAR 571.10 Maintenance Release.');
    }

    public function test_certificate_description_uses_unit_name_before_workorder_description(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
            'can_sign_certificates' => true,
        ]);
        $unit = $this->createUnit([
            'name' => 'PIN',
            'description' => 'Unit fallback description',
        ]);
        $workorder = $this->createWorkorder([
            'number' => 107616,
            'unit_id' => $unit->id,
            'description' => 'Pin, Torque Arm',
        ]);

        $response = $this->actingAs($manager)
            ->get(route('quality.forms.certificate', $workorder));

        $response->assertOk();
        $response->assertSee('data-certificate-item-description', false);
        $response->assertSee('contenteditable="true"', false);
        $response->assertSee('>PIN</div>', false);
        $response->assertDontSee('>Pin, Torque Arm</div>', false);
    }

    public function test_certificate_replacement_parts_remark_uses_ordered_part_list(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder();
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'REPAIR-PN',
            'name' => 'Repair Part',
            'ipl_num' => '1-1',
        ]);
        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'tdr_type' => Tdr::TYPE_COMPONENT_TDR,
            'component_id' => $component->id,
            'qty' => 1,
        ]);

        $response = $this->actingAs($manager)
            ->get(route('quality.forms.certificate', $workorder));

        $response->assertOk();
        $response->assertSee('Replacements parts: None.');
        $response->assertDontSee('For the replacement parts refer to Teardown Report.');
    }

    public function test_certificate_can_use_selected_log_card_detail_item(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder([
            'number' => 107736,
            'description' => 'Main Detail',
            'serial_number' => 'MAIN-SN',
        ]);
        $workorder->unit->forceFill(['name' => 'Main Detail'])->save();
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LOG-PN',
            'name' => 'Log Card Detail',
            'ipl_num' => '7-42',
            'eff_code' => null,
        ]);
        $secondComponent = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'LOG-PN-2',
            'name' => 'Second Log Card Detail',
            'ipl_num' => '7-43',
            'eff_code' => null,
        ]);
        LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data_out' => [
                [
                    'component_id' => $component->id,
                    'serial_number' => 'LOG-SN',
                    'qa_fit_csn' => '11111',
                    'qa_fit_cso' => '22',
                ],
                [
                    'component_id' => $secondComponent->id,
                    'serial_number' => 'LOG-SN-2',
                    'qa_fit_csn' => '30228',
                    'qa_fit_cso' => '3162',
                ],
            ],
        ]);
        $workorder->forceFill([
            'certificate_data' => [
                'certificate_item_source' => 'log:1',
                'certificate_tracking_mode' => 'c',
            ],
        ])->save();

        $response = $this->actingAs($manager)
            ->get(route('quality.forms.certificate', $workorder));

        $response->assertOk();
        $response->assertSee('data-certificate-detail-main', false);
        $response->assertSee('data-certificate-detail-toggle', false);
        $response->assertSee('data-certificate-detail-select', false);
        $response->assertSee('Log Card Detail | LOG-PN | LOG-SN');
        $response->assertSee('Second Log Card Detail | LOG-PN-2 | LOG-SN-2');
        $response->assertSee('<div class="arc-tracking-no" data-certificate-tracking-number>W107736-2</div>', false);
        $response->assertSee('>Second Log Card Detail</div>', false);
        $response->assertSee('data-certificate-item-part', false);
        $response->assertSee('>LOG-PN-2</div>', false);
        $response->assertSee('data-certificate-item-serial', false);
        $response->assertSee('>LOG-SN-2</div>', false);
        $response->assertSee('CSN-30228; CSO-3162.');
        $this->assertMatchesRegularExpression(
            '/<span[^>]*data-certificate-life-remark[^>]*>\s*CSN-30228; CSO-3162\.\s*<\/span>/s',
            $response->getContent()
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<span[^>]*data-certificate-life-remark[^>]*>\s*CSN-11111; CSO-22\.\s*<\/span>/s',
            $response->getContent()
        );
        $response->assertSee('Main Detail');
    }

    public function test_certificate_log_card_detail_does_not_use_whole_gear_life_when_row_is_blank(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder([
            'number' => 107738,
            'serial_number' => 'MAIN-SN',
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => '1840-0006',
            'name' => 'BOLT',
            'ipl_num' => '7-44',
            'eff_code' => null,
        ]);
        LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data_out' => [
                [
                    'component_id' => $component->id,
                    'serial_number' => 'L895',
                    'qa_aircraft_records' => [
                        [
                            'fit_csn' => '30,227',
                            'fit_cso' => '15',
                        ],
                    ],
                ],
            ],
        ]);
        $workorder->forceFill([
            'certificate_data' => [
                'certificate_item_source' => 'log:0',
            ],
        ])->save();

        $response = $this->actingAs($manager)
            ->get(route('quality.forms.certificate', $workorder));

        $response->assertOk();
        $response->assertSee('>BOLT</div>', false);
        $response->assertSee('>1840-0006</div>', false);
        $response->assertSee('>L895</div>', false);
        $this->assertDoesNotMatchRegularExpression(
            '/<span[^>]*data-certificate-life-remark[^>]*>\s*CSN-30,227; CSO-15\.\s*<\/span>/s',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/<div[^>]*hidden[^>]*data-certificate-life-remark-row/s',
            $response->getContent()
        );
        $this->assertMatchesRegularExpression(
            '/<div[^>]*hidden[^>]*data-certificate-c-correction-row/s',
            $response->getContent()
        );
    }

    public function test_certificate_tracking_c_suffix_applies_to_main_detail_only(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $unit = $this->createUnit([
            'name' => 'Main Detail',
        ]);
        $workorder = $this->createWorkorder([
            'number' => 107736,
            'unit_id' => $unit->id,
            'description' => 'Old Workorder Description',
        ]);

        $workorder->forceFill([
            'certificate_data' => [
                'certificate_tracking_mode' => 'c',
            ],
        ])->save();

        $response = $this->actingAs($manager)
            ->get(route('quality.forms.certificate', $workorder));

        $response->assertOk();
        $response->assertSee('data-certificate-tracking-c-toggle', false);
        $response->assertSee('<div class="arc-tracking-no" data-certificate-tracking-number>W107736-C</div>', false);
        $response->assertSee('>Main Detail</div>', false);
        $response->assertSee('This certificate issued to correct CSN info in Block 12 on original certificate W107736');
        $this->assertMatchesRegularExpression(
            '/<div(?=[^>]*data-certificate-c-correction-row)(?![^>]*hidden)/s',
            $response->getContent()
        );
        $response->assertDontSee('>Old Workorder Description</div>', false);
    }

    public function test_certificate_c_mode_uses_editable_item_overrides(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $repairInstruction = $this->createInstruction(['name' => 'Repair']);
        $unit = $this->createUnit([
            'name' => 'Main Detail',
            'part_number' => 'MAIN-PN',
        ]);
        $workorder = $this->createWorkorder([
            'number' => 107737,
            'unit_id' => $unit->id,
            'serial_number' => 'MAIN-SN',
            'customer_po' => 'PO-BASE',
        ]);

        $workorder->forceFill([
            'certificate_data' => [
                'certificate_tracking_mode' => 'c',
                'item_settings' => [
                    'main:c' => [
                        'certificate_work_order' => 'C-WO-77',
                        'certificate_item_description' => 'C Detail',
                        'certificate_item_part' => "C-PN\nALT-PN",
                        'certificate_item_serial' => 'C-SN',
                        'certificate_status_instruction_id' => (string) $repairInstruction->id,
                        'certificate_status_work' => 'Repaired',
                        'certificate_remarks' => [
                            'C status remark.',
                            'C workorder remark.',
                            'C replacement remark.',
                            'C AD remark.',
                            'C log card remark.',
                            'CSN-777; CSO-88.',
                            'C overhauled on remark.',
                            'C service remark.',
                            'C correction paragraph.',
                            'C extra remark.',
                        ],
                    ],
                ],
            ],
        ])->save();

        $response = $this->actingAs($manager)
            ->get(route('quality.forms.certificate', $workorder));

        $response->assertOk();
        $response->assertSee('<div class="arc-tracking-no" data-certificate-tracking-number>W107737-C</div>', false);
        $response->assertSee('data-certificate-work-order', false);
        $response->assertSee('contenteditable="true"', false);
        $response->assertSee('>C-WO-77</div>', false);
        $response->assertSee('>C Detail</div>', false);
        $response->assertSee('C-PN', false);
        $response->assertSee('ALT-PN', false);
        $response->assertSee('>C-SN</div>', false);
        $response->assertSee('<div class="arc-status-work-print-value" data-certificate-status-output>Repaired</div>', false);
        $response->assertSee('C status remark.');
        $response->assertSee('CSN-777; CSO-88.');
        $response->assertSee('C overhauled on remark.');
        $response->assertSee('C correction paragraph.');
        $response->assertSee('C extra remark.');
        $response->assertDontSee('>PO-BASE</div>', false);
        $response->assertDontSee('>Main Detail</div>', false);
        $response->assertDontSee('>MAIN-SN</div>', false);
    }

    public function test_certificate_status_work_updates_workorder_instruction_and_prints_past_tense(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $overhaulInstruction = $this->createOverhaulInstruction();
        $repairInstruction = $this->createInstruction(['name' => 'Repair']);
        $testInspectInstruction = $this->createInstruction(['name' => 'Test & inspect']);
        $this->createInstruction(['name' => 'Draft']);
        $this->createInstruction(['name' => 'Custom']);
        $manual = $this->createManual(['number' => '32-21-06']);
        $unit = $this->createUnit(['manual_id' => $manual->id]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'instruction_id' => $repairInstruction->id,
        ]);

        $updateResponse = $this->actingAs($manager)
            ->patchJson(route('quality.workorder.top_fields.update', $workorder), [
                'field' => 'instruction_id',
                'value' => (string) $testInspectInstruction->id,
            ]);

        $updateResponse->assertOk();
        $updateResponse->assertJson(['ok' => true]);
        $this->assertSame($testInspectInstruction->id, (int) $workorder->fresh()->instruction_id);

        $response = $this->actingAs($manager)
            ->get(route('quality.forms.certificate', $workorder));

        $response->assertOk();
        $response->assertSee('data-certificate-status-select', false);
        $response->assertSeeInOrder([
            'value="' . $testInspectInstruction->id . '"',
            'Tested/Inspected',
            'value="' . $repairInstruction->id . '"',
            'Repaired',
            'value="' . $overhaulInstruction->id . '"',
            'Overhauled',
        ], false);
        $response->assertSee('data-status-display="Repaired"', false);
        $response->assertSee('data-status-display="Tested/Inspected"', false);
        $response->assertSee('data-status-display="Overhauled"', false);
        $response->assertDontSee('Draft');
        $response->assertDontSee('Custom');
        $response->assertSee('<div class="arc-status-work-print-value" data-certificate-status-output>Tested/Inspected</div>', false);
        $response->assertSee('Tested/Inspected in accordance with CMM # 32-21-06', false);
    }

    public function test_certificate_manager_name_switch_is_limited_to_certificate_signers(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'name' => 'Current Manager',
            'qa_access' => true,
        ]);
        $selectedManager = $this->createUserWithRole('Manager', [
            'name' => 'Selected Certificate Manager',
            'qa_access' => true,
            'can_sign_certificates' => true,
        ]);
        $ordinaryManager = $this->createUserWithRole('Manager', [
            'name' => 'Ordinary Manager',
            'qa_access' => true,
        ]);
        $admin = $this->createUserWithRole('Admin', [
            'name' => 'System Admin',
        ]);
        $workorder = $this->createWorkorder();
        $workorder->forceFill(['approve_name' => 'Approved Manager'])->save();
        $workorder->forceFill([
            'certificate_data' => [
                'item_settings' => [
                    'main' => [
                        'include_landing_gear_log_card' => false,
                        'include_royco_service' => true,
                    ],
                ],
                'certificate_manager_id' => (string) $selectedManager->id,
                'certificate_date' => '2025-11-03',
            ],
        ])->save();

        $managerResponse = $this->actingAs($manager)
            ->get(route('quality.forms.certificate', $workorder));

        $managerResponse->assertOk();
        $managerResponse->assertSee('Selected Certificate Manager');
        $managerResponse->assertDontSee('Ordinary Manager');
        $managerResponse->assertSee('03/Nov/2025');
        $managerResponse->assertSee('data-certificate-manager-select', false);
        $managerResponse->assertSee('data-certificate-date-input', false);
        $managerResponse->assertSee('data-certificate-date-picker', false);
        $managerResponse->assertSee('value="03/Nov/2025"', false);
        $managerResponse->assertSee('value="2025-11-03"', false);
        $managerResponse->assertSee('Replacements parts: None.');
        $managerResponse->assertSee('Landing Gear Log Card attached');
        $managerResponse->assertSee('CSN-19453; CSO-0.');
        $managerResponse->assertSee('data-setting-key="include_landing_gear_log_card"', false);
        $managerResponse->assertSee('is-print-disabled');
        $managerResponse->assertSee('Serviced with ROYCO LGF (Yellow)');
        $managerResponse->assertSee('data-setting-key="include_royco_service"', false);
        $managerResponse->assertSee('arc-remark-print-toggle');
        $managerResponse->assertSee('certificate-state', false);
        $managerResponse->assertDontSee('user-ui-settings', false);
        $managerResponse->assertDontSee('arc-toolbar-form', false);
        $managerResponse->assertDontSee('Apply');

        $adminResponse = $this->actingAs($admin)
            ->get(route('quality.forms.certificate', [
                'workorder' => $workorder,
                'certificate_manager_id' => $selectedManager->id,
            ]));

        $adminResponse->assertOk();
        $adminResponse->assertSee('data-certificate-manager-select', false);
        $adminResponse->assertSee('Selected Certificate Manager');

        $this->actingAs($manager)
            ->patchJson(route('quality.forms.certificate.state.update', $workorder), [
                'key' => 'certificate_manager_id',
                'value' => $ordinaryManager->id,
            ])
            ->assertNotFound();
    }

    public function test_certificate_state_is_saved_on_workorder_certificate_data(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder();

        $response = $this->actingAs($manager)
            ->patchJson(route('quality.forms.certificate.state.update', $workorder), [
                'key' => 'certificate_detail_open',
                'value' => true,
            ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);

        $this->assertSame([
            'certificate_detail_open' => true,
        ], $workorder->fresh()->certificate_data);

        $this->actingAs($manager)
            ->patchJson(route('quality.forms.certificate.state.update', $workorder), [
                'key' => 'certificate_item_source',
                'value' => 'log:0',
            ])
            ->assertOk();

        $this->assertSame('', $workorder->fresh()->certificate_data['certificate_tracking_mode']);
        $this->assertSame('log:0', $workorder->fresh()->certificate_data['certificate_item_source']);

        $this->actingAs($manager)
            ->patchJson(route('quality.forms.certificate.state.update', $workorder), [
                'key' => 'include_royco_service',
                'value' => true,
                'item_source' => 'log:0',
            ])
            ->assertOk();

        $certificateData = $workorder->fresh()->certificate_data;
        $this->assertTrue($certificateData['item_settings']['log:0']['include_royco_service']);
        $this->assertArrayNotHasKey('include_royco_service', $certificateData);

        $signer = $this->createUserWithRole('Manager', [
            'can_sign_certificates' => true,
        ]);
        $this->actingAs($manager)
            ->patchJson(route('quality.forms.certificate.state.update', $workorder), [
                'key' => 'certificate_manager_id',
                'value' => $signer->id,
                'item_source' => 'main:c',
            ])
            ->assertOk();
        $this->actingAs($manager)
            ->patchJson(route('quality.forms.certificate.state.update', $workorder), [
                'key' => 'certificate_date',
                'value' => '2026-05-12',
                'item_source' => 'main:c',
            ])
            ->assertOk();

        $certificateData = $workorder->fresh()->certificate_data;
        $this->assertSame((string) $signer->id, $certificateData['item_settings']['main:c']['certificate_manager_id']);
        $this->assertSame('2026-05-12', $certificateData['item_settings']['main:c']['certificate_date']);
        $this->assertArrayNotHasKey('certificate_manager_id', $certificateData);

        $this->actingAs($manager)
            ->patchJson(route('quality.forms.certificate.state.update', $workorder), [
                'key' => 'certificate_item_part',
                'value' => 'C-PN-STATE',
                'item_source' => 'main:c',
            ])
            ->assertOk();
        $this->actingAs($manager)
            ->patchJson(route('quality.forms.certificate.state.update', $workorder), [
                'key' => 'certificate_remarks',
                'value' => ['C remark 1', 'C remark 2'],
                'item_source' => 'main:c',
            ])
            ->assertOk();
        $this->actingAs($manager)
            ->patchJson(route('quality.forms.certificate.state.update', $workorder), [
                'key' => 'include_overhauled_on',
                'value' => true,
                'item_source' => 'main:c',
            ])
            ->assertOk();

        $certificateData = $workorder->fresh()->certificate_data;
        $this->assertSame('C-PN-STATE', $certificateData['item_settings']['main:c']['certificate_item_part']);
        $this->assertSame(['C remark 1', 'C remark 2'], $certificateData['item_settings']['main:c']['certificate_remarks']);
        $this->assertTrue($certificateData['item_settings']['main:c']['include_overhauled_on']);
        $this->assertArrayNotHasKey('certificate_item_part', $certificateData);
    }

    public function test_serial_search_returns_workorder_links_from_tdr_and_log_card(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $tdrWorkorder = $this->createWorkorder(['number' => 988831]);
        $logCardWorkorder = $this->createWorkorder(['number' => 988832]);
        $component = Component::query()->create([
            'manual_id' => $tdrWorkorder->unit->manual_id,
            'part_number' => 'QA-SN-PN',
            'name' => 'QA SN Component',
            'ipl_num' => '7-77',
            'eff_code' => null,
        ]);

        Tdr::query()->create([
            'workorder_id' => $tdrWorkorder->id,
            'component_id' => $component->id,
            'serial_number' => 'SN-FIND-777',
            'qty' => 1,
        ]);
        LogCard::query()->create([
            'workorder_id' => $logCardWorkorder->id,
            'component_data' => json_encode([
                ['serial_number' => 'SN-FIND-777', 'part_number' => 'LC-PN'],
            ]),
        ]);

        $response = $this->actingAs($manager)->getJson(route('quality.serial_search', [
            'q' => 'SN-FIND-777',
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', true);

        $numbers = collect($response->json('results'))->pluck('workorder_number')->all();
        $this->assertContains('988831', $numbers);
        $this->assertContains('988832', $numbers);

        $tdrRow = collect($response->json('results'))->firstWhere('workorder_number', '988831');
        $this->assertSame(route('mains.show', $tdrWorkorder->id), $tdrRow['workorder_url']);
        $this->assertSame(route('tdrs.show', $tdrWorkorder->id), $tdrRow['tdr_url']);
    }

    public function test_serial_search_finds_full_log_card_serial_with_slash(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder(['number' => 988833]);

        LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                ['serial_number' => '1463290/006', 'part_number' => 'LC-SLASH-PN'],
            ]),
        ]);

        $response = $this->actingAs($manager)->getJson(route('quality.serial_search', [
            'q' => '1463290/006',
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', true);

        $this->assertTrue(collect($response->json('results'))->contains(
            fn (array $row) => $row['workorder_number'] === '988833'
                && $row['serial'] === '1463290/006'
        ));
    }

    public function test_serial_search_finds_log_card_json_serial_with_slash_by_decoded_value(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder(['number' => 988834]);
        $otherWorkorder = $this->createWorkorder(['number' => 988835]);

        LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                ['serial_number' => '100500/001', 'part_number' => 'LC-SLASH-PN'],
            ]),
        ]);
        LogCard::query()->create([
            'workorder_id' => $otherWorkorder->id,
            'component_data' => json_encode([
                ['serial_number' => '100500/002', 'part_number' => 'LC-SLASH-OTHER'],
            ]),
        ]);

        $response = $this->actingAs($manager)->getJson(route('quality.serial_search', [
            'q' => '100500/001',
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', true);

        $rows = collect($response->json('results'));
        $this->assertTrue($rows->contains(
            fn (array $row) => $row['workorder_number'] === '988834'
                && $row['serial'] === '100500/001'
        ));
        $this->assertFalse($rows->contains(
            fn (array $row) => $row['workorder_number'] === '988835'
                && $row['serial'] === '100500/002'
        ));
    }

    public function test_quality_incomplete_processes_is_failed_when_process_dates_are_missing(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder([
            'number' => 988802,
            'instruction_id' => $this->createOverhaulInstruction()->id,
        ]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'part_number' => 'QA-MISSING-DATES',
            'name' => 'QA Missing Dates Component',
            'ipl_num' => '1-1',
            'eff_code' => 'ALL',
        ]);
        $tdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'QA-MISSING-DATES-SN',
            'qty' => 1,
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'QA Missing Dates Process',
            'process_sheet_name' => 'QA',
            'form_number' => 'QA',
            'print_form' => false,
            'show_in_process_picker' => true,
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $tdr->id,
            'process_names_id' => $processName->id,
            'sort_order' => 1,
            'date_start' => null,
            'date_finish' => null,
        ]);
        $completedGeneralTask = GeneralTask::query()->create([
            'name' => 'QA Complete Stage',
            'sort_order' => 99,
        ]);
        $completedTask = Task::query()->create([
            'name' => 'Completed',
            'general_task_id' => $completedGeneralTask->id,
            'task_has_start_date' => false,
        ]);
        Main::query()->create([
            'workorder_id' => $workorder->id,
            'general_task_id' => $completedGeneralTask->id,
            'task_id' => $completedTask->id,
            'user_id' => $manager->id,
            'date_start' => null,
            'date_finish' => null,
        ]);
        $finalInspectionGeneralTask = GeneralTask::query()->create([
            'name' => 'Final inspection',
            'sort_order' => 98,
        ]);
        $submittedFinalTask = Task::query()->create([
            'name' => 'Submitted for Final Inspection',
            'general_task_id' => $finalInspectionGeneralTask->id,
            'task_has_start_date' => false,
        ]);
        $finalInspectionTask = Task::query()->create([
            'name' => 'Final inspection',
            'general_task_id' => $finalInspectionGeneralTask->id,
            'task_has_start_date' => false,
        ]);
        Main::query()->create([
            'workorder_id' => $workorder->id,
            'general_task_id' => $finalInspectionGeneralTask->id,
            'task_id' => $submittedFinalTask->id,
            'user_id' => $manager->id,
            'date_start' => null,
            'date_finish' => '2026-05-04',
        ]);
        Main::query()->create([
            'workorder_id' => $workorder->id,
            'general_task_id' => $finalInspectionGeneralTask->id,
            'task_id' => $finalInspectionTask->id,
            'user_id' => $manager->id,
            'date_start' => null,
            'date_finish' => '2026-05-05',
        ]);
        $ndtProcessName = ProcessName::query()->create([
            'name' => 'STD NDT List',
            'process_sheet_name' => 'NDT',
            'form_number' => 'NDT',
            'print_form' => false,
            'show_in_process_picker' => false,
        ]);
        $cadProcessName = ProcessName::query()->create([
            'name' => 'STD CAD List',
            'process_sheet_name' => 'CAD',
            'form_number' => 'CAD',
            'print_form' => false,
            'show_in_process_picker' => false,
        ]);
        WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'ndt',
            'process_name_id' => $ndtProcessName->id,
            'date_start' => '2026-05-01',
            'date_finish' => '2026-05-02',
        ]);
        WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'cad',
            'process_name_id' => $cadProcessName->id,
            'date_start' => '2026-05-03',
            'date_finish' => null,
        ]);

        $response = $this->actingAs($manager)->getJson(route('quality.workorder', [
            'q' => '988802',
        ]));

        $response->assertOk();

        $incompleteCheck = collect($response->json('workorder.checks'))
            ->firstWhere('label', 'Incomplete processes');
        $submittedCheck = collect($response->json('workorder.checks'))
            ->firstWhere('label', 'Submitted WO');
        $missingRoCheck = collect($response->json('workorder.checks'))
            ->firstWhere('label', 'Missing RO');
        $completedTaskCheck = collect($response->json('workorder.checks'))
            ->firstWhere('label', 'Completed task finished');
        $stdProcessesCheck = collect($response->json('workorder.checks'))
            ->firstWhere('label', 'STD processes complete');

        $this->assertNotNull($incompleteCheck);
        $this->assertNotNull($submittedCheck);
        $this->assertNotNull($missingRoCheck);
        $this->assertNotNull($completedTaskCheck);
        $this->assertNotNull($stdProcessesCheck);
        $this->assertFalse($incompleteCheck['ok']);
        $this->assertSame('qaSubmittedInspectionCards', $submittedCheck['target']);
        $this->assertFalse($missingRoCheck['ok']);
        $this->assertFalse($completedTaskCheck['ok']);
        $this->assertStringContainsString('tab=tasks', $completedTaskCheck['url']);
        $this->assertStringContainsString('general_task='.$completedGeneralTask->id, $completedTaskCheck['url']);
        $this->assertStringContainsString('task='.$completedTask->id, $completedTaskCheck['url']);
        $this->assertFalse($stdProcessesCheck['ok']);
        $this->assertSame('qaStdProcessBlock', $stdProcessesCheck['target']);
        $finalInspectionRow = collect($response->json('workorder.submitted'))
            ->firstWhere('missing_inspection', 'Final inspection');
        $this->assertNotNull($finalInspectionRow);
        $this->assertStringContainsString('tab=tasks', $finalInspectionRow['inspection_url']);
        $this->assertStringContainsString('general_task='.$finalInspectionGeneralTask->id, $finalInspectionRow['inspection_url']);
        $this->assertStringContainsString('task='.$finalInspectionTask->id, $finalInspectionRow['inspection_url']);
        $stdRows = collect($response->json('workorder.std_processes'));
        $this->assertSame('01/May/2026', $stdRows->firstWhere('type', 'ndt')['date_start']);
        $this->assertSame('02/May/2026', $stdRows->firstWhere('type', 'ndt')['date_finish']);
        $this->assertSame('03/May/2026', $stdRows->firstWhere('type', 'cad')['date_start']);
        $this->assertSame('-', $stdRows->firstWhere('type', 'cad')['date_finish']);
        $this->assertSame(1, collect($response->json('workorder.repair_orders'))->where('ok', false)->count());
    }

    public function test_quality_std_process_check_ignores_ignored_rows(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder([
            'number' => 988803,
            'instruction_id' => $this->createOverhaulInstruction()->id,
        ]);
        $ndtProcessName = ProcessName::query()->create([
            'name' => 'STD NDT Ignored Check',
            'process_sheet_name' => 'NDT',
            'form_number' => 'NDT',
            'print_form' => false,
            'show_in_process_picker' => false,
        ]);
        $cadProcessName = ProcessName::query()->create([
            'name' => 'STD CAD Ignored Check',
            'process_sheet_name' => 'CAD',
            'form_number' => 'CAD',
            'print_form' => false,
            'show_in_process_picker' => false,
        ]);

        WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'ndt',
            'process_name_id' => $ndtProcessName->id,
            'date_start' => '2026-05-01',
            'date_finish' => '2026-05-02',
        ]);
        WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'cad',
            'process_name_id' => $cadProcessName->id,
            'date_start' => '2026-05-03',
            'date_finish' => null,
            'ignore_row' => true,
        ]);

        $response = $this->actingAs($manager)->getJson(route('quality.workorder', [
            'q' => '988803',
        ]));

        $response->assertOk();

        $stdProcessesCheck = collect($response->json('workorder.checks'))
            ->firstWhere('label', 'STD processes complete');
        $stdRows = collect($response->json('workorder.std_processes'));

        $this->assertNotNull($stdProcessesCheck);
        $this->assertTrue($stdProcessesCheck['ok']);
        $this->assertFalse($stdRows->firstWhere('type', 'ndt')['ignored']);
        $this->assertTrue($stdRows->firstWhere('type', 'cad')['ignored']);
        $this->assertSame('CAD', $stdRows->firstWhere('type', 'cad')['short_label']);
    }

    public function test_quality_std_processes_are_complete_and_hidden_for_non_overhaul_instruction(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder([
            'number' => 988805,
            'instruction_id' => $this->createInstruction(['name' => 'Repair'])->id,
        ]);
        $processName = ProcessName::query()->create([
            'name' => 'STD CAD Non Overhaul Check',
            'process_sheet_name' => 'CAD',
            'form_number' => 'CAD',
            'print_form' => false,
            'show_in_process_picker' => false,
        ]);
        WorkorderStdProcess::query()->create([
            'workorder_id' => $workorder->id,
            'std_type' => 'cad',
            'process_name_id' => $processName->id,
            'date_start' => '2026-05-03',
            'date_finish' => null,
        ]);

        $response = $this->actingAs($manager)->getJson(route('quality.workorder', [
            'q' => '988805',
        ]));

        $response->assertOk();

        $stdProcessesCheck = collect($response->json('workorder.checks'))
            ->firstWhere('label', 'STD processes complete');

        $this->assertNotNull($stdProcessesCheck);
        $this->assertTrue($stdProcessesCheck['ok']);
        $this->assertSame([], $response->json('workorder.std_processes'));
    }

    public function test_quality_main_links_target_general_task_when_main_row_is_missing(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder(['number' => 988804]);

        $finalInspectionGeneralTask = GeneralTask::query()->create([
            'name' => 'Final Test',
            'sort_order' => 50,
        ]);
        $finalInspectionTask = Task::query()->create([
            'name' => 'Final inspection',
            'general_task_id' => $finalInspectionGeneralTask->id,
            'task_has_start_date' => false,
        ]);

        $completeGeneralTask = GeneralTask::query()->create([
            'name' => 'Complete',
            'sort_order' => 60,
        ]);
        $completedTask = Task::query()->create([
            'name' => 'Completed',
            'general_task_id' => $completeGeneralTask->id,
            'task_has_start_date' => false,
        ]);

        $response = $this->actingAs($manager)->getJson(route('quality.workorder', [
            'q' => '988804',
        ]));

        $response->assertOk();

        $finalInspectionRow = collect($response->json('workorder.submitted'))
            ->firstWhere('missing_inspection', 'Final inspection');
        $completedTaskCheck = collect($response->json('workorder.checks'))
            ->firstWhere('label', 'Completed task finished');

        $this->assertNotNull($finalInspectionRow);
        $this->assertStringContainsString('tab=tasks', $finalInspectionRow['inspection_url']);
        $this->assertStringContainsString('general_task='.$finalInspectionGeneralTask->id, $finalInspectionRow['inspection_url']);
        $this->assertStringContainsString('task='.$finalInspectionTask->id, $finalInspectionRow['inspection_url']);

        $this->assertNotNull($completedTaskCheck);
        $this->assertStringContainsString('tab=tasks', $completedTaskCheck['url']);
        $this->assertStringContainsString('general_task='.$completeGeneralTask->id, $completedTaskCheck['url']);
        $this->assertStringContainsString('task='.$completedTask->id, $completedTaskCheck['url']);
    }

    public function test_manager_can_update_quality_top_workorder_fields(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $unit = $this->createUnit([
            'part_number' => 'OLD-PN',
            'name' => 'Old Unit Name',
            'description' => 'Old Unit Description',
        ]);
        $newUnit = $this->createUnit([
            'manual_id' => $unit->manual_id,
            'part_number' => 'NEW-PN',
            'name' => 'New Unit Name',
            'description' => 'New Unit Description',
        ]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
            'description' => 'Original Workorder Description',
            'modified' => null,
            'serial_number' => 'OLD-SN',
        ]);

        $unitResponse = $this->actingAs($manager)->patchJson(route('quality.workorder.top_fields.update', $workorder), [
            'field' => 'unit_id',
            'value' => (string) $newUnit->id,
        ]);

        $unitResponse->assertOk();
        $unitResponse->assertJsonPath('top.unit', 'NEW-PN');
        $this->assertSame($newUnit->id, $workorder->fresh()->unit_id);
        $this->assertSame('OLD-PN', $unit->fresh()->part_number);

        $modifiedResponse = $this->actingAs($manager)->patchJson(route('quality.workorder.top_fields.update', $workorder), [
            'field' => 'modified',
            'value' => 'MOD-7',
        ]);

        $modifiedResponse->assertOk();
        $modifiedResponse->assertJsonPath('top.modified', 'MOD-7');

        $serialResponse = $this->actingAs($manager)->patchJson(route('quality.workorder.top_fields.update', $workorder), [
            'field' => 'serial',
            'value' => 'NEW-SN',
        ]);

        $serialResponse->assertOk();
        $serialResponse->assertJsonPath('top.serial', 'NEW-SN');

        $descriptionResponse = $this->actingAs($manager)->patchJson(route('quality.workorder.top_fields.update', $workorder), [
            'field' => 'description',
            'value' => 'Edited QA Description',
        ]);

        $descriptionResponse->assertOk();
        $descriptionResponse->assertJsonPath('top.description', 'Edited QA Description');

        $workorder->refresh();
        $unit->refresh();
        $newUnit->refresh();
        $this->assertSame('MOD-7', $workorder->modified);
        $this->assertSame('NEW-SN', $workorder->serial_number);
        $this->assertSame('Original Workorder Description', $workorder->description);
        $this->assertSame('Old Unit Name', $unit->name);
        $this->assertSame('Old Unit Description', $unit->description);
        $this->assertSame('Edited QA Description', $newUnit->name);
        $this->assertSame('New Unit Description', $newUnit->description);

        $component = Component::query()->create([
            'manual_id' => $newUnit->manual_id,
            'part_number' => 'QA-COMP-PN',
            'name' => 'Old Component Name',
            'ipl_num' => '9-99',
            'eff_code' => null,
        ]);

        $componentResponse = $this->actingAs($manager)->patchJson(route('quality.workorder.top_fields.update', $workorder), [
            'field' => 'component_name',
            'component_id' => $component->id,
            'value' => 'Edited Component Name',
        ]);

        $componentResponse->assertOk();
        $component->refresh();
        $workorder->refresh();
        $newUnit->refresh();
        $this->assertSame('Edited Component Name', $component->name);
        $this->assertSame('Original Workorder Description', $workorder->description);
        $this->assertSame('Edited QA Description', $newUnit->name);

        $otherManualComponent = Component::query()->create([
            'manual_id' => $this->createManual()->id,
            'part_number' => 'QA-OTHER-PN',
            'name' => 'Other Manual Component',
            'ipl_num' => '1-01',
            'eff_code' => null,
        ]);

        $this->actingAs($manager)->patchJson(route('quality.workorder.top_fields.update', $workorder), [
            'field' => 'component_name',
            'component_id' => $otherManualComponent->id,
            'value' => 'Should Not Save',
        ])->assertStatus(422);

        $this->assertSame('Other Manual Component', $otherManualComponent->fresh()->name);
    }

    public function test_technician_cannot_open_quality_dashboard(): void
    {
        $technician = $this->createUserWithRole('Technician');

        $response = $this->actingAs($technician)->getJson(route('quality.index'));

        $response->assertForbidden();
    }

    public function test_log_card_cell_background_color_is_saved_in_json(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder();
        LogCard::create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                ['name' => 'Bolt'],
            ]),
        ]);

        $response = $this->actingAs($manager)->postJson(route('quality.forms.log_card.update', $workorder), [
            'side' => 'left',
            'section' => 'primary',
            'row' => 0,
            'field' => 'description',
            'style' => 'background',
            'value' => '#d3f9d8',
        ]);

        $response->assertOk();
        $rows = json_decode(LogCard::where('workorder_id', $workorder->id)->first()->component_data, true);
        $this->assertSame('#d3f9d8', $rows[0]['qa_cell_colors']['description']);
    }

    public function test_right_log_card_header_part_number_is_saved_in_json(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder();
        LogCard::create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                ['name' => 'Bolt'],
            ]),
        ]);

        $response = $this->actingAs($manager)->postJson(route('quality.forms.log_card.update', $workorder), [
            'side' => 'right',
            'section' => 'header',
            'row' => 0,
            'field' => 'part_number',
            'value' => 'DCL1032/04',
        ]);

        $response->assertOk();
        $rows = LogCard::where('workorder_id', $workorder->id)->first()->component_data_out;
        $this->assertSame('DCL1032/04', $rows[0]['qa_header_part_number']);
    }

    public function test_manager_can_upload_quality_documents_to_workorder(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder();

        $response = $this->actingAs($manager)->post(route('quality.documents.store', $workorder), [
            'files' => [
                $this->makeUploadedFile('qa-certificate.pdf', '%PDF-1.4 test', 'application/pdf'),
            ],
        ]);

        $response->assertRedirect();
        $this->assertCount(1, $workorder->fresh()->getMedia('quality'));
        $this->assertSame($manager->id, $workorder->fresh()->getMedia('quality')->first()->getCustomProperty('uploaded_by'));
    }

    public function test_manager_can_delete_quality_document_from_workorder(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder();

        $media = $workorder
            ->addMedia($this->makeUploadedFile('qa-log.pdf', '%PDF-1.4 qa', 'application/pdf'))
            ->withCustomProperties(['uploaded_by' => $manager->id, 'uploaded_by_name' => $manager->name])
            ->toMediaCollection('quality');

        $response = $this->actingAs($manager)->delete(route('quality.documents.destroy', [$workorder, $media]));

        $response->assertRedirect();
        $this->assertCount(0, $workorder->fresh()->getMedia('quality'));
    }

    public function test_delete_rejects_quality_document_that_belongs_to_another_workorder(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder();
        $otherWorkorder = $this->createWorkorder([
            'number' => 100555,
        ]);

        $media = $otherWorkorder
            ->addMedia($this->makeUploadedFile('qa-other.pdf', '%PDF-1.4 other', 'application/pdf'))
            ->toMediaCollection('quality');

        $response = $this->actingAs($manager)->delete(route('quality.documents.destroy', [$workorder, $media]));

        $response->assertNotFound();
        $this->assertCount(1, $otherWorkorder->fresh()->getMedia('quality'));
    }

    public function test_destruction_certificate_lists_all_log_card_components(): void
    {
        $workorder = $this->createWorkorder();
        $manualId = $workorder->unit->manual_id;

        $certificateCode = Code::query()->create([
            'name' => 'Scrap',
            'code' => 'SCR',
            'requires_destruction_cert' => true,
        ]);
        $regularCode = Code::query()->create([
            'name' => 'Repair',
            'code' => 'REP',
            'requires_destruction_cert' => false,
        ]);

        $eligible = Component::query()->create([
            'manual_id' => $manualId,
            'name' => 'Eligible Part',
            'part_number' => 'PN-ELIGIBLE',
            'ipl_num' => '1-10',
            'log_card' => true,
        ]);
        $wrongCode = Component::query()->create([
            'manual_id' => $manualId,
            'name' => 'Wrong Code Part',
            'part_number' => 'PN-WRONG',
            'ipl_num' => '1-20',
            'log_card' => true,
        ]);
        $notLogCard = Component::query()->create([
            'manual_id' => $manualId,
            'name' => 'Not Log Card Part',
            'part_number' => 'PN-NOLOG',
            'ipl_num' => '1-30',
            'log_card' => false,
        ]);

        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $eligible->id,
            'codes_id' => $certificateCode->id,
            'serial_number' => 'SN-ELIGIBLE',
        ]);
        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $wrongCode->id,
            'codes_id' => $regularCode->id,
            'serial_number' => 'SN-WRONG',
        ]);
        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $notLogCard->id,
            'codes_id' => $certificateCode->id,
            'serial_number' => 'SN-NOLOG',
        ]);

        LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                ['component_id' => $eligible->id, 'serial_number' => 'SN-ELIGIBLE'],
                ['component_id' => $wrongCode->id, 'serial_number' => 'SN-WRONG'],
                ['component_id' => $notLogCard->id, 'serial_number' => 'SN-NOLOG'],
            ]),
        ]);

        $rows = LogCardDestructionCertificate::rowsForWorkorder($workorder);

        $this->assertCount(3, $rows);
        $this->assertSame('Eligible Part', $rows[0]['description']);
        $this->assertSame('PN-ELIGIBLE', $rows[0]['part_number']);
        $this->assertSame('SN-ELIGIBLE', $rows[0]['serial_number']);
        $this->assertSame('Wrong Code Part', $rows[1]['description']);
        $this->assertSame('Not Log Card Part', $rows[2]['description']);
        $this->assertFalse($rows[0]['selected']);
        $this->assertFalse($rows[1]['selected']);
        $this->assertFalse($rows[2]['selected']);
    }

    public function test_destruction_certificate_settings_are_saved_on_log_card(): void
    {
        $manager = $this->createUserWithRole('Manager');
        $workorder = $this->createWorkorder();
        $manualId = $workorder->unit->manual_id;
        $component = Component::query()->create([
            'manual_id' => $manualId,
            'name' => 'Saved Part',
            'part_number' => 'PN-SAVED',
            'ipl_num' => '2-10',
            'log_card' => true,
        ]);
        LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                ['component_id' => $component->id, 'part_number' => 'PN-SAVED', 'name' => 'Saved Part', 'serial_number' => 'SN-SAVED'],
            ]),
        ]);

        $key = LogCardDestructionCertificate::rowsForWorkorder($workorder)[0]['key'];

        $response = $this->actingAs($manager)->postJson(route('log_card.destruction_certificate.update', $workorder->id), [
            'selected_keys' => [$key],
            'certificate_date' => '05/May/2026',
            'manual_selected' => true,
            'manual_row' => [
                'part_number' => 'MANUAL-PN',
                'description' => 'Manual Part',
                'serial_number' => 'MANUAL-SN',
            ],
        ]);

        $response->assertOk();
        $logCard = LogCard::where('workorder_id', $workorder->id)->first();

        $this->assertSame([$key], $logCard->destruction_certificate_data['selected_keys']);
        $this->assertSame('05/May/2026', $logCard->destruction_certificate_data['certificate_date']);
        $this->assertTrue($logCard->destruction_certificate_data['manual_selected']);
        $this->assertSame('MANUAL-PN', $logCard->destruction_certificate_data['manual_row']['part_number']);

        $form = $this->actingAs($manager)->get(route('log_card.sertDistrForm', $workorder->id));
        $form->assertOk();
        $form->assertSee('MANUAL-PN', false);
        $form->assertSee('MANUAL-SN', false);
        $form->assertSee('05/May/2026', false);
    }
}
