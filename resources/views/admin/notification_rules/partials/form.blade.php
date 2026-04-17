@php
    $selectedEvent = old('event_key', $rule?->event_key ?? array_key_first($events));
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

<div class="row g-3">
    <div class="col-lg-4">
        <label class="form-label">Event</label>
        <select name="event_key" class="form-select js-event-select">
            @foreach($events as $key => $meta)
                <option value="{{ $key }}" @selected($selectedEvent === $key)>{{ $meta['label'] }} ({{ $key }})</option>
            @endforeach
        </select>
        @error('event_key')<div class="text-danger small">{{ $message }}</div>@enderror
        <div class="text-muted event-help mt-1 js-event-description">{{ $eventMeta['description'] ?? '' }}</div>
    </div>

    <div class="col-lg-4">
        <label class="form-label">Rule name</label>
        <input type="text" class="form-control" name="name" value="{{ old('name', $rule?->name) }}" placeholder="Optional name">
        @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-lg-2">
        <label class="form-label">Severity</label>
        <select name="severity" class="form-select">
            @foreach($severityOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('severity', $rule?->severity ?? ($eventMeta['default_severity'] ?? 'info')) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-2 d-flex align-items-end">
        <div class="form-check">
            <input type="hidden" name="enabled" value="0">
            <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled_{{ $rule?->id ?? 'new' }}" @checked(old('enabled', $rule?->enabled ?? true))>
            <label class="form-check-label" for="enabled_{{ $rule?->id ?? 'new' }}">Enabled</label>
        </div>
    </div>

    <div class="col-lg-6">
        <label class="form-label">Title template</label>
        <input type="text" class="form-control" name="title_template" value="{{ old('title_template', $rule?->title_template ?? ($eventMeta['default_title'] ?? '')) }}">
        @error('title_template')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="col-lg-3">
        <label class="form-label">Repeat</label>
        <select name="repeat_policy" class="form-select">
            @foreach($repeatOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('repeat_policy', $rule?->repeat_policy ?? 'event_default') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-3">
        <label class="form-label">Every N minutes</label>
        <input type="number" class="form-control" name="repeat_every_minutes" min="1" max="43200" value="{{ old('repeat_every_minutes', $rule?->repeat_every_minutes) }}" placeholder="Only for Every N">
    </div>

    <div class="col-12">
        <label class="form-label">Message template</label>
        <textarea class="form-control" name="message_template">{{ old('message_template', $rule?->message_template ?? ($eventMeta['default_message'] ?? '')) }}</textarea>
        @error('message_template')<div class="text-danger small">{{ $message }}</div>@enderror
        <div class="text-muted small mt-1">
            Variables:
            <span class="js-event-variables">{{ implode(', ', array_map(fn ($v) => '{' . $v . '}', $eventMeta['variables'] ?? [])) }}</span>
        </div>
    </div>

    <div class="col-lg-4">
        <label class="form-label">Roles</label>
        <select name="recipient_roles[]" class="form-select" multiple>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected(in_array((string) $role->id, $selectedRoles, true))>{{ $role->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-4">
        <label class="form-label">Users</label>
        <select name="recipient_users[]" class="form-select" multiple>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(in_array((string) $user->id, $selectedUsers, true))>
                    {{ $user->name }}@if($user->email) ({{ $user->email }})@endif
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-4">
        <label class="form-label">Dynamic recipients</label>
        <select name="recipient_dynamic[]" class="form-select js-dynamic-recipient-select" multiple>
            @foreach(($eventMeta['dynamic_recipients'] ?? []) as $value => $label)
                <option value="{{ $value }}" @selected(in_array($value, $selectedDynamic, true))>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-lg-3">
        <div class="form-check">
            <input type="hidden" name="respect_user_preferences" value="0">
            <input class="form-check-input" type="checkbox" name="respect_user_preferences" value="1" id="respect_user_preferences_{{ $rule?->id ?? 'new' }}" @checked(old('respect_user_preferences', $rule?->respect_user_preferences ?? true))>
            <label class="form-check-label" for="respect_user_preferences_{{ $rule?->id ?? 'new' }}">Respect user mute settings</label>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="form-check">
            <input type="hidden" name="exclude_actor" value="0">
            <input class="form-check-input" type="checkbox" name="exclude_actor" value="1" id="exclude_actor_{{ $rule?->id ?? 'new' }}" @checked(old('exclude_actor', $rule?->exclude_actor ?? true))>
            <label class="form-check-label" for="exclude_actor_{{ $rule?->id ?? 'new' }}">Exclude actor</label>
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const events = @json($events);

            function refreshEventHelp(form) {
                const select = form.querySelector('.js-event-select');
                if (!select) return;

                const meta = events[select.value] || {};
                const desc = form.querySelector('.js-event-description');
                const vars = form.querySelector('.js-event-variables');
                const dynamic = form.querySelector('.js-dynamic-recipient-select');

                if (desc) desc.textContent = meta.description || '';
                if (vars) vars.textContent = (meta.variables || []).map(v => `{${v}}`).join(', ');
                if (dynamic) {
                    const selected = new Set(Array.from(dynamic.selectedOptions).map(o => o.value));
                    dynamic.innerHTML = '';
                    Object.entries(meta.dynamic_recipients || {}).forEach(([value, label]) => {
                        const option = new Option(label, value, selected.has(value), selected.has(value));
                        dynamic.add(option);
                    });
                }
            }

            document.querySelectorAll('form .js-event-select').forEach(select => {
                select.addEventListener('change', () => refreshEventHelp(select.closest('form')));
            });
        });
    </script>
@endonce
