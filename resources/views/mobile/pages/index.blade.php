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
            margin-left: 5px !important;
            pointer-events: auto !important;
            position: relative !important;
        }

        .fancybox__toolbar {
            display: flex !important;
            align-items: center !important;
            overflow: visible !important;
            width: auto !important;
            pointer-events: auto !important;
        }
    </style>
@endsection

@section('content')
    <!-- Спиннер загрузки -->
    <div id="loading-spinner" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1050;">
        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

      <!-- Контент -->
    <div class="container-fluid d-flex flex-column bg-dark p-0" style="padding-top: 60px; padding-bottom: 60px; min-height: 100vh;">
        <div class="row flex-grow-1 g-0 p-0 m-0">
            <div class="col-12 d-flex flex-column align-items-center g-0 p-0 m-0">
                <div class="table-responsive" style="max-height: calc(100vh - 120px); overflow-y: auto; width: 100%; margin-top: 60px;">
                    <table class="table table-dark table-striped m-0 w-100 table-bordered">
                        <thead class="bg-primary">
                        <tr>
                            <th class="text-center bg-gradient">Workorder</th>
                            <th class="text-center bg-gradient">Description</th>
                            <th class="text-center bg-gradient">Gallery</th>
                            <th class="text-center bg-gradient">Camera</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($workorders as $workorder)
                            <tr>
                                <td class="text-center">{{ $workorder->number}}</td>
                                <td class="text-start">{{ $workorder->unit->part_number }}</td>
                                <td class="text-center" data-workorder-id="{{ $workorder->id }}">
                                    @php
                                        $photoCount = $workorder->getMedia('photos')->count();
                                    @endphp

                                    @if ($photoCount > 0)
                                        <div style="position: relative; display: inline-block; margin: 5px;">
                                            @foreach($workorder->getMedia('photos') as $index => $photo)
                                                <a href="{{ route('image.show.big', ['mediaId' => $photo->id, 'modelId' => $workorder->id, 'mediaName' => 'photos']) }}"
                                                   data-fancybox="gallery-{{ $workorder->id }}"
                                                   data-media-id="{{ $photo->id }}"
                                                   data-caption="Workorder: {{ $workorder->number }}"
                                                   style="{{ $index === 0 ? '' : 'display: none;' }}"> {{-- Показывать только первую фотку --}}
                                                    <img
                                                        class="rounded-circle"
                                                        src="{{ route('image.show.thumb', ['mediaId' => $photo->id, 'modelId' => $workorder->id, 'mediaName' => 'photos']) }}"
                                                        width="40"
                                                        height="40"
                                                        alt="Photo">
                                                </a>
                                            @endforeach
                                            <span class="little-info">{{ $photoCount > 99 ? '99+' : $photoCount }}</span>
                                        </div>
                                    @else
                                        <span class="text-white-50">No Photos</span>
                                    @endif
                                </td>
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

