<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserUiSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InternetProjectBackground
{
    public const KEY = 'background_source';
    public const PHOTO_KEY = 'internet_background_photo';
    private ?array $issue = null;

    public function issue(): ?array
    {
        return $this->issue;
    }

    public function settings(User $user): array
    {
        $row = UserUiSetting::where('user_id', $user->id)
            ->where('scope', UserUiSetting::PROJECT_APPEARANCE_SCOPE)->where('key', self::KEY)->first();

        return is_array($row?->value) ? $row->value : ['mode' => 'manual', 'topic' => ''];
    }

    public function current(User $user, bool $refresh = false): ?array
    {
        $this->issue = null;
        $settings = $this->settings($user);
        $topic = trim((string) ($settings['topic'] ?? ''));
        if (($settings['mode'] ?? '') !== 'internet' || $topic === '') return null;

        $key = 'internet-background:v1:'.$user->id.':'.hash('sha256', $topic);
        $deviceKey = \App\Support\UiSettingsDevice::keyFor((int) $user->id);
        if ($deviceKey !== '') $key .= ':device:'.$deviceKey;
        $previous = $this->savedPhoto($user, $topic);
        $daily = ($settings['cadence'] ?? 'button') === 'daily';
        if (! $refresh && isset($previous['photo']) && (! $daily || ($previous['date'] ?? '') === now()->toDateString())) return $previous['photo'];
        // Automatic checks back off; an explicit New photo is always allowed to retry.
        if (! $refresh && Cache::has($key.':retry')) {
            $this->issue = ['code' => 'retry_later', 'message' => 'The photo search failed earlier. Press New photo to retry now.'];
            return $previous['photo'] ?? null;
        }

        $lock = Cache::lock($key.':lock', 30);
        if (! $lock->get()) {
            $this->issue = ['code' => 'busy', 'message' => 'A photo is already loading. Please try again shortly.'];
            return $previous['photo'] ?? null;
        }
        try {
            $previous = $this->savedPhoto($user, $topic);
            if (! $refresh && isset($previous['photo']) && (! $daily || ($previous['date'] ?? '') === now()->toDateString())) return $previous['photo'];
            $photos = Cache::remember('commons-background:v1:'.hash('sha256', $topic), now()->addDays(7), fn () => $this->search($topic));
            $history = $previous['history'] ?? [];
            $choices = array_values(array_filter($photos, fn ($photo) => ! in_array($photo['id'], $history, true)));
            if (! $choices) {
                $history = array_filter([$previous['photo']['id'] ?? null]);
                $choices = array_values(array_filter($photos, fn ($photo) => ! in_array($photo['id'], $history, true)));
            }
            if (! $choices) $choices = $photos;
            if (! $choices) throw new \RuntimeException('No suitable photos');
            $photo = $choices[random_int(0, count($choices) - 1)];
            $history[] = $photo['id'];
            UserUiSetting::updateOrCreate([
                'user_id' => $user->id, 'scope' => UserUiSetting::PROJECT_APPEARANCE_SCOPE, 'key' => self::PHOTO_KEY,
            ], ['value' => ['topic' => $topic, 'date' => now()->toDateString(), 'photo' => $photo, 'history' => array_slice($history, -100)]]);
            Cache::forget($key.':retry');
            return $photo;
        } catch (\Throwable $e) {
            $this->issue = $e instanceof \RuntimeException && $e->getMessage() === 'No suitable photos'
                ? ['code' => 'no_photos', 'message' => 'No suitable landscape photos were found. Try another topic or the city name in English.']
                : ['code' => 'search_unavailable', 'message' => 'The photo search is temporarily unavailable. Your background is kept. Press New photo to retry.'];
            // Do not log topics, full upstream URLs, user details or response bodies.
            Log::warning('Internet background search failed', ['exception_type' => get_class($e), 'code' => $this->issue['code']]);
            Cache::put($key.':retry', true, now()->addMinutes(30));
            return $previous['photo'] ?? null;
        } finally {
            $lock->release();
        }
    }

    private function savedPhoto(User $user, string $topic): array
    {
        $value = UserUiSetting::where('user_id', $user->id)->where('scope', UserUiSetting::PROJECT_APPEARANCE_SCOPE)
            ->where('key', self::PHOTO_KEY)->first()?->value;
        return is_array($value) && ($value['topic'] ?? '') === $topic ? $value : [];
    }

    private function search(string $topic): array
    {
        $data = Http::withHeaders(['User-Agent' => 'AVIA-Background/1.0 (https://aviatechnik.ca)'])
            ->connectTimeout(5)->timeout(20)->withOptions(['allow_redirects' => false])
            ->get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query', 'format' => 'json', 'generator' => 'search',
                'gsrsearch' => $topic.' filetype:bitmap', 'gsrnamespace' => 6, 'gsrlimit' => 100,
                'prop' => 'imageinfo', 'iiprop' => 'url|size|mime|extmetadata', 'iiurlwidth' => 1920,
                'iiextmetadatafilter' => 'Artist|LicenseShortName|LicenseUrl',
            ])->throw()->json();
        $photos = [];
        foreach ($data['query']['pages'] ?? [] as $page) {
            $info = $page['imageinfo'][0] ?? [];
            $width = (int) ($info['width'] ?? 0);
            $height = (int) ($info['height'] ?? 0);
            $license = $this->text($info['extmetadata']['LicenseShortName']['value'] ?? '');
            $url = $info['thumburl'] ?? '';
            $source = $info['descriptionurl'] ?? '';
            if ($width < 1280 || $height < 720 || $width < $height || $width > $height * 3
                || ! in_array($info['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true)
                || ! preg_match('/^(CC BY(?:-SA)? [0-9.]+|CC0(?: [0-9.]+)?|Public domain)$/i', $license)
                || ! $this->allowedUrl($url, ['upload.wikimedia.org', 'thumb.wikimedia.org'])
                || ! $this->allowedUrl($source, ['commons.wikimedia.org'])) continue;
            $licenseUrl = $info['extmetadata']['LicenseUrl']['value'] ?? '';
            $photos[] = [
                'id' => (int) $page['pageid'], 'url' => $url, 'source' => $source,
                'title' => $this->text($page['title'] ?? ''),
                'author' => $this->text($info['extmetadata']['Artist']['value'] ?? ''),
                'license' => $license,
                'license_url' => $this->allowedUrl($licenseUrl, ['creativecommons.org', 'commons.wikimedia.org']) ? $licenseUrl : $source,
            ];
        }
        if (! $photos) throw new \RuntimeException('No suitable photos');

        return $photos;
    }

    private function allowedUrl(string $url, array $hosts): bool
    {
        $parts = parse_url($url);
        return is_array($parts) && ($parts['scheme'] ?? '') === 'https'
            && in_array($parts['host'] ?? '', $hosts, true) && ! isset($parts['user']) && ! isset($parts['pass'])
            && (! isset($parts['port']) || $parts['port'] === 443);
    }

    private function text(string $value): string
    {
        return mb_substr(trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 0, 500);
    }
}
