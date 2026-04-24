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
        $recipientSummary = function ($rule) use ($roles, $users, $events) {
            if (! $rule) {
                return 'Nobody selected yet';
            }

            $roleNames = $roles->pluck('name', 'id');
            $userNames = $users->pluck('name', 'id');
            $dynamicLabels = $events[$rule->event_key]['dynamic_recipients'] ?? [];

            $summary = $rule->recipients->map(function ($recipient) use ($roleNames, $userNames, $dynamicLabels) {
                if ($recipient->recipient_type === 'role') {
                    return $roleNames[(int) $recipient->recipient_value] ?? $recipient->recipient_value;
                }

                if ($recipient->recipient_type === 'user') {
                    return $userNames[(int) $recipient->recipient_value] ?? $recipient->recipient_value;
                }

                return $dynamicLabels[$recipient->recipient_value] ?? $recipient->recipient_value;
            })->filter()->implode(', ');

            return $summary !== '' ? $summary : 'Nobody selected yet';
        };
    @endphp

    <style>
        .notification-rule-page .card,
        .notification-rule-page .accordion-item {
            border-radius: 10px;
        }

        .notification-rule-page select[multiple] {
            min-height: 150px;
        }

        .notification-rule-page .rule-summary {
            font-size: 13px;
        }
    </style>

    <div class="container-fluid notification-rule-page py-3 pb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Notification Events</h4>
                <div class="text-muted small">
                    Simple setup: choose who should get each notification and whether it is on or off.
                </div>
            </div>
        </div>

        <div class="card bg-gradient">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Events</strong>
                <span class="text-muted small">{{ count($events) }} available</span>
            </div>
            <div class="card-body">
                <div class="accordion" id="notificationEventsAccordion">
                    @foreach($events as $eventKey => $meta)
                        @php
                            $rule = $rulesByEvent[$eventKey] ?? null;
                            $collapseId = 'notificationEventCollapse' . md5($eventKey);
                            $saveFormId = 'notificationRuleForm' . md5($eventKey);
                        @endphp

                        <div class="accordion-item bg-transparent border-secondary mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-transparent text-light" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}">
                                    <span class="me-2 badge bg-{{ $rule?->enabled ? 'success' : 'secondary' }}">
                                        {{ $rule?->enabled ? 'On' : 'Off' }}
                                    </span>
                                    <span class="me-3">{{ $meta['label'] }}</span>
                                    <span class="text-muted small rule-summary">{{ $recipientSummary($rule) }}</span>
                                </button>
                            </h2>
                            <div id="{{ $collapseId }}" class="accordion-collapse collapse" data-bs-parent="#notificationEventsAccordion">
                                <div class="accordion-body">
                                    <form id="{{ $saveFormId }}" method="POST" action="{{ $rule ? route('admin.notification-rules.update', $rule) : route('admin.notification-rules.store') }}">
                                        @csrf
                                        @if($rule)
                                            @method('PUT')
                                        @endif

                                        @include('admin.notification_rules.partials.form', [
                                            'rule' => $rule,
                                            'eventKey' => $eventKey,
                                            'events' => $events,
                                            'roles' => $roles,
                                            'users' => $users,
                                        ])

                                    </form>

                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                                        <button class="btn btn-outline-primary" type="submit" form="{{ $saveFormId }}">
                                            Save Notification
                                        </button>

                                            @if($rule)
                                                <form method="POST" action="{{ route('admin.notification-rules.destroy', $rule) }}" class="mb-0">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-outline-danger"
                                                            type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#useConfirmDelete"
                                                            data-title="Delete Notification">
                                                        Delete Notification
                                                    </button>
                                                </form>
                                            @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @include('components.delete')
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('useConfirmDelete');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            let deleteForm = null;

            if (!modal || !confirmDeleteBtn) {
                return;
            }

            modal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                deleteForm = button ? button.closest('form') : null;

                const title = button ? button.getAttribute('data-title') : null;
                const modalTitle = modal.querySelector('#confirmDeleteLabel');

                if (modalTitle) {
                    modalTitle.textContent = title || 'Delete Confirmation';
                }
            });

            confirmDeleteBtn.addEventListener('click', function () {
                if (deleteForm) {
                    deleteForm.submit();
                }
            });
        });
    </script>
@endsection
