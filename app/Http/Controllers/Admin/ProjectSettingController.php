<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectSetting;
use App\Models\User;
use App\Models\UserUiSetting;
use App\Services\MarketingWoEstimateNotificationService;
use App\Services\UserProjectBackground;
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
            ->withoutReviewAccounts()
            ->with('role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role_id']);
        $selectedUserId = (int) $request->query('user_id', 0);
        $selectedUser = $users->firstWhere('id', $selectedUserId) ?? $users->first();
        $background = $selectedUser ? UserUiSetting::projectBackgroundFor((int) $selectedUser->id) : [];
        $backgrounds = app(UserProjectBackground::class);
        $images = $selectedUser ? $backgrounds->images($selectedUser) : [];
        $current = $selectedUser ? $backgrounds->current($selectedUser, $images) : null;

        return view('admin.project_settings.index', [
            'activeSection' => $activeSection,
            'qrEnabled' => ProjectSetting::boolean(ProjectSetting::PRINT_FORMS_QR_ENABLED, true),
            'qrForms' => app(\App\Services\PrintFormQrSettings::class)->catalog(),
            'qrFormSelections' => app(\App\Services\PrintFormQrSettings::class)->selections(),
            'marketingWoEstimateEmailRecipientsText' => implode("\n", $marketingEmails),
            'marketingWoEstimateEmailDelayDays' => ProjectSetting::marketingWoEstimateEmailDelayDays(),
            'users' => $users,
            'selectedUser' => $selectedUser,
            'userBackground' => $background,
            'userBackgroundImages' => array_map(fn ($image) => $image + ['url' => $backgrounds->url($selectedUser, $image)], $images),
            'userBackgroundCurrentId' => $current['id'] ?? null,
            'userBackgroundUrl' => $current ? $backgrounds->url($selectedUser, $current) : null,
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
            'qr_forms_present' => ['sometimes', 'boolean'],
            'qr_forms' => ['sometimes', 'array'],
            'qr_forms.*' => ['boolean'],
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
            if ($request->boolean('qr_forms_present')) {
                $forms = app(\App\Services\PrintFormQrSettings::class)->catalog();
                $selections = [];
                foreach ($forms as $key => $form) {
                    $selections[$key] = $request->boolean('qr_forms.'.$key);
                }
                ProjectSetting::updateOrCreate(
                    ['key' => \App\Services\PrintFormQrSettings::KEY], ['value' => $selections]
                );
            }
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
            'background_image' => ['required_without:background_images', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'background_images' => ['required_without:background_image', 'array', 'min:1', 'max:20'],
            'background_images.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $user = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $data) {
            $user = User::query()->lockForUpdate()->findOrFail((int) $data['user_id']);
            $files = $request->file('background_images', []);
            if ($request->hasFile('background_image')) $files[] = $request->file('background_image');
            $images = app(UserProjectBackground::class)->images($user);
            if (count($images) + count($files) > 20) {
                throw \Illuminate\Validation\ValidationException::withMessages(['background_images' => 'Maximum 20 backgrounds per user. Remove an image before adding more.']);
            }
            $setting = UserUiSetting::projectBackgroundFor($user->id);
            $setting['rotation_started_on'] ??= now()->toDateString();
            $created = [];
            try {
                foreach ($files as $file) {
                    $media = $user->addMedia($file)->toMediaCollection(User::PROJECT_BACKGROUND_COLLECTION);
                    $created[] = $media;
                    if (!$images && count($created) === 1) {
                        $setting['media_id'] = $media->id;
                        $setting['original_name'] = $file->getClientOriginalName();
                    }
                }
                UserUiSetting::updateOrCreate(['user_id' => $user->id,
                    'scope' => UserUiSetting::PROJECT_APPEARANCE_SCOPE, 'key' => UserUiSetting::PROJECT_BACKGROUND_KEY], ['value' => $setting]);
            } catch (\Throwable $exception) {
                foreach ($created as $media) $media->delete();
                throw $exception;
            }
            return $user;
        });

        return redirect()
            ->route('admin.project-settings.index', ['section' => 'user-background', 'user_id' => $user->id])
            ->with('success', "Background updated for {$user->name}.");
    }

    public function destroyUserBackground(Request $request, User $user): RedirectResponse
    {
        $background = UserUiSetting::projectBackgroundFor((int) $user->id);
        $path = app(UserProjectBackground::class)->legacyPath($user, $background);
        $image = $request->validate(['image' => ['nullable', 'regex:/^(legacy|[0-9]+)$/']])['image'] ?? null;
        if ($image && $image !== 'legacy') {
            $media = $user->media()->where('collection_name', User::PROJECT_BACKGROUND_COLLECTION)->findOrFail($image);
            $media->delete();
        } elseif (!$image) {
            $user->clearMediaCollection(User::PROJECT_BACKGROUND_COLLECTION);
        }
        if ((!$image || $image === 'legacy') && $path) {
            Storage::disk('public')->delete($path);
            unset($background['path']);
        }
        $user->unsetRelation('media');
        $settingsQuery = UserUiSetting::query()
            ->where('user_id', $user->id)
            ->where('scope', UserUiSetting::PROJECT_APPEARANCE_SCOPE)
            ->where('key', UserUiSetting::PROJECT_BACKGROUND_KEY);
        if ($user->getMedia(User::PROJECT_BACKGROUND_COLLECTION)->isEmpty() && empty($background['path'])) {
            $settingsQuery->delete();
        } else {
            $settingsQuery->first()?->update(['value' => $background]);
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

        $backgrounds = app(UserProjectBackground::class);
        $images = $backgrounds->images($user);
        $image = $request->has('image')
            ? collect($images)->firstWhere('id', (string) $request->query('image'))
            : $backgrounds->current($user, $images);
        abort_unless($image, 404);
        return response()->file($image['path'], ['Cache-Control' => 'private, no-store']);
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

}
