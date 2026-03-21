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
            // ÐžÐ±Ñ€Ð°Ð±Ð¾Ñ‚Ñ‡Ð¸Ðº Ð´Ð»Ñ Ð²ÑÐµÑ… ÐºÐ½Ð¾Ð¿Ð¾Ðº Form (Ð´Ð»Ñ wo_bushings.show ÑÑ‚Ñ€Ð°Ð½Ð¸Ñ†Ñ‹)
            document.querySelectorAll('.form-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();

                    // ÐŸÐ¾Ð»ÑƒÑ‡Ð°ÐµÐ¼ ID Ð´Ñ€Ð¾Ð¿Ð´Ð°ÑƒÐ½Ð° Ð¸Ð· data-Ð°Ñ‚Ñ€Ð¸Ð±ÑƒÑ‚Ð°
                    var vendorSelectId = this.getAttribute('data-vendor-select');
                    var vendorSelect = document.getElementById(vendorSelectId);

                    // ÐŸÐ¾Ð»ÑƒÑ‡Ð°ÐµÐ¼ Ð²Ñ‹Ð±Ñ€Ð°Ð½Ð½Ð¾Ðµ Ð·Ð½Ð°Ñ‡ÐµÐ½Ð¸Ðµ vendor_id
                    var vendorId = vendorSelect ? vendorSelect.value : '';

                    // ÐŸÐ¾Ð»ÑƒÑ‡Ð°ÐµÐ¼ Ð±Ð°Ð·Ð¾Ð²Ñ‹Ð¹ URL
                    var baseUrl = this.getAttribute('href');

                    // Ð”Ð¾Ð±Ð°Ð²Ð»ÑÐµÐ¼ vendor_id Ðº URL, ÐµÑÐ»Ð¸ Ð¾Ð½ Ð²Ñ‹Ð±Ñ€Ð°Ð½
                    var finalUrl = baseUrl;
                    if (vendorId) {
                        finalUrl += (baseUrl.includes('?') ? '&' : '?') + 'vendor_id=' + vendorId;
                    }

                    // Открываем форму в новом окне
                    window.open(finalUrl, '_blank');
                });
            });
        });
    </script>

@endsection
