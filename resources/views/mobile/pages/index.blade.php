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

        .fancybox__button--delete {
            display: inline-block !important;
            visibility: visible !important;
            background: red !important;
            padding: 5px !important;
            border: 1px solid white !important;
            color: white !important;
            cursor: pointer !important;
            z-index: 10000 !important;
            line-height: 1 !important;
            font-size: 16px !important;
            margin-right: 5px !important;
            pointer-events: auto !important;
            position: relative !important;
        }

        .fancybox__toolbar {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            overflow: visible !important;
            width: auto !important;
            pointer-events: auto !important;
        }

        .category-header {
            cursor: pointer;
        }

        .category-header.active {
            background: gold !important;
            color: black !important;
        }

        .equal-width-column {
            width: 75px;
        }

        .text-size {
            font-size: 0.75rem;
            line-height: 2;
        }


    </style>
@endsection

@section('content')

    <div class="container-fluid d-flex flex-column bg-dark p-0" style="padding-top: 60px; padding-bottom: 60px; min-height: 100vh; ">

        <div class="position-sticky top-0 z-3 bg-dark shadow-sm" style="margin-top: 15px;">
            <div class="px-3 py-2 border-bottom border-secondary">
                <div class="position-relative">
                    <input type="text" id="searchInput"
                           class="form-control form-control-sm bg-dark text-white border-secondary pe-5"
                           placeholder="Search by Workorder Number...">
                    <button type="button" id="clearSearch"
                            class="btn btn-sm btn-outline-light border-0 position-absolute top-50 end-0 translate-middle-y me-2 px-2 py-0"
                            style="display: none; font-size: 1.2rem;">
                        &times;
                    </button>
                </div>
            </div>
        </div>


        <div class="row flex-grow-1 g-0 p-0 m-0" style="background-color:#343A40;">

            <div class="col-12 d-flex flex-column align-items-center g-0 p-0 m-0">
                <div class="table-responsive shadow" style="max-height: calc(100vh - 150px); overflow-y: auto; width: 100%; margin-top: 0; ">
                    <table class="table-sm table-dark table-striped m-0 w-100 table-bordered">
                        <thead class="bg-primary sticky-top">
                        <tr>
                            <th class="text-center bg-gradient text-size ">W_order</th>
                            <th class="text-center bg-gradient category-header equal-width-column text-size active" data-category="photos">Photo</th>
                            <th class="text-center bg-gradient category-header equal-width-column text-size " data-category="logs">Log card</th>
                            <th class="text-center bg-gradient category-header equal-width-column text-size " data-category="damages">Dam&corr</th>
                            <th class="text-center bg-gradient text-size ">Camera</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($workorders as $workorder)
                            <tr>
                                <td class="text-center align-middle">
                                    <span class="text-info fw-bold workorder-click" style="cursor: pointer;" data-number="{{ $workorder->number }}">{{ $workorder->number }}</span>
                                </td>

                                @foreach(['photos', 'logs', 'damages'] as $type)
                                    <td class="text-center equal-width-column " data-workorder-id="{{ $workorder->id }}" data-category="{{ $type }}">
                                        @php $media = $workorder->getMedia($type); $count = $media->count(); @endphp
                                        @if ($count > 0)
                                            <div style="position: relative; display: inline-block; margin: 5px;">
                                                @foreach($media as $index => $photo)
                                                    <a href="{{ route('image.show.big', ['mediaId' => $photo->id, 'modelId' => $workorder->id, 'mediaName' => $type]) }}"
                                                       data-fancybox="gallery-{{ $workorder->id }}-{{ $type }}"
                                                       data-media-id="{{ $photo->id }}"
                                                       data-caption="Workorder: {{ $workorder->number }} - {{ ucfirst($type) }}"
                                                       style="{{ $index === 0 ? '' : 'display: none;' }}">
                                                        <img class="rounded-circle"
                                                             src="{{ route('image.show.thumb', ['mediaId' => $photo->id, 'modelId' => $workorder->id, 'mediaName' => $type]) }}"
                                                             width="40" height="40" alt="Photo">
                                                    </a>
                                                @endforeach
                                                <span class="little-info">{{ $count > 99 ? '99+' : $count }}</span>
                                            </div>
                                        @else
                                            <span class="text-white-50" style="font-size: 0.70rem;">No Photos</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center">
                                    <a href="#" onclick="openCamera({{ $workorder->id }}, '{{ $workorder->number }}')" class="text-info">
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
    </div>

    <form id="photo-upload-form" data-url-template="/mobile/workorders/photo/WORKORDER_ID" method="POST" enctype="multipart/form-data" style="display: none;">
        @csrf
    </form>
@endsection

