<?php

namespace App\Services;

use App\Models\ProjectSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BirthdayProjectBackground
{
    public const KEY = 'appearance.birthday_background';
    public const STAFF_ROLES = ['Admin', 'Manager', 'Team Leader', 'Technician', 'Shipping', 'Paint', 'Machining'];

    public function settings(): array
    {
        return ProjectSetting::valueFor(self::KEY);
    }

    public function isStaff(User $user): bool
    {
        return $user->roleIs(self::STAFF_ROLES)
            && ! in_array(strtolower((string) $user->email), array_map('strtolower', array_keys(config('mobile_review.accounts', []))), true);
    }

    public function imagePath(?array $settings = null): ?string
    {
        $path = ($settings ?? $this->settings())['path'] ?? '';
        return preg_match('~^birthday-backgrounds/[A-Za-z0-9_-]+\.(jpg|jpeg|png|webp)$~i', $path)
            && Storage::disk('private')->exists($path) ? $path : null;
    }

    public function imageUrl(?array $settings = null): ?string
    {
        $path = $this->imagePath($settings);
        return $path ? route('admin.project-settings.birthday-background.image', ['v' => basename($path)]) : null;
    }

    private function anniversary(User $user, int $year): CarbonImmutable
    {
        $month = CarbonImmutable::create($year, $user->birthday->month, 1, 0, 0, 0, config('app.timezone'));
        // Observe February 29 on February 28 in non-leap years.
        return $month->day(min($user->birthday->day, $month->daysInMonth));
    }

    public function state(User $viewer): array
    {
        $inactive = ['active' => false, 'url' => null, 'caption' => '', 'expires_at' => null];
        $settings = $this->settings();
        $mode = $settings['mode'] ?? 'off';
        if (! $this->isStaff($viewer) || $mode === 'off' || ! $this->imagePath($settings)) return $inactive;
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        if ($mode === 'birthday_and_everyone') {
            $people = User::query()->withoutReviewAccounts()->whereNotNull('birthday')
                ->whereHas('role', fn ($q) => $q->where('name', 'Technician'))
                ->whereMonth('birthday', $today->month)
                ->where(function ($q) use ($today) {
                    $q->whereDay('birthday', $today->day);
                    if ($today->month === 2 && $today->day === 28 && ! $today->isLeapYear()) $q->orWhereDay('birthday', 29);
                })->orderBy('name')->get(['id', 'name', 'selection_name_order']);
            if ($people->isNotEmpty()) {
                return ['active' => true, 'url' => $this->imageUrl($settings),
                    'caption' => 'Happy birthday: '.$people->map(fn ($person) => $person->selection_name)->implode(', ').'!',
                    'expires_at' => $today->addDay()->toIso8601String()];
            }
        }
        // In both enabled modes the birthday Technician retains the full four-day window.
        if (! $viewer->roleIs('Technician') || ! $viewer->birthday) return $inactive;
        foreach ([$today->year - 1, $today->year, $today->year + 1] as $year) {
            $birthday = $this->anniversary($viewer, $year);
            if ($today->betweenIncluded($birthday->subDay(), $birthday->addDays(2))) {
                return ['active' => true, 'url' => $this->imageUrl($settings), 'caption' => '',
                    'expires_at' => $birthday->addDays(3)->toIso8601String()];
            }
        }
        return $inactive;
    }

    public function save(string $mode, ?UploadedFile $file): void
    {
        $newPath = $file?->store('birthday-backgrounds', 'private');
        if ($file && ! $newPath) throw new \RuntimeException('The birthday image could not be stored.');
        $oldPath = null;
        try {
            DB::transaction(function () use ($mode, $file, $newPath, &$oldPath) {
                $row = ProjectSetting::where('key', self::KEY)->lockForUpdate()->first();
                $value = $row?->value ?? [];
                $oldPath = $this->imagePath($value);
                $value['mode'] = $mode;
                if ($newPath) {
                    $value['path'] = $newPath;
                    $value['original_name'] = $file->getClientOriginalName();
                }
                ProjectSetting::updateOrCreate(['key' => self::KEY], ['value' => $value]);
            });
        } catch (\Throwable $e) {
            if ($newPath) Storage::disk('private')->delete($newPath);
            throw $e;
        }
        if ($newPath && $oldPath && $newPath !== $oldPath) Storage::disk('private')->delete($oldPath);
    }
}
