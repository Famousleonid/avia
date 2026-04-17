@extends('admin.master')

@section('style')
    <style>
        .content {
            overflow-y: auto !important;
        }

        .content-inner {
            display: block !important;
            height: auto !important;
            min-height: 100%;
        }
    </style>
@endsection

@section('content')
    @php
        $severityOptions = ['info' => 'Info', 'success' => 'Success', 'warning' => 'Warning', 'danger' => 'Danger'];
        $repeatOptions = [
            'event_default' => 'Event default',
            'once' => 'Once',
            'daily' => 'Once per day',
            'minutes' => 'Every N minutes',
        ];

        $recipientSummary = function ($rule) use ($roles, $users, $events) {
            $roleNames = $roles->pluck('name', 'id');
            $userNames = $users->pluck('name', 'id');
            $dynamicLabels = $events[$rule->event_key]['dynamic_recipients'] ?? [];

            return $rule->recipients->map(function ($recipient) use ($roleNames, $userNames, $dynamicLabels) {
                if ($recipient->recipient_type === 'role') {
                    return 'Role: ' . ($roleNames[(int) $recipient->recipient_value] ?? $recipient->recipient_value);
                }
                if ($recipient->recipient_type === 'user') {
                    return 'User: ' . ($userNames[(int) $recipient->recipient_value] ?? $recipient->recipient_value);
                }

                return 'Dynamic: ' . ($dynamicLabels[$recipient->recipient_value] ?? $recipient->recipient_value);
            })->implode(', ');
        };
    @endphp

    <style>
        .notification-rule-page .card,
        .notification-rule-page .accordion-item {
            border-radius: 8px;
        }
        .notification-rule-page select[multiple] {
            min-height: 150px;
        }
        .notification-rule-page textarea {
            min-height: 92px;
        }
        .notification-rule-page .event-help {
            font-size: 12px;
        }
    </style>

    <div class="container-fluid notification-rule-page py-3 pb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Notification Rules</h4>
                <div class="text-muted small">
                    Configure who receives system event notifications. Delivery uses the existing bell and notifications page.
                </div>
            </div>
        </div>

        <div class="card bg-gradient mb-3">
            <div class="card-header">
                <strong>Create rule</strong>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.notification-rules.store') }}">
                    @csrf
                    @include('admin.notification_rules.partials.form', [
                        'rule' => null,
                        'events' => $events,
                        'roles' => $roles,
                        'users' => $users,
                        'severityOptions' => $severityOptions,
                        'repeatOptions' => $repeatOptions,
                    ])
                    <button class="btn btn-outline-primary mt-3" type="submit">Create Rule</button>
                </form>
            </div>
        </div>

        <div class="card bg-gradient">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Rules</strong>
                <span class="text-muted small">{{ $rules->count() }} total</span>
            </div>
            <div class="card-body">
                @if($rules->isEmpty())
                    <div class="text-muted">No notification rules yet. Existing hard-coded recipients are still used.</div>
                @else
                    <div class="accordion" id="notificationRulesAccordion">
                        @foreach($rules as $rule)
                            @php
                                $meta = $events[$rule->event_key] ?? null;
                                $collapseId = 'notificationRuleCollapse' . $rule->id;
                            @endphp
                            <div class="accordion-item bg-transparent border-secondary mb-2">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed bg-transparent text-light" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}">
                                        <span class="me-2 badge bg-{{ $rule->enabled ? 'success' : 'secondary' }}">
                                            {{ $rule->enabled ? 'Active' : 'Disabled' }}
                                        </span>
                                        <span class="me-3">{{ $rule->name ?: ($meta['label'] ?? $rule->event_key) }}</span>
                                        <span class="text-muted small">{{ $rule->event_key }}</span>
                                    </button>
                                </h2>
                                <div id="{{ $collapseId }}" class="accordion-collapse collapse" data-bs-parent="#notificationRulesAccordion">
                                    <div class="accordion-body">
                                        <div class="row g-3 mb-3">
                                            <div class="col-lg-3">
                                                <div class="text-muted small">Severity</div>
                                                <div>{{ $severityOptions[$rule->severity] ?? $rule->severity }}</div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="text-muted small">Repeat</div>
                                                <div>
                                                    {{ $repeatOptions[$rule->repeat_policy] ?? $rule->repeat_policy }}
                                                    @if($rule->repeat_policy === 'minutes')
                                                        ({{ $rule->repeat_every_minutes }} min)
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="text-muted small">Recipients</div>
                                                <div>{{ $recipientSummary($rule) ?: 'No recipients selected' }}</div>
                                            </div>
                                        </div>

                                        <form method="POST" action="{{ route('admin.notification-rules.update', $rule) }}">
                                            @csrf
                                            @method('PUT')
                                            @include('admin.notification_rules.partials.form', [
                                                'rule' => $rule,
                                                'events' => $events,
                                                'roles' => $roles,
                                                'users' => $users,
                                                'severityOptions' => $severityOptions,
                                                'repeatOptions' => $repeatOptions,
                                            ])
                                            <div class="d-flex gap-2 mt-3">
                                                <button class="btn btn-outline-primary" type="submit">Update Rule</button>
                                            </div>
                                        </form>
                                        <div class="mt-2">
                                            <form method="POST" action="{{ route('admin.notification-rules.destroy', $rule) }}" onsubmit="return confirm('Delete this notification rule?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
