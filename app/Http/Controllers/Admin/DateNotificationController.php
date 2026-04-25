<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DateNotification;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DateNotificationController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        $notifications = DateNotification::query()
            ->with('recipients')
            ->orderBy('run_month')
            ->orderBy('run_day')
            ->orderBy('name')
            ->get();

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.date_notifications.index', compact('notifications', 'roles', 'users'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        $data = $this->validated($request);
        $notification = DateNotification::query()->create(array_merge($data, [
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        $this->syncRecipients($notification, $request);

        return redirect()
            ->route('admin.date-notifications.index')
            ->with('success', 'Date notification created.');
    }

    public function update(Request $request, DateNotification $dateNotification)
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        $data = $this->validated($request);
        $dateNotification->update(array_merge($data, [
            'updated_by' => auth()->id(),
        ]));

        $this->syncRecipients($dateNotification, $request);

        return redirect()
            ->route('admin.date-notifications.index')
            ->with('success', 'Date notification updated.');
    }

    public function destroy(DateNotification $dateNotification)
    {
        abort_unless(auth()->user()?->roleIs('Admin'), 403);

        $dateNotification->delete();

        return redirect()
            ->route('admin.date-notifications.index')
            ->with('success', 'Date notification deleted.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'run_on' => ['required', 'date'],
            'repeat_mode' => ['required', 'in:yearly,once'],
            'enabled' => ['nullable', 'boolean'],
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'respect_user_preferences' => ['nullable', 'boolean'],
            'recipient_roles' => ['nullable', 'array'],
            'recipient_roles.*' => ['integer', 'exists:roles,id'],
            'recipient_users' => ['nullable', 'array'],
            'recipient_users.*' => ['integer', 'exists:users,id'],
            'recipient_dynamic' => ['nullable', 'array'],
            'recipient_dynamic.*' => ['in:all_users,system_admins'],
        ]);

        $runOn = Carbon::parse((string) $data['run_on']);

        $data['run_month'] = (int) $runOn->month;
        $data['run_day'] = (int) $runOn->day;
        $data['repeats_yearly'] = ($data['repeat_mode'] ?? 'yearly') === 'yearly';
        $data['run_year'] = $data['repeats_yearly'] ? null : (int) $runOn->year;
        $data['enabled'] = $request->boolean('enabled');
        $data['respect_user_preferences'] = $request->boolean('respect_user_preferences', true);

        unset($data['run_on'], $data['repeat_mode'], $data['recipient_roles'], $data['recipient_users'], $data['recipient_dynamic']);

        return $data;
    }

    protected function syncRecipients(DateNotification $notification, Request $request): void
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

        foreach ((array) $request->input('recipient_dynamic', []) as $dynamic) {
            if (in_array($dynamic, ['all_users', 'system_admins'], true)) {
                $rows[] = ['recipient_type' => 'dynamic', 'recipient_value' => $dynamic];
            }
        }

        $notification->recipients()->delete();

        foreach (collect($rows)->unique(fn ($row) => $row['recipient_type'] . ':' . $row['recipient_value']) as $row) {
            $notification->recipients()->create($row);
        }
    }
}
