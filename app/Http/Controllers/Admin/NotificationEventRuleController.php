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
        $rulesByEvent = $rules->groupBy('event_key')->map(fn ($group) => $group->first());

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);
        $events = $this->registry->all();

        return view('admin.notification_rules.index', compact('rules', 'rulesByEvent', 'roles', 'users', 'events'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        $data = $this->validated($request);
        $data['updated_by'] = auth()->id();
        $isNew = ! NotificationEventRule::query()->where('event_key', $data['event_key'])->exists();
        $rule = NotificationEventRule::query()->firstOrNew([
            'event_key' => $data['event_key'],
        ]);
        $before = $rule->exists ? $this->ruleSnapshot($rule->loadMissing('recipients')) : null;
        $rule->fill($data);
        $rule->created_by = $rule->created_by ?: auth()->id();
        $rule->updated_by = auth()->id();
        $rule->save();
        $this->syncRecipients($rule, $request);
        $rule->load('recipients');
        $this->logRuleChange($rule, $isNew ? 'created' : 'updated', $before, $this->ruleSnapshot($rule));

        return redirect()
            ->route('admin.notification-rules.index')
            ->with('success', 'Notification updated.');
    }

    public function update(Request $request, NotificationEventRule $notificationRule)
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        $notificationRule->load('recipients');
        $before = $this->ruleSnapshot($notificationRule);
        $data = $this->validated($request);
        $data['updated_by'] = auth()->id();

        $notificationRule->update($data);
        $this->syncRecipients($notificationRule, $request);
        $notificationRule->load('recipients');
        $this->logRuleChange($notificationRule, 'updated', $before, $this->ruleSnapshot($notificationRule));

        return redirect()
            ->route('admin.notification-rules.index')
            ->with('success', 'Notification rule updated.');
    }

    public function destroy(NotificationEventRule $notificationRule)
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        $notificationRule->load('recipients');
        $before = $this->ruleSnapshot($notificationRule);
        $this->logRuleChange($notificationRule, 'deleted', $before, null);
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
        $eventKey = (string) $request->input('event_key');
        $eventMeta = $this->registry->get($eventKey) ?? [];

        $data = $request->validate([
            'event_key' => ['required', Rule::in($eventKeys)],
            'name' => ['nullable', 'string', 'max:255'],
            'enabled' => ['nullable', 'boolean'],
            'severity' => ['nullable', Rule::in($severities)],
            'repeat_policy' => ['nullable', Rule::in($repeatPolicies)],
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
        $data['severity'] = $data['severity'] ?? ($eventMeta['default_severity'] ?? 'info');
        $data['repeat_policy'] = $data['repeat_policy'] ?? 'event_default';
        $data['title_template'] = $data['title_template'] ?? ($eventMeta['default_title'] ?? null);
        $data['message_template'] = $data['message_template'] ?? ($eventMeta['default_message'] ?? null);
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

    protected function ruleSnapshot(NotificationEventRule $rule): array
    {
        return [
            'event_key' => $rule->event_key,
            'name' => $rule->name,
            'enabled' => (bool) $rule->enabled,
            'respect_user_preferences' => (bool) $rule->respect_user_preferences,
            'exclude_actor' => (bool) $rule->exclude_actor,
            'recipients' => $rule->recipients
                ->map(fn ($recipient) => [
                    'type' => $recipient->recipient_type,
                    'value' => (string) $recipient->recipient_value,
                ])
                ->sortBy(fn ($recipient) => $recipient['type'] . ':' . $recipient['value'])
                ->values()
                ->all(),
        ];
    }

    protected function logRuleChange(NotificationEventRule $rule, string $event, ?array $before, ?array $after): void
    {
        activity('notification_rules')
            ->causedBy(auth()->user())
            ->performedOn($rule)
            ->event($event)
            ->withProperties(array_filter([
                'before' => $before,
                'after' => $after,
            ], fn ($value) => $value !== null))
            ->log('notification_rule_' . $event);
    }
}
