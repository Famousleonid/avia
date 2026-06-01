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

    public function test_process_names_index_defaults_to_name_sort(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $suffix = uniqid();
        $firstName = '0000 Codex Sort A '.$suffix;
        $secondName = '0001 Codex Sort B '.$suffix;

        ProcessName::query()->create([
            'name' => $firstName,
            'process_sheet_name' => 'TEST',
            'form_number' => 'TST',
        ]);
        ProcessName::query()->create([
            'name' => $secondName,
            'process_sheet_name' => 'TEST',
            'form_number' => 'TST',
        ]);

        $response = $this->actingAs($admin)->get(route('process_names.index'));

        $response->assertOk();
        $response->assertSeeInOrder([$firstName, $secondName]);
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
}
