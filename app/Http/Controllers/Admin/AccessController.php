<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserFeatureAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccessController extends Controller
{
    private const TRAINING_FLAGS = [
        'trainings.sca' => 'can_sign_certificates',
        'trainings.manage_approved' => 'can_manage_approved_trainings',
    ];

    public function index(): View
    {
        $features = $this->managedFeatures();
        $featureKeys = array_keys($features);

        $accessRecords = UserFeatureAccess::query()
            ->with(['user.role', 'grantedBy'])
            ->whereIn('feature_key', $featureKeys)
            ->get()
            ->filter(fn (UserFeatureAccess $access): bool => ! $access->user?->isSystemAdmin())
            ->sortBy([
                fn (UserFeatureAccess $left, UserFeatureAccess $right) => strcmp($left->feature_key, $right->feature_key),
                fn (UserFeatureAccess $left, UserFeatureAccess $right) => strcmp(
                    strtolower((string) ($left->user?->name ?? '')),
                    strtolower((string) ($right->user?->name ?? ''))
                ),
            ])
            ->groupBy('feature_key');

        foreach (self::TRAINING_FLAGS as $key => $column) {
            $accessRecords->put($key, User::with('role')->where($column, true)->orderBy('name')->get()
                ->map(fn (User $user) => (object) [
                    'user' => $user, 'user_id' => $user->id, 'created_at' => null, 'grantedBy' => null,
                    'remove_url' => route('admin.access.training-flag.destroy', ['feature' => $key, 'user' => $user->id]),
                ]));
        }

        return view('admin.access.index', [
            'features' => $features,
            'featureGroups' => collect($features)->groupBy('group', preserveKeys: true),
            'roles' => Role::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'users' => User::query()
                ->with('role')
                ->orderBy('name')
                ->orderBy('email')
                ->get()
                ->sortBy(fn (User $user) => mb_strtolower($user->selection_name))
                ->values(),
            'accessRecords' => $accessRecords,
            'assignedUserIdsByFeature' => $accessRecords
                ->map(fn ($records) => $records->pluck('user_id')->map(fn ($id) => (int) $id)->all())
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $featureKeys = array_keys($this->managedFeatures());

        $data = $request->validate([
            'feature_key' => ['required', 'string', Rule::in($featureKeys)],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ]);

        $userIds = collect($data['user_ids'])
            ->map(fn ($userId): int => (int) $userId)
            ->unique();

        if (isset(self::TRAINING_FLAGS[$data['feature_key']])) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($userIds, $data) {
                foreach (User::whereIn('id', $userIds)->get() as $user) {
                    $user->update([self::TRAINING_FLAGS[$data['feature_key']] => true]);
                }
            });
            return redirect()->route('admin.access.index')
                ->with('active_access_feature', $data['feature_key'])
                ->with('success', 'Access saved.');
        }

        $assignableUserIds = User::query()
            ->with('role')
            ->whereIn('id', $userIds->all())
            ->get()
            ->reject(fn (User $user): bool => $user->isSystemAdmin())
            ->pluck('id')
            ->map(fn ($userId): int => (int) $userId)
            ->values();

        $granted = $assignableUserIds
            ->map(function (int $userId) use ($data, $request): UserFeatureAccess {
                return UserFeatureAccess::query()->firstOrCreate(
                    [
                        'feature_key' => $data['feature_key'],
                        'user_id' => $userId,
                    ],
                    [
                        'granted_by_user_id' => $request->user()?->id,
                    ]
                );
            });

        return redirect()
            ->route('admin.access.index')
            ->with('active_access_feature', $data['feature_key'])
            ->with('success', $granted->count() === 1 ? 'Access saved.' : 'Access saved for '.$granted->count().' users.');
    }

    public function destroy(UserFeatureAccess $access): RedirectResponse
    {
        abort_unless($this->isManagedFeature($access->feature_key), 404);

        $access->delete();

        return redirect()
            ->route('admin.access.index')
            ->with('active_access_feature', $access->feature_key)
            ->with('success', 'Access removed.');
    }

    private function managedFeatures(): array
    {
        $features = collect(config('features', []))
            ->filter(fn (array $definition): bool => (bool) ($definition['managed'] ?? false))
            ->map(function (array $definition, string $key): array {
                return [
                    'key' => $key,
                    'label' => $definition['label'] ?? Str::headline($key),
                    'group' => $definition['group'] ?? 'Other',
                    'dom_id' => 'access-feature-'.Str::slug(str_replace('.', '-', $key)),
                ];
            })
            ->all();
        foreach (['trainings.sca' => 'SCA (training qualification)', 'trainings.manage_approved' => 'Manage approved trainings'] as $key => $label) {
            $features[$key] = ['key' => $key, 'label' => $label, 'group' => 'Trainings',
                'dom_id' => 'access-feature-'.Str::slug(str_replace('.', '-', $key)), 'user_flag' => true];
        }
        return $features;
    }

    public function destroyTrainingFlag(string $feature, User $user): RedirectResponse
    {
        abort_unless(isset(self::TRAINING_FLAGS[$feature]), 404);
        $user->update([self::TRAINING_FLAGS[$feature] => false]);
        return redirect()->route('admin.access.index')
            ->with('active_access_feature', $feature)
            ->with('success', 'Access removed.');
    }

    private function isManagedFeature(string $featureKey): bool
    {
        return array_key_exists($featureKey, $this->managedFeatures());
    }
}
