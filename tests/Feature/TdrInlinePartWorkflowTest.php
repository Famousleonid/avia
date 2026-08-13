<?php

namespace Tests\Feature;

use App\Models\Code;
use App\Models\Component;
use App\Models\LogCard;
use App\Models\Necessary;
use App\Models\ProcessName;
use App\Models\Tdr;
use App\Models\TdrProcess;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\BuildsDomainData;
use Tests\TestCase;

class TdrInlinePartWorkflowTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_empty_inline_row_cannot_create_a_tdr(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->postJson(route('tdrs.store'), [
            'workorder_id' => $workorder->id,
            'inline_create' => 1,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('component_id');
        $this->assertDatabaseMissing('tdrs', ['workorder_id' => $workorder->id]);
    }

    public function test_edit_part_returns_to_the_tdr_table(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'ipl_num' => '1-101',
            'part_number' => 'EDIT-101',
            'name' => 'Original Part',
        ]);
        $tdrUrl = route('tdrs.show', ['id' => $workorder->id]);

        $this->actingAs($admin)
            ->post(route('components.updateFromInspection', $component), [
                'workorder_id' => $workorder->id,
                'manual_id' => $workorder->unit->manual_id,
                'redirect' => $tdrUrl,
                'ipl_num' => $component->ipl_num,
                'part_number' => $component->part_number,
                'name' => 'Updated Part',
            ])
            ->assertRedirect($tdrUrl);

        $this->assertDatabaseHas('components', [
            'id' => $component->id,
            'name' => 'Updated Part',
        ]);
    }

    public function test_part_photo_limit_is_fifteen_megabytes(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'ipl_num' => '1-104',
            'part_number' => 'PHOTO-LIMIT-104',
            'name' => 'Photo Limit Part',
        ]);
        $payload = [
            'workorder_id' => $workorder->id,
            'manual_id' => $workorder->unit->manual_id,
            'ipl_num' => $component->ipl_num,
            'part_number' => $component->part_number,
            'name' => $component->name,
        ];

        $this->actingAs($admin)
            ->post(route('components.updateFromInspection', $component), $payload + [
                'img' => UploadedFile::fake()->image('fourteen-mb.jpg')->size(14 * 1024),
            ])
            ->assertRedirect(route('tdrs.show', ['id' => $workorder->id]));

        $component->refresh()->unsetRelation('media');
        $this->assertSame('fourteen-mb.jpg', $component->primaryImageMedia()?->file_name);

        $this->actingAs($admin)
            ->from(route('tdrs.show', ['id' => $workorder->id]))
            ->post(route('components.updateFromInspection', $component), $payload + [
                'img' => UploadedFile::fake()->image('sixteen-mb.jpg')->size(16 * 1024),
            ])
            ->assertSessionHasErrors('img');
    }

    public function test_repair_uses_log_card_serial_but_does_not_require_one(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'ipl_num' => '1-102',
            'part_number' => 'REPAIR-102',
            'name' => 'Repair Part',
        ]);
        $code = Code::query()->firstOrCreate(['name' => 'Corroded'], ['code' => 'COR']);
        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);
        LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                ['component_id' => $component->id, 'serial_number' => 'LC-SN-102'],
            ]),
        ]);

        $show = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));
        $show->assertOk();
        $show->assertSee('"'.$component->id.'":"LC-SN-102"', false);

        $this->actingAs($admin)
            ->postJson(route('tdrs.store'), [
                'workorder_id' => $workorder->id,
                'component_id' => $component->id,
                'codes_id' => $code->id,
                'necessaries_id' => $repair->id,
                'assy_serial_number' => null,
                'description' => null,
                'order_component_id' => null,
                'qty' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('tdrs', [
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'necessaries_id' => $repair->id,
            'serial_number' => 'LC-SN-102',
        ]);

        $this->actingAs($admin)
            ->postJson(route('tdrs.store'), [
                'workorder_id' => $workorder->id,
                'component_id' => $component->id,
                'codes_id' => $code->id,
                'necessaries_id' => $repair->id,
                'serial_number' => 'EDITED-SN-102',
                'assy_serial_number' => null,
                'description' => null,
                'order_component_id' => null,
                'qty' => 1,
            ])
            ->assertOk();

        $this->assertDatabaseHas('tdrs', [
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'serial_number' => 'EDITED-SN-102',
        ]);

        $workorderWithoutLogCard = $this->createWorkorder(['user_id' => $admin->id]);
        $componentWithoutSerial = Component::query()->create([
            'manual_id' => $workorderWithoutLogCard->unit->manual_id,
            'ipl_num' => '1-103',
            'part_number' => 'REPAIR-103',
            'name' => 'Repair Part Without Serial',
        ]);

        $this->actingAs($admin)
            ->postJson(route('tdrs.store'), [
                'workorder_id' => $workorderWithoutLogCard->id,
                'component_id' => $componentWithoutSerial->id,
                'codes_id' => $code->id,
                'necessaries_id' => $repair->id,
                'assy_serial_number' => null,
                'description' => null,
                'order_component_id' => null,
                'qty' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('tdrs', [
            'workorder_id' => $workorderWithoutLogCard->id,
            'component_id' => $componentWithoutSerial->id,
            'necessaries_id' => $repair->id,
            'serial_number' => 'NSN',
        ]);
    }

    public function test_order_new_rejects_a_part_that_is_already_in_kit(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $inspectedComponent = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '1-100',
            'part_number' => 'INSPECTED-100',
            'name' => 'Inspected Part',
        ]);
        $kitComponent = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '1-110',
            'part_number' => 'KIT-110',
            'name' => 'Kit Part',
            'kit' => true,
        ]);
        $code = Code::query()->firstOrCreate(['name' => 'Damaged'], ['code' => 'DMG']);
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);

        $response = $this->actingAs($admin)->postJson(route('tdrs.store'), [
            'workorder_id' => $workorder->id,
            'inline_create' => 1,
            'component_id' => $inspectedComponent->id,
            'codes_id' => $code->id,
            'necessaries_id' => $orderNew->id,
            'order_component_id' => $kitComponent->id,
            'qty' => 1,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_component_id')
            ->assertJsonPath(
                'errors.order_component_id.0',
                'This part is already included in KIT and must not be duplicated in PRL.'
            );
        $this->assertDatabaseMissing('tdrs', [
            'workorder_id' => $workorder->id,
            'order_component_id' => $kitComponent->id,
        ]);
    }

    public function test_part_image_is_replaced_and_can_be_deleted_from_edit_part(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'ipl_num' => '2-200',
            'part_number' => 'PHOTO-200',
            'name' => 'Photo Part',
        ]);
        $component->addMedia(UploadedFile::fake()->image('legacy.jpg', 40, 40))->toMediaCollection('component');

        $this->actingAs($admin)
            ->post(route('components.updateFromInspection', $component), [
                'workorder_id' => $workorder->id,
                'manual_id' => $workorder->unit->manual_id,
                'ipl_num' => $component->ipl_num,
                'part_number' => $component->part_number,
                'name' => $component->name,
                'img' => UploadedFile::fake()->image('replacement.jpg', 40, 40),
            ])
            ->assertRedirect();

        $component->refresh()->unsetRelation('media');
        $this->assertCount(1, $component->getMedia('components'));
        $this->assertCount(0, $component->getMedia('component'));
        $this->assertSame('replacement.jpg', $component->primaryImageMedia()?->file_name);

        $media = $component->primaryImageMedia();
        $this->actingAs($admin)
            ->deleteJson(route('components.image.destroy', [$component, $media]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $component->refresh()->unsetRelation('media');
        $this->assertNull($component->primaryImageMedia());
    }

    public function test_tdr_and_ordered_parts_render_component_thumbnails(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $component = Component::query()->create([
            'manual_id' => $workorder->unit->manual_id,
            'ipl_num' => '3-300',
            'part_number' => 'PHOTO-300',
            'name' => 'Rendered Photo Part',
        ]);
        $component->replacePrimaryImage(UploadedFile::fake()->image('rendered.jpg', 40, 40));
        $repairCode = Code::query()->firstOrCreate(['name' => 'Repairable'], ['code' => 'R']);
        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);
        $orderNew = Necessary::query()->firstOrCreate(['name' => 'Order New']);

        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'codes_id' => $repairCode->id,
            'necessaries_id' => $repair->id,
            'use_tdr' => true,
            'use_process_forms' => true,
            'qty' => 1,
        ]);
        Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $component->id,
            'order_component_id' => $component->id,
            'codes_id' => $repairCode->id,
            'necessaries_id' => $orderNew->id,
            'use_tdr' => true,
            'use_process_forms' => false,
            'qty' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));

        $response->assertOk();
        $response->assertSee('tdr-component-images-'.$workorder->id, false);
        $response->assertSee('ordered-component-images-'.$workorder->id, false);
        $response->assertSee('tdr-component-thumb', false);
    }

    public function test_tdr_ec_marker_recognizes_ec_process_names_without_marking_plain_machining(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $workorder = $this->createWorkorder(['user_id' => $admin->id]);
        $manualId = $workorder->unit->manual_id;
        $repairCode = Code::query()->firstOrCreate(['name' => 'Repairable'], ['code' => 'R']);
        $repair = Necessary::query()->firstOrCreate(['name' => 'Repair']);

        $ecComponent = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '4-690A',
            'part_number' => '47101-103',
            'name' => 'Outer Cylinder',
        ]);
        $plainComponent = Component::query()->create([
            'manual_id' => $manualId,
            'ipl_num' => '4-500B',
            'part_number' => '47105-105',
            'name' => 'Inner Cylinder NP',
        ]);

        $ecTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $ecComponent->id,
            'codes_id' => $repairCode->id,
            'necessaries_id' => $repair->id,
            'use_tdr' => true,
            'use_process_forms' => true,
            'qty' => 1,
        ]);
        $plainTdr = Tdr::query()->create([
            'workorder_id' => $workorder->id,
            'component_id' => $plainComponent->id,
            'codes_id' => $repairCode->id,
            'necessaries_id' => $repair->id,
            'use_tdr' => true,
            'use_process_forms' => true,
            'qty' => 1,
        ]);

        $machiningEc = ProcessName::query()->firstOrCreate(
            ['name' => 'Machining (EC)'],
            ['process_sheet_name' => 'MACHINING', 'form_number' => '018']
        );
        $machining = ProcessName::query()->firstOrCreate(
            ['name' => 'Machining'],
            ['process_sheet_name' => 'MACHINING', 'form_number' => '018']
        );
        TdrProcess::query()->create([
            'tdrs_id' => $ecTdr->id,
            'process_names_id' => $machiningEc->id,
            'ec' => false,
        ]);
        TdrProcess::query()->create([
            'tdrs_id' => $plainTdr->id,
            'process_names_id' => $machining->id,
            'ec' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('tdrs.show', $workorder->id));

        $response->assertOk();
        $response->assertSee('data-tdr-id="'.$ecTdr->id.'" data-tdr-ec="1"', false);
        $response->assertSee('data-tdr-id="'.$plainTdr->id.'" data-tdr-ec="0"', false);
    }
}
