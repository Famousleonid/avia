<?php

namespace Tests\Feature;

use App\Models\MobileApiToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\BuildsDomainData;
use Tests\TestCase;

/**
 * Android contour of the mobile API (/api/android/*).
 * AndroidApiController inherits the iOS controller; these tests pin down the
 * platform-specific overrides AND that the iOS contour stays untouched.
 */
class AndroidApiTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    public function test_public_app_config_reports_android_platform(): void
    {
        $this->getJson(route('api.android.public.app-config'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.app.platform', 'android')
            ->assertJsonPath('data.app.android.min_sdk', 26)
            // shared branding still comes from the parent payload
            ->assertJsonPath('data.launch.initial_route', 'login');
    }

    public function test_login_issues_platform_tagged_token_and_bootstrap_works(): void
    {
        $user = $this->createUserWithRole('Technician');

        $res = $this->postJson(route('api.android.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('ok', true);

        $token = $res->json('data.token');
        $this->assertNotEmpty($token);

        $row = MobileApiToken::where('token_hash', MobileApiToken::hashPlainTextToken($token))->first();
        $this->assertNotNull($row);
        $this->assertSame('android', $row->platform);
        $this->assertSame('Android device', $row->name);

        // the shared token middleware accepts the android token on the android contour
        $this->getJson(route('api.android.bootstrap'), ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonStructure(['data' => ['navigation', 'screens', 'menu_mode']]);
    }

    public function test_push_token_is_stored_on_the_session_row(): void
    {
        $user = $this->createUserWithRole('Technician');

        $token = $this->postJson(route('api.android.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->json('data.token');

        $this->postJson(route('api.android.push-token.store'), [
            'fcm_token' => 'fcm-device-token-123',
        ], ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $row = MobileApiToken::where('token_hash', MobileApiToken::hashPlainTextToken($token))->first();
        $this->assertSame('fcm-device-token-123', $row->fcm_token);

        // logout deletes the session row — the push token dies with it
        $this->postJson(route('api.android.auth.logout'), [], ['Authorization' => 'Bearer ' . $token])
            ->assertOk();
        $this->assertNull(MobileApiToken::find($row->id));
    }

    public function test_log_card_read_fill_and_variant_switch(): void
    {
        $user = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder();
        $manualId = (int) $workorder->unit->manual_id;

        $main = \App\Models\Component::query()->create([
            'manual_id' => $manualId, 'name' => 'CYLINDER ASSY', 'part_number' => '47401-1',
            'ipl_num' => '1-190', 'log_card' => 1,
        ]);
        $variant = \App\Models\Component::query()->create([
            'manual_id' => $manualId, 'name' => 'CYLINDER ASSY', 'part_number' => '47401-5',
            'ipl_num' => '1-190A', 'log_card' => 1,
        ]);

        $logCard = \App\Models\LogCard::query()->create([
            'workorder_id' => $workorder->id,
            'component_data' => json_encode([
                ['row_type' => 'manual', 'manual_id' => (string) $manualId, 'manual_label' => 'Test manual'],
                [
                    'component_id' => (string) $main->id, 'included' => '1', 'serial_number' => '',
                    'assy_serial_number' => '', 'reason' => '', 'new_serial_number' => '',
                    'manual_id' => (string) $manualId, 'ipl_group' => '1-190',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $token = $this->postJson(route('api.android.auth.login'), [
            'email' => $user->email, 'password' => 'password',
        ])->json('data.token');
        $auth = ['Authorization' => 'Bearer ' . $token];

        // read: resolved component + both variants offered
        $res = $this->getJson(route('api.android.workorders.log-card.show', $workorder->id), $auth)
            ->assertOk()
            ->assertJsonPath('data.exists', true)
            ->assertJsonPath('data.rows.0.kind', 'manual')
            ->assertJsonPath('data.rows.1.component.part_number', '47401-1');
        $this->assertCount(2, $res->json('data.rows.1.variants'));

        // fill a serial
        $this->patchJson(route('api.android.log-card.rows.update', [$logCard->id, 1]), [
            'field' => 'serial_number', 'value' => 'CPS1278',
        ], $auth)->assertOk()->assertJsonPath('data.value', 'CPS1278');

        // switch the variant
        $this->patchJson(route('api.android.log-card.rows.variant.update', [$logCard->id, 1]), [
            'component_id' => $variant->id,
        ], $auth)->assertOk()->assertJsonPath('data.component_id', $variant->id);

        $rows = json_decode($logCard->fresh()->component_data, true);
        $this->assertSame('CPS1278', $rows[1]['serial_number']);
        $this->assertSame((string) $variant->id, (string) $rows[1]['component_id']);

        // a stranger component is rejected as a variant
        $other = \App\Models\Component::query()->create([
            'manual_id' => $manualId, 'name' => 'GLAND', 'part_number' => '47407-1',
            'ipl_num' => '1-240', 'log_card' => 1,
        ]);
        $this->patchJson(route('api.android.log-card.rows.variant.update', [$logCard->id, 1]), [
            'component_id' => $other->id,
        ], $auth)->assertStatus(422);
    }

    public function test_log_card_template_and_mobile_create(): void
    {
        $user = $this->createUserWithRole('Technician');
        $workorder = $this->createWorkorder();
        $manualId = (int) $workorder->unit->manual_id;

        $a = \App\Models\Component::query()->create([
            'manual_id' => $manualId, 'name' => 'CYLINDER ASSY', 'part_number' => '47401-1',
            'ipl_num' => '1-190', 'log_card' => 1,
        ]);
        \App\Models\Component::query()->create([
            'manual_id' => $manualId, 'name' => 'CYLINDER ASSY', 'part_number' => '47401-5',
            'ipl_num' => '1-190A', 'log_card' => 1,
        ]);
        $washer = \App\Models\Component::query()->create([
            'manual_id' => $manualId, 'name' => 'WASHER', 'part_number' => '47409-1',
            'ipl_num' => '1-100', 'log_card' => 1, 'units_assy' => 'UNITS002',
        ]);

        $token = $this->postJson(route('api.android.auth.login'), [
            'email' => $user->email, 'password' => 'password',
        ])->json('data.token');
        $auth = ['Authorization' => 'Bearer ' . $token];

        $tpl = $this->getJson(route('api.android.workorders.log-card.template', $workorder->id), $auth)
            ->assertOk()
            ->assertJsonPath('data.exists', false);
        $group = collect($tpl->json('data.groups'))->firstWhere('ipl_group', '1-190');
        $this->assertCount(2, $group['variants']);

        $this->postJson(route('api.android.workorders.log-card.store', $workorder->id), [
            'rows' => [
                ['component_id' => $a->id, 'ipl_group' => '1-190', 'included' => true],
                ['component_id' => $washer->id, 'ipl_group' => '1-100', 'included' => false],
            ],
        ], $auth)->assertOk()->assertJsonPath('ok', true);

        $card = \App\Models\LogCard::query()->where('workorder_id', $workorder->id)->firstOrFail();
        $rows = json_decode($card->component_data, true);
        $this->assertSame('manual', $rows[0]['row_type']);
        $this->assertSame((string) $a->id, (string) $rows[1]['component_id']);
        $this->assertSame('0', (string) $rows[2]['included']);

        // a second create is rejected (one card per WO)
        $this->postJson(route('api.android.workorders.log-card.store', $workorder->id), [
            'rows' => [['component_id' => $a->id]],
        ], $auth)->assertStatus(422);
    }

    public function test_android_login_rejects_bad_credentials(): void
    {
        $user = $this->createUserWithRole('Technician');

        $this->postJson(route('api.android.auth.login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonPath('ok', false);
    }

    public function test_ios_contour_is_unchanged(): void
    {
        $user = $this->createUserWithRole('Technician');

        $res = $this->postJson(route('api.mobile.auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('ok', true);

        $row = MobileApiToken::where('token_hash', MobileApiToken::hashPlainTextToken($res->json('data.token')))->first();
        $this->assertSame('iOS device', $row->name);
        $this->assertNull($row->platform); // iOS login does not set platform

        // iOS app-config has no android block
        $config = $this->getJson(route('api.mobile.public.app-config'))->assertOk()->json('data.app');
        $this->assertArrayNotHasKey('platform', $config);
        $this->assertArrayNotHasKey('android', $config);
    }
}
