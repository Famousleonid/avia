<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectSetting;
use App\Models\User;
use App\Models\UserUiSetting;
use App\Services\MarketingWoEstimateNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectSettingController extends Controller
{
    public function index(Request $request): View
    {
        $marketingEmails = ProjectSetting::marketingWoEstimateEmailRecipients();
        $activeSection = in_array($request->query('section'), ['printed-forms', 'marketing', 'user-background'], true)
            ? (string) $request->query('section')
            : 'printed-forms';
        $users = User::query()
            ->with('role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role_id']);
        $selectedUserId = (int) $request->query('user_id', 0);
        $selectedUser = $users->firstWhere('id', $selectedUserId) ?? $users->first();
        $background = $selectedUser ? UserUiSetting::projectBackgroundFor((int) $selectedUser->id) : [];
        $backgroundPath = $selectedUser ? $this->validBackgroundPath((int) $selectedUser->id, $background) : null;
        $backgroundMedia = $selectedUser?->getFirstMedia(User::PROJECT_BACKGROUND_COLLECTION);

        return view('admin.project_settings.index', [
            'activeSection' => $activeSection,
            'qrEnabled' => ProjectSetting::boolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true),
            'marketingWoEstimateEmailRecipientsText' => implode("\n", $marketingEmails),
            'marketingWoEstimateEmailDelayDays' => ProjectSetting::marketingWoEstimateEmailDelayDays(),
            'users' => $users,
            'selectedUser' => $selectedUser,
            'userBackground' => $background,
            'userBackgroundUrl' => $selectedUser
                ? $this->backgroundUrl($selectedUser, $backgroundMedia, $backgroundPath)
                : null,
        ]);
    }

    public function update(
        Request $request,
        MarketingWoEstimateNotificationService $estimateNotifications
    ): RedirectResponse
    {
        $section = (string) $request->input('settings_section', 'all');
        abort_unless(in_array($section, ['all', 'printed-forms', 'marketing'], true), 422);

        $data = $request->validate([
            'marketing_wo_estimate_email_recipients' => ['nullable', 'string', 'max:4000'],
            'marketing_wo_estimate_email_delay_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        if (in_array($section, ['all', 'marketing'], true)) {
            $emails = $this->parseEmailList((string) ($data['marketing_wo_estimate_email_recipients'] ?? ''));
            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    return back()
                        ->withErrors(['marketing_wo_estimate_email_recipients' => "Invalid email: {$email}"])
                        ->withInput();
                }
            }

            $delayDays = (int) ($data['marketing_wo_estimate_email_delay_days']
                ?? ProjectSetting::marketingWoEstimateEmailDelayDays());

            ProjectSetting::setMarketingWoEstimateEmailSettings($emails, $delayDays);
            $estimateNotifications->reschedulePending($delayDays);
        }

        if (in_array($section, ['all', 'printed-forms'], true)) {
            ProjectSetting::setBoolean(
                ProjectSetting::PRINT_FORMS_QR_ENABLED,
                $request->boolean('print_forms_qr_enabled')
            );
        }

        return redirect()
            ->route('admin.project-settings.index', $section === 'all' ? [] : [
                'section' => $section === 'printed-forms' ? 'printed-forms' : 'marketing',
            ])
            ->with('success', 'Project settings saved.');
    }

    public function storeUserBackground(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
            ],
            'background_image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $user = User::query()->findOrFail((int) $data['user_id']);
        $oldBackground = UserUiSetting::projectBackgroundFor((int) $user->id);
        $oldPath = $this->validBackgroundPath((int) $user->id, $oldBackground);
        $uploadedFile = $request->file('background_image');
        $media = $user
            ->addMedia($uploadedFile)
            ->toMediaCollection(User::PROJECT_BACKGROUND_COLLECTION);

        try {
            UserUiSetting::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'scope' => UserUiSetting::PROJECT_APPEARANCE_SCOPE,
                    'key' => UserUiSetting::PROJECT_BACKGROUND_KEY,
                ],
                [
                    'value' => [
                        'media_id' => $media->id,
                        'original_name' => $uploadedFile->getClientOriginalName(),
                    ],
                ]
            );
        } catch (\Throwable $exception) {
            $media->delete();
            throw $exception;
        }

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('admin.project-settings.index', ['section' => 'user-background', 'user_id' => $user->id])
            ->with('success', "Background updated for {$user->name}.");
    }

    public function destroyUserBackground(User $user): RedirectResponse
    {
        $background = UserUiSetting::projectBackgroundFor((int) $user->id);
        $path = $this->validBackgroundPath((int) $user->id, $background);

        $user->clearMediaCollection(User::PROJECT_BACKGROUND_COLLECTION);

        UserUiSetting::query()
            ->where('user_id', $user->id)
            ->where('scope', UserUiSetting::PROJECT_APPEARANCE_SCOPE)
            ->where('key', UserUiSetting::PROJECT_BACKGROUND_KEY)
            ->delete();

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()
            ->route('admin.project-settings.index', ['section' => 'user-background', 'user_id' => $user->id])
            ->with('success', "Background removed for {$user->name}.");
    }

    public function showUserBackground(Request $request, User $user): BinaryFileResponse
    {
        abort_unless(
            (int) $request->user()?->id === (int) $user->id || $request->user()?->isSystemAdmin(),
            403
        );

        $media = $user->getFirstMedia(User::PROJECT_BACKGROUND_COLLECTION);
        if ($media) {
            $path = $media->getPath();
            abort_unless($path && is_file($path), 404);

            return response()->file($path, ['Content-Type' => $media->mime_type]);
        }

        $background = UserUiSetting::projectBackgroundFor((int) $user->id);
        $legacyPath = $this->validBackgroundPath((int) $user->id, $background);
        abort_unless($legacyPath && Storage::disk('public')->exists($legacyPath), 404);

        return response()->file(Storage::disk('public')->path($legacyPath));
    }

    /**
     * @return list<string>
     */
    private function parseEmailList(string $value): array
    {
        return collect(preg_split('/[\s,;]+/', $value) ?: [])
            ->map(fn ($email): string => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function validBackgroundPath(int $userId, array $background): ?string
    {
        $path = trim((string) data_get($background, 'path', ''));

        return $path !== '' && str_starts_with($path, "user-backgrounds/{$userId}/")
            ? $path
            : null;
    }

    private function backgroundUrl(User $user, mixed $media, ?string $legacyPath): ?string
    {
        if ($media) {
            return route('admin.project-settings.user-background.show', [
                'user' => $user->id,
                'v' => $media->updated_at?->getTimestamp() ?? $media->id,
            ]);
        }

        if ($legacyPath && Storage::disk('public')->exists($legacyPath)) {
            return route('admin.project-settings.user-background.show', [
                'user' => $user->id,
                'v' => Storage::disk('public')->lastModified($legacyPath),
            ]);
        }

        return null;
    }
}
