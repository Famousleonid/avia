<script>
// mains-photos-logs.js
// Всё, что связано с фото (модалка, удаление, ZIP) и логами (activity)

document.addEventListener('DOMContentLoaded', () => {

    // =========================
    // 1. Фото — подтверждение удаления
    //    Модалка: #confirmDeletePhotoModal
    //    Кнопка: #confirmPhotoDeleteBtn
    //    window.pendingDelete = {mediaId, photoBlock}
    // =========================
    const confirmPhotoBtn = document.getElementById('confirmPhotoDeleteBtn');

    confirmPhotoBtn?.addEventListener('click', async function () {
        const {mediaId, photoBlock} = window.pendingDelete || {};
        if (!mediaId) return;

        safeShowSpinner();

        try {
            const response = await fetch(`/workorders/photo/delete/${mediaId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                if (photoBlock) {
                    photoBlock.style.transition = 'opacity 0.3s ease';
                    photoBlock.style.opacity    = '0';
                }

                setTimeout(() => {
                    photoBlock?.remove();
                    if (window.currentWorkorderId) {
                        loadPhotoModal(window.currentWorkorderId);
                    }
                }, 300);

                const toastEl = document.getElementById('photoDeletedToast');
                if (toastEl) {
                    const toast = new bootstrap.Toast(toastEl);
                    toast.show();
                }
            } else {
                alert('Failed to delete photo');
            }
        } catch (err) {
            console.error('Delete error:', err);
            alert('Server error');
        } finally {
            safeHideSpinner();
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmDeletePhotoModal'));
            modal?.hide();
            window.pendingDelete = null;
        }
    });

    // =========================
    // 2. Фото — открытие модалки и загрузка
    //    Кнопки: .open-photo-modal[data-id][data-number]
    //    Модалка: #photoModal
    //    Контент: #photoModalContent
    // =========================
    document.querySelectorAll('.open-photo-modal').forEach(button => {
        button.addEventListener('click', async function () {
            const workorderId     = this.dataset.id;
            const workorderNumber = this.dataset.number;

            window.currentWorkorderId     = workorderId;
            window.currentWorkorderNumber = workorderNumber;

            await loadPhotoModal(workorderId);
            new bootstrap.Modal(document.getElementById('photoModal')).show();
        });
    });

    async function loadPhotoModal(workorderId) {
        const modalContent = document.getElementById('photoModalContent');
        if (!modalContent) return;

        safeShowSpinner();

        try {
            const response = await fetch(`/workorders/${workorderId}/photos`);

            if (!response.ok) {
                throw new Error('Response not ok');
            }

            const data = await response.json();

            let html = '';

            const groupsConfig = {
                photos:  'Photos',
                damages: 'Damage',
                logs:    'Log card',
                final:   'Final assy'
            };

            Object.entries(groupsConfig).forEach(([group, label]) => {
                const items = data[group] || [];

                html += `
                    <div class="col-12">
                        <h6 class="text-primary text-uppercase mt-2">${label}</h6>
                        <div class="row g-2">
                `;

                if (!items.length) {
                    html += `
                        <div class="col-12 text-muted small">No photos</div>
                    `;
                } else {
                    items.forEach(media => {
                        html += `
                            <div class="col-4 col-md-2 col-lg-1 photo-item">
                                <div class="position-relative d-inline-block w-100">
                                    <a data-fancybox="${group}" href="${media.big}" data-caption="${label}">
                                        <img src="${media.thumb}" class="photo-thumbnail border border-primary rounded" />
                                    </a>
                                    <button class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center position-absolute delete-photo-btn"
                                            style="top: -6px; right: -6px; width: 20px; height: 20px; z-index: 10;"
                                            data-id="${media.id}" title="Delete">
                                        <i class="bi bi-x" style="font-size: 12px;"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                }

                html += `
                        </div>
                    </div>
                `;
            });

            modalContent.innerHTML = html;
            bindDeleteButtons();

        } catch (e) {
            console.error('Load photo error', e);
            modalContent.innerHTML = '<div class="text-danger">Failed to load photos</div>';
        } finally {
            safeHideSpinner();
        }
    }

    function bindDeleteButtons() {
        document.querySelectorAll('.delete-photo-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const mediaId    = this.dataset.id;
                const photoBlock = this.closest('.photo-item');

                window.pendingDelete = {mediaId, photoBlock};
                new bootstrap.Modal(document.getElementById('confirmDeletePhotoModal')).show();
            });
        });
    }

    // =========================
    // 3. Фото — скачать все в ZIP
    //    Кнопка: #saveAllPhotos
    // =========================
    document.getElementById('saveAllPhotos')?.addEventListener('click', function () {
        const workorderId     = window.currentWorkorderId;
        const workorderNumber = window.currentWorkorderNumber || 'workorder';
        if (!workorderId) return alert('Workorder ID missing');

        safeShowSpinner();

        fetch(`/workorders/download/${workorderId}/all`)
            .then(response => {
                if (!response.ok) throw new Error('Download failed');
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a   = document.createElement('a');
                a.href    = url;
                a.download = `workorder_${workorderNumber}_images.zip`;
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(err => {
                console.error('Error downloading ZIP:', err);
                alert('Download failed');
            })
            .finally(() => {
                safeHideSpinner();
            });
    });

    // =========================
    // 4. ЛОГИ (activity) — .open-log-modal
    //    Модалка: #logModal
    //    Контент: #logModalContent
    // =========================
    document.querySelectorAll('.open-log-modal').forEach(btn => {
        btn.addEventListener('click', async function () {
            const url = this.dataset.url;
            await loadLogModal(url);
            new bootstrap.Modal(document.getElementById('logModal')).show();
        });
    });

    // =========================
    // 5. ЛОГИ (activity) — .js-open-log (кнопки в таблице)
    //    Тоже пользуемся тем же loadLogModal
    // =========================
    const logModalEl = document.getElementById('logModal');
    const logModal   = logModalEl ? new bootstrap.Modal(logModalEl) : null;

    document.addEventListener('click', async (e) => {
        const b = e.target.closest('.js-open-log');
        if (!b) return;

        const url = b.dataset.url;
        await loadLogModal(url);
        logModal?.show();
    });

    // ОДНА общая функция отрисовки логов (с деталями изменений)
    async function loadLogModal(url) {
        const container = document.getElementById('logModalContent');
        if (!container) return;

        container.innerHTML = '<div class="text-muted small">Loading...</div>';

        try {
            if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

            const resp = await fetch(url, {
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                credentials: 'same-origin'
            });

            if (!resp.ok) throw new Error('Response not ok');

            const data = await resp.json();

            if (!data || !data.length) {
                container.innerHTML = '<div class="text-muted small">No log entries for this workorder.</div>';
                return;
            }

            let html = '';

            data.forEach(item => {
                const created = item.created_at ?? '';
                const desc    = item.description ?? '';
                const event   = item.event ?? '';
                const causer  = item.causer_name ?? '';
                const changes = item.changes ?? [];

                let badgeClass, icon;
                if (event === 'created') {
                    badgeClass = 'bg-success';
                    icon       = '<i class="bi bi-check-circle me-1"></i>';
                } else if (event === 'updated') {
                    badgeClass = 'bg-warning text-dark';
                    icon       = '<i class="bi bi-pencil-square me-1"></i>';
                } else if (event === 'deleted') {
                    badgeClass = 'bg-danger';
                    icon       = '<i class="bi bi-x-circle me-1"></i>';
                } else {
                    badgeClass = 'bg-secondary';
                    icon       = '<i class="bi bi-info-circle me-1"></i>';
                }

                html += `
                    <div class="p-3 mb-3 border rounded bg-dark bg-opacity-25">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="text-info">${created}</strong>
                                ${causer ? `<span class="text-muted ms-2">${causer}</span>` : ''}
                                ${desc ? `<span class="ms-2 text-light">${desc}</span>` : ''}
                            </div>
                            <span class="badge rounded-pill ${badgeClass}">
                                ${icon}${event || 'log'}
                            </span>
                        </div>
                `;

                if (changes.length) {
                    html += `<ul class="mt-2 mb-0 ps-3">`;
                    changes.forEach(ch => {
                        html += `
                            <li>
                                <strong>${ch.label}:</strong>
                                <span class="text-danger">${ch.old ?? '—'}</span>
                                <span class="text-muted mx-1">→</span>
                                <span class="text-success">${ch.new ?? '—'}</span>
                            </li>
                        `;
                    });
                    html += `</ul>`;
                }

                html += `</div>`;
            });

            container.innerHTML = html;

        } catch (e) {
            console.error('Load log error', e);
            container.innerHTML = '<div class="text-danger">Failed to load log</div>';
        } finally {
            if (typeof hideLoadingSpinner === 'function') hideLoadingSpinner();
        }
    }
});
</script>
