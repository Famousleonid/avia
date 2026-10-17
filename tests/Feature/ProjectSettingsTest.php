<?php

namespace Tests\Feature;

use App\Models\MarketingWoEstimateNotification;
use App\Models\PrintMark;
use App\Models\ProjectSetting;
use App\Models\User;
use App\Models\UserUiSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\BuildsDomainData;
use Tests\TestCase;

class ProjectSettingsTest extends TestCase
{
    use BuildsDomainData;
    use DatabaseTransactions;

    private function signIn(User $user): self
    {
        return $this->actingAs($user)->withSession([
            'auth.version' => (int) $user->auth_version,
            'password_hash_web' => $user->getAuthPassword(),
        ]);
    }

    public function test_system_admin_can_open_and_update_project_settings(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $customer = $this->createCustomer(['name' => 'Settings Reminder Customer']);
        $workorder = $this->createWorkorder([
            'customer_id' => $customer->id,
            'number' => 108001,
            'wo_estimate_date' => '2026-07-01',
        ]);
        $notification = MarketingWoEstimateNotification::query()->create([
            'workorder_id' => $workorder->id,
            'customer_id' => $customer->id,
            'estimate_date' => '2026-07-01',
            'triggered_at' => '2026-07-01 08:00:00',
            'due_at' => '2026-07-02 08:00:00',
        ]);

        $this->signIn($admin)
            ->get(route('admin.project-settings.index'))
            ->assertOk()
            ->assertSee('System Settings')
            ->assertSee('Printed Forms')
            ->assertSee('Marketing')
            ->assertSee('Fon for user')
            ->assertSee('QR code mark')
            ->assertDontSee('WO Estimate Date email recipients');

        $this->signIn($admin)
            ->get(route('admin.project-settings.index', ['section' => 'marketing']))
            ->assertOk()
            ->assertSee('WO Estimate Date email recipients');

        $this->signIn($admin)
            ->post(route('admin.project-settings.update'), [
                'print_forms_qr_enabled' => '0',
                'marketing_wo_estimate_email_recipients' => "sales@example.test\nManager@Example.Test",
                'marketing_wo_estimate_email_delay_days' => '3',
            ])
            ->assertRedirect(route('admin.project-settings.index'));

        $this->assertFalse(ProjectSetting::boolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true));
        $this->assertSame(['sales@example.test', 'manager@example.test'], ProjectSetting::marketingWoEstimateEmailRecipients());
        $this->assertSame(3, ProjectSetting::marketingWoEstimateEmailDelayDays());
        $this->assertDatabaseHas('marketing_wo_estimate_notifications', [
            'id' => $notification->id,
            'due_at' => '2026-07-04 00:00:00',
        ]);
    }

    public function test_admin_role_without_is_admin_cannot_open_project_settings(): void
    {
        $roleOnlyAdmin = $this->createUserWithRole('Admin', ['is_admin' => false]);

        $this->signIn($roleOnlyAdmin)
            ->get(route('admin.project-settings.index'))
            ->assertForbidden();
    }

    public function test_system_admin_can_upload_apply_and_remove_background_for_one_user(): void
    {
        Storage::fake('public');

        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $target = $this->createUserWithRole('Admin', [
            'is_admin' => true,
            'name' => 'Background Target',
        ]);
        $other = $this->createUserWithRole('Admin', [
            'is_admin' => true,
            'name' => 'Background Other',
        ]);

        $upload = $this->signIn($admin)->post(
            route('admin.project-settings.user-background.store'),
            [
                'user_id' => $target->id,
                'background_image' => UploadedFile::fake()->image('hangar-background.jpg', 1600, 900),
            ]
        );

        $upload->assertRedirect(route('admin.project-settings.index', [
            'section' => 'user-background',
            'user_id' => $target->id,
        ]));

        $setting = UserUiSetting::query()
            ->where('user_id', $target->id)
            ->where('scope', UserUiSetting::PROJECT_APPEARANCE_SCOPE)
            ->where('key', UserUiSetting::PROJECT_BACKGROUND_KEY)
            ->firstOrFail();
        $media = $target->fresh()->getFirstMedia(User::PROJECT_BACKGROUND_COLLECTION);

        $this->assertNotNull($media);
        $this->assertSame($media->id, data_get($setting->value, 'media_id'));
        $this->assertSame('hangar-background.jpg', data_get($setting->value, 'original_name'));
        Storage::disk($media->disk)->assertExists($media->getPathRelativeToRoot());

        $backgroundUrl = route('admin.project-settings.user-background.show', ['user' => $target->id]);

        $this->signIn($target)
            ->get(route('admin.project-settings.index'))
            ->assertOk()
            ->assertSee('has-user-project-background', false)
            ->assertSee($backgroundUrl, false);

        $this->signIn($target)
            ->get($backgroundUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $this->signIn($other)
            ->get(route('admin.project-settings.index'))
            ->assertOk()
            ->assertDontSee('has-user-project-background', false)
            ->assertDontSee($backgroundUrl, false);

        $this->signIn($admin)
            ->delete(route('admin.project-settings.user-background.destroy', ['user' => $target->id]))
            ->assertRedirect(route('admin.project-settings.index', [
                'section' => 'user-background',
                'user_id' => $target->id,
            ]));

        Storage::disk($media->disk)->assertMissing($media->getPathRelativeToRoot());
        $this->assertDatabaseMissing('user_ui_settings', [
            'user_id' => $target->id,
            'scope' => UserUiSetting::PROJECT_APPEARANCE_SCOPE,
            'key' => UserUiSetting::PROJECT_BACKGROUND_KEY,
        ]);
    }

    public function test_existing_legacy_background_is_served_without_public_storage_link(): void
    {
        Storage::fake('public');

        $user = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $path = "user-backgrounds/{$user->id}/legacy-background.jpg";
        Storage::disk('public')->put($path, 'legacy image bytes');
        UserUiSetting::query()->create([
            'user_id' => $user->id,
            'scope' => UserUiSetting::PROJECT_APPEARANCE_SCOPE,
            'key' => UserUiSetting::PROJECT_BACKGROUND_KEY,
            'value' => ['path' => $path, 'original_name' => 'legacy-background.jpg'],
        ]);

        $url = route('admin.project-settings.user-background.show', ['user' => $user->id]);

        $this->signIn($user)
            ->get(route('admin.project-settings.index'))
            ->assertOk()
            ->assertSee($url, false);

        $response = $this->signIn($user)->get($url);

        $response->assertOk();
        $this->assertSame(
            realpath(Storage::disk('public')->path($path)),
            realpath($response->baseResponse->getFile()->getPathname())
        );
    }

    public function test_backgrounds_rotate_daily_preserve_existing_photos_and_keep_the_plane(): void
    {
        Storage::fake('public');
        $this->travelTo(Carbon::parse('2026-10-14 12:00:00'));
        $user = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $service = app(\App\Services\UserProjectBackground::class);
        $this->signIn($user)->get(route('cabinet.index'))->assertOk()->assertSee('img/avia190.png', false);
        $this->post(route('admin.project-settings.user-background.store'), [
            'user_id' => $user->id, 'background_image' => UploadedFile::fake()->image('existing.jpg'),
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.project-settings.user-background.store'), [
            'user_id' => $user->id, 'background_images' => [UploadedFile::fake()->image('second.jpg'), UploadedFile::fake()->image('third.png')],
        ])->assertSessionHasNoErrors();
        $images = $service->images($user->fresh());
        $this->assertCount(3, $images);
        foreach ([14 => 'existing.jpg', 15 => 'second.jpg', 16 => 'third.png', 17 => 'existing.jpg'] as $day => $name) {
            $this->travelTo(Carbon::parse("2026-10-$day 12:00:00"));
            $current = $service->current($user->fresh());
            $this->assertSame($name, $current['name']);
            $response = $this->get(route('admin.project-settings.user-background.show', $user))->assertOk();
            $this->assertSame(realpath($current['path']), realpath($response->baseResponse->getFile()->getPathname()));
            $this->get(route('cabinet.index'))->assertOk()->assertSee('img/avia190.png', false)->assertSee('has-user-project-background', false);
        }
        $this->get(route('admin.project-settings.index', ['section' => 'user-background', 'user_id' => $user->id]))
            ->assertOk()->assertSee('Daily rotation')->assertSee('existing.jpg')->assertSee('second.jpg')->assertSee('third.png');
        $this->delete(route('admin.project-settings.user-background.destroy', $user), ['image' => $images[1]['id']])->assertSessionHasNoErrors();
        $this->assertCount(2, $service->images($user->fresh()));
        $this->assertFileDoesNotExist($images[1]['path']);
        $this->assertFileExists($images[0]['path']);
        $this->delete(route('admin.project-settings.user-background.destroy', $user))->assertSessionHasNoErrors();
        $this->get(route('cabinet.index'))->assertOk()->assertSee('img/avia190.png', false);
    }

    public function test_background_gallery_is_private_and_rejects_invalid_uploads_atomically(): void
    {
        Storage::fake('public');
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $owner = $this->createUserWithRole('Technician');
        $other = $this->createUserWithRole('Technician');
        $this->signIn($admin)->post(route('admin.project-settings.user-background.store'), [
            'user_id' => $owner->id, 'background_images' => [UploadedFile::fake()->image('valid.jpg'), UploadedFile::fake()->create('invalid.txt')],
        ])->assertSessionHasErrors('background_images.1');
        $this->assertCount(0, app(\App\Services\UserProjectBackground::class)->images($owner->fresh()));
        $this->post(route('admin.project-settings.user-background.store'), [
            'user_id' => $owner->id, 'background_images' => [UploadedFile::fake()->image('private.jpg')],
        ])->assertSessionHasNoErrors();
        $media = $owner->fresh()->getFirstMedia(User::PROJECT_BACKGROUND_COLLECTION);
        $url = route('admin.project-settings.user-background.show', ['user' => $owner->id, 'image' => $media->id]);
        $this->signIn($owner)->get($url)->assertOk();
        $this->signIn($other)->get($url)->assertForbidden();
        $this->signIn($admin)->get(route('admin.project-settings.user-background.show', ['user' => $admin->id, 'image' => $media->id]))->assertNotFound();
        $this->signIn($other)->delete(route('admin.project-settings.user-background.destroy', $owner))->assertForbidden();
    }

    public function test_legacy_photo_stays_in_rotation_until_explicitly_removed(): void
    {
        Storage::fake('public');
        $this->travelTo(Carbon::parse('2026-10-14 23:59:00'));
        $user = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $path = "user-backgrounds/{$user->id}/old.jpg";
        Storage::disk('public')->put($path, 'old image bytes');
        UserUiSetting::create(['user_id' => $user->id, 'scope' => UserUiSetting::PROJECT_APPEARANCE_SCOPE,
            'key' => UserUiSetting::PROJECT_BACKGROUND_KEY, 'value' => ['path' => $path, 'original_name' => 'old.jpg']]);
        $this->signIn($user)->post(route('admin.project-settings.user-background.store'), [
            'user_id' => $user->id, 'background_images' => [UploadedFile::fake()->image('new.jpg')],
        ])->assertSessionHasNoErrors();
        $service = app(\App\Services\UserProjectBackground::class);
        $this->assertCount(2, $service->images($user));
        $this->assertSame('old.jpg', $service->current($user)['name']);
        $this->travelTo(Carbon::parse('2026-10-15 00:00:00'));
        $this->assertSame('new.jpg', $service->current($user)['name']);
        $this->delete(route('admin.project-settings.user-background.destroy', $user), ['image' => 'legacy'])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($path);
        $this->assertCount(1, $service->images($user));
        $this->assertSame('new.jpg', $service->current($user)['name']);
    }

    public function test_qr_partial_does_not_create_print_mark_when_setting_is_disabled(): void
    {
        ProjectSetting::setBoolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, false);

        $html = view('shared.print-mark.qr', [
            'printMarkWorkorder' => '107736',
            'printMarkPrintedBy' => 'ADMIN',
            'printMarkPrintedAt' => Carbon::create(2026, 5, 26),
            'printMarkFormName' => 'NDT',
        ])->render();

        $this->assertStringNotContainsString('system-print-qr', $html);
        $this->assertSame(0, PrintMark::query()->count());
    }
}
