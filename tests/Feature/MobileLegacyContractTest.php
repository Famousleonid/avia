<?php

namespace Tests\Feature;

use App\Models\Component;
use App\Models\GeneralTask;
use App\Models\LogCard;
use App\Models\Main;
use App\Models\Task;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\BuildsDomainData;
use Tests\TestCase;

class MobileLegacyContractTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public static function platforms(): array
    {
        return ['Android' => ['android'], 'Shared mobile API' => ['mobile']];
    }

    private function account(string $platform): array
    {
        Bus::fake();
        Http::fake();
        $user = $this->createUserWithRole('Technician');
        $wo = $this->createWorkorder(['user_id' => $user->id]);
        $token = $this->postJson(route("api.$platform.auth.login"), [
            'email' => $user->email, 'password' => 'password',
        ])->assertOk()->json('data.token');

        return [$wo, ['Authorization' => 'Bearer '.$token]];
    }

    /** @dataProvider platforms */
    public function test_legacy_single_instance_log_card_payload_remains_supported(string $platform): void
    {
        [$wo, $headers] = $this->account($platform);
        $part = Component::create([
            'manual_id' => $wo->unit->manual_id, 'name' => 'Legacy single part',
            'part_number' => 'QA-LEGACY-ONE', 'ipl_num' => '10-10', 'units_assy' => 1, 'log_card' => true,
        ]);
        $this->postJson(route("api.$platform.workorders.log-card.store", $wo->id), [
            'rows' => [['component_id' => $part->id, 'ipl_group' => '10-10', 'included' => true]],
        ], $headers)->assertOk();
        $card = LogCard::where('workorder_id', $wo->id)->firstOrFail();
        $rows = collect(json_decode($card->component_data, true))->where('component_id', $part->id);
        $this->assertCount(1, $rows);
        $this->assertSame('1', (string) $rows->first()['included']);
        Http::assertNothingSent();
    }

    /** @dataProvider platforms */
    public function test_legacy_multi_instance_payload_requires_client_update_without_creating_a_card(string $platform): void
    {
        [$wo, $headers] = $this->account($platform);
        $part = Component::create([
            'manual_id' => $wo->unit->manual_id, 'name' => 'Legacy repeated part',
            'part_number' => 'QA-LEGACY-TWO', 'ipl_num' => '20-20', 'units_assy' => 2, 'log_card' => true,
        ]);
        $template = $this->getJson(route("api.$platform.workorders.log-card.template", $wo->id), $headers)->assertOk();
        $instances = collect($template->json('data.separate'))->where('component_id', $part->id)->pluck('unit_index')->values()->all();
        $this->assertSame([1, 2], $instances);
        $row = ['component_id' => $part->id, 'ipl_group' => '20-20', 'included' => true];
        $this->postJson(route("api.$platform.workorders.log-card.store", $wo->id), ['rows' => [$row, $row]], $headers)
            ->assertUnprocessable();
        $this->assertFalse(LogCard::where('workorder_id', $wo->id)->exists());
        $this->assertSame(2, (int) $part->fresh()->units_assy);
        Http::assertNothingSent();
    }

    /** @dataProvider platforms */
    public function test_photo_endpoint_cannot_bypass_the_post_disassembly_lock(string $platform): void
    {
        [$wo, $headers] = $this->account($platform);
        $general = GeneralTask::create(['name' => 'QA legacy contract lock', 'sort_order' => 1]);
        $task = Task::create(['name' => 'Post Disassembly inspection', 'general_task_id' => $general->id, 'task_has_start_date' => 0]);
        Main::create(['workorder_id' => $wo->id, 'general_task_id' => $general->id,
            'task_id' => $task->id, 'date_finish' => '2026-11-02']);
        $this->postJson(route("api.$platform.workorders.log-card.photo.store", $wo->id), [
            'photo' => UploadedFile::fake()->image('landscape.jpg', 120, 80), 'recognize' => true,
        ], $headers)->assertStatus(423);
        $this->assertCount(0, $wo->fresh()->getMedia('logs'));
        Http::assertNothingSent();
    }
}
