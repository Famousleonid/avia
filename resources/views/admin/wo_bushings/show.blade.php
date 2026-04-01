@extends('admin.master')

@section('content')
    <style>
        .table-wrapper {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: auto;
            width: 100%;
            position: relative;
        }

        .table-scroll-container {
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: auto;
            position: relative;
        }

        .table th, .table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 80px;
            max-width: 133px;
            padding: 8px 12px;
            vertical-align: middle;
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            min-width: 120px;
            max-width: 167px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            min-width: 80px;
            max-width: 100px;
            text-align: center;
        }

        .table th:nth-child(3), .table td:nth-child(3),
        .table th:nth-child(4), .table td:nth-child(4),
        .table th:nth-child(5), .table td:nth-child(5),
        .table th:nth-child(6), .table td:nth-child(6),
        .table th:nth-child(7), .table td:nth-child(7),
        .table th:nth-child(8), .table td:nth-child(8) {
            min-width: 140px;
            max-width: 190px;
            text-align: center;
        }

        .table thead th {
            position: sticky;
            height: 60px;
            top: -1px;
            vertical-align: middle;
            border-top: 1px;
            z-index: 1020;
            background-color: #031e3a;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
        }

        .table-scroll-container thead th {
            position: sticky;
            top: 0;
            background-color: #031e3a;
            z-index: 1020;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.4);
            height: 60px;
            vertical-align: middle;
        }

        .table-scroll-container table {
            margin-bottom: 0;
        }

        .form-select, .form-control {
            font-size: 0.875rem;
            padding: 0.375rem 0.5rem;
        }

        .header-row th {
            border-bottom: 2px solid #dee2e6;
            font-weight: bold;
        }

        .sub-header-row th {
            border-top: none;
            font-size: 0.8rem;
            color: #6c757d;
            font-style: italic;
        }

        .bushing-checkbox {
            transform: scale(1.2);
        }

        .qty-input {
            width: 70px;
            text-align: center;
        }

        .table-info {
            background-color: #d1ecf1 !important;
        }

        .table-info td {
            border-bottom: 2px solid #bee5eb !important;
            font-weight: bold;
            color: #0c5460;
        }

        .ps-4 {
            padding-left: 1.5rem !important;
        }

        .badge {
            font-size: 0.8rem;
        }

        .text-readonly {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            color: #495057;
        }
    </style>

    <div class="card-shadow">
        <div class="card-header m-1 shadow">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-center" style="width: 100px;">
                        <h5 class="text-success-emphasis  ps-1">{{__('WO')}}
                            <a class="text-success-emphasis " href="{{ route('mains.show', $current_wo->id) }}"
                                {{$current_wo->number}}>{{$current_wo->number}}
                            </a>
                        </h5>
                    </div>

                </div>
                    <div>
                        <h4 class="ps-xl-2">{{__('BUSHINGS PROCESSES')}}</h4>
                    </div>

                <div class="ps-2 d-flex" style="width: 400px;margin-top: -5px">
                    @if($woBushing)
                        <a href="{{ route('wo_bushings.edit', $woBushing->id) }}" class="btn btn-outline-primary mt-2 me-2"
                           style="height: 60px;width: 120px;line-height: 1.2rem;align-content: center">
                            <i class="fas fa-edit"></i> Update Bushings List
                        </a>
                        <div class="ms-4" style="width: 100px; margin-top: 6px">
{{--                        <a href="{{ route('wo_bushings.specProcessForm', $woBushing->id) }}" class="btn btn-outline-warning"--}}
{{--                               style="height: 60px;width: 120px" target="_blank">--}}
{{--                            <i class="fas fa-list"></i> Spec Process Form--}}
{{--                        </a>--}}
                        <x-paper-button
                            text="Bushing SP Form"
                            href="{{ route('wo_bushings.specProcessForm', $woBushing->id) }}}"
                            size="landscape"
                            width="90px"
                            target="_blank"
                            color="outline-primary"
                        />
                        </div>
                    @else
                        @if($bushings->flatten()->count() > 0)
                            <a href="{{ route('wo_bushings.create', $current_wo->id) }}" class="btn btn-success"
                               style="height: 60px; width: 130px">
                                <i class="fas fa-plus"></i> Create Bushings List
                            </a>
                        @endif
                    @endif
                </div>
                <div class="">
                    <a href="{{ route('tdrs.show', ['id'=>$current_wo->id]) }}"
                       class="btn btn-outline-secondary me-2" style="height: 60px;width: 90px;align-content: center;
                       line-height: 1.2rem">
                        {{ __('Back to TDR') }}
                    </a>
                </div>
            </div>
        </div>



        @include('admin.wo_bushings.partials.bushing-content', ['returnTo' => route('wo_bushings.show', $current_wo->id)])
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.form-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    var vendorSelectId = this.getAttribute('data-vendor-select');
                    var vendorSelect = vendorSelectId ? document.getElementById(vendorSelectId) : null;
                    var vendorId = vendorSelect ? vendorSelect.value : '';

                    var baseUrl = this.getAttribute('href');
                    var processKey = this.getAttribute('data-process-key');

                    var queryParts = [];
                    if (processKey) {
                        var seen = {};
                        document.querySelectorAll('.bushing-process-include-checkbox').forEach(function(cb) {
                            if ((cb.getAttribute('data-process-key') || '') !== processKey || !cb.checked) return;
                            var cid = cb.getAttribute('data-component-id');
                            if (cid && !seen[cid]) {
                                seen[cid] = true;
                                queryParts.push('bushing_component_ids[]=' + encodeURIComponent(cid));
                            }
                        });
                        if (queryParts.length === 0) {
                            alert({!! json_encode(__('Select at least one bushing for this process using the checkboxes in the table.')) !!});
                            return;
                        }
                    }

                    if (vendorId) {
                        queryParts.push('vendor_id=' + encodeURIComponent(vendorId));
                    }

                    var finalUrl = baseUrl + (queryParts.length ? (baseUrl.indexOf('?') === -1 ? '?' : '&') + queryParts.join('&') : '');

                    window.open(finalUrl, '_blank');
                });
            });

            document.body.addEventListener('click', function(e) {
                var createBatchBtn = e.target.closest('.js-bushing-create-batch');
                var ungroupBatchBtn = e.target.closest('.js-bushing-ungroup-batch');
                if (!createBatchBtn && !ungroupBatchBtn) return;
                e.preventDefault();
                var actionBtn = createBatchBtn || ungroupBatchBtn;
                var actionUrl = actionBtn.getAttribute('data-url');
                if (!actionUrl) return;
                var scopeKey = actionBtn.getAttribute('data-process-key') || '';
                var selector = createBatchBtn ? '.bushing-batch-group-checkbox:checked' : '.bushing-batch-ungroup-checkbox:checked';
                var checkboxes = Array.from(document.querySelectorAll(selector));
                if (scopeKey) {
                    checkboxes = checkboxes.filter(function (cb) { return (cb.getAttribute('data-process-key') || '') === scopeKey; });
                }
                var selected = checkboxes.map(function (cb) {
                    return { processKey: cb.getAttribute('data-process-key') || '', id: cb.getAttribute('data-wo-process-id') || '' };
                }).filter(function (row) { return !!row.id; });
                if (selected.length === 0) {
                    alert(createBatchBtn
                        ? {!! json_encode(__('Select rows using the small “batch” checkbox (not grouped yet).')) !!}
                        : {!! json_encode(__('Select rows using the small checkbox next to “Grp” to ungroup.')) !!});
                    return;
                }
                var processKeys = Array.from(new Set(selected.map(function (r) { return r.processKey; })));
                if (processKeys.length !== 1) {
                    alert({!! json_encode(__('Please select rows from one process column only.')) !!});
                    return;
                }
                var tokenEl = document.querySelector('meta[name="csrf-token"]');
                var csrf = tokenEl ? tokenEl.getAttribute('content') : '';
                fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ wo_bushing_process_ids: selected.map(function (r) { return parseInt(r.id, 10); }) })
                }).then(function (r) {
                    return r.text().then(function (text) {
                        try { return { status: r.status, json: JSON.parse(text) }; } catch (e) { return { status: r.status, json: null }; }
                    });
                }).then(function (res) {
                    if (res.status >= 200 && res.status < 300) {
                        window.location.reload();
                        return;
                    }
                    var msg = (res.json && (res.json.message || res.json.error)) ? (res.json.message || res.json.error) : ('HTTP ' + res.status);
                    alert(msg);
                }).catch(function () {
                    alert('Batch operation failed.');
                });
            });
        });
    </script>

@endsection
