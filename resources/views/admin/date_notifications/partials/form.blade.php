@php
    $notificationRecipients = $notification?->recipients ?? collect();
    $selectedRoles = collect(old('recipient_roles', $notificationRecipients->where('recipient_type', 'role')->pluck('recipient_value')->all()))
        ->map(fn ($id) => (string) $id)
        ->all();
    $selectedUsers = collect(old('recipient_users', $notificationRecipients->where('recipient_type', 'user')->pluck('recipient_value')->all()))
        ->map(fn ($id) => (string) $id)
        ->all();
    $selectedDynamic = collect(old('recipient_dynamic', $notificationRecipients->where('recipient_type', 'dynamic')->pluck('recipient_value')->all()))
        ->map(fn ($value) => (string) $value)
        ->all();
    $runOnValue = old(
        'run_on',
        $notification
            ? sprintf(
                '%04d-%02d-%02d',
                (int) ($notification->run_year ?: now()->year),
                (int) $notification->run_month,
                (int) $notification->run_day
            )
            : now()->format('Y-m-d')
    );
    $repeatMode = old('repeat_mode', ($notification && ! $notification->repeats_yearly) ? 'once' : 'yearly');
@endphp

<div class="row g-3">
    <div class="col-lg-6">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $notification?->name) }}" placeholder="Christmas, New Year, Company meeting">
    </div>

    <div class="col-lg-3">
        <label class="form-label">Date</label>
        <input type="date" name="run_on" class="form-control" value="{{ $runOnValue }}">
    </div>

    <div class="col-lg-3">
        <label class="form-label">Repeat</label>
        <select name="repeat_mode" class="form-select">
            <option value="yearly" @selected($repeatMode === 'yearly')>Every year</option>
            <option value="once" @selected($repeatMode === 'once')>One time only</option>
        </select>
        <div class="text-muted small mt-1">Choose whether this date repeats every year or runs only once.</div>
    </div>

    <div class="col-lg-3 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input type="hidden" name="enabled" value="0">
            <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled_{{ $notification?->id ?? 'new' }}" @checked(old('enabled', $notification?->enabled ?? true))>
            <label class="form-check-label" for="enabled_{{ $notification?->id ?? 'new' }}">Send this notification</label>
        </div>
    </div>

    <div class="col-lg-6">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="{{ old('title', $notification?->title) }}" placeholder="Optional short title">
    </div>

    <div class="col-lg-6">
        <label class="form-label">Message</label>
        <textarea name="message" class="form-control" rows="3" placeholder="Write the message people should receive.">{{ old('message', $notification?->message) }}</textarea>
    </div>

    <div class="col-lg-4">
        <label class="form-label">Send to roles</label>
        <select name="recipient_roles[]" class="form-select" multiple>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected(in_array((string) $role->id, $selectedRoles, true))>{{ $role->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-4">
        <label class="form-label">Send to people</label>
        <select name="recipient_users[]" class="form-select" multiple>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(in_array((string) $user->id, $selectedUsers, true))>
                    {{ $user->name }}@if($user->email) ({{ $user->email }})@endif
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-4">
        <label class="form-label">Automatically include</label>
        <select name="recipient_dynamic[]" class="form-select" multiple>
            <option value="all_users" @selected(in_array('all_users', $selectedDynamic, true))>All users</option>
            <option value="system_admins" @selected(in_array('system_admins', $selectedDynamic, true))>System admins</option>
        </select>
    </div>

    <div class="col-lg-12">
        <div class="form-check">
            <input type="hidden" name="respect_user_preferences" value="0">
            <input class="form-check-input" type="checkbox" name="respect_user_preferences" value="1" id="respect_user_preferences_{{ $notification?->id ?? 'new' }}" @checked(old('respect_user_preferences', $notification?->respect_user_preferences ?? true))>
            <label class="form-check-label" for="respect_user_preferences_{{ $notification?->id ?? 'new' }}">Respect each user's mute settings</label>
        </div>
    </div>
</div>
