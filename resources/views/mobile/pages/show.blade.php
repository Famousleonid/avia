@extends('mobile.master')

@section('style')
    <style>
        .little-info {
            position: absolute;
            bottom: -5px;
            right: -5px;
            background: red;
            color: white;
            font-size: 10px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in-out forwards;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .fancybox__container .fancybox__button--delete {
            display: inline-block;
            visibility: visible;
            background: red;
            padding: 5px;
            border: 1px solid white;
            color: white;
            cursor: pointer;
            z-index: 10000;
            line-height: 1;
            font-size: 16px;
            margin-right: 5px;
            pointer-events: auto;
            position: relative;
        }

        .table-wrapper {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Блок, содержащий прокручиваемое тело таблицы */
        .table-body-scrollable {
            overflow-y: auto;
            flex-grow: 1; /* Занимает все доступное место по высоте */
            padding-bottom: 230px;
            box-sizing: border-box;
        }

        .col-media {
            width: 35%;

        }

        .col-camera {
            width: 30%;
        }

        .table-body-scrollable .table-bordered {
            border-top: none;
        }

        .table-body-scrollable .table-bordered td {
            border-top: none;
        }

        .gradient-pane {
            background: #343A40;
            color: #f8f9fa;
        }

        /* Левая колонка с названиями категорий */
        .category-label {
            cursor: pointer;
            width: 35%;
        }

        .table-dark .category-label.active {
            background: dodgerblue;
            color: black;
        }
        .media-cell {
            min-height: 48px;              /* одинаковая высота */
            display: flex;
            align-items: center;
            justify-content: center;
        }

    </style>
@endsection

@section('content')

    <div class="container-fluid d-flex flex-column bg-dark p-0" style="height: 100%;">

        {{-- Блок с информацией по воркордеру --}}
        <div id="block-info" class="rounded-3 border border-info gradient-pane shadow-sm" style="margin: 5px; padding: 3px;">

            <div class="d-flex justify-content-between align-items-center w-100 fw-bold fs-2 ms-3">
                @if(!$workorder->isDone())
                    <span class="text-info">W {{ $workorder->number }}</span>
                @else
                    <span class="text-secondary">{{ $workorder->number }}</span>
                @endif

                @if($workorder->open_at)
                    <span class="text-secondary fw-normal fs-6 me-4">
                        Open at: {{ $workorder->open_at->format('d-M-Y') }}
                    </span>
                @endif
            </div>

            <hr class="border-secondary opacity-50 my-2">

            <div class="d-flex justify-content-between align-items-center w-100 fw-bold text-info ms-3">
                <div>
                    <span class="text-secondary fw-normal">p/n: </span>
                    <span class="text-white">{{ $workorder->unit?->part_number ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-secondary fw-normal">s/n: </span>
                    <span class="text-white me-4">{{ $workorder->serial_number ?? '-' }}</span>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center w-100 fw-bold text-info ms-3">
                <div>
                    <span class="text-secondary fw-normal">Unit: </span>
                    <span class="text-white">{{ $workorder->unit?->name ?? '-' }}</span>
                </div>
            </div>

            <hr class="border-secondary opacity-50 my-2">

            <div class="d-flex justify-content-between align-items-center w-100 fw-bold text-info ms-3">
                <div>
                    <span class="text-secondary fw-normal">Customer: </span>
                    <span class="text-white">{{ $workorder->customer?->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-secondary fw-normal">Lib: </span>
                    <span class="text-white me-4">{{ $workorder->unit?->manual->lib ?? '-' }}</span>
                </div>
            </div>

            <hr class="border-secondary opacity-50 my-2">

            <div class="d-flex justify-content-between align-items-center w-100 fw-bold text-info ms-3">
                <div>
                    <span class="text-secondary fw-normal">Instruction: </span>
                    <span class="text-white me-4">{{ $workorder->instruction?->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-secondary fw-normal">Manual: </span>
                    <span class="text-white me-4">{{ $workorder->unit?->manual->number ?? '-' }}</span>
                </div>
            </div>

            <hr class="border-secondary opacity-50 my-2">

            <div class="d-flex justify-content-between align-items-center w-100 fw-bold text-info ms-3 pb-2">
                <div>
                    @if($workorder->approve_at)
                        <span class="badge bg-success">
                            <i class="bi bi-check-circle me-1"></i> Approved
                        </span>
                    @else
                        <span class="badge bg-secondary">
                            <i class="bi bi-x-circle me-1"></i> Not approved
                        </span>
                    @endif
                </div>
                <div>
                    @if($workorder->isDone())
                        <span class="text-success fw-bold">
                            <i class="bi bi-check-circle-fill me-1"></i> Done:
                        </span>
                        <span class="text-white me-4">{{ $workorder->doneDate()->format('d-M-Y') }}</span>
                    @else
                        <span class="text-secondary fw-normal">
                            <i class="bi bi-circle me-1"></i> Done:
                        </span>
                        <span class="text-secondary me-4">—</span>
                    @endif
                </div>
            </div>

        </div>

        <hr class="border-secondary opacity-50 my-2">

        {{-- Таблица с 4 группами фото + общая камера --}}
        <div class="table-wrapper" style="flex-grow: 1; min-height: 0;">

            <div class="table-body-scrollable">
                <table class="table-sm table-dark table-striped m-0 w-100 table-bordered align-middle">
                    <tbody>
                    @php

                        $categories = config('workorder_media.groups', []);
                        if (!is_array($categories) || empty($categories)) {
                            $categories = [
                                'photos'  => 'Photo',
                            ];
                        }
                    @endphp
                    @foreach($categories as $type => $label)
                        <tr>
                            {{-- Левая колонка: название группы --}}
                            <td class="category-label {{ $loop->first ? 'active' : '' }} ps-3"
                                data-category="{{ $type }}">
                                {{ $label }}
                            </td>

                            {{-- Средняя колонка: превью / счетчик --}}
                            <td class="text-center col-media"
                                data-workorder-id="{{ $workorder->id }}"
                                data-category="{{ $type }}">
                                <div class="media-cell">
                                    @php
                                        $mediaForType = $workorder->getMedia($type);
                                        $count = $mediaForType->count();
                                    @endphp

                                    @if ($count > 0)
                                        <div style="position: relative; display: inline-block; margin: 5px;">
                                            @foreach($mediaForType as $index => $media)
                                                <a href="{{ $workorder->generateMediaUrl($media, '', $type) }}"
                                                   data-fancybox="gallery-{{ $workorder->id }}-{{ $type }}"
                                                   data-media-id="{{ $media->id }}"
                                                   data-workorder-number="{{ $workorder->number }}"
                                                   data-caption="Workorder: {{ $workorder->number }} - {{ ucfirst($type) }}"
                                                   style="{{ $index === 0 ? '' : 'display: none;' }}">
                                                    @if ($index === 0)
                                                        <img class="rounded-circle"
                                                             src="{{ $workorder->generateMediaUrl($media, 'thumb', $type) }}"
                                                             width="40" height="40" alt="Photo">
                                                    @endif
                                                </a>
                                            @endforeach
                                            <span class="little-info">{{ $count > 99 ? '99+' : $count }}</span>
                                        </div>
                                    @else
                                        <span class="text-white-50" style="font-size: 0.70rem;">No Photos</span>
                                    @endif
                                </div>
                            </td>

                            {{-- Правая колонка: одна камера на все 4 строки --}}
                            @if($loop->first)
                                <td class="text-center col-camera" rowspan="{{ count($categories) }}">
                                    <a href="#"
                                       class="text-info js-camera-btn"
                                       data-workorder-id="{{ $workorder->id }}"
                                       data-workorder-number="{{ $workorder->number }}">
                                        <i class="bi bi-camera" style="font-size: 1.5rem;"></i>
                                    </a>
                                </td>
                            @endif
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>

        </div>
    </div>

    {{-- скрытая форма для загрузки фото с камеры --}}
    <form id="photo-upload-form"
          data-url-template="/mobile/workorders/photo/WORKORDER_ID"
          method="POST"
          enctype="multipart/form-data"
          style="display: none;"></form>

@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            let currentPhotoCategory = 'photos';
            let currentWorkorderId = null;
            let currentWorkorderNumber = null;

            // ===== Утилита для HTTP-запросов (POST/DELETE/GET) через fetch =====
            async function makeRequest(url, method, body = null) {
                const headers = {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                };
                const options = {method, headers};
                if (body) options.body = body;

                try {
                    const response = await fetch(url, options);

                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`HTTP error! Status: ${response.status}, Body: ${errorText}`);
                    }

                    // Попытка парсить JSON только после успешного ответа
                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.message || 'Server returned an error.');
                    }
                    return data;

                } catch (err) {
                    console.error('makeRequest error:', err);
                    throw err;
                }
            }

            // ===== ЕДИНЫЙ ОБРАБОТЧИК КЛИКОВ (ДЕЛЕГИРОВАНИЕ СОБЫТИЙ) =====
            document.body.addEventListener('click', (event) => {
                const target = event.target;

                // Клик по кнопке "Камера"
                const cameraBtn = target.closest('.js-camera-btn');
                if (cameraBtn) {
                    event.preventDefault();
                    currentWorkorderId = cameraBtn.dataset.workorderId;
                    currentWorkorderNumber = cameraBtn.dataset.workorderNumber;
                    openCamera();
                    return;
                }

                // Клик по названию категории (левая колонка)
                const categoryLabel = target.closest('.category-label');
                if (categoryLabel) {
                    document.querySelectorAll('.category-label').forEach(h => h.classList.remove('active'));
                    categoryLabel.classList.add('active');
                    currentPhotoCategory = categoryLabel.dataset.category;
                    return;
                }

            });

            // ===== Создание кнопки удаления фото в тулбаре Fancybox =====
            function createDeleteButton(fancybox) {
                const slide = fancybox.getSlide();
                if (!slide) return;

                const triggerEl = slide.triggerEl;
                const cell = triggerEl?.closest('td[data-workorder-id][data-category]');
                if (!cell) return;

                const workorderId = cell.dataset.workorderId;
                const category = cell.dataset.category;
                const workorderNumber = triggerEl.dataset.workorderNumber || '';

                const btn = document.createElement('button');
                btn.className = 'fancybox__button fancybox__button--delete';
                btn.title = 'Delete photo';
                btn.innerHTML = '🗑️';

                btn.onclick = async () => {
                    const mediaId = triggerEl?.dataset.mediaId;
                    if (!mediaId) return alert('Media ID not found!');
                    if (!confirm('Are you sure you want to delete this photo?')) return;

                    try {
                        await makeRequest(`/mobile/workorders/photo/delete/${mediaId}`, 'DELETE');
                        // Если запрос успешен, обновляем галерею
                        refreshGalleryAfterAction(workorderId, workorderNumber, category);
                        fancybox.close();
                    } catch (e) {
                        alert(`Error deleting photo: ${e.message}`);
                    }
                };

                // Вставляем кнопку в тулбар
                const counter = fancybox.toolbar.querySelector('.fancybox__counter');
                if (counter) {
                    counter.before(btn);
                } else {
                    fancybox.toolbar.prepend(btn);
                }
            }

            // ===== Обновление галереи и бейджа после действия (удаление/добавление) =====
            async function refreshGalleryAfterAction(workorderId, workorderNumber, category) {
                try {
                    const response = await makeRequest(`/mobile/workorders/photos/${workorderId}?category=${category}`, 'GET');
                    updateGalleryUI(workorderId, workorderNumber, response, category);
                    // Небольшая задержка перед перепривязкой Fancybox
                    setTimeout(() => bindFancyboxForCell(workorderId, category), 100);
                } catch (e) {
                    console.error('Failed to refresh gallery:', e);
                }
            }

            // ===== Полная перерисовка ячейки галереи на основе новых данных =====
            function updateGalleryUI(workorderId, workorderNumber, response, category) {
                const cell = document.querySelector(`td[data-workorder-id="${workorderId}"][data-category="${category}"]`);
                if (!cell) return;

                cell.innerHTML = '';

                if (response.photos && response.photos.length > 0) {
                    const wrapper = document.createElement('div');
                    wrapper.style.cssText = 'position: relative; display: inline-block; margin: 5px;';

                    response.photos.forEach((photo, index) => {
                        const a = document.createElement('a');
                        a.href = photo.big_url;
                        a.dataset.fancybox = `gallery-${workorderId}-${category}`;
                        a.dataset.mediaId = photo.id;
                        a.dataset.workorderNumber = workorderNumber;
                        a.dataset.caption = `Workorder: ${workorderNumber} - ${category}`;
                        if (index > 0) a.style.display = 'none';

                        const img = document.createElement('img');
                        img.src = photo.thumb_url;
                        img.alt = 'Photo';
                        img.className = 'rounded-circle fade-in';
                        img.style.cssText = 'width: 40px; height: 40px; object-fit: cover;';

                        a.appendChild(img);
                        wrapper.appendChild(a);
                    });

                    const badge = document.createElement('span');
                    badge.className = 'little-info';
                    badge.innerText = response.photo_count > 99 ? '99+' : response.photo_count;
                    wrapper.appendChild(badge);
                    cell.appendChild(wrapper);
                } else {
                    cell.innerHTML = '<span class="text-white-50" style="font-size: 0.70rem;">No Photos</span>';
                }
            }

            // ===== Привязка Fancybox к элементам в конкретной ячейке =====
            function bindFancyboxForCell(workorderId, category) {
                const selector = `[data-fancybox="gallery-${workorderId}-${category}"]`;
                Fancybox.unbind(selector);
                Fancybox.bind(selector, {
                    on: {
                        done: (fancybox) => createDeleteButton(fancybox),
                    }
                });
            }

            function openCamera() {
                const form = document.getElementById('photo-upload-form');
                // Удаляем старый input, если он есть
                document.getElementById('camera-input')?.remove();

                const fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.id = 'camera-input';
                fileInput.name = 'photos[]';
                fileInput.accept = 'image/*';
                fileInput.multiple = true;
                fileInput.capture = 'environment';
                fileInput.style.display = 'none';

                fileInput.onchange = () => {
                    if (fileInput.files.length > 0) {
                        submitPhotos(fileInput.files);
                    } else {
                        hideLoadingSpinner();
                    }
                };

                form.appendChild(fileInput);
                fileInput.click();
            }

            async function submitPhotos(files) {
                showLoadingSpinner();
                const form = document.getElementById('photo-upload-form');
                const urlTemplate = form.dataset.urlTemplate;
                const actionUrl = urlTemplate.replace('WORKORDER_ID', currentWorkorderId) + `?category=${currentPhotoCategory}`;

                const formData = new FormData();
                for (let file of files) {
                    formData.append('photos[]', file);
                }

                try {
                    const response = await makeRequest(actionUrl, 'POST', formData);
                    updateGalleryUI(currentWorkorderId, currentWorkorderNumber, response, currentPhotoCategory);
                    setTimeout(() => bindFancyboxForCell(currentWorkorderId, currentPhotoCategory), 100);
                } catch (e) {
                    alert(`Error uploading photos: ${e.message}`);
                } finally {
                    hideLoadingSpinner();
                }
            }

            // ===== Первичная привязка Fancybox ко всем галереям при загрузке страницы =====
            document.querySelectorAll('td[data-workorder-id][data-category]').forEach(cell => {
                const workorderId = cell.dataset.workorderId;
                const category = cell.dataset.category;
                if (cell.querySelector('a[data-fancybox]')) {
                    bindFancyboxForCell(workorderId, category);
                }
            });
        });
    </script>
@endsection
