<script>
    // mains-photos-logs.js
    // Фото: модалка, удаление, ZIP, drag&drop между группами, upload в группу
    // Логи(activity): модалка, загрузка html/json

    document.addEventListener('DOMContentLoaded', () => {

        // =====================================================
        // Helpers
        // =====================================================
        const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
        const showSpin = () => {
            if (typeof safeShowSpinner === 'function') safeShowSpinner();
        };
        const hideSpin = () => {
            if (typeof safeHideSpinner === 'function') safeHideSpinner();
        };

        function getEl(id) {
            return document.getElementById(id);
        }

        // =====================================================
        // 1) PHOTO DELETE CONFIRM
        //    Modal: #confirmDeletePhotoModal
        //    Button: #confirmPhotoDeleteBtn
        //    window.pendingDelete = { mediaId, photoBlock }
        // =====================================================
        const confirmPhotoBtn = getEl('confirmPhotoDeleteBtn');

        confirmPhotoBtn?.addEventListener('click', async function () {
            const {mediaId, photoBlock} = window.pendingDelete || {};
            if (!mediaId) return;

            showSpin();

            try {
                const response = await fetch(`/workorders/photo/delete/${mediaId}`, {
                    method: 'DELETE',
                    headers: {'X-CSRF-TOKEN': csrf()}
                });

                if (!response.ok) throw new Error('Delete failed');

                // fade
                if (photoBlock) {
                    photoBlock.style.transition = 'opacity 0.25s ease';
                    photoBlock.style.opacity = '0';
                }

                setTimeout(async () => {
                    photoBlock?.remove();
                    if (window.currentWorkorderId) {
                        await loadPhotoModal(window.currentWorkorderId);
                    }
                }, 250);

                // toast
                const toastEl = getEl('photoDeletedToast');
                if (toastEl && typeof bootstrap !== 'undefined') {
                    new bootstrap.Toast(toastEl).show();
                }

            } catch (err) {
                console.error('Delete error:', err);
                alert('Failed to delete photo');
            } finally {
                hideSpin();
                const modalEl = getEl('confirmDeletePhotoModal');
                const modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
                modal?.hide();
                window.pendingDelete = null;
            }
        });

        // =====================================================
        // 2) OPEN PHOTO MODAL (.open-photo-modal)
        // =====================================================
        document.querySelectorAll('.open-photo-modal').forEach(button => {
            button.addEventListener('click', async function () {
                const workorderId = this.dataset.id;
                const workorderNumber = this.dataset.number;

                window.currentWorkorderId = workorderId;
                window.currentWorkorderNumber = workorderNumber;

                await loadPhotoModal(workorderId);

                const modalEl = getEl('photoModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    new bootstrap.Modal(modalEl).show();
                }
            });
        });

        // =====================================================
        // 2a) LOAD PHOTO MODAL CONTENT
        //     expects: { groups: {key:label}, media: { key: [{id,thumb,big}] } }
        // =====================================================
        async function loadPhotoModal(workorderId) {
            const modalContent = getEl('photoModalContent');
            if (!modalContent) return;

            showSpin();

            let data = null;

            try {
                const response = await fetch(`/workorders/${workorderId}/photos`, {
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    credentials: 'same-origin'
                });

                if (!response.ok) throw new Error('Response not ok');
                data = await response.json();

                if (!data || !data.groups || !data.media) {
                    throw new Error('Invalid response format');
                }

                const groupsConfig = data.groups;
                const mediaData = data.media;

                let html = '';

                Object.entries(groupsConfig).forEach(([group, label]) => {
                    const items = mediaData[group] || [];

                    html += `
  <div class="col-12 media-group" data-group="${group}">
    <div class="d-flex align-items-center justify-content-between mt-2">
      <h6 class="text-primary text-uppercase mb-1">${label}</h6>

      <div class="d-flex gap-2 align-items-center">
        <input type="file"
               class="d-none group-upload-input"
               data-group="${group}"
               multiple
               accept="image/*" />

        <button type="button"
                class="btn btn-sm btn-outline-info group-upload-btn"
                data-group="${group}">
          <i class="bi bi-plus-lg"></i> Add
        </button>

        <button type="button"
                class="btn btn-sm btn-outline-secondary group-download-btn"
                data-group="${group}">
          <i class="bi bi-download"></i>
        </button>
      </div>
    </div>

    <div class="group-dropzone rounded p-2 mt-1" data-group="${group}">
      <div class="row g-2 group-grid">
`;

                    if (!items.length) {
                        html += `<div class="col-12 text-muted small">No photos</div>`;
                    } else {
                        items.forEach(media => {
                            html += `
                            <div class="col-4 col-md-2 col-lg-1 photo-item"
                                 draggable="true"
                                 data-media-id="${media.id}"
                                 data-group="${group}">
                                <div class="position-relative d-inline-block w-100">
                                    <a data-fancybox="${group}" href="${media.big}" data-caption="${label}">
                                        <img src="${media.thumb}" class="photo-thumbnail border border-primary rounded" />
                                    </a>

                                    <button class="btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center position-absolute delete-photo-btn"
                                            style="top:-6px;right:-6px;width:20px;height:20px;z-index:10;"
                                            data-id="${media.id}" title="Delete">
                                        <i class="bi bi-x" style="font-size:12px;"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                        });
                    }

                    html += `
      </div>
    </div>
    <hr class="group-hr my-2">
  </div>
`;
                });

                modalContent.innerHTML = html;

                // bind after render
                bindDeleteButtons();
                bindUploadButtons();
                bindGroupDownloadButtons();
                bindDnD();

            } catch (e) {
                console.error('Load photo error', e, data);
                modalContent.innerHTML = '<div class="text-danger">Failed to load photos</div>';
            } finally {
                hideSpin();
            }
        }

        // =====================================================
        // 2b) bind delete buttons (dynamic)
        // =====================================================
        function bindDeleteButtons() {
            document.querySelectorAll('.delete-photo-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const mediaId = this.dataset.id;
                    const photoBlock = this.closest('.photo-item');

                    window.pendingDelete = {mediaId, photoBlock};

                    const modalEl = getEl('confirmDeletePhotoModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        new bootstrap.Modal(modalEl).show();
                    }
                });
            });
        }

        // =====================================================
        // 2c) Upload to group
        // =====================================================
        function bindUploadButtons() {
            // click "Add" -> open file dialog
            document.querySelectorAll('.group-upload-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const g = btn.dataset.group;
                    document.querySelector(`.group-upload-input[data-group="${g}"]`)?.click();
                });
            });

            // file selected -> upload
            document.querySelectorAll('.group-upload-input').forEach(inp => {
                inp.addEventListener('change', async () => {
                    const files = inp.files;
                    const group = inp.dataset.group;
                    const workorderId = window.currentWorkorderId;

                    if (!workorderId) return alert('Workorder ID missing');
                    if (!files || !files.length) return;

                    const fd = new FormData();
                    fd.append('group', group);
                    Array.from(files).forEach(f => fd.append('files[]', f));

                    showSpin();
                    try {
                        const resp = await fetch(`/workorders/${workorderId}/media/upload`, {
                            method: 'POST',
                            headers: {'X-CSRF-TOKEN': csrf()},
                            body: fd
                        });

                        if (!resp.ok) throw new Error('Upload failed');

                        await loadPhotoModal(workorderId);
                    } catch (e) {
                        console.error('Upload error:', e);
                        alert('Upload failed');
                    } finally {
                        inp.value = '';
                        hideSpin();
                    }
                });
            });
        }

        function bindGroupDownloadButtons() {
            document.querySelectorAll('.group-download-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const group = btn.dataset.group;
                    const workorderId = window.currentWorkorderId;
                    const workorderNumber = window.currentWorkorderNumber || 'workorder';

                    if (!workorderId) return alert('Workorder ID missing');
                    if (!group) return;

                    showSpin();
                    try {
                        const resp = await fetch(`/workorders/download/${workorderId}/group/${group}`);
                        if (!resp.ok) throw new Error('Download group failed');

                        const blob = await resp.blob();
                        const url = window.URL.createObjectURL(blob);

                        const a = document.createElement('a');
                        a.href = url;
                        a.download = `workorder_${workorderNumber}_${group}.zip`;
                        a.click();

                        window.URL.revokeObjectURL(url);
                    } catch (e) {
                        console.error(e);
                        alert('Download failed');
                    } finally {
                        hideSpin();
                    }
                });
            });
        }


        // =====================================================
// 2d) Drag & Drop between groups + AUTO SCROLL
// =====================================================
        function bindDnD() {
            // защита от повторной инициализации (после перерисовки модалки)
            if (window.__photoDnDInited) return;
            window.__photoDnDInited = true;

            let dragMediaId = null;
            let dragFromGroup = null;

            const modalEl = document.getElementById('photoModal');
            if (!modalEl) return;

            const modalBody = modalEl.querySelector('.modal-body');

            // -------------------------
            // AUTO SCROLL while dragging
            // -------------------------
            let rafLock = false;

            function autoScroll(e) {
                if (!modalBody) return;
                if (!dragMediaId) return; // скроллим только когда реально тащим

                const rect = modalBody.getBoundingClientRect();
                const y = e.clientY;

                const EDGE = 80;   // зона у края
                const SPEED = 22;  // скорость

                let delta = 0;
                if (y < rect.top + EDGE) delta = -SPEED;
                else if (y > rect.bottom - EDGE) delta = SPEED;

                if (!delta) return;

                if (rafLock) return;
                rafLock = true;
                requestAnimationFrame(() => {
                    modalBody.scrollTop += delta;
                    rafLock = false;
                });
            }

            // слушаем dragover на modal body (чтобы скроллилось хоть над пустотой)
            modalBody?.addEventListener('dragover', (e) => {
                // важно: preventDefault, иначе drop может не работать в некоторых браузерах
                e.preventDefault();
                autoScroll(e);
            });

            // -------------------------
            // DRAG START / END (delegation)
            // -------------------------
            modalEl.addEventListener('dragstart', (e) => {
                const el = e.target.closest('.photo-item[draggable="true"]');
                if (!el) return;

                dragMediaId = el.dataset.mediaId || null;
                dragFromGroup = el.dataset.group || null;

                try {
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', String(dragMediaId || ''));
                } catch (_) {
                }

                el.classList.add('dragging');
                modalEl.classList.add('dnd-active');

            }, true);

            modalEl.addEventListener('dragend', (e) => {
                const el = e.target.closest('.photo-item[draggable="true"]');
                el?.classList.remove('dragging');

                // снять hover со всех зон
                modalEl.querySelectorAll('.group-dropzone.drop-hover')
                    .forEach(z => z.classList.remove('drop-hover'));

                modalEl.classList.remove('dnd-active');

                dragMediaId = null;
                dragFromGroup = null;
            }, true);

            // -------------------------
            // DROPZONE HOVER + DROP (delegation)
            // -------------------------
            modalEl.addEventListener('dragover', (e) => {
                const zone = e.target.closest('.group-dropzone');
                if (!zone) return;

                e.preventDefault(); // обязательно для drop
                zone.classList.add('drop-hover');
                try {
                    e.dataTransfer.dropEffect = 'move';
                } catch (_) {
                }

                autoScroll(e);
            });

            modalEl.addEventListener('dragleave', (e) => {
                const zone = e.target.closest('.group-dropzone');
                if (!zone) return;

                // dragleave часто стреляет при переходе между детьми — проверяем, реально ли ушли
                const related = e.relatedTarget;
                if (related && zone.contains(related)) return;

                zone.classList.remove('drop-hover');
            });

            modalEl.addEventListener('drop', async (e) => {
                const zone = e.target.closest('.group-dropzone');
                if (!zone) return;

                e.preventDefault();
                zone.classList.remove('drop-hover');
                modalEl.classList.remove('dnd-active');
                const toGroup = zone.dataset.group;
                const workorderId = window.currentWorkorderId;

                // fallback если переменные обнулились
                let fallbackId = null;
                try {
                    fallbackId = e.dataTransfer.getData('text/plain');
                } catch (_) {
                }
                const mediaId = dragMediaId || fallbackId;

                if (!workorderId) return alert('Workorder ID missing');
                if (!mediaId) return;
                if (toGroup && dragFromGroup && toGroup === dragFromGroup) return;

                showSpin();
                try {
                    const resp = await fetch(`/workorders/media/${mediaId}/move`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf()
                        },
                        body: JSON.stringify({
                            workorder_id: workorderId,
                            to: toGroup
                        })
                    });

                    if (!resp.ok) throw new Error('Move failed');

                    await loadPhotoModal(workorderId);
                } catch (err) {
                    console.error('Move error:', err);
                    alert('Move failed');
                } finally {
                    hideSpin();
                }
            });
        }


        // =====================================================
        // 3) DOWNLOAD ALL PHOTOS ZIP
        // =====================================================
        getEl('saveAllPhotos')?.addEventListener('click', function () {
            const workorderId = window.currentWorkorderId;
            const workorderNumber = window.currentWorkorderNumber || 'workorder';
            if (!workorderId) return alert('Workorder ID missing');

            showSpin();

            fetch(`/workorders/download/${workorderId}/all`)
                .then(response => {
                    if (!response.ok) throw new Error('Download failed');
                    return response.blob();
                })
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `workorder_${workorderNumber}_images.zip`;
                    a.click();
                    window.URL.revokeObjectURL(url);
                })
                .catch(err => {
                    console.error('Error downloading ZIP:', err);
                    alert('Download failed');
                })
                .finally(() => hideSpin());
        });

        // =====================================================
        // 4) LOG MODAL (.open-log-modal)
        // =====================================================
        document.querySelectorAll('.open-log-modal').forEach(btn => {
            btn.addEventListener('click', async function () {
                const url = this.dataset.url;
                await loadLogModal(url);

                const modalEl = getEl('logModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    new bootstrap.Modal(modalEl).show();
                }
            });
        });

        // =====================================================
        // 5) LOG MODAL buttons in table (.js-open-log)
        // =====================================================
        const logModalEl = getEl('logModal');
        const logModal = logModalEl && typeof bootstrap !== 'undefined'
            ? new bootstrap.Modal(logModalEl)
            : null;

        document.addEventListener('click', async (e) => {
            const b = e.target.closest('.js-open-log');
            if (!b) return;

            const url = b.dataset.url;
            await loadLogModal(url);
            logModal?.show();
        });

        // =====================================================
        // 6) LOAD LOG MODAL (html or array)
        // =====================================================
        async function loadLogModal(url) {
            const container = getEl('logModalContent');
            if (!container) return;

            container.innerHTML = '<div class="text-muted small">Loading...</div>';

            try {
                // если у тебя есть старые showLoadingSpinner/hideLoadingSpinner - оставим поддержку
                if (typeof showLoadingSpinner === 'function') showLoadingSpinner();

                const resp = await fetch(url, {
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    credentials: 'same-origin'
                });

                if (!resp.ok) throw new Error('Response not ok');

                const data = await resp.json();

                // server may return {html:"..."}
                if (data && typeof data.html === 'string') {
                    container.innerHTML = data.html.trim()
                        ? data.html
                        : '<div class="text-muted small">No activity yet.</div>';
                    return;
                }

                if (!data || !data.length) {
                    container.innerHTML = '<div class="text-muted small">No activity yet.</div>';
                    return;
                }

                let html = '';

                data.forEach(item => {
                    const created = item.created_at ?? '';
                    const desc = item.description ?? '';
                    const event = item.event ?? '';
                    const causer = item.causer_name ?? '';
                    const changes = item.changes ?? [];

                    let badgeClass, icon;
                    if (event === 'created') {
                        badgeClass = 'bg-success';
                        icon = '<i class="bi bi-check-circle me-1"></i>';
                    } else if (event === 'updated') {
                        badgeClass = 'bg-warning text-dark';
                        icon = '<i class="bi bi-pencil-square me-1"></i>';
                    } else if (event === 'deleted') {
                        badgeClass = 'bg-danger';
                        icon = '<i class="bi bi-x-circle me-1"></i>';
                    } else {
                        badgeClass = 'bg-secondary';
                        icon = '<i class="bi bi-info-circle me-1"></i>';
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
