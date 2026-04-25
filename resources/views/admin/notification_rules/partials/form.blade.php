@php
    $selectedEvent = old('event_key', $eventKey ?? $rule?->event_key ?? array_key_first($events));
    $eventMeta = $events[$selectedEvent] ?? reset($events);
    $ruleRecipients = $rule?->recipients ?? collect();
    $selectedRoles = collect(old('recipient_roles', $ruleRecipients->where('recipient_type', 'role')->pluck('recipient_value')->all()))
        ->map(fn ($id) => (string) $id)
        ->all();
    $selectedUsers = collect(old('recipient_users', $ruleRecipients->where('recipient_type', 'user')->pluck('recipient_value')->all()))
        ->map(fn ($id) => (string) $id)
        ->all();
    $selectedDynamic = collect(old('recipient_dynamic', $ruleRecipients->where('recipient_type', 'dynamic')->pluck('recipient_value')->all()))
        ->map(fn ($id) => (string) $id)
        ->all();
@endphp

<input type="hidden" name="event_key" value="{{ $selectedEvent }}">
<input type="hidden" name="severity" value="{{ old('severity', $rule?->severity ?? ($eventMeta['default_severity'] ?? 'info')) }}">
<input type="hidden" name="repeat_policy" value="{{ old('repeat_policy', $rule?->repeat_policy ?? 'event_default') }}">
<input type="hidden" name="repeat_every_minutes" value="{{ old('repeat_every_minutes', $rule?->repeat_every_minutes) }}">
<input type="hidden" name="title_template" value="{{ old('title_template', $rule?->title_template ?? ($eventMeta['default_title'] ?? '')) }}">
<input type="hidden" name="message_template" value="{{ old('message_template', $rule?->message_template ?? ($eventMeta['default_message'] ?? '')) }}">
<input type="hidden" name="name" value="{{ old('name', $rule?->name ?? ($eventMeta['label'] ?? 'Notification')) }}">

<div class="row g-3">
    <div class="col-lg-8">
        <div class="small text-muted">What happened</div>
        <div class="fw-semibold">{{ $eventMeta['label'] ?? $selectedEvent }}</div>
        <div class="text-muted small mt-1">{{ $eventMeta['description'] ?? '' }}</div>
    </div>

    <div class="col-lg-4 d-flex align-items-start justify-content-lg-end">
        <div class="form-check form-switch mt-1">
            <input type="hidden" name="enabled" value="0">
            <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled_{{ $selectedEvent }}" @checked(old('enabled', $rule?->enabled ?? false))>
            <label class="form-check-label" for="enabled_{{ $selectedEvent }}">Send this notification</label>
        </div>
    </div>

    <div class="col-lg-4">
        <label class="form-label">Send to roles</label>
        <select name="recipient_roles[]" class="form-select" multiple>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected(in_array((string) $role->id, $selectedRoles, true))>{{ $role->name }}</option>
            @endforeach
        </select>
        <div class="text-muted small mt-1">Examples: Manager, Admin, Technician.</div>
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
        <div class="text-muted small mt-1">Optional if a notification should go to someone specific.</div>
    </div>

    <div class="col-lg-4">
        <label class="form-label">Automatically include</label>
        <select name="recipient_dynamic[]" class="form-select" multiple>
            @forelse(($eventMeta['dynamic_recipients'] ?? []) as $value => $label)
                <option value="{{ $value }}" @selected(in_array($value, $selectedDynamic, true))>{{ $label }}</option>
            @empty
                <option value="" disabled>No automatic recipients for this event</option>
            @endforelse
        </select>
        <div class="text-muted small mt-1">Use this for built-in recipients like assigned user or system admins.</div>
    </div>

    <div class="col-lg-6">
        <div class="form-check">
            <input type="hidden" name="respect_user_preferences" value="0">
            <input class="form-check-input" type="checkbox" name="respect_user_preferences" value="1" id="respect_user_preferences_{{ $selectedEvent }}" @checked(old('respect_user_preferences', $rule?->respect_user_preferences ?? true))>
            <label class="form-check-label" for="respect_user_preferences_{{ $selectedEvent }}">Respect each user's mute settings</label>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="form-check">
            <input type="hidden" name="exclude_actor" value="0">
            <input class="form-check-input" type="checkbox" name="exclude_actor" value="1" id="exclude_actor_{{ $selectedEvent }}" @checked(old('exclude_actor', $rule?->exclude_actor ?? true))>
            <label class="form-check-label" for="exclude_actor_{{ $selectedEvent }}">Do not notify the person who triggered the event</label>
        </div>
    </div>
</div>
