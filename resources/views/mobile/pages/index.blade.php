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

        .category-header {
            cursor: pointer;
        }

        .table-dark .category-header.active {
            background: gold;
            color: black;
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

        .col-workorder { width: 20%; }
        .col-media { width: 20%; }
        .col-camera { width: 20%; }

        .table-body-scrollable .table-bordered {
            border-top: none;
        }
        .table-body-scrollable .table-bordered td {
            border-top: none;
        }

    </style>
@endsection

@section('content')

    <div class="container-fluid d-flex flex-column bg-dark p-0" style="height: 100%;">

        <div class="position-sticky bg-dark shadow-sm" style="top: 0; z-index: 10; flex-shrink: 0;">
            <div class="px-3 py-2 border-bottom border-secondary">
                <div class="position-relative">
                    <input type="text" id="searchInput"
                           class="form-control form-control-sm bg-dark text-white border-secondary pe-5"
                           placeholder="Search by Workorder Number...">
                    <button type="button" id="clearSearch"
                            class="btn btn-sm btn-outline-light border-0 position-absolute top-50 end-0 translate-middle-y me-2 px-2 py-0"
                            style="display: none; font-size: 1.2rem;">
                        ×
                    </button>
                </div>
            </div>
        </div>

        <div class="table-wrapper" style="flex-grow: 1; min-height: 0;">

            <div class="table-header-sticky">
                <table class="table-sm table-dark m-0 w-100 table-bordered">
                    <thead>
                    <tr>
                        <th class="text-center bg-gradient text-size col-workorder">W_order</th>
                        <th class="text-center bg-gradient category-header text-size col-media active" data-category="photos">Photo</th>
                        <th class="text-center bg-gradient category-header text-size col-media" data-category="logs">Log card</th>
                        <th class="text-center bg-gradient category-header text-size col-media" data-category="damages">Dam&corr</th>
                        <th class="text-center bg-gradient text-size col-camera">Camera</th>
                    </tr>
                    </thead>
                </table>
            </div>

            <div class="table-body-scrollable">
                <table class="table-sm table-dark table-striped m-0 w-100 table-bordered">
                    <tbody>
                    @foreach($workorders as $workorder)
                        <tr>
                            <td class="text-center align-middle col-workorder">
                                <span class="text-info fw-bold workorder-click" style="cursor: pointer;" data-number="{{ $workorder->number }}">{{ $workorder->number }}</span>
                            </td>

                            @foreach(['photos', 'logs', 'damages'] as $type)
                                <td class="text-center col-media" data-workorder-id="{{ $workorder->id }}" data-category="{{ $type }}">
                                    @php
                                        // 1. Получаем всю коллекцию медиа один раз.
                                        // Благодаря ->with('media') в контроллере, это не делает новый запрос к БД.
                                        $mediaForType = $workorder->getMedia($type);
                                        $count = $mediaForType->count();
                                    @endphp

                                    @if ($count > 0)
                                        <div style="position: relative; display: inline-block; margin: 5px;">
                                            {{-- 2. Генерируем ссылки для галереи Fancybox --}}
                                            @foreach($mediaForType as $index => $media)
                                                <a href="{{ $workorder->generateMediaUrl($media, '', $type) }}"
                                                   data-fancybox="gallery-{{ $workorder->id }}-{{ $type }}"
                                                   data-media-id="{{ $media->id }}"
                                                   data-caption="Workorder: {{ $workorder->number }} - {{ ucfirst($type) }}"
                                                   {{-- 3. Показываем только первое изображение, остальные скрываем для галереи --}}
                                                   style="{{ $index === 0 ? '' : 'display: none;' }}">

                                                    {{-- 4. Показываем превью только для первого элемента в цикле --}}
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
                                </td>
                            @endforeach
                            <td class="text-center col-camera">
                                <a href="#" class="text-info js-camera-btn"
                                   data-workorder-id="{{ $workorder->id }}"
                                   data-workorder-number="{{ $workorder->number }}">
                                    <i class="bi bi-camera" style="font-size: 1.5rem;"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form id="photo-upload-form" data-url-template="/mobile/workorders/photo/WORKORDER_ID" method="POST" enctype="multipart/form-data" style="display: none;"></form>

@endsection


@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            let currentPhotoCategory = 'photos';
            let currentWorkorderId = null;
            let currentWorkorderNumber = null;

            // ===== Утилита для HTTP-запросов (POST/DELETE и т.п.) через fetch =====
            async function makeRequest(url, method, body = null) {
                const headers = {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                };
                const options = { method, headers };
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
                    // Перебрасываем ошибку, чтобы вызывающий код мог ее обработать
                    throw err;
                }
            }

            // ===== Поиск по номеру воркордера =====
            const searchInput = document.getElementById('searchInput');
            const clearSearchBtn = document.getElementById('clearSearch');

            searchInput.addEventListener('input', () => {
                const filter = searchInput.value.toLowerCase();
                clearSearchBtn.style.display = searchInput.value ? 'block' : 'none';
                document.querySelectorAll('tbody tr').forEach(row => {
                    const workorderCell = row.querySelector('td:first-child');
                    const workorderNumber = workorderCell?.textContent?.toLowerCase() || '';
                    row.style.display = workorderNumber.includes(filter) ? '' : 'none';
                });
            });

            clearSearchBtn.addEventListener('click', () => {
                searchInput.value = '';
                clearSearchBtn.style.display = 'none';
                document.querySelectorAll('tbody tr').forEach(row => row.style.display = '');
                searchInput.focus();
            });


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

                // Клик по заголовку категории
                const categoryHeader = target.closest('.category-header');
                if (categoryHeader) {
                    document.querySelectorAll('.category-header').forEach(h => h.classList.remove('active'));
                    categoryHeader.classList.add('active');
                    currentPhotoCategory = categoryHeader.dataset.category;
                    return;
                }

                // Клик по номеру воркордера для быстрой фильтрации
                const workorderLink = target.closest('.workorder-click');
                if (workorderLink) {
                    const number = workorderLink.dataset.number;
                    searchInput.value = number;
                    // Имитируем событие input для запуска фильтрации
                    searchInput.dispatchEvent(new Event('input'));
                }
            });

            // ===== Создание кнопки удаления фото в тулбаре Fancybox =====
            function createDeleteButton(fancybox) {
                const slide = fancybox.getSlide();
                if (!slide) return;

                const workorderId = slide.triggerEl?.closest('td')?.dataset.workorderId;
                const category = slide.triggerEl?.closest('td')?.dataset.category;
                const workorderNumberEl = slide.triggerEl?.closest('tr')?.querySelector('.workorder-click');
                const workorderNumber = workorderNumberEl?.dataset.number;

                const btn = document.createElement('button');
                btn.className = 'fancybox__button fancybox__button--delete';
                btn.title = 'Delete photo';
                btn.innerHTML = '🗑️';

                btn.onclick = async () => {
                    const mediaId = slide.triggerEl?.dataset.mediaId;
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
                    // Небольшая задержка перед перепривязкой Fancybox для гарантии отрисовки DOM
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
                // Удаляем старый input, если он есть, чтобы избежать проблем
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
