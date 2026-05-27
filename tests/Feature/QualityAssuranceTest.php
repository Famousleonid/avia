<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Code;
use App\Models\Component;
use App\Models\GeneralTask;
use App\Models\LogCard;
use App\Models\Main;
use App\Models\ProcessName;
use App\Models\Task;
use App\Models\Tdr;
use App\Models\TdrProcess;
use App\Models\Workorder;
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
        $this->assertSame('log_card', $response->json('workorder.forms.0.key'));
        $forms = collect($response->json('workorder.forms'));
        $this->assertContains('sp_form', $forms->pluck('key')->all());
        $this->assertSame(
            route('tdrs.specProcessFormEmp', ['id' => $workorder->id]),
            $forms->firstWhere('key', 'sp_form')['url']
        );
    }

    public function test_shipment_release_form_defaults_shipset_to_no(): void
    {
        $manager = $this->createUserWithRole('Manager', [
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
        $workorder = $this->createWorkorder(['number' => 988802]);
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
        $this->assertSame('01/may/2026', $stdRows->firstWhere('type', 'ndt')['date_start']);
        $this->assertSame('02/may/2026', $stdRows->firstWhere('type', 'ndt')['date_finish']);
        $this->assertSame('03/may/2026', $stdRows->firstWhere('type', 'cad')['date_start']);
        $this->assertSame('-', $stdRows->firstWhere('type', 'cad')['date_finish']);
        $this->assertSame(1, collect($response->json('workorder.repair_orders'))->where('ok', false)->count());
    }

    public function test_quality_std_process_check_ignores_ignored_rows(): void
    {
        $manager = $this->createUserWithRole('Manager', [
            'qa_access' => true,
        ]);
        $workorder = $this->createWorkorder(['number' => 988803]);
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
        ]);
        $newUnit = $this->createUnit([
            'manual_id' => $unit->manual_id,
            'part_number' => 'NEW-PN',
        ]);
        $workorder = $this->createWorkorder([
            'unit_id' => $unit->id,
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

        $workorder->refresh();
        $this->assertSame('MOD-7', $workorder->modified);
        $this->assertSame('NEW-SN', $workorder->serial_number);
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
