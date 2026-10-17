<?php

namespace Tests\Feature;

use App\Models\Component;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Activitylog\Models\Activity;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ComponentKitActivityTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    public function test_kit_checkbox_records_actor_time_identity_and_both_values_without_noop_logs(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword()]);
        $manual = $this->createManual(['number' => 'KIT-AUDIT-32-21-06']);
        $part = Component::create(['manual_id' => $manual->id, 'ipl_num' => '3-150',
            'part_number' => 'KIT-AUDIT-PN', 'name' => 'Bushing', 'kit' => false]);
        $events = fn () => Activity::where('subject_type', Component::class)
            ->where('subject_id', $part->id)->where('event', 'updated');
        foreach ([true, false] as $value) {
            $this->travelTo(now()->startOfSecond()->addMinute());
            $this->patchJson(route('components.updateFlags', $part), ['field' => 'kit', 'value' => $value])
                ->assertOk()->assertJsonPath('value', $value);
            $event = $events()->latest('id')->firstOrFail();
            $this->assertSame($admin->id, (int) $event->causer_id);
            $this->assertTrue($event->created_at->equalTo(now()));
            $this->assertSame(!$value, $event->properties['old']['kit']);
            $this->assertSame($value, $event->properties['attributes']['kit']);
            $this->assertSame($manual->number, $event->properties['component_snapshot']['manual_number']);
            $this->assertSame('3-150', $event->properties['component_snapshot']['ipl_num']);
            $this->assertSame('KIT-AUDIT-PN', $event->properties['component_snapshot']['part_number']);
            $this->assertSame($admin->selection_name, $event->properties['causer_name']);
            $count = $events()->count();
            $this->patchJson(route('components.updateFlags', $part), ['field' => 'kit', 'value' => $value])->assertOk();
            $this->assertSame($count, $events()->count());
        }
        $this->assertSame(2, $events()->count());
        // The journal must display the historical identity, not today's renamed part.
        $part->update(['ipl_num' => '3-999', 'part_number' => 'RENAMED-PN']);
        $manual->update(['number' => 'RENAMED-MANUAL']);
        $response = $this->get(route('admin.activity.index', ['q' => 'KIT-AUDIT-32-21-06']))->assertOk();
        $response->assertSee('KIT-AUDIT-32-21-06')->assertSee('3-150')->assertSee('KIT-AUDIT-PN');
        $response->assertSee('KIT: <span class="props-value">Yes</span>', false)
            ->assertSee('KIT: <span class="props-value">No</span>', false)
            ->assertSee(format_project_date(now()))->assertSee($admin->selection_name);
    }

    public function test_full_component_edit_also_logs_kit_and_system_updates_do_not_invent_an_actor(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $part = Component::create(['manual_id' => $manual->id, 'ipl_num' => '8-360',
            'part_number' => 'EDIT-KIT-PN', 'name' => 'Bushing', 'kit' => false]);
        $this->actingAs($admin)->putJson(route('components.update', $part), [
            'manual_id' => $manual->id, 'ipl_num' => $part->ipl_num, 'part_number' => $part->part_number,
            'name' => $part->name, 'units_assy' => '2', 'kit' => true,
        ])->assertOk();
        $event = Activity::where('subject_type', Component::class)->where('subject_id', $part->id)
            ->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertSame(false, $event->properties['old']['kit']);
        $this->assertSame(true, $event->properties['attributes']['kit']);
        $this->assertSame($admin->id, (int) $event->causer_id);
        auth()->forgetGuards();
        $part->refresh()->update(['kit' => false]);
        $event = Activity::where('subject_type', Component::class)->where('subject_id', $part->id)
            ->latest('id')->firstOrFail();
        $this->assertNull($event->causer_id);
        $this->assertNull($event->properties['causer_name']);
        $this->assertSame(true, $event->properties['old']['kit']);
        $this->assertSame(false, $event->properties['attributes']['kit']);
    }
}
