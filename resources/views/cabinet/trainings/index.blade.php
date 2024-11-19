
@extends('cabinet.master')

@php use Carbon\Carbon; @endphp

@section('content')
    <style>


        @media (max-width: 1100px) {
            .table th:nth-child(2),
            .table td:nth-child(2) {
                display: none;
            }
        }

        @media (max-width: 770px) {
            .table th:nth-child(2),
            .table td:nth-child(2), /* Revision Date */
            .table th:nth-child(4), /* Revision Date */
            .table td:nth-child(4),
            .table th:nth-child(5),
            .table td:nth-child(5) {
                display: none;
            }
        }

        @media (max-width: 590px) {
            .table th:nth-child(2), /* Image */
            .table td:nth-child(2),
            .table th:nth-child(4), /* Revision Date */
            .table td:nth-child(4),
            .table th:nth-child(5), /* Revision Date */
            .table td:nth-child(5),
            .table th:nth-child(6),
            .table td:nth-child(6) {
                display: none;
            }

            @media (max-width: 490px) {
                .table th:nth-child(2), /* Image */
                .table td:nth-child(2),
                .table th:nth-child(4), /* Revision Date */
                .table td:nth-child(4),
                .table th:nth-child(5), /* Revision Date */
                .table td:nth-child(5),
                .table th:nth-child(6), /* Revision Date */
                .table td:nth-child(6),
                .table th:nth-child(7),
                .table td:nth-child(7) {
                    display: none;
                }

                /*.form-switch {*/
                /*    display: none;*/
                /*}*/

                /*.table {*/
                /*    display: none;*/
                /*}*/
            }

        }

    </style>


    <div class="container ">
        <div class="card shadow">
            <div class="card-header">
                <div class="d-flex justify-content-between">
                    <div class="" style="width: 450px">
                        <h3>{{ __('Trainings') }}</h3>
                    </div>
                    <div class="form-check form-switch pt-1">
                        <input class="form-check-input" type="checkbox"
                               id="trainingNotUpdated">
                        <label class="form-check-label"
                               for="trainingNotUpdated">Not updated
                            trainings</label>
                    </div>
                    <div class="align-middle">
                        <a href="{{ route('training.create') }}"
                           class="btn btn-primary align-middle">
                            {{ __('Add Unit') }}</a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{--                <pre>{{ print_r($formattedTrainingLists, true) }}</pre> <!-- Здесь вывод данных -->--}}
                <table id="trainingsTable" data-toggle="table"
                       data-search="true" data-pagination="false"
                       data-page-size="5" class="table table-bordered">
                    <thead>
                    <tr>
                        <th data-priority="1" data-visible="true"
                            class="text-center
                        align-middle">{{ __('Training
                         (Yes/No)') }}</th>
                        <th data-priority="2" data-visible="true"
                            class="text-center align-middle">{{ __('Form
                        132') }}</th>
                        <th data-priority="3" data-visible="true" class="text-center
                        align-middle">{{ __('Unit
                        PN') }}</th>
                        <th data-priority="4" data-visible="true" class="text-center
                        align-middle">{{ __
                        ('Description') }}</th>
                        <th data-priority="5" data-visible="true" class="text-center
                        align-middle">{{ __('First
                        Training Date') }}</th>
                        <th data-priority="6" data-visible="true" class="text-center
                        align-middle">{{ __('Last
                        Training Date') }}</th>
                        <th data-priority="7" data-visible="true" class="text-center
                        align-middle">{{ __
                        ('Actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($formattedTrainingLists as $trainingList)
                        <tr>
                            <td class="text-center ">
                                <div class="form-check form-switch mt-2 ms-4">
                                    <input class="form-check-input "
                                           type="checkbox"
                                           @if(isset($trainingList['last_training']) && Carbon::parse($trainingList['last_training']->date_training)
                                           ->diffInDays(Carbon::now()) < 340)
                                               disabled
                                           @endif
                                           onchange="handleCheckboxChange(this, '{{ $trainingList['first_training']->manuals_id }}', '{{ $trainingList['first_training']->date_training }}', '{{ $trainingList['first_training']->manual->title ?? 'N/A' }}')">
                                    <label class="form-check-label justify-content-center"
                                           for="flexSwitchCheckChecked"></label>

                                </div>
                            </td>
                            <td class="text-center">
                                @if(isset($trainingList['first_training']) && $trainingList['first_training']->form_type == 132)
                                    <label>OK</label>
                                @else
                                    <label>No</label>
                                @endif
                            </td>

                            <td class="text-center">{{ $trainingList['first_training']->manual->units_pn ?? 'N/A' }}</td>

                            <td class="text-center">
                                <a href="" data-bs-toggle="modal"
                                   data-bs-target="#cmmModal{{
                                       $trainingList['first_training']->manual->id }}">
                                    {{ $trainingList['first_training']->manual->title ?? 'N/A' }}
                                </a>

                            </td>

                            <td class="text-center">
                                {{ isset($trainingList['first_training']) ? Carbon::parse($trainingList['first_training']->date_training)->format('m-d-Y') : 'N/A' }}
                            </td>

                            <td class="text-center"
                                @if(isset($trainingList['last_training']) && Carbon::parse($trainingList['last_training']->date_training)->diffInDays(Carbon::now()) > 340)
                                    style="color: red"
                                @endif>
                                {{ isset($trainingList['last_training']) ? Carbon::parse($trainingList['last_training']->date_training)->format('m-d-Y') : 'N/A' }}
                            </td>

                            <td class="text-center">
                                <!-- Кнопка для вызова модального окна -->
                                <button class="btn btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#trainingModal{{
                                        $trainingList['first_training']->manuals_id }}">
                                   {{__('View Training')}}
                                </button>

                                <!-- Модальное окно -->
                                <div class="modal fade"
                                     id="trainingModal{{ $trainingList['first_training']->manuals_id }}"
                                     tabindex="-1"
                                     aria-labelledby="trainingModalLabel"
                                     aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header justify-content-between">
                                                <h5 class="modal-title"
                                                    id="trainingModalLabel">
                                                    Training
                                                    for {{ $trainingList['first_training']->manual->title }}
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Закрыть"></button>
{{--                                                <button type="button"--}}
{{--                                                        class="close"--}}
{{--                                                        data-dismiss="modal"--}}
{{--                                                        aria-label="Close">--}}
{{--                                                    <span aria-hidden="true">&times;</span>--}}
{{--                                                </button>--}}
                                            </div>
                                            <div class="modal-body">
                                                @foreach($trainingList['trainings'] as $training)
                                                    <div class="form-group">
                                                        <label>
                                                            {{ Carbon::parse($training->date_training)->format('M.d.Y') }}
                                                            (Form: {{ $training->form_type }}
                                                            )
                                                        </label>
                                                        @if($training->form_type == '112')
                                                            <a href="{{ route('training.form112', ['id'=> $training->id, 'showImage' => 'false']) }}"
                                                               class="btn
                                                               btn-success mb-1
                                                               formLink "
                                                               target="_blank"
                                                               id="formLink{{ $trainingList['first_training']->manuals_id }}">
                                                                View/Print Form
                                                                112
                                                            </a>
                                                        @elseif($training->form_type == '132')
                                                            <a href="{{ route('training.form132', ['id' => $training->id, 'showImage' => 'false']) }}"
                                                               class="btn
                                                               btn-info mb-1
                                                               formLink "
                                                               target="_blank"
                                                               id="formLink{{ $trainingList['first_training']->manuals_id }}">
                                                                View/Print Form
                                                                132
                                                            </a>
                                                        @endif

                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="modal-footer">
                                                {{--                                                @if(Auth::user()->role !== null && Auth::user()->role->name !== 'Technician')--}}
                                                <div class="form-check ">
                                                    <input type="checkbox"
                                                           class="form-check-input"
                                                           id="showImage{{ $trainingList['first_training']->manuals_id }}">
                                                    <label
                                                        class="form-check-label"
                                                        for="showImage{{ $trainingList['first_training']->manuals_id }}">
                                                        {{__('Sign In')}}
                                                    </label>
                                                </div>
                                                {{--                                                @endif--}}
                                                <button type="button"
                                                        class="btn btn-secondary ms-5"
                                                        data-bs-dismiss="modal">
                                                    Close
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{--    <!-- Модальное окно для просмотра деталей CMM -->--}}

                                <div class="modal fade" id="cmmModal{{
                            $trainingList['first_training']->manual->id }}" tabindex="-1"
                                     role="dialog" aria-labelledby="cmmModalLabel{{
                                  $trainingList['first_training']->manual->id }}"
                                     aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <div>
                                                    <h5 class="modal-title"
                                                        id="imageModalLabel{{ $trainingList['first_training']->manual->id }}">
                                                        {{ $trainingList['first_training']->manual->title }}{{__(': ')}}
                                                    </h5 >
                                                    <h6>{{ $trainingList['first_training']->manual->units_pn }}</h6>
                                                </div>
                                                <button type="button"
                                                        class="btn-close pb-2"
                                                        data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body">
                                                <div class="d-flex">
                                                    <div class="me-2">
                                                        <img src="{{ asset('storage/image/cmm/' . $trainingList['first_training']->manual->img) }}"
                                                             style="max-width: 200px;"
                                                             alt="{{ $trainingList['first_training']->manual->title }}">
                                                    </div>
                                                    <div>
                                                        <p><strong>{{ __('CMM:') }}</strong> {{ $trainingList['first_training']->manual->number }}</p>
                                                        <p><strong>{{ __('Description:') }}</strong>
                                                            {{ $trainingList['first_training']->manual->title }}</p>
                                                        <p><strong>{{ __('Revision Date:')}}</strong> {{ $trainingList['first_training']->manual->revision_date }}</p>
                                                        <p><strong>{{ __('AirCraft Type:')}}</strong>
                                                            {{ $planes[$trainingList['first_training']->manual->planes_id] ?? 'N/A' }}</p>
                                                        <p><strong>{{ __('MFR:') }}</strong> {{$builders[$trainingList['first_training']->manual->builders_id] ?? 'N/A' }}</p>
                                                        <p><strong>{{ __('Scope:') }}</strong> {{$scopes[$trainingList['first_training']->manual->scopes_id] ?? 'N/A' }}</p>
                                                        <p><strong>{{ __('Library:') }}</strong> {{$trainingList['first_training']->manual->lib }}</p>
                                                    </div>

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>


                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>
        </div>
    </div>



    <script>

        function handleCheckboxChange(checkbox, manualsId, dateTraining, manualsTitle) {
            if (checkbox.checked) {
// Определяем номер недели и год последней тренировки
                const lastTrainingDate = new Date(dateTraining);
                const lastTrainingYear = lastTrainingDate.getFullYear();
                const lastTrainingWeek = getWeekNumber(lastTrainingDate);

// Получаем текущую дату
                const currentYear = new Date().getFullYear();

// Создаем массив для данных, которые будем отправлять

                let trainingData = {
                    manuals_id: [],
                    date_training: [],
                    form_type: []
                };

// Генерируем данные для создания тренингов за следующие годы
                for (let year = lastTrainingYear + 1; year <= currentYear; year++) {
                    const trainingDate = getDateFromWeekAndYear(lastTrainingWeek, year);
                    trainingData.manuals_id.push(manualsId);
                    trainingData.date_training.push(trainingDate.toISOString().split('T')[0]); // Преобразуем в формат YYYY-MM-DD
                    trainingData.form_type.push('112');
                }

// Подготовка сообщения для подтверждения
                let confirmationMessage = "Предоставленные данные для создания тренингов:\n";
                trainingData.manuals_id.forEach((id, index) => {
                    confirmationMessage += `\nTraining for ${lastTrainingYear + index + 1} years:\n`;
                    confirmationMessage += `Manuals ID: ${id} ${manualsTitle}\n`;
                    confirmationMessage += `Дата тренировки: ${trainingData.date_training[index]} \n`;
                    confirmationMessage += `Форма: ${trainingData.form_type[index]} \n`;
                });

// Показываем сообщение для подтверждения
                if (confirm(confirmationMessage + "\nВы уверены, что хотите продолжить создание тренингов?")) {
// Если пользователь подтвердил, выполняем запрос
                    fetch('{{ route('training.create') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(trainingData) // Отправляем ассоциативный массив
                    })
                        .then(response => response.json())

                        .then(data => {
                            if (data.success) {
                                alert('Тренинги успешно созданы!');
                                location.reload();
                                checkbox.checked = false;
                            } else {
                                alert('Ошибка при создании тренингов.');
                            }
                        })

                        .catch(error => {
                            console.error('Ошибка:', error);
                            alert('Произошла ошибка: ' + error.message);
                        });
                } else {
// Если пользователь отказался, снимаем галочку
                    checkbox.checked = false;
                }
            }
        }


        function getWeekNumber(d) {
            const oneJan = new Date(d.getFullYear(), 0, 1);
            const numberOfDays = Math.floor((d - oneJan) / (24 * 60 * 60 * 1000));
            return Math.ceil((numberOfDays + oneJan.getDay() + 1) / 7);
        }

        function getDateFromWeekAndYear(week, year) {
            const firstJan = new Date(year, 0, 1);
            const days = (week - 1) * 7 - firstJan.getDay() + 1;
            return new Date(year, 0, 1 + days);
        }


        document.querySelectorAll('.form-check-input').forEach(checkbox => {
            checkbox.addEventListener('change', function () {
                const showImage = this.checked ? 'true' : 'false';  // Получаем значение параметра showImage
                // const manualsId = this.id.replace('showImage', ''); // Получаем manuals_id из id чекбокса
                const formLinks = document.querySelectorAll(`.formLink`); // Находим все ссылки на формы

                formLinks.forEach(link => {
                    let url = new URL(link.href); // Получаем текущий URL
                    url.searchParams.set('showImage', showImage); // Устанавливаем значение showImage в URL
                    link.href = url.toString(); // Обновляем href ссылки
                    console.log('Updated URL: ', link.href); // Выводим в консоль обновленный URL
                });
            });
        });


        document.addEventListener('DOMContentLoaded', function () {
            const trainingNotUpdatedCheckbox = document.getElementById('trainingNotUpdated');
            const trainingsTableBody = document.querySelector('#trainingsTable tbody');

            trainingNotUpdatedCheckbox.addEventListener('change', function () {
                const isChecked = this.checked;

                // Проходим по каждой строке таблицы и проверяем условие
                Array.from(trainingsTableBody.rows).forEach(row => {
                    const lastTrainingDateCell = row.cells[5]; // ячейка с датой последней тренировки

                    if (isChecked) {
                        // Показываем строки, где дата последней тренировки больше 340 дней от текущей даты
                        const lastTrainingDate = new Date(lastTrainingDateCell.textContent.trim());
                        const daysDiff = Math.floor((new Date() - lastTrainingDate) / (1000 * 60 * 60 * 24));
                        if (daysDiff <= 340) {
                            row.style.display = 'none';
                        } else {
                            row.style.display = '';
                        }
                    } else {
                        // Показываем все строки, если переключатель не активен
                        row.style.display = '';
                    }
                });
            });
        });


    </script>

@endsection