// ========== Глобальный блок скриптов для мобильной галереи ==========
@section('scripts')
    <script>
        // ===== Поиск по номеру воркрдера с крестиком =====
        const searchInput = document.getElementById('searchInput');
        const clearBtn = document.getElementById('clearSearch');

        searchInput.addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            clearBtn.style.display = this.value ? 'block' : 'none';

            document.querySelectorAll('tbody tr').forEach(row => {
                const workorder = row.querySelector('td')?.textContent?.toLowerCase() || '';
                row.style.display = workorder.includes(filter) ? '' : 'none';
            });
        });

        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            clearBtn.style.display = 'none';
            document.querySelectorAll('tbody tr').forEach(row => row.style.display = '');
            searchInput.focus();
        });

        // ===== Утилита для HTTP-запросов (POST/DELETE и т.п.) через fetch =====
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
                const text = await response.text();
                console.log('RAW RESPONSE:', text);

                const data = JSON.parse(text);

                // ❗ Убираем проверку на response.ok
                if (!data.success) throw new Error(data.message || 'Ошибка ответа от сервера');
                return data;

            } catch (err) {
                console.error('makeRequest error:', err);
                throw err;
            }
        }

        // ===== Создание кнопки удаления фото в тулбаре Fancybox =====
        function createDeleteButton(fancybox, workorderId, workorderNumber, category) {
            const toolbar = document.querySelector('.fancybox__toolbar');
            if (!toolbar || document.querySelector('.fancybox__button--delete')) return;

            const btn = document.createElement('button');
            btn.className = 'fancybox__button fancybox__button--delete';
            btn.title = 'Удалить фото';
            btn.innerHTML = '🗑️';

            btn.onclick = async () => {
                const slide = fancybox.getSlide();
                const mediaId = slide.triggerEl?.dataset.mediaId;
                if (!mediaId) return alert('ID не найден');
                if (!confirm('Удалить это фото?')) return;

                try {
                    const data = await makeRequest(`/mobile/workorders/photo/delete/${mediaId}`, 'DELETE');

                    if (data.success) {
                        refreshGalleryAfterDeletion(workorderId, workorderNumber, category);
                        fancybox.close();
                    } else {
                        console.log('Ответ сервера:', data);
                        alert('Ошибка при удалении');
                    }
                } catch (e) {
                    console.error('Ошибка удаления:', e);
                    alert('Ошибка удаления');
                }
            };
            const counter = toolbar.querySelector('.fancybox__counter');
            if (counter) {
                counter.before(btn);
            } else {
                toolbar.prepend(btn);
            }
        }

        // ===== Обновление галереи и бейджа после удаления фото =====
        async function refreshGalleryAfterDeletion(workorderId, workorderNumber, category) {
            try {
                const response = await makeRequest(`/mobile/workorders/photos/${workorderId}?category=${category}`, 'GET');

                // Ожидаем, что сервер вернёт: { success: true, photos: [...], photo_count: N }
                if (response.success) {
                    updateGallery(workorderId, workorderNumber, response, category);
                    setTimeout(() => {
                        bindFancybox(workorderId, workorderNumber, category);
                    }, 100);
                } else {
                    console.warn('Не удалось обновить ячейку после удаления');
                }
            } catch (e) {
                console.error('Ошибка при обновлении галереи после удаления:', e);
            }
        }

        // ===== Полная перерисовка ячейки галереи на основе новых данных =====
        function updateGallery(workorderId, workorderNumber, response, category) {
            const cell = document.querySelector(`td[data-workorder-id="${workorderId}"][data-category="${category}"]`);
            if (!cell) return;

            cell.innerHTML = '';

            if (response.photos && response.photos.length > 0) {
                const wrapper = document.createElement('div');
                wrapper.style.position = 'relative';
                wrapper.style.display = 'inline-block';
                wrapper.style.margin = '5px';

                response.photos.forEach((photo, index) => {
                    const a = document.createElement('a');
                    a.href = photo.big_url;
                    a.setAttribute('data-fancybox', `gallery-${workorderId}-${category}`);
                    a.setAttribute('data-media-id', photo.id);
                    a.setAttribute('data-caption', `Workorder: ${workorderNumber} - ${category}`);
                    if (index > 0) a.style.display = 'none';

                    const img = document.createElement('img');
                    img.src = photo.thumb_url;
                    img.alt = 'Photo';
                    img.className = 'rounded-circle fade-in';
                    img.style.width = '40px';
                    img.style.height = '40px';
                    img.style.objectFit = 'cover';

                    a.appendChild(img);
                    wrapper.appendChild(a);
                });

                const badge = document.createElement('span');
                badge.className = 'little-info';
                badge.innerText = response.photo_count > 99 ? '99+' : response.photo_count;
                wrapper.appendChild(badge);
                cell.appendChild(wrapper);
            } else {
                cell.innerHTML = '<span class="text-white-50">No Photos</span>';
            }
        }

        // ===== Привязка fancybox к новой галерее =====
        function bindFancybox(workorderId, workorderNumber, category) {
            Fancybox.unbind(`[data-fancybox="gallery-${workorderId}-${category}"]`);
            Fancybox.bind(`[data-fancybox="gallery-${workorderId}-${category}"]`, {
                animated: true,
                compact: true,
                dragToClose: true,
                toolbar: [
                    {id: "counter", position: "left"},
                    {id: "close", position: "right"}
                ],
                on: {
                    done: (fancybox) => createDeleteButton(fancybox, workorderId, workorderNumber, category),
                    close: () => {
                        const btn = document.querySelector('.fancybox__button--delete');
                        if (btn) btn.remove();
                    }
                }
            });
        }

        // ===== Открытие камеры, создание input и запуск загрузки =====
        function openCamera(workorderId, workorderNumber) {
            currentWorkorderId = workorderId;
            currentWorkorderNumber = workorderNumber;

            const form = document.getElementById('photo-upload-form');
            const oldInput = document.getElementById('camera-input');
            if (oldInput) oldInput.remove();

            const newInput = document.createElement('input');
            newInput.type = 'file';
            newInput.id = 'camera-input';
            newInput.name = 'photos[]';
            newInput.accept = 'image/*';
            newInput.multiple = true;
            newInput.capture = 'environment';
            newInput.style.display = 'none';

            newInput.onchange = () => {
                if (newInput.files.length > 0) {
                    showLoadingSpinner(); // Показать спинер после выбора
                    submitPhotos(currentPhotoCategory);
                }
            };

            form.appendChild(newInput);
            newInput.click();
        }

        // ===== Отправка фото на сервер =====
        async function submitPhotos(category) {
            const form = document.getElementById('photo-upload-form');
            const input = document.getElementById('camera-input');
            const urlTemplate = form.dataset.urlTemplate;
            form.action = urlTemplate.replace('WORKORDER_ID', currentWorkorderId) + `?category=${category}`;

            const formData = new FormData();
            for (let file of input.files) {
                formData.append('photos[]', file);
            }

            try {
                const response = await makeRequest(form.action, 'POST', formData);
                if (response.success) {
                    updateGallery(currentWorkorderId, currentWorkorderNumber, response, category);
                    await new Promise(resolve => setTimeout(resolve, 100));
                    bindFancybox(currentWorkorderId, currentWorkorderNumber, category);
                } else {
                    alert('Ошибка загрузки фото');
                }
            } catch (e) {
                alert('Ошибка сети');
            } finally {
                hideLoadingSpinner(); // Скрыть спинер после ответа
            }
        }

        // ===== Глобальные переменные =====
        let currentPhotoCategory = 'photos';
        let currentWorkorderId = null;
        let currentWorkorderNumber = null;

        // ===== Обработка кликов по заголовкам категорий =====
        document.querySelectorAll('.category-header').forEach((header, index) => {
            header.addEventListener('click', () => {
                document.querySelectorAll('.category-header').forEach(h => h.classList.remove('active'));
                header.classList.add('active');
                currentPhotoCategory = header.dataset.category;
                clearColumnHighlights();
                highlightColumn(index + 1);
            });
        });

        // ===== Очистка подсветки колонок =====
        function clearColumnHighlights() {
            document.querySelectorAll('th, td').forEach(el => el.classList.remove('active-column'));
        }

        // ===== Подсветка выбранной колонки =====
        function highlightColumn(index) {
            document.querySelectorAll('table tr').forEach(row => {
                const cells = row.querySelectorAll('th, td');
                if (cells.length > index) {
                    cells[index].classList.add('active-column');
                }
            });
        }

        // ===== Привязка fancybox ко всем галереям после загрузки =====
        window.addEventListener('load', () => {
            document.querySelectorAll('td[data-workorder-id][data-category]').forEach(cell => {
                const workorderId = cell.dataset.workorderId;
                const category = cell.dataset.category;
                const workorderNumber = cell.querySelector('a[data-fancybox]')?.dataset?.caption?.split(': ')[1]?.split(' - ')[0] || '';
                bindFancybox(workorderId, workorderNumber, category);
            });
        });

        // ===== Клик по номеру воркордера — фильтрация =====
        document.querySelectorAll('.workorder-click').forEach(el => {
            el.addEventListener('click', () => {
                const number = el.dataset.number;
                searchInput.value = number;
                clearBtn.style.display = 'block';

                document.querySelectorAll('tbody tr').forEach(row => {
                    const workorder = row.querySelector('td')?.textContent?.toLowerCase() || '';
                    row.style.display = workorder.includes(number.toLowerCase()) ? '' : 'none';
                });
            });
        });

    </script>
@endsection

