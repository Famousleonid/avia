<?php

namespace Tests\Feature;

use App\Models\Component;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualPartFlagLockTest extends TestCase
{
    use DatabaseTransactions, BuildsDomainData;

    public function test_column_locks_block_everyone_and_are_independent(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $part = Component::create(['manual_id' => $manual->id, 'ipl_num' => '1-10',
            'part_number' => 'LOCK-PART', 'name' => 'Part', 'kit' => true]);
        $this->actingAs($admin);
        foreach (['kit', 'np', 'kit_e'] as $flag) {
            $this->patchJson(route('manuals.part-flag-locks.update', $manual), ['field' => $flag, 'locked' => true])->assertOk();
            $value = ! (bool) $part->fresh()->{$flag};
            $this->patchJson(route('components.updateFlags', $part), ['field' => $flag, 'value' => $value])->assertUnprocessable();
            $this->assertSame(! $value, (bool) $part->fresh()->{$flag});
        }
        $this->patchJson(route('components.updateFlags', $part), ['field' => 'ndt_list', 'value' => true])->assertOk();
        $this->get(route('manuals.show', $manual))->assertOk()->assertSee('manual-flag-lock-button')
            ->assertSee('KIT: Locked by '.$admin->selection_name)
            ->assertSee('Kit_E: Locked by '.$admin->selection_name);
        $this->patchJson(route('manuals.part-flag-locks.update', $manual), ['field' => 'kit', 'locked' => false])->assertOk();
        $this->patchJson(route('components.updateFlags', $part), ['field' => 'kit', 'value' => false])->assertOk();
        $this->patchJson(route('components.updateFlags', $part), ['field' => 'np', 'value' => true])->assertUnprocessable();
        // Full edit cannot bypass a locked flag, even as system administrator.
        $this->putJson(route('components.update', $part), ['manual_id' => $manual->id,
            'ipl_num' => '1-10', 'part_number' => 'LOCK-PART', 'name' => 'Part', 'np' => true])->assertUnprocessable();
    }

    public function test_only_explicit_managers_or_system_admin_can_change_locks(): void
    {
        $manual = $this->createManual();
        $manager = $this->createUserWithRole('Manager');
        $manager->permittedManuals()->attach($manual);
        $url = route('manuals.part-flag-locks.update', $manual);
        $this->actingAs($manager)->patchJson($url, ['field' => 'kit', 'locked' => true])->assertForbidden();
        $this->grantFeatureAccess($manager, 'manuals.flag_locks');
        $this->actingAs($manager->fresh())->patchJson($url, ['field' => 'kit', 'locked' => true])->assertOk();
        $part = Component::create(['manual_id' => $manual->id, 'ipl_num' => '1-20',
            'part_number' => 'NEW-PART', 'name' => 'New part']);
        $this->patchJson(route('components.updateFlags', $part), ['field' => 'kit', 'value' => true])->assertUnprocessable();
        $this->patchJson($url, ['field' => 'kit', 'locked' => false])->assertOk();
        $this->patchJson($url, ['field' => 'ndt_list', 'locked' => true])->assertUnprocessable();
        $other = $this->createManual();
        $this->patchJson(route('manuals.part-flag-locks.update', $other), ['field' => 'kit', 'locked' => true])->assertForbidden();
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => false]);
        $this->actingAs($roleOnlyAdmin)->withSession(['auth.version' => (int) $roleOnlyAdmin->auth_version,
            'password_hash_web' => $roleOnlyAdmin->getAuthPassword()])
            ->patchJson($url, ['field' => 'kit', 'locked' => true])->assertForbidden();
    }
}
