<?php

namespace App\Services\Push;

use App\Models\MobileApiToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FCM HTTP v1 sender (no SDK dependency): the service-account JSON signs an
 * RS256 JWT which is exchanged for a short-lived OAuth2 access token.
 * Silently no-ops when the credentials file is absent, so environments
 * without Firebase keep working.
 */
class FcmPushService
{
    protected ?array $credentials = null;
    protected bool $loaded = false;

    public function enabled(): bool
    {
        return $this->credentials() !== null;
    }

    /** Sends to every device (mobile_api_tokens.fcm_token) of the user. */
    public function sendToUser(User $user, string $title, string $body, ?string $url = null): void
    {
        if (! $this->enabled()) {
            return;
        }

        $tokens = MobileApiToken::query()
            ->where('user_id', $user->id)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token', 'id');

        foreach ($tokens as $rowId => $token) {
            $this->sendToToken($token, $title, $body, $url, (int) $rowId);
        }
    }

    protected function sendToToken(string $token, string $title, string $body, ?string $url, int $rowId): void
    {
        $credentials = $this->credentials();
        $accessToken = $this->accessToken();
        if (! $credentials || ! $accessToken) {
            return;
        }

        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => mb_substr($title, 0, 200),
                    'body' => mb_substr($body, 0, 500),
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => ['channel_id' => 'avia_default'],
                ],
            ],
        ];
        if ($url) {
            // set only when present: an empty PHP array encodes as [] and
            // FCM requires 'data' to be a JSON object
            $message['message']['data'] = ['url' => $url];
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post(
                    'https://fcm.googleapis.com/v1/projects/' . $credentials['project_id'] . '/messages:send',
                    $message
                );

            // A dead registration (uninstalled app / rotated token) — forget it.
            if ($response->status() === 404
                || str_contains((string) $response->body(), 'UNREGISTERED')) {
                MobileApiToken::query()->where('id', $rowId)->update(['fcm_token' => null]);
            } elseif (! $response->successful()) {
                Log::warning('FCM send failed', ['status' => $response->status(), 'body' => mb_substr($response->body(), 0, 500)]);
            }
        } catch (\Throwable $e) {
            Log::warning('FCM send exception: ' . $e->getMessage());
        }
    }

    protected function credentials(): ?array
    {
        if (! $this->loaded) {
            $this->loaded = true;
            $path = config('services.fcm.credentials');
            if ($path && is_file($path)) {
                $data = json_decode((string) file_get_contents($path), true);
                if (is_array($data) && ! empty($data['project_id']) && ! empty($data['private_key']) && ! empty($data['client_email'])) {
                    $this->credentials = $data;
                }
            }
        }

        return $this->credentials;
    }

    protected function accessToken(): ?string
    {
        $credentials = $this->credentials();
        if (! $credentials) {
            return null;
        }

        return Cache::remember('fcm_access_token', 3300, function () use ($credentials) {
            $now = time();
            $header = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->b64(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            $signature = '';
            if (! openssl_sign($header . '.' . $claims, $signature, $credentials['private_key'], 'sha256WithRSAEncryption')) {
                Log::warning('FCM: JWT signing failed');

                return null;
            }
            $jwt = $header . '.' . $claims . '.' . $this->b64($signature);

            $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);
            if (! $response->successful()) {
                Log::warning('FCM: token exchange failed: ' . mb_substr($response->body(), 0, 300));

                return null;
            }

            return $response->json('access_token');
        });
    }

    protected function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
