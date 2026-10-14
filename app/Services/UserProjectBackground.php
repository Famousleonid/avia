<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserUiSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class UserProjectBackground
{
    public function legacyPath(User $user, array $setting): ?string
    {
        $path = (string) ($setting['path'] ?? '');
        return str_starts_with($path, "user-backgrounds/{$user->id}/") && !str_contains($path, '..') ? $path : null;
    }

    public function images(User $user): array
    {
        $setting = UserUiSetting::projectBackgroundFor($user->id);
        $images = [];
        $path = $this->legacyPath($user, $setting);
        if ($path && Storage::disk('public')->exists($path)) {
            $images[] = ['id' => 'legacy', 'name' => $setting['original_name'] ?? basename($path),
                'path' => Storage::disk('public')->path($path), 'version' => Storage::disk('public')->lastModified($path)];
        }
        foreach ($user->media()->where('collection_name', User::PROJECT_BACKGROUND_COLLECTION)->orderBy('id')->get() as $media) {
            if (is_file($media->getPath())) {
                $images[] = ['id' => (string) $media->id, 'name' => $media->file_name,
                    'path' => $media->getPath(), 'version' => $media->updated_at?->timestamp ?? $media->id];
            }
        }
        return $images;
    }

    public function current(User $user, ?array $images = null): ?array
    {
        $images ??= $this->images($user);
        if (!$images) return null;
        $setting = UserUiSetting::projectBackgroundFor($user->id);
        $start = Carbon::parse($setting['rotation_started_on'] ?? $user->created_at ?? '2026-01-01')->startOfDay();
        $days = max(0, (int) $start->diffInDays(Carbon::today(), false));
        return $images[$days % count($images)];
    }

    public function url(User $user, ?array $image = null): string
    {
        return route('admin.project-settings.user-background.show', array_filter([
            'user' => $user->id, 'image' => $image['id'] ?? null,
            'v' => $image['version'] ?? Carbon::today()->toDateString(),
        ]));
    }
}
