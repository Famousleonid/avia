<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationEventRule;
use App\Models\Role;
use App\Models\User;
use App\Services\NotificationEventRegistry;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationEventRuleController extends Controller
{
    public function __construct(
        protected NotificationEventRegistry $registry,
    ) {}

    public function index()
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        $rules = NotificationEventRule::query()
            ->with('recipients')
            ->orderBy('event_key')
            ->orderBy('name')
            ->get();

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);
        $events = $this->registry->all();

        return view('admin.notification_rules.index', compact('rules', 'roles', 'users', 'events'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $rule = NotificationEventRule::create($data);
        $this->syncRecipients($rule, $request);

        return redirect()
            ->route('admin.notification-rules.index')
            ->with('success', 'Notification rule created.');
    }

    public function update(Request $request, NotificationEventRule $notificationRule)
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        $data = $this->validated($request);
        $data['updated_by'] = auth()->id();

        $notificationRule->update($data);
        $this->syncRecipients($notificationRule, $request);

        return redirect()
            ->route('admin.notification-rules.index')
            ->with('success', 'Notification rule updated.');
    }

    public function destroy(NotificationEventRule $notificationRule)
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        $notificationRule->delete();

        return redirect()
            ->route('admin.notification-rules.index')
            ->with('success', 'Notification rule deleted.');
    }

    protected function validated(Request $request): array
    {
        $eventKeys = $this->registry->keys();
        $repeatPolicies = ['event_default', 'once', 'daily', 'minutes'];
        $severities = ['info', 'success', 'warning', 'danger'];

        $data = $request->validate([
            'event_key' => ['required', Rule::in($eventKeys)],
            'name' => ['nullable', 'string', 'max:255'],
            'enabled' => ['nullable', 'boolean'],
            'severity' => ['required', Rule::in($severities)],
            'repeat_policy' => ['required', Rule::in($repeatPolicies)],
            'repeat_every_minutes' => ['nullable', 'integer', 'min:1', 'max:43200'],
            'title_template' => ['nullable', 'string', 'max:255'],
            'message_template' => ['nullable', 'string', 'max:2000'],
            'respect_user_preferences' => ['nullable', 'boolean'],
            'exclude_actor' => ['nullable', 'boolean'],
            'recipient_roles' => ['nullable', 'array'],
            'recipient_roles.*' => ['integer', 'exists:roles,id'],
            'recipient_users' => ['nullable', 'array'],
            'recipient_users.*' => ['integer', 'exists:users,id'],
            'recipient_dynamic' => ['nullable', 'array'],
            'recipient_dynamic.*' => ['string', 'max:120'],
        ]);

        $data['enabled'] = $request->boolean('enabled');
        $data['respect_user_preferences'] = $request->boolean('respect_user_preferences', true);
        $data['exclude_actor'] = $request->boolean('exclude_actor', true);
        $data['repeat_every_minutes'] = $data['repeat_policy'] === 'minutes'
            ? (int) $data['repeat_every_minutes']
            : null;
        unset($data['recipient_roles'], $data['recipient_users'], $data['recipient_dynamic']);

        return $data;
    }

    protected function syncRecipients(NotificationEventRule $rule, Request $request): void
    {
        $rows = [];

        foreach ((array) $request->input('recipient_roles', []) as $roleId) {
            if ($roleId !== '') {
                $rows[] = ['recipient_type' => 'role', 'recipient_value' => (string) (int) $roleId];
            }
        }

        foreach ((array) $request->input('recipient_users', []) as $userId) {
            if ($userId !== '') {
                $rows[] = ['recipient_type' => 'user', 'recipient_value' => (string) (int) $userId];
            }
        }

        $allowedDynamic = array_keys($this->registry->get($rule->event_key)['dynamic_recipients'] ?? []);
        foreach ((array) $request->input('recipient_dynamic', []) as $dynamic) {
            if (in_array($dynamic, $allowedDynamic, true)) {
                $rows[] = ['recipient_type' => 'dynamic', 'recipient_value' => $dynamic];
            }
        }

        $rule->recipients()->delete();
        foreach (collect($rows)->unique(fn ($row) => $row['recipient_type'] . ':' . $row['recipient_value']) as $row) {
            $rule->recipients()->create($row);
        }
    }
}