@section('scripts')
    <script>
        const pageSpinner = document.getElementById('loading-spinner');
        pageSpinner.style.display = 'block';

        async function makeRequest(url, method, body = null) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            console.log(`Making request: ${method} ${url}, CSRF Token: ${csrfToken || 'Not found'}`);
            const headers = {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            const options = { method, headers };
            if (body) options.body = body;

            const spinner = document.getElementById('loading-spinner');
            if (spinner) spinner.style.display = 'block';

            try {
                const response = await fetch(url, options);
                const contentType = response.headers.get('content-type');

                if (response.status === 401 || (response.status === 404 && contentType?.includes('text/html'))) {
                    throw new Error('401 Unauthorized: Сессия истекла');
                }
                if (!response.ok) {
                    const text = await response.text();
                    console.log(`Server response: ${text}`);
                    throw new Error(`Ошибка сервера: ${response.status} ${response.statusText} - ${text}`);
                }
                return await response.json();
            } catch (error) {
                console.error(`Ошибка запроса (${method} ${url}):`, error.message);
                if (error.message.includes('401')) {
                    alert('Сессия истекла. Пожалуйста, войдите снова.');
                    window.location.href = '/mobile/login';
                    return;
                }
                alert(`Ошибка: ${error.message}`);
                throw error;
            } finally {
                if (spinner) spinner.style.display = 'none';
            }
        }

        function createDeleteButton(fancybox, workorderId, callback) {
            const toolbar = document.querySelector('.fancybox__toolbar');
            if (!toolbar) return;

            let deleteButton = document.querySelector('.fancybox__button--delete');
            if (deleteButton) return;

            deleteButton = document.createElement('button');
            deleteButton.className = 'fancybox__button fancybox__button--delete';
            deleteButton.title = 'Удалить фото';
            deleteButton.innerHTML = '🗑️';

            deleteButton.addEventListener('click', async () => {
                if (!confirm('Удалить это фото?')) return;

                const slide = fancybox.getSlide();
                const mediaId = slide.triggerEl?.dataset?.mediaId || slide.triggerEl?.getAttribute('data-media-id');
                if (!mediaId || mediaId === 'undefined' || !workorderId) {
                    alert('Ошибка: ID фотографии или workorder не найден.');
                    console.log('Slide trigger element:', slide.triggerEl);
                    console.log('mediaId:', mediaId, 'workorderId:', workorderId);
                    return;
                }

                try {
                    const data = await makeRequest(`/mobile/workorders/photo/delete/${mediaId}`, 'DELETE');
                    if (data.success) {
                        slide.triggerEl?.remove();

                        const remainingImages = document.querySelectorAll(`a[data-fancybox="gallery-${workorderId}"]`);
                        const galleryCell = document.querySelector(`td[data-workorder-id="${workorderId}"]`);

                        if (remainingImages.length === 0) {
                            if (galleryCell) {
                                galleryCell.innerHTML = '<span class="text-white-50">No Photos</span>';
                            }
                        } else {
                            const response = await makeRequest(`/mobile/workorders/photos/${workorderId}`, 'GET');
                            console.log('Server response in createDeleteButton:', response);
                            if (response.success) {
                                callback(workorderId, slide.triggerEl?.dataset?.caption?.replace('Workorder: ', '') || '');
                            }
                        }

                        fancybox.close();

                        document.body.style.overflow = 'auto';
                        document.body.style.position = 'static';
                        document.body.style.width = 'auto';
                        document.body.style.height = 'auto';
                        document.body.style.margin = '0';
                        document.body.style.padding = '0';

                        const tableContainer = document.querySelector('.table-responsive');
                        const table = document.querySelector('.table');
                        const parentContainer = document.querySelector('.container-fluid');
                        const rowContainer = document.querySelector('.row');
                        const colContainer = document.querySelector('.col-12');

                        if (tableContainer) {
                            tableContainer.style.display = 'block';
                            tableContainer.style.visibility = 'visible';
                            tableContainer.style.opacity = '1';
                            tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                        if (table) {
                            table.style.display = 'table';
                            table.style.visibility = 'visible';
                            table.style.opacity = '1';
                        }
                        if (parentContainer) {
                            parentContainer.style.display = 'flex';
                            parentContainer.style.visibility = 'visible';
                            parentContainer.style.opacity = '1';
                        }
                        if (rowContainer) {
                            rowContainer.style.display = 'flex';
                            rowContainer.style.visibility = 'visible';
                            rowContainer.style.opacity = '1';
                        }
                        if (colContainer) {
                            colContainer.style.display = 'flex';
                            colContainer.style.visibility = 'visible';
                            colContainer.style.opacity = '1';
                        }
                    } else {
                        alert('Не удалось удалить фотографию: ' + (data.message || 'Неизвестная ошибка'));
                    }
                } catch (error) {
                    if (error.message.includes('401')) {
                        alert('Сессия истекла. Пожалуйста, войдите снова.');
                        window.location.href = '/mobile/login';
                    }
                }
            });

            const counter = toolbar.querySelector('.fancybox__counter');
            if (counter) {
                counter.after(deleteButton);
            } else {
                toolbar.prepend(deleteButton);
            }
        }

        function bindFancybox(workorderId, workorderNumber) {
            const galleryElements = document.querySelectorAll(`[data-fancybox="gallery-${workorderId}"]:not(.fancybox-bound)`);
            console.log(`Binding Fancybox for workorder ${workorderId}, elements found: ${galleryElements.length}`);
            if (galleryElements.length === 0) return;

            Fancybox.unbind(`[data-fancybox="gallery-${workorderId}"]`);
            Fancybox.bind(`[data-fancybox="gallery-${workorderId}"]:not(.fancybox-bound)`, {
                animated: true,
                compact: true,
                dragToClose: true,
                toolbar: {
                    display: [
                        { id: "counter", position: "left" },
                        { id: "close", position: "right" }
                    ]
                },
                on: {
                    done: (fancybox) => {
                        createDeleteButton(fancybox, workorderId, async (id, number) => {
                            const data = await makeRequest(`/mobile/workorders/photos/${id}`, 'GET');
                            if (data.success) {
                                updateGallery(id, number, data);
                            }
                        });
                    },
                    close: (fancybox) => {
                        const deleteButton = document.querySelector('.fancybox__button--delete');
                        if (deleteButton) deleteButton.remove();
                    }
                }
            });
        }

        function updateGallery(workorderId, workorderNumber, response) {
            const galleryCell = document.querySelector(`td[data-workorder-id="${workorderId}"]`);
            if (!galleryCell) {
                alert('Галерея не найдена для workorder: ' + workorderId);
                return;
            }

            galleryCell.innerHTML = '';

            if (response.photos && response.photos.length > 0) {
                const wrapper = document.createElement('div');
                wrapper.style.position = 'relative';
                wrapper.style.display = 'inline-block';
                wrapper.style.margin = '5px';

                response.photos.forEach((photo, index) => {
                    if (!photo.id) {
                        console.error('Photo ID is missing:', photo);
                        return;
                    }

                    const a = document.createElement('a');
                    a.href = photo.big_url;
                    a.setAttribute('data-fancybox', `gallery-${workorderId}`);
                    a.setAttribute('data-media-id', photo.id);
                    a.setAttribute('data-caption', `Workorder: ${workorderNumber}`);
                    if (index > 0) a.style.display = 'none';

                    const img = document.createElement('img');
                    img.src = photo.thumb_url;
                    img.alt = photo.alt || 'Photo';
                    img.style.width = '40px';
                    img.style.height = '40px';
                    img.style.objectFit = 'cover';
                    img.classList.add('rounded-circle', 'fade-in');
                    img.style.margin = '2px';

                    a.appendChild(img);
                    wrapper.appendChild(a);
                    console.log(`Created element with mediaId ${photo.id}:`, a);
                });

                const badge = document.createElement('span');
                badge.className = 'little-info';
                badge.innerText = response.photo_count > 99 ? '99+' : response.photo_count;
                wrapper.appendChild(badge);

                galleryCell.appendChild(wrapper);

                console.log(`Total elements in gallery-${workorderId}:`, document.querySelectorAll(`[data-fancybox="gallery-${workorderId}"]`).length);
                bindFancybox(workorderId, workorderNumber);
            } else {
                galleryCell.innerHTML = '<span class="text-white-50">No Photos</span>';
            }

            const table = galleryCell.closest('table');
            const tableContainer = galleryCell.closest('.table-responsive');
            console.log('Table visibility:', table.offsetHeight > 0 ? 'Visible' : 'Hidden');
            if (tableContainer) {
                tableContainer.style.display = 'block';
                tableContainer.style.visibility = 'visible';
                tableContainer.style.opacity = '1';
            }
            if (table) {
                table.style.display = 'table';
                table.style.visibility = 'visible';
                table.style.opacity = '1';
            }
        }

        function updatePhotoBadge(workorderId) {
            const cell = document.querySelector(`td[data-workorder-id="${workorderId}"]`);
            if (!cell) return;

            const images = document.querySelectorAll(`a[data-fancybox="gallery-${workorderId}"]`);
            const count = images.length;

            const badge = cell.querySelector('.little-info');
            if (badge) {
                badge.innerText = count > 99 ? '99+' : count;
            }

            if (count === 0) {
                cell.innerHTML = '<span class="text-white-50">No Photos</span>';
            }
        }

        let currentWorkorderId = null;
        let currentWorkorderNumber = null;

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
                    submitPhotos();
                }
            };
            form.appendChild(newInput);
            newInput.click();
        }

        async function submitPhotos() {
            const form = document.getElementById('photo-upload-form');
            const input = document.getElementById('camera-input');
            const urlTemplate = form.dataset.urlTemplate;

            if (input.files.length === 0) {
                alert('Фотография не выбрана.');
                return;
            }

            form.action = urlTemplate.replace('WORKORDER_ID', currentWorkorderId);

            const formData = new FormData();
            for (let i = 0; i < input.files.length; i++) {
                formData.append('photos[]', input.files[i]);
            }

            try {
                const data = await makeRequest(form.action, 'POST', formData);
                console.log('Server response in submitPhotos:', data);
                if (data.success) {
                    updateGallery(currentWorkorderId, currentWorkorderNumber, data);
                    await new Promise(resolve => setTimeout(resolve, 100));
                    bindFancybox(currentWorkorderId, currentWorkorderNumber);
                } else {
                    alert('Ошибка загрузки фотографий: ' + (data.message || 'Неизвестная ошибка'));
                }
            } catch (error) {
                // Ошибка уже обработана в makeRequest
            }
        }

        window.addEventListener('load', () => {
            pageSpinner.style.display = 'none';

            document.querySelectorAll('td[data-workorder-id]').forEach(cell => {
                const workorderId = cell.dataset.workorderId;
                const workorderNumber = cell.querySelector('a[data-fancybox]')?.dataset?.caption?.replace('Workorder: ', '') || '';
                bindFancybox(workorderId, workorderNumber);
            });
        });
    </script>
@endsection
