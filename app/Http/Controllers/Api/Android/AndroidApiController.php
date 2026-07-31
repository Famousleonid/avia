<?php

namespace App\Http\Controllers\Api\Android;

use App\Http\Controllers\Api\Mobile\MobileApiController;
use App\Models\MobileApiToken;
use App\Models\User;
use App\Models\Workorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Android contour of the mobile API (/api/android/*).
 *
 * The iOS contour (/api/mobile/* -> MobileApiController) is the source of
 * truth and stays untouched. This controller inherits every endpoint and
 * overrides ONLY the platform-specific bits, so the two clients can evolve
 * independently without duplicating the shared domain logic.
 */
class AndroidApiController extends MobileApiController
{
    public function publicAppConfig(): JsonResponse
    {
        $response = parent::publicAppConfig();
        $payload = $response->getData(true);

        $payload['data']['app']['platform'] = 'android';
        // Client hints for the Compose shell (the iOS payload is unchanged).
        $payload['data']['app']['android'] = [
            'min_sdk' => 26,
            // Brand palette over Material You dynamic colors.
            'dynamic_color' => false,
            // Self-update: drop aviatechnik-vX.Y.Z.apk into public/app/ —
            // the newest version is detected from the file name.
            'update' => $this->latestAndroidBuild(),
        ];

        return response()->json($payload, $response->getStatusCode());
    }

    protected function latestAndroidBuild(): ?array
    {
        $dir = public_path('app');
        if (! is_dir($dir)) {
            return null;
        }

        $bestFile = null;
        $bestVersion = null;
        foreach (glob($dir . '/aviatechnik-v*.apk') ?: [] as $file) {
            if (preg_match('/aviatechnik-v(\d+(?:\.\d+)*)\.apk$/i', basename($file), $m)
                && ($bestVersion === null || version_compare($m[1], $bestVersion, '>'))) {
                $bestVersion = $m[1];
                $bestFile = $file;
            }
        }

        return $bestFile ? [
            'version_name' => $bestVersion,
            'url' => asset('app/' . basename($bestFile)),
        ] : null;
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->with(['role', 'team'])
            ->where('email', $data['email'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || ! $user->hasVerifiedEmail()) {
            return $this->fail('Invalid credentials.', 422, [
                'email' => ['Invalid credentials.'],
            ]);
        }

        $plainToken = Str::random(80);
        MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => $data['device_name'] ?? 'Android device',
            'platform' => 'android',
            'token_hash' => MobileApiToken::hashPlainTextToken($plainToken),
        ]);

        return $this->ok([
            'token' => $plainToken,
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Desktop-parity visibility for the Tasks screen: Technician/Team Leader
     * don't see the configured group position (Complete) nor the
     * Approved/Completed tasks (config/workorders.php). iOS keeps the
     * unfiltered historical payload.
     */
    protected function mobileVisibleGeneralTasks(?User $user): \Illuminate\Support\Collection
    {
        return app(\App\Services\Workorders\WorkorderVisibilityService::class)
            ->visibleGeneralTasksFor($user);
    }

    protected function mobileVisibleTasks(\Illuminate\Support\Collection $tasks, ?User $user): \Illuminate\Support\Collection
    {
        return app(\App\Services\Workorders\WorkorderVisibilityService::class)
            ->filterVisibleMainsTasks($tasks, $user);
    }

    /**
     * Additive Android extra: recorded_by is a raw user id — resolve the name
     * for display. iOS payload shape is unchanged (extra key only).
     */
    protected function arrivalBoxPayload(Workorder $workorder, User $user): array
    {
        $payload = parent::arrivalBoxPayload($workorder, $user);
        $payload['recorded_by_name'] = $workorder->arrival_box_recorded_by
            ? User::find($workorder->arrival_box_recorded_by)?->selection_name
            : null;

        return $payload;
    }
}
