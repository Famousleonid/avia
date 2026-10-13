<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ManualServiceBulletinOrderTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_reorder_is_scoped_and_text_save_does_not_overwrite_order(): void
    {
        $admin = $this->createUserWithRole('Admin');
        $manual = $this->createManual();
        $a = $manual->serviceBulletins()->create(['sort_order' => 5, 'description' => 'A']);
        $b = $manual->serviceBulletins()->create(['sort_order' => 9, 'description' => 'B']);
        $foreign = $this->createManual()->serviceBulletins()->create(['sort_order' => 1]);
        $this->actingAs($admin)->withSession(['auth.version' => (int) $admin->auth_version,
            'password_hash_web' => $admin->getAuthPassword()]);
        $url = route('manuals.service-bulletins.reorder', $manual);
        foreach ([[$a->id], [$a->id, $a->id], [$a->id, $foreign->id]] as $ids) {
            $this->patchJson($url, compact('ids'))->assertUnprocessable();
        }
        $this->assertSame(5, $a->fresh()->sort_order);
        $this->patchJson($url, ['ids' => [$b->id, $a->id]])->assertOk();
        $this->assertSame(1, $b->fresh()->sort_order);
        $this->assertSame(2, $a->fresh()->sort_order);
        $this->putJson(route('manuals.service-bulletins.update', [$manual, $a]),
            ['description' => 'Edited', 'sort_order' => 99, 'is_active' => true])->assertOk();
        $this->assertSame(2, $a->fresh()->sort_order);
        $this->assertSame('Edited', $a->fresh()->description);
        $this->assertSame('B', $b->fresh()->description);
        $this->delete(route('manuals.service-bulletins.destroy', [$manual, $b]))->assertRedirect();
        $this->assertSame(1, $a->fresh()->sort_order);
    }
}
