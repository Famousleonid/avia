@extends('admin.master')

@section('style')
    <style>
        .content {
            overflow-y: auto !important;
            height: 100% !important;
        }

        .content-inner {
            display: block !important;
            height: auto !important;
            min-height: 100%;
            overflow: visible !important;
        }

        .date-notification-page .card,
        .date-notification-page .accordion-item {
            border-radius: 10px;
        }

        .date-notification-page {
            min-height: calc(100vh - 90px);
            padding-bottom: 7rem !important;
        }

        .date-notification-page .card-body,
        .date-notification-page .accordion,
        .date-notification-page .accordion-collapse,
        .date-notification-page .accordion-body {
            overflow: visible;
        }

        .date-notification-page select[multiple] {
            min-height: 150px;
        }

        .date-notification-page .accordion-button {
            align-items: flex-start;
            gap: 0.75rem;
        }

        .date-notification-page .accordion-title {
            flex: 1 1 auto;
            min-width: 0;
        }

        .date-notification-page .accordion-title-main {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 0.75rem;
            align-items: center;
        }

        .date-notification-page .accordion-title-summary {
            font-size: 13px;
            word-break: break-word;
        }
    </style>
@endsection

@section('content')
    @php
        $createOpen = $errors->any() || $notifications->isEmpty();

        $recipientSummary = function ($notification) use ($roles, $users) {
            $roleNames = $roles->pluck('name', 'id');
            $userNames = $users->pluck('name', 'id');

            return $notification->recipients->map(function ($recipient) use ($roleNames, $userNames) {
                if ($recipient->recipient_type === 'role') {
                    return $roleNames[(int) $recipient->recipient_value] ?? $recipient->recipient_value;
                }

                if ($recipient->recipient_type === 'user') {
                    return $userNames[(int) $recipient->recipient_value] ?? $recipient->recipient_value;
                }

                return match ($recipient->recipient_value) {
                    'all_users' => 'All users',
                    'system_admins' => 'System admins',
                    default => $recipient->recipient_value,
                };
            })->implode(', ');
        };

        $dateSummary = function ($notification) {
            $baseDate = \Carbon\Carbon::create(
                $notification->repeats_yearly ? now()->year : (int) $notification->run_year,
                (int) $notification->run_month,
                (int) $notification->run_day
            )->format('M d');

            return $notification->repeats_yearly
                ? $baseDate . ' every year'
                : $baseDate . ', ' . (int) $notification->run_year . ' only';
        };
    @endphp

    <div class="container-fluid date-notification-page py-3 pb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Date Notifications</h4>
                <div class="text-muted small">
                    Create reminders for a specific date. They can repeat every year or run only once.
                </div>
            </div>
        </div>

        <div class="card bg-gradient">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Date notifications</strong>
                <span class="text-muted small">{{ $notifications->count() }} total</span>
            </div>
            <div class="card-body">
                <div class="accordion" id="dateNotificationsAccordion">
                    <div class="accordion-item bg-transparent border-secondary mb-2">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $createOpen ? '' : 'collapsed' }} bg-transparent text-light" type="button" data-bs-toggle="collapse" data-bs-target="#dateNotificationCreate" aria-expanded="{{ $createOpen ? 'true' : 'false' }}">
                                <span class="badge bg-primary">New</span>
                                <span class="accordion-title">
                                    <span class="accordion-title-main">
                                        <span>Add date notification</span>
                                    </span>
                                    <span class="text-muted small accordion-title-summary">
                                        Create a one-time or repeating date reminder.
                                    </span>
                                </span>
                            </button>
                        </h2>
                        <div id="dateNotificationCreate" class="accordion-collapse collapse {{ $createOpen ? 'show' : '' }}" data-bs-parent="#dateNotificationsAccordion">
                            <div class="accordion-body">
                                <form method="POST" action="{{ route('admin.date-notifications.store') }}">
                                    @csrf
                                    @include('admin.date_notifications.partials.form', [
                                        'notification' => null,
                                        'roles' => $roles,
                                        'users' => $users,
                                    ])
                                    <button class="btn btn-outline-primary mt-3" type="submit">Save Notification</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    @forelse($notifications as $notification)
                        @php
                            $collapseId = 'dateNotificationCollapse' . $notification->id;
                            $saveFormId = 'dateNotificationForm' . $notification->id;
                        @endphp

                        <div class="accordion-item bg-transparent border-secondary mb-2">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-transparent text-light" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}">
                                    <span class="badge bg-{{ $notification->enabled ? 'success' : 'secondary' }}">
                                        {{ $notification->enabled ? 'On' : 'Off' }}
                                    </span>
                                    <span class="accordion-title">
                                        <span class="accordion-title-main">
                                            <span>{{ $notification->name }}</span>
                                            <span class="text-muted small">{{ $dateSummary($notification) }}</span>
                                        </span>
                                        <span class="text-muted small accordion-title-summary">{{ $recipientSummary($notification) ?: 'Nobody selected yet' }}</span>
                                    </span>
                                </button>
                            </h2>
                            <div id="{{ $collapseId }}" class="accordion-collapse collapse" data-bs-parent="#dateNotificationsAccordion">
                                <div class="accordion-body">
                                    <form id="{{ $saveFormId }}" method="POST" action="{{ route('admin.date-notifications.update', $notification) }}">
                                        @csrf
                                        @method('PUT')
                                        @include('admin.date_notifications.partials.form', [
                                            'notification' => $notification,
                                            'roles' => $roles,
                                            'users' => $users,
                                        ])
                                    </form>

                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                                        <button class="btn btn-outline-primary" type="submit" form="{{ $saveFormId }}">Save Changes</button>

                                        <form method="POST" action="{{ route('admin.date-notifications.destroy', $notification) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    class="btn btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#useConfirmDelete"
                                                    data-title="Delete Notification">
                                                Delete Notification
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">No date notifications yet.</div>
                    @endforelse
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
