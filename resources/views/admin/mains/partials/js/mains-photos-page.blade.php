<script>
    // Dedicated workorder photos page.

    document.addEventListener('DOMContentLoaded', () => {
        const pageEl = document.getElementById('photoPage');
        const contentEl = document.getElementById('photoPageContent');
        const scrollerEl = document.querySelector('.photo-page-body');
        const workorderId = pageEl?.dataset.workorderId || window.currentWorkorderId;
        const workorderNumber = pageEl?.dataset.workorderNumber || workorderId;

        const urls = {
            list: @json(route('workorders.photos', ['id' => '__WO_ID__'])),
            delete: @json(route('workorders.photo.delete', ['id' => '__MEDIA_ID__'])),
            upload: @json(route('workorders.media.upload', ['workorder' => '__WO_ID__'])),
            move: @json(route('workorders.media.move', ['media' => '__MEDIA_ID__'])),
            downloadAll: @json(route('workorders.downloadAllGrouped', ['id' => '__WO_ID__'])),
            downloadGroup: @json(route('workorders.downloadGroup', ['id' => '__WO_ID__', 'group' => '__GROUP__'])),
        };

        const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
        const showSpin = () => {
            if (typeof safeShowSpinner === 'function') safeShowSpinner();
        };
        const hideSpin = () => {
            if (typeof safeHideSpinner === 'function') safeHideSpinner();
        };
        const notify = (message, type = 'info') => {
            if (typeof showNotification === 'function') {
                showNotification(message, type);
                return;
            }
            window.showNotification(message);
        };

        function buildUrl(template, replacements) {
            return Object.entries(replacements).reduce((url, [key, value]) => {
                return url.replace(key, encodeURIComponent(String(value ?? '')));
            }, template);
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function escapeAttr(value) {
            return escapeHtml(value).replaceAll('`', '&#096;');
        }

        const photoPrintButtonTemplate = `
            <button type="button"
                    data-fancybox-print
                    class="fancybox-button fancybox-button--print"
                    title="Print"
                    aria-label="Print">
                <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                    <path d="M5 1a2 2 0 0 0-2 2v2h10V3a2 2 0 0 0-2-2H5zm7 4H4V3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2z"/>
                    <path d="M3 6a3 3 0 0 0-3 3v2a2 2 0 0 0 2 2h1v2h10v-2h1a2 2 0 0 0 2-2V9a3 3 0 0 0-3-3H3zm10 8H4v-4h8v4h1zm1-2h-1V9H3v3H2a1 1 0 0 1-1-1V9a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2a1 1 0 0 1-1 1z"/>
                </svg>
            </button>`;

        function openPhotoPrintDialog(source) {
            let photoUrl;

            try {
                const parsedUrl = new URL(String(source || ''), window.location.href);
                if (!['http:', 'https:', 'blob:', 'data:'].includes(parsedUrl.protocol)) {
                    throw new Error('Unsupported photo URL');
                }
                photoUrl = parsedUrl.href;
            } catch (error) {
                console.error('Invalid photo URL for print', error);
                notify('Unable to print this photo', 'error');
                return;
            }

            const printWindow = window.open('', '_blank', 'width=1200,height=900');
            if (!printWindow) {
                notify('Allow pop-ups to print this photo', 'error');
                return;
            }

            printWindow.opener = null;
            const printDocument = printWindow.document;
            printDocument.open();
            printDocument.write('<!doctype html><html><head><meta charset="utf-8"><title></title></head><body></body></html>');
            printDocument.close();
            printDocument.title = `WO ${workorderNumber} photo`;

            const style = printDocument.createElement('style');
            style.textContent = `
                @page { margin: 10mm; }
                html, body { width: 100%; min-height: 100%; margin: 0; background: #fff; }
                body { display: flex; align-items: center; justify-content: center; }
                img { display: block; max-width: 100%; max-height: calc(100vh - 20mm); object-fit: contain; image-orientation: from-image; }
            `;
            printDocument.head.appendChild(style);

            const image = printDocument.createElement('img');
            image.alt = `WO ${workorderNumber} photo`;
            printDocument.body.appendChild(image);

            let printStarted = false;
            const startPrint = () => {
                if (printStarted) return;
                printStarted = true;
                window.setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                }, 100);
            };

            image.addEventListener('load', startPrint, {once: true});
            image.addEventListener('error', () => {
                notify('Photo could not be loaded for printing', 'error');
                printWindow.close();
            }, {once: true});
            printWindow.addEventListener('afterprint', () => printWindow.close(), {once: true});
            image.src = photoUrl;

            if (image.complete && image.naturalWidth > 0) {
                startPrint();
            }
        }

        function bindPhotoFancybox() {
            if (!contentEl || !window.jQuery?.fn?.fancybox) return;

            const $ = window.jQuery;
            $(contentEl).find('[data-fancybox]').fancybox({
                buttons: ['zoom', 'slideShow', 'thumbs', 'print', 'close'],
                btnTpl: {
                    print: photoPrintButtonTemplate
                }
            });

            $(document)
                .off('click.photo-page-print', '[data-fancybox-print]')
                .on('click.photo-page-print', '[data-fancybox-print]', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    const instance = $.fancybox.getInstance();
                    const current = instance?.current;
                    if (!current || current.type !== 'image' || !current.src) {
                        notify('Only photos can be printed here', 'error');
                        return;
                    }

                    openPhotoPrintDialog(current.src);
                });
        }

        function setDownloadAllState(state) {
            const statusEl = document.getElementById('downloadAllPhotosStatus');
            const textEl = document.getElementById('downloadAllPhotosStatusText');
            const btn = document.getElementById('saveAllPhotos');

            if (!statusEl || !textEl || !btn) return;

            if (state === 'preparing') {
                statusEl.hidden = false;
                textEl.textContent = 'Preparing ZIP...';
                btn.disabled = true;
                return;
            }

            if (state === 'started') {
                statusEl.hidden = false;
                textEl.textContent = 'Download started';
                btn.disabled = false;
                window.setTimeout(() => {
                    statusEl.hidden = true;
                    textEl.textContent = 'Preparing ZIP...';
                }, 2200);
                return;
            }

            statusEl.hidden = true;
            textEl.textContent = 'Preparing ZIP...';
            btn.disabled = false;
        }

        function selectorValue(value) {
            if (window.CSS && typeof window.CSS.escape === 'function') {
                return CSS.escape(String(value ?? ''));
            }

            return String(value ?? '').replaceAll('"', '\\"').replaceAll('\\', '\\\\');
        }

        async function loadPhotos() {
            if (!contentEl || !workorderId) return;

            showSpin();
            let data = null;

            try {
                const response = await fetch(buildUrl(urls.list, {'__WO_ID__': workorderId}), {
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
                    const safeGroup = escapeAttr(group);
                    const safeLabel = escapeHtml(label);
                    const items = mediaData[group] || [];

                    html += `
  <div class="col-12 media-group" data-group="${safeGroup}">
    <div class="d-flex align-items-center justify-content-between mt-2">
      <h6 class="text-primary text-uppercase mb-1">${safeLabel}</h6>

      <div class="d-flex gap-2 align-items-center">
        <input type="file"
               class="d-none group-upload-input"
               data-group="${safeGroup}"
               multiple
               accept="image/*" />

        <button type="button"
                class="btn btn-sm btn-outline-info group-upload-btn"
                data-group="${safeGroup}">
          <i class="bi bi-plus-lg"></i> Add
        </button>

        <button type="button"
                class="btn btn-sm btn-outline-secondary group-download-btn"
                data-group="${safeGroup}">
          <i class="bi bi-download"></i>
        </button>
      </div>
    </div>

    <div class="group-dropzone rounded p-2 mt-1" data-group="${safeGroup}">
      <div class="row g-2 group-grid">
`;

                    if (!items.length) {
                        html += '<div class="col-12 text-muted small">No photos</div>';
                    } else {
                        items.forEach(media => {
                            html += `
                            <div class="col-4 col-md-2 col-lg-1 photo-item"
                                 draggable="true"
                                 data-media-id="${escapeAttr(media.id)}"
                                 data-group="${safeGroup}">
                                <div class="position-relative d-inline-block w-100">
                                    <a data-fancybox="${safeGroup}" href="${escapeAttr(media.big)}" data-caption="${safeLabel}">
                                        <img src="${escapeAttr(media.thumb)}" class="photo-thumbnail border border-primary rounded" alt="${safeLabel}" />
                                    </a>

                                    <button type="button"
                                            class="p-0 d-flex align-items-center justify-content-center position-absolute delete-photo-btn"
                                            data-id="${escapeAttr(media.id)}" title="Delete">
                                        <i class="bi bi-x-lg"></i>
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

                contentEl.innerHTML = html;
                bindRenderedButtons();
                bindDnD();
                bindPhotoFancybox();
            } catch (e) {
                console.error('Load photo error', e, data);
                contentEl.innerHTML = '<div class="col-12 text-danger">Failed to load photos</div>';
            } finally {
                hideSpin();
            }
        }

        function bindRenderedButtons() {
            document.querySelectorAll('.delete-photo-btn').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    window.pendingDelete = {
                        mediaId: this.dataset.id,
                        photoBlock: this.closest('.photo-item')
                    };

                    const modalEl = document.getElementById('confirmDeletePhotoModal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        new bootstrap.Modal(modalEl).show();
                    }
                });
            });

            document.querySelectorAll('.group-upload-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const group = btn.dataset.group;
                    document.querySelector(`.group-upload-input[data-group="${selectorValue(group)}"]`)?.click();
                });
            });

            document.querySelectorAll('.group-upload-input').forEach(input => {
                input.addEventListener('change', async () => {
                    const files = input.files;
                    const group = input.dataset.group;

                    if (!workorderId) return notify('Workorder ID missing', 'error');
                    if (!files || !files.length) return;

                    const MAX_FILE_KB = 15360;
                    const MAX_FILE_BYTES = MAX_FILE_KB * 1024;
                    const MAX_FILE_MB = Math.round((MAX_FILE_BYTES / (1024 * 1024)) * 10) / 10;
                    const fileArr = Array.from(files);
                    const tooBig = fileArr.find(f => (f?.size ?? 0) > MAX_FILE_BYTES);

                    if (tooBig) {
                        notify(`File "${tooBig.name}" is too large. Max per file: ${MAX_FILE_MB}MB.`, 'error');
                        input.value = '';
                        return;
                    }

                    const formData = new FormData();
                    formData.append('group', group);
                    fileArr.forEach(file => formData.append('files[]', file));

                    showSpin();
                    try {
                        const resp = await fetch(buildUrl(urls.upload, {'__WO_ID__': workorderId}), {
                            method: 'POST',
                            headers: {'X-CSRF-TOKEN': csrf()},
                            body: formData
                        });

                        if (resp.status === 413) {
                            notify(`Upload failed: payload too large. Ensure each file is <= ${MAX_FILE_MB}MB.`, 'error');
                            return;
                        }

                        if (!resp.ok) throw new Error('Upload failed');

                        await loadPhotos();
                    } catch (e) {
                        console.error('Upload error:', e);
                        notify('Upload failed', 'error');
                    } finally {
                        input.value = '';
                        hideSpin();
                    }
                });
            });

            document.querySelectorAll('.group-download-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const group = btn.dataset.group;
                    if (!workorderId) return notify('Workorder ID missing', 'error');
                    if (!group) return;

                    showSpin();
                    try {
                        const a = document.createElement('a');
                        a.href = buildUrl(urls.downloadGroup, {'__WO_ID__': workorderId, '__GROUP__': group});
                        a.click();
                    } catch (e) {
                        console.error(e);
                        notify('Download failed', 'error');
                    } finally {
                        hideSpin();
                    }
                });
            });
        }

        document.getElementById('confirmPhotoDeleteBtn')?.addEventListener('click', async () => {
            const {mediaId, photoBlock} = window.pendingDelete || {};
            if (!mediaId) return;

            showSpin();
            try {
                const response = await fetch(buildUrl(urls.delete, {'__MEDIA_ID__': mediaId}), {
                    method: 'DELETE',
                    headers: {'X-CSRF-TOKEN': csrf()}
                });

                if (!response.ok) throw new Error('Delete failed');

                if (photoBlock) {
                    photoBlock.style.transition = 'opacity 0.25s ease';
                    photoBlock.style.opacity = '0';
                }

                setTimeout(loadPhotos, 250);

                const toastEl = document.getElementById('photoDeletedToast');
                if (toastEl && typeof bootstrap !== 'undefined') {
                    new bootstrap.Toast(toastEl).show();
                }
            } catch (err) {
                console.error('Delete error:', err);
                notify('Failed to delete photo', 'error');
            } finally {
                hideSpin();
                const modalEl = document.getElementById('confirmDeletePhotoModal');
                const modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
                modal?.hide();
                window.pendingDelete = null;
            }
        });

        function bindDnD() {
            if (window.__photoPageDnDInited || !pageEl) return;
            window.__photoPageDnDInited = true;

            let dragMediaId = null;
            let dragFromGroup = null;
            let rafLock = false;

            function autoScroll(e) {
                if (!dragMediaId) return;
                if (!scrollerEl) return;

                const edge = 80;
                const speed = 22;
                const y = e.clientY;
                const rect = scrollerEl.getBoundingClientRect();
                let delta = 0;

                if (y < rect.top + edge) delta = -speed;
                else if (y > rect.bottom - edge) delta = speed;
                if (!delta || rafLock) return;

                rafLock = true;
                requestAnimationFrame(() => {
                    scrollerEl.scrollTop += delta;
                    rafLock = false;
                });
            }

            pageEl.addEventListener('dragstart', (e) => {
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
                pageEl.classList.add('dnd-active');
            }, true);

            pageEl.addEventListener('dragend', (e) => {
                const el = e.target.closest('.photo-item[draggable="true"]');
                el?.classList.remove('dragging');

                pageEl.querySelectorAll('.group-dropzone.drop-hover')
                    .forEach(zone => zone.classList.remove('drop-hover'));

                pageEl.classList.remove('dnd-active');
                dragMediaId = null;
                dragFromGroup = null;
            }, true);

            pageEl.addEventListener('dragover', (e) => {
                const zone = e.target.closest('.group-dropzone');
                if (!zone) return;

                e.preventDefault();
                zone.classList.add('drop-hover');
                try {
                    e.dataTransfer.dropEffect = 'move';
                } catch (_) {
                }
                autoScroll(e);
            });

            pageEl.addEventListener('dragleave', (e) => {
                const zone = e.target.closest('.group-dropzone');
                if (!zone) return;

                const related = e.relatedTarget;
                if (related && zone.contains(related)) return;

                zone.classList.remove('drop-hover');
            });

            pageEl.addEventListener('drop', async (e) => {
                const zone = e.target.closest('.group-dropzone');
                if (!zone) return;

                e.preventDefault();
                zone.classList.remove('drop-hover');
                pageEl.classList.remove('dnd-active');

                const toGroup = zone.dataset.group;
                let fallbackId = null;

                try {
                    fallbackId = e.dataTransfer.getData('text/plain');
                } catch (_) {
                }

                const mediaId = dragMediaId || fallbackId;

                if (!workorderId) return notify('Workorder ID missing', 'error');
                if (!mediaId) return;
                if (toGroup && dragFromGroup && toGroup === dragFromGroup) return;

                showSpin();
                try {
                    const resp = await fetch(buildUrl(urls.move, {'__MEDIA_ID__': mediaId}), {
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

                    await loadPhotos();
                } catch (err) {
                    console.error('Move error:', err);
                    notify('Move failed', 'error');
                } finally {
                    hideSpin();
                }
            });
        }

        document.getElementById('saveAllPhotos')?.addEventListener('click', () => {
            if (!workorderId) return notify('Workorder ID missing', 'error');

            setDownloadAllState('preparing');
            try {
                const a = document.createElement('a');
                a.href = buildUrl(urls.downloadAll, {'__WO_ID__': workorderId});
                a.click();
                window.setTimeout(() => setDownloadAllState('started'), 900);
            } catch (err) {
                console.error('Error downloading ZIP:', err);
                setDownloadAllState('idle');
                notify('Download failed', 'error');
            }
        });

        loadPhotos();
    });
</script>
