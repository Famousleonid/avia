<?php

namespace Tests\Feature;

use App\Models\ProcessName;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class DirectoryProcessNamesTest extends TestCase
{
    use DatabaseTransactions;
    use BuildsDomainData;

    public function test_sp_order_can_be_saved_cleared_and_validated(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $process = ProcessName::create(['name' => 'SP order test', 'process_sheet_name' => 'TEST']);
        $url = route('directories.field.update', ['directory' => 'process_names', 'id' => $process->id, 'field' => 'sp_sort_order']);
        $this->actingAs($admin)->patchJson($url, ['sp_sort_order' => 1])->assertOk()->assertJsonPath('value', 1);
        $this->assertSame(1, $process->fresh()->sp_sort_order);
        foreach ([-1, 1.5, 100000] as $invalid) {
            $this->patchJson($url, ['sp_sort_order' => $invalid])->assertUnprocessable();
        }
        $this->patchJson($url, ['sp_sort_order' => null])->assertOk();
        $this->assertNull($process->fresh()->sp_sort_order);
    }

    public function test_sp_position_insertion_renumbers_duplicates_and_moves_both_directions(): void
    {
        $admin = $this->createUserWithRole('Admin');
        ProcessName::query()->update(['sp_sort_order' => null]);
        $rows = collect([1, 2, 2, 3, null])->map(fn ($order, $index) => ProcessName::create([
            'name' => 'Reorder '.$index, 'process_sheet_name' => 'TEST', 'sp_sort_order' => $order,
        ]));
        $url = route('directories.field.update', ['directory' => 'process_names', 'id' => $rows[3]->id, 'field' => 'sp_sort_order']);
        $response = $this->actingAs($admin)->patchJson($url, ['sp_sort_order' => 3])->assertOk();
        $this->assertSame([$rows[0]->id, $rows[1]->id, $rows[3]->id, $rows[2]->id], ProcessName::inSpFormOrder()->limit(4)->pluck('id')->all());
        $this->assertSame(4, $rows[2]->fresh()->sp_sort_order);
        $this->assertCount(ProcessName::count(), $response->json('updates'));
        $this->assertSame(range(1, ProcessName::count()), ProcessName::inSpFormOrder()->pluck('sp_sort_order')->all());

        $this->patchJson($url, ['sp_sort_order' => 1])->assertOk();
        $this->assertSame([$rows[3]->id, $rows[0]->id, $rows[1]->id, $rows[2]->id], ProcessName::inSpFormOrder()->limit(4)->pluck('id')->all());
        $this->patchJson($url, ['sp_sort_order' => 4])->assertOk();
        $this->assertSame([$rows[0]->id, $rows[1]->id, $rows[2]->id, $rows[3]->id], ProcessName::inSpFormOrder()->limit(4)->pluck('id')->all());
        $this->patchJson($url, ['sp_sort_order' => 99999])->assertOk()->assertJsonPath('value', ProcessName::count());
        $this->patchJson($url, ['sp_sort_order' => null])->assertOk();
        $this->assertSame(range(1, ProcessName::count() - 1), ProcessName::whereNotNull('sp_sort_order')->inSpFormOrder()->pluck('sp_sort_order')->all());
    }

    public function test_zero_excludes_process_from_preview_and_reordering_until_reenabled(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $excluded = ProcessName::create(['name' => 'Zero excluded process', 'process_sheet_name' => 'TEST', 'show_in_process_picker' => true]);
        $other = ProcessName::create(['name' => 'Active process', 'process_sheet_name' => 'TEST', 'show_in_process_picker' => true]);
        $url = fn ($row) => route('directories.field.update', ['directory' => 'process_names', 'id' => $row->id, 'field' => 'sp_sort_order']);
        $this->actingAs($admin)->patchJson($url($excluded), ['sp_sort_order' => 0])->assertOk()->assertJsonPath('value', 0);
        $this->patchJson($url($other), ['sp_sort_order' => 1])->assertOk();
        $this->assertSame(0, $excluded->fresh()->sp_sort_order);
        $active = ProcessName::includedInSpForm()->inSpFormOrder()->get();
        $this->assertSame(range(1, $active->count()), $active->pluck('sp_sort_order')->all());
        $this->get(route('process_names.sp-form-preview'))->assertOk()->assertDontSee('Zero excluded process');
        $this->patchJson($url($excluded), ['sp_sort_order' => 1])->assertOk();
        $this->assertSame(1, $excluded->fresh()->sp_sort_order);
        $this->assertSame(2, $other->fresh()->sp_sort_order);
        $this->get(route('process_names.sp-form-preview'))->assertOk()->assertSee('Zero excluded process');
    }

    public function test_sp_preview_is_blank_and_lists_all_picker_processes_in_saved_order(): void
    {
        $admin = $this->createUserWithRole('Admin');
        ProcessName::query()->update(['sp_sort_order' => null]);
        $later = ProcessName::create(['name' => 'Preview later', 'process_sheet_name' => 'TEST', 'show_in_process_picker' => true, 'sp_sort_order' => 20]);
        $first = ProcessName::create(['name' => 'Preview first', 'process_sheet_name' => 'NDT', 'show_in_process_picker' => true, 'sp_sort_order' => 10]);
        $tie = ProcessName::create(['name' => 'Preview tie', 'process_sheet_name' => 'TEST', 'show_in_process_picker' => true, 'sp_sort_order' => 20]);
        $response = $this->actingAs($admin)->get(route('process_names.sp-form-preview'))->assertOk();
        $this->assertSame([$first->id, $later->id, $tie->id], $response->viewData('processNames')->take(3)->pluck('id')->all());
        $this->assertCount(ProcessName::forPicker()->count(), $response->viewData('processNames')->filter(fn ($row) => $row->id));
        $this->assertFalse($response->viewData('current_wo')->exists);
        $this->assertTrue($response->viewData('componentChunks')->every(fn ($chunk) => $chunk->isEmpty()));
        $this->assertTrue($response->viewData('processNamePages')->every(fn ($page) => $page->count() <= 15));
        $response->assertSeeInOrder(['Preview first', 'Preview later', 'Preview tie']);
        $this->get(route('process_names.index'))->assertSee(route('process_names.sp-form-preview'), false)->assertSee('js-sp-sort-order', false);
    }

    public function test_sp_preview_and_order_updates_require_library_access(): void
    {
        $user = $this->createUserWithRole('Technician');
        $process = ProcessName::create(['name' => 'SP access test', 'process_sheet_name' => 'TEST']);
        $this->actingAs($user)->get(route('process_names.sp-form-preview'))->assertForbidden();
        $this->patchJson(route('directories.field.update', ['directory' => 'process_names', 'id' => $process->id, 'field' => 'sp_sort_order']), ['sp_sort_order' => 1])->assertForbidden();
    }

    public function test_process_names_index_defaults_to_sp_order_with_zero_at_end(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $suffix = uniqid();
        $firstName = '0000 Codex Sort A '.$suffix;
        $secondName = '0001 Codex Sort B '.$suffix;

        ProcessName::query()->create([
            'name' => $firstName,
            'sp_sort_order' => 2,
            'process_sheet_name' => 'TEST',
            'form_number' => 'TST',
        ]);
        ProcessName::query()->create([
            'name' => $secondName,
            'sp_sort_order' => 1,
            'process_sheet_name' => 'TEST',
            'form_number' => 'TST',
        ]);

        $response = $this->actingAs($admin)->get(route('process_names.index'));

        $response->assertOk();
        $response->assertSeeInOrder([$secondName, $firstName]);
        $items = $response->viewData('items')->getCollection();
        $positive = $items->filter(fn ($row) => $row->sp_sort_order > 0)->pluck('sp_sort_order')->all();
        $this->assertSame([1, 2], $positive);

        ProcessName::where('name', $secondName)->update(['sp_sort_order' => 0]);
        $this->get(route('process_names.index'))->assertOk()->assertSeeInOrder([$firstName, $secondName]);
    }

    public function test_process_name_code_can_be_updated_as_single_field(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $processName = ProcessName::query()->create([
            'name' => 'Codex Quick Code '.uniqid(),
            'code' => 'OLD'.random_int(10000, 99999),
            'process_sheet_name' => 'TEST',
            'form_number' => 'TST',
        ]);

        $newCode = 'NEW'.random_int(10000, 99999);

        $response = $this->actingAs($admin)->patchJson(route('directories.field.update', [
            'directory' => 'process_names',
            'id' => $processName->id,
            'field' => 'code',
        ]), [
            'code' => $newCode,
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('id', $processName->id)
            ->assertJsonPath('field', 'code')
            ->assertJsonPath('value', $newCode);

        $this->assertDatabaseHas('process_names', [
            'id' => $processName->id,
            'code' => $newCode,
        ]);
    }

    public function test_process_name_can_be_created_without_optional_fields(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $name = 'Codex Optional Fields '.uniqid();

        $response = $this->actingAs($admin)->post(route('process_names.store'), [
            'name' => $name,
            'process_sheet_name' => 'TEST',
        ]);

        $response
            ->assertRedirect(route('process_names.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('process_names', [
            'name' => $name,
            'process_sheet_name' => 'TEST',
            'form_number' => null,
            'code' => null,
            'std_days' => null,
            'notify_user_id' => null,
        ]);
    }
}
