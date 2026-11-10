<?php

namespace Tests\Feature;

use App\Models\ProjectSetting;
use App\Models\User;
use App\Models\UserUiSetting;
use App\Services\BirthdayProjectBackground;
use App\Services\InternetProjectBackground;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\BuildsDomainData;
use Tests\TestCase;

class BirthdayBackgroundTest extends TestCase
{
    use BuildsDomainData, DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        ProjectSetting::where('key', BirthdayProjectBackground::KEY)->delete();
        $this->freezeClock('2026-11-10 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function freezeClock(string $date): void
    {
        $time = Carbon::parse($date, 'America/Toronto');
        Carbon::setTestNow($time); CarbonImmutable::setTestNow($time);
    }

    private function configure(string $mode): void
    {
        Storage::disk('private')->put('birthday-backgrounds/test.png', 'test image');
        ProjectSetting::updateOrCreate(['key' => BirthdayProjectBackground::KEY],
            ['value' => ['mode' => $mode, 'path' => 'birthday-backgrounds/test.png']]);
    }

    private function signIn(User $user): self
    {
        return $this->actingAs($user)->withSession(['auth.version' => (int) $user->auth_version,
            'password_hash_web' => $user->getAuthPassword()]);
    }

    public function test_both_enabled_modes_keep_personal_four_days_and_share_only_on_birthday(): void
    {
        $birthday = $this->createUserWithRole('Technician', ['birthday' => '1990-11-10', 'name' => 'Birthday Person']);
        $other = $this->createUserWithRole('Technician', ['birthday' => null]);
        $manager = $this->createUserWithRole('Manager');
        $service = app(BirthdayProjectBackground::class);
        foreach (['birthday_person', 'birthday_and_everyone', 'off'] as $mode) {
            $this->configure($mode);
            foreach ([8 => false, 9 => true, 10 => true, 11 => true, 12 => true, 13 => false] as $day => $active) {
                $this->freezeClock('2026-11-'.$day.' 12:00');
                $own = $service->state($birthday);
                $this->assertSame($mode !== 'off' && $active, $own['active'], "$mode day $day");
                foreach ([$other, $manager] as $viewer) {
                    $shared = $service->state($viewer);
                    $this->assertSame($mode === 'birthday_and_everyone' && $day === 10, $shared['active']);
                    if ($shared['active']) $this->assertStringContainsString('Birthday', $shared['caption']);
                }
            }
        }
    }

    public function test_year_boundaries_leap_day_and_toronto_midnight(): void
    {
        $user = $this->createUserWithRole('Technician', ['birthday' => '1990-01-01']);
        $this->configure('birthday_person');
        $service = app(BirthdayProjectBackground::class);
        foreach (['2026-12-30' => false, '2026-12-31' => true, '2027-01-01' => true,
            '2027-01-03' => true, '2027-01-04' => false] as $day => $expected) {
            $this->freezeClock($day.' 12:00');
            $this->assertSame($expected, $service->state($user)['active'], $day);
        }
        $user->birthday = '1992-02-29';
        foreach (['2027-02-26' => false, '2027-02-27' => true, '2027-03-02' => true,
            '2027-03-03' => false, '2028-02-27' => false, '2028-02-28' => true, '2028-03-02' => true] as $day => $expected) {
            $this->freezeClock($day.' 12:00');
            $this->assertSame($expected, $service->state($user)['active'], $day);
        }
        $user->birthday = '1990-11-10';
        $this->freezeClock('2026-11-13T04:59:59Z');
        $this->assertTrue($service->state($user)['active']);
        $this->assertSame('2026-11-13T00:00:00-05:00', $service->state($user)['expires_at']);
        $this->freezeClock('2026-11-13T05:00:00Z');
        $this->assertFalse($service->state($user)['active']);
    }

    public function test_shared_mode_lists_all_technician_birthdays_but_excludes_other_roles_and_archived_users(): void
    {
        $this->configure('birthday_and_everyone');
        $viewer = $this->createUserWithRole('Manager', ['birthday' => '1980-11-10', 'name' => 'Manager Birthday']);
        $this->createUserWithRole('Technician', ['birthday' => '1990-11-10', 'name' => 'Alex One', 'selection_name_order' => 'first_last']);
        $this->createUserWithRole('Technician', ['birthday' => '1991-11-10', 'name' => 'Sam Two', 'selection_name_order' => 'first_last']);
        $this->createUserWithRole('Technician', ['birthday' => '1992-11-10', 'name' => 'Archived Person'])->delete();
        $state = app(BirthdayProjectBackground::class)->state($viewer);
        $this->assertSame('Happy birthday: Alex One, Sam Two!', $state['caption']);
        $this->assertSame('2026-11-11T00:00:00-05:00', $state['expires_at']);
        $this->configure('birthday_person');
        $this->assertFalse(app(BirthdayProjectBackground::class)->state($viewer)['active']);
    }

    public function test_settings_upload_replace_switch_off_and_reenable_preserve_personal_background(): void
    {
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $user = $this->createUserWithRole('Technician', ['birthday' => '1990-11-10']);
        $personal = UserUiSetting::create(['user_id' => $user->id, 'scope' => UserUiSetting::PROJECT_APPEARANCE_SCOPE,
            'key' => InternetProjectBackground::KEY, 'value' => ['mode' => 'internet', 'topic' => 'Mountains', 'cadence' => 'button']]);
        $original = $personal->value;
        $url = route('admin.project-settings.birthday-background.store');
        $this->signIn($admin)->post($url, ['mode' => 'birthday_person', 'birthday_image' => $this->makeUploadedImage('first.png')])->assertSessionHasNoErrors()->assertRedirect();
        $service = app(BirthdayProjectBackground::class);
        $oldPath = $service->imagePath(); $oldUrl = $service->imageUrl();
        $this->assertTrue($service->state($user)['active']);
        $this->post($url, ['mode' => 'birthday_and_everyone', 'birthday_image' => $this->makeUploadedImage('second.png')])->assertSessionHasNoErrors();
        $this->assertNotSame($oldPath, $service->imagePath());
        $this->assertNotSame($oldUrl, $service->imageUrl());
        Storage::disk('private')->assertMissing($oldPath);
        $saved = $service->imagePath();
        $this->post($url, ['mode' => 'off'])->assertSessionHasNoErrors();
        $this->assertFalse($service->state($user)['active']);
        $this->assertSame($saved, $service->imagePath());
        $this->post($url, ['mode' => 'birthday_person'])->assertSessionHasNoErrors();
        $this->assertTrue($service->state($user)['active']);
        $this->assertSame($original, $personal->fresh()->value);
        $this->get(route('admin.project-settings.index', ['section' => 'birthday-background']))->assertOk()
            ->assertSee('Birthday Technician + all employees')->assertSee('Off — no birthday backgrounds')->assertSee('second.png');
    }

    public function test_validation_and_access_reject_non_admin_and_invalid_images(): void
    {
        $url = route('admin.project-settings.birthday-background.store');
        $user = $this->createUserWithRole('Technician');
        $this->signIn($user)->postJson($url, ['mode' => 'off'])->assertForbidden();
        $admin = $this->createUserWithRole('Admin', ['is_admin' => true]);
        $this->signIn($admin)->postJson($url, ['mode' => 'birthday_person'])->assertUnprocessable()->assertJsonValidationErrors('birthday_image');
        $this->postJson($url, ['mode' => 'everyone'])->assertUnprocessable()->assertJsonValidationErrors('mode');
        $this->postJson($url, ['mode' => 'birthday_person', 'birthday_image' => $this->makeUploadedFile('bad.svg', '<svg/>', 'image/svg+xml')])
            ->assertUnprocessable()->assertJsonValidationErrors('birthday_image');
        $this->postJson($url, ['mode' => 'off'])->assertRedirect();
        $this->assertNull(app(BirthdayProjectBackground::class)->imagePath());
    }

    public function test_state_and_image_are_scoped_to_current_user_and_master_keeps_personal_mode_off(): void
    {
        $this->configure('birthday_person');
        $user = $this->createUserWithRole('Technician', ['birthday' => '1990-11-10']);
        UserUiSetting::create(['user_id' => $user->id, 'scope' => UserUiSetting::PROJECT_APPEARANCE_SCOPE,
            'key' => InternetProjectBackground::KEY, 'value' => ['mode' => 'off']]);
        $state = $this->signIn($user)->getJson(route('admin.project-settings.birthday-background.state'))
            ->assertOk()->assertJsonPath('active', true)->assertHeader('Cache-Control', 'no-store, private')->json();
        $this->get($state['url'])->assertOk();
        $this->get(route('cabinet.index'))->assertOk()->assertSee('has-birthday-background')->assertSee('birthdayBackgroundConfig');
        $this->assertSame(['mode' => 'off'], UserUiSetting::where('user_id', $user->id)->where('key', InternetProjectBackground::KEY)->first()->value);
        $other = $this->createUserWithRole('Technician', ['birthday' => null]);
        $this->signIn($other)->getJson(route('admin.project-settings.birthday-background.state'))->assertJsonPath('active', false);
        $this->get($state['url'])->assertForbidden();
        $this->configure('birthday_and_everyone');
        $this->getJson(route('admin.project-settings.birthday-background.state'))->assertJsonPath('active', true);
        $this->get($state['url'])->assertOk();
    }
}
