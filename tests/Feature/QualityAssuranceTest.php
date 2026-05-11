<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Code;
use App\Models\Component;
use App\Models\LogCard;
use App\Models\Tdr;
use App\Models\Workorder;
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
                'repair_orders',
                'forms',
            ],
        ]);
        $this->assertSame('log_card', $response->json('workorder.forms.0.key'));
        $this->assertNotContains('spf', collect($response->json('workorder.forms'))->pluck('key')->all());
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
