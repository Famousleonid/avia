@extends('admin.master')

@section('style')
    <style>
        .access-page {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            height: 100%;
            min-height: 0;
            overflow: hidden;
            padding: 12px 14px;
        }

        .access-shell {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            height: 100%;
            max-width: 1280px;
            min-height: 0;
            width: 100%;
        }

        .access-title {
            font-size: 1rem;
            line-height: 1.2;
            margin: 0;
        }

        .access-layout {
            align-items: stretch;
            display: grid;
            flex: 1 1 auto;
            gap: 16px;
            grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
            min-height: 0;
            overflow: hidden;
        }

        .access-left,
        .access-column {
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            overflow: hidden;
        }

        .access-left {
            background: var(--bs-body-bg);
            min-height: 0;
            overflow: auto;
        }

        .access-group-button {
            align-items: center;
            background: var(--bs-tertiary-bg);
            border: 0;
            border-bottom: 1px solid var(--bs-border-color);
            color: var(--bs-info);
            display: flex;
            font-size: .82rem;
            font-weight: 600;
            gap: 8px;
            justify-content: space-between;
            min-height: 34px;
            padding: 7px 10px;
            text-align: left;
            width: 100%;
        }

        .access-group-button[aria-expanded="true"] .access-chevron {
            transform: rotate(180deg);
        }

        .access-chevron {
            transition: transform .16s ease;
        }

        .access-feature-button {
            align-items: center;
            background: transparent;
            border: 0;
            border-bottom: 1px solid var(--bs-border-color);
            color: inherit;
            display: flex;
            font-size: .84rem;
            gap: 10px;
            justify-content: space-between;
            min-height: 36px;
            padding: 7px 10px;
            text-align: left;
            width: 100%;
        }

        .access-feature-button:hover,
        .access-feature-button.active {
            background: rgba(var(--bs-info-rgb), .12);
        }

        .access-feature-label {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .access-feature-count {
            flex: 0 0 auto;
        }

        .access-panels {
            height: 100%;
            min-height: 0;
            overflow: hidden;
        }

        .access-panel {
            display: none;
            height: 100%;
            min-height: 0;
        }

        .access-panel.active {
            display: flex;
            flex-direction: column;
        }

        .access-panel-header {
            align-items: center;
            background: var(--bs-tertiary-bg);
            border-bottom: 1px solid var(--bs-border-color);
            display: flex;
            gap: 12px;
            justify-content: space-between;
            min-height: 44px;
            padding: 10px 12px;
        }

        .access-panel-header h2 {
            font-size: .98rem;
            line-height: 1.2;
            margin: 0;
        }

        .access-panel-body {
            display: grid;
            flex: 1 1 auto;
            gap: 16px;
            grid-template-columns: minmax(300px, .9fr) minmax(340px, 1.1fr);
            min-height: 0;
            overflow: hidden;
        }

        .access-column {
            background: var(--bs-body-bg);
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .access-column-header {
            align-items: center;
            background: var(--bs-tertiary-bg);
            border-bottom: 1px solid var(--bs-border-color);
            display: flex;
            flex: 0 0 auto;
            gap: 8px;
            justify-content: space-between;
            min-height: 38px;
            padding: 8px 10px;
        }

        .access-column-title {
            color: var(--bs-info);
            font-size: .82rem;
            font-weight: 600;
            line-height: 1.2;
            margin: 0;
        }

        .access-assigned-list {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
        }

        .access-assigned-row {
            align-items: center;
            border-bottom: 1px solid var(--bs-border-color);
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(0, 1fr) auto;
            min-height: 42px;
            padding: 7px 8px;
        }

        .access-assigned-row:last-child {
            border-bottom: 0;
        }

        .access-assigned-meta {
            color: var(--bs-secondary-color);
            font-size: .72rem;
            line-height: 1.2;
            margin-top: 2px;
        }

        .access-add-form {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            padding: 12px;
        }

        .access-add-toolbar {
            align-items: end;
            display: grid;
            gap: 10px;
            grid-template-columns: minmax(180px, 260px) auto;
            flex: 0 0 auto;
            margin-bottom: 12px;
        }

        .access-role-select {
            max-width: 260px;
        }

        .access-user-grid {
            display: grid;
            flex: 1 1 auto;
            gap: 8px;
            grid-template-columns: 1fr;
            min-height: 0;
            overflow: auto;
            padding-right: 4px;
        }

        .access-user-option {
            align-items: flex-start;
            border: 1px solid var(--bs-border-color);
            border-radius: 6px;
            display: flex;
            gap: 8px;
            min-height: 38px;
            padding: 7px 8px;
        }

        .access-user-option:has(input:checked) {
            border-color: var(--bs-info);
            box-shadow: 0 0 0 1px rgba(var(--bs-info-rgb), .25);
        }

        .access-user-option input {
            flex: 0 0 auto;
            margin-top: 3px;
        }

        .access-user-name {
            display: block;
            font-size: .84rem;
            font-weight: 600;
            line-height: 1.15;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .access-user-meta {
            color: var(--bs-secondary-color);
            font-size: .75rem;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .access-empty {
            color: var(--bs-secondary-color);
            padding: 14px;
        }

        .access-remove-button {
            align-items: center;
            display: inline-flex;
            height: 32px;
            justify-content: center;
            padding: 0;
            width: 32px;
        }

        .access-role-inline {
            color: var(--bs-secondary-color);
            font-weight: 500;
        }

        @media (max-width: 900px) {
            .access-page {
                overflow: auto;
            }

            .access-layout {
                grid-template-columns: 1fr;
                overflow: visible;
            }

            .access-panel-body {
                grid-template-columns: 1fr;
                overflow: visible;
            }

            .access-left {
                max-height: none;
            }

            .access-panel,
            .access-panel.active {
                height: auto;
            }
        }

        @media (max-width: 560px) {
            .access-add-toolbar {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
    @php
        $firstFeatureKey = array_key_first($features);
    @endphp

    <div class="access-page">
        <div class="access-shell">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h1 class="access-title">Access</h1>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger py-2">
                    {{ $errors->first() }}
                </div>
            @endif

            @if($features === [])
                <div class="alert alert-warning mb-0">No access sections configured.</div>
            @else
                <div class="access-layout">
                    <aside class="access-left" aria-label="Access sections">
                        <div class="accordion accordion-flush" id="access-groups">
                            @foreach($featureGroups as $groupName => $groupFeatures)
                                @php
                                    $groupDomId = 'access-group-'.\Illuminate\Support\Str::slug((string) $groupName);
                                    $groupContainsFirst = $groupFeatures->has($firstFeatureKey);
                                @endphp
                                <section class="accordion-item border-0">
                                    <h2 class="accordion-header">
                                        <button class="access-group-button"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#{{ $groupDomId }}"
                                                aria-expanded="{{ $groupContainsFirst ? 'true' : 'false' }}"
                                                aria-controls="{{ $groupDomId }}">
                                            <span>{{ $groupName }}</span>
                                            <i class="bi bi-chevron-down access-chevron"></i>
                                        </button>
                                    </h2>
                                    <div id="{{ $groupDomId }}"
                                         class="accordion-collapse collapse {{ $groupContainsFirst ? 'show' : '' }}">
                                        @foreach($groupFeatures as $featureKey => $feature)
                                            @php
                                                $records = $accessRecords->get($featureKey, collect());
                                            @endphp
                                            <button type="button"
                                                    class="access-feature-button {{ $featureKey === $firstFeatureKey ? 'active' : '' }}"
                                                    data-access-feature="{{ $featureKey }}"
                                                    aria-controls="{{ $feature['dom_id'] }}">
                                                <span class="access-feature-label">
                                                    <i class="bi bi-person-lock me-1"></i>{{ $feature['label'] }}
                                                </span>
                                                <span class="badge text-bg-secondary access-feature-count">{{ $records->count() }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </aside>

                    <main class="access-panels">
                        @foreach($features as $featureKey => $feature)
                            @php
                                $records = $accessRecords->get($featureKey, collect());
                                $assignedUserIds = $assignedUserIdsByFeature[$featureKey] ?? [];
                                $availableUsers = $users->reject(fn ($user) => in_array((int) $user->id, $assignedUserIds, true));
                            @endphp

                            <section id="{{ $feature['dom_id'] }}"
                                     class="access-panel {{ $featureKey === $firstFeatureKey ? 'active' : '' }}"
                                     data-access-panel="{{ $featureKey }}">
                                <div class="access-panel-header">
                                    <div>
                                        <h2>{{ $feature['label'] }}</h2>
                                        <div class="text-muted small">{{ $feature['group'] }}</div>
                                    </div>
                                    <span class="badge text-bg-secondary">{{ $records->count() }}</span>
                                </div>

                                <div class="access-panel-body">
                                    <section class="access-column">
                                        <div class="access-column-header">
                                            <h3 class="access-column-title">Allowed users</h3>
                                            <span class="badge text-bg-secondary">{{ $records->count() }}</span>
                                        </div>

                                        @if($records->isEmpty())
                                            <div class="access-empty">No users</div>
                                        @else
                                            <div class="access-assigned-list">
                                                @foreach($records as $access)
                                                    <div class="access-assigned-row">
                                                        <div class="min-w-0">
                                                            <div class="access-user-name">
                                                                {{ $access->user?->selection_name ?? 'Deleted user' }}@if($access->user?->role)<span class="access-role-inline"> / {{ $access->user->role->name }}</span>@endif
                                                            </div>
                                                            <div class="access-assigned-meta">
                                                                {{ format_project_date($access->created_at) }}
                                                                @if($access->grantedBy?->selection_name)
                                                                    by {{ $access->grantedBy->selection_name }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <form method="POST" action="{{ route('admin.access.destroy', $access) }}" class="m-0" data-no-spinner>
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm access-remove-button" title="Remove" aria-label="Remove">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </section>

                                    <section class="access-column">
                                        <div class="access-column-header">
                                            <h3 class="access-column-title">Add users</h3>
                                            <span class="badge text-bg-secondary">{{ $availableUsers->count() }}</span>
                                        </div>

                                        <form method="POST"
                                              action="{{ route('admin.access.store') }}"
                                              class="access-add-form"
                                              data-no-spinner
                                              data-access-form="{{ $featureKey }}">
                                            @csrf
                                            <input type="hidden" name="feature_key" value="{{ $featureKey }}">

                                            <div class="access-add-toolbar">
                                                <div>
                                                    <select id="access-role-{{ $feature['dom_id'] }}"
                                                            class="form-select form-select-sm access-role-select"
                                                            data-access-role-select="{{ $featureKey }}"
                                                            @disabled($availableUsers->isEmpty())>
                                                        <option value="">Select role</option>
                                                        @foreach($roles as $role)
                                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <button type="submit"
                                                        class="btn btn-primary btn-sm access-submit"
                                                        @disabled(true)>
                                                    <i class="bi bi-plus-lg me-1"></i>Add selected
                                                </button>
                                            </div>

                                            @if($availableUsers->isEmpty())
                                                <div class="access-empty border rounded-2 p-2">All users already have this access.</div>
                                            @else
                                                <div class="access-user-grid">
                                                    @foreach($availableUsers as $user)
                                                        <label class="access-user-option" data-role-id="{{ $user->role_id ?? '' }}">
                                                            <input type="checkbox"
                                                                   class="form-check-input access-user-checkbox"
                                                                   name="user_ids[]"
                                                                   value="{{ $user->id }}">
                                                            <span class="min-w-0">
                                                                <span class="access-user-name">
                                                                    {{ $user->selection_name }}@if($user->role)<span class="access-role-inline"> / {{ $user->role->name }}</span>@endif
                                                                </span>
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </form>
                                    </section>
                                </div>
                            </section>
                        @endforeach
                    </main>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const buttons = Array.from(document.querySelectorAll('[data-access-feature]'));
            const panels = Array.from(document.querySelectorAll('[data-access-panel]'));

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    const featureKey = button.dataset.accessFeature;

                    buttons.forEach((candidate) => {
                        candidate.classList.toggle('active', candidate === button);
                    });

                    panels.forEach((panel) => {
                        panel.classList.toggle('active', panel.dataset.accessPanel === featureKey);
                    });
                });
            });

            document.querySelectorAll('[data-access-role-select]').forEach((select) => {
                select.addEventListener('change', () => {
                    const form = select.closest('[data-access-form]');
                    const roleId = select.value;

                    if (!form) {
                        return;
                    }

                    form.querySelectorAll('.access-user-checkbox').forEach((checkbox) => {
                        const option = checkbox.closest('[data-role-id]');
                        checkbox.checked = roleId !== '' && option?.dataset.roleId === roleId;
                    });

                    updateSubmitState(form);
                });
            });

            document.querySelectorAll('[data-access-form]').forEach((form) => {
                form.addEventListener('change', (event) => {
                    if (event.target.classList.contains('access-user-checkbox')) {
                        updateSubmitState(form);
                    }
                });

                updateSubmitState(form);
            });

            function updateSubmitState(form) {
                const submit = form.querySelector('.access-submit');
                const hasCheckedUser = Array.from(form.querySelectorAll('.access-user-checkbox'))
                    .some((checkbox) => checkbox.checked);

                if (submit) {
                    submit.disabled = !hasCheckedUser;
                }
            }
        });
    </script>
@endsection
