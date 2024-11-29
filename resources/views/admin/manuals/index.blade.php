@extends('admin.master')

@section('content')
    <style>
        /* Добавьте свои стили для адаптивности */
        @media (max-width: 1100px) {
            .table th:nth-child(5), .table td:nth-child(5) {
                display: none;
            }
        }

        @media (max-width: 770px) {
            .table th:nth-child(3), .table td:nth-child(3),
            .table th:nth-child(5), .table td:nth-child(5),
            .table th:nth-child(6), .table td:nth-child(6) {
                display: none;
            }
        }

        @media (max-width: 490px) {
            .table th:nth-child(3), .table td:nth-child(3),
            .table th:nth-child(4), .table td:nth-child(4),
            .table th:nth-child(5), .table td:nth-child(5),
            .table th:nth-child(6), .table td:nth-child(6) {
                display: none;
            }
        }
    </style>

    <div class="container">
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <h3>{{__('Manage CMMs')}}</h3>
                    <a href="{{ route('manuals.create') }}" class="btn btn-primary mb-3">{{ __('Add CMM') }}</a>
                </div>
            </div>

            <div class="card-body">
                <!-- Поиск таблицы будет работать благодаря data-search="true" -->
                <table id="cmmTable" data-toggle="table" data-search="true" class="table table-bordered">
                    <thead>
                    <tr>
                        <th data-field="number" class="text-center">{{__('Number')}}</th>
                        <th data-field="title" class="text-center">{{__('Title')}}</th>
                        <th data-field="units_pn" class="text-center">{{__('Units PN')}}</th>
                        <th data-field="img" class="text-center">{{__('Unit Image')}}</th>
                        <th data-field="revision_date" class="text-center">{{__('Revision Date')}}</th>
                        <th data-field="lib" class="text-center">{{__('Library')}}</th>
                        <th data-field="action" class="text-center">{{__('Action')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($cmms as $cmm)
                        <tr>
                            <td class="text-center">{{$cmm->number}}</td>
                            <td class="text-center">{{$cmm->title}}</td>
                            <td class="text-center">{{$cmm->units_pn}}</td>
                            <td class="text-center">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#imageModal{{$cmm->id}}">
                                    <img src="{{ asset('storage/image/cmm/' . $cmm->img) }}" style="width: 36px; cursor: pointer;" alt="Img">
                                </a>
                            </td>
                            <td class="text-center">{{$cmm->revision_date}}</td>
                            <td class="text-center">{{$cmm->lib}}</td>
                            <td class="text-center">
                                <a href="{{ route('manuals.edit', $cmm->id) }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('manuals.destroy', $cmm->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Модальное окно для изображения -->
                        <div class="modal fade" id="imageModal{{$cmm->id}}" tabindex="-1" role="dialog"
                             aria-labelledby="imageModalLabel{{$cmm->id}}" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="imageModalLabel{{$cmm->id}}">{{$cmm->title}}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        @if($cmm->img)
                                            <img src="{{ asset('storage/image/cmm/' . $cmm->img) }}" alt="{{ $cmm->title }}" class="img-fluid"/>
                                        @else
                                            <p>Изображение не доступно.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="mobile-message" style="display: none; text-align: center;">
        <p>Доступна только версия для десктопа.</p>
    </div>

@endsection

@push('scripts')
    <script>
        // Проверка на мобильные устройства
        function checkScreenWidth() {
            const screenWidth = window.innerWidth;
            const table = document.querySelector('.table');
            const mobileMessage = document.getElementById('mobile-message');

            if (screenWidth < 312) {
                table.style.display = 'none';
                if (mobileMessage) mobileMessage.style.display = 'block';
            } else {
                table.style.display = 'table';
                if (mobileMessage) mobileMessage.style.display = 'none';
            }
        }

        window.onload = checkScreenWidth;
        window.onresize = checkScreenWidth;
    </script>
@endpush
