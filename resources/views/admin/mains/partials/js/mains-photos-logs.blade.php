<script>
    // mains-photos-logs.js
    // Logs only. Workorder photo management lives on the dedicated mains photos page.

    document.addEventListener('DOMContentLoaded', () => {
        function getEl(id) {
            return document.getElementById(id);
        }

        function escapeHtml(s) {
            return String(s ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

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

        async function loadLogModal(url) {
            const container = getEl('logModalContent');
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
                    const created = escapeHtml(item.created_at ?? '');
                    const desc = escapeHtml(item.description ?? '');
                    const event = escapeHtml(item.event ?? '');
                    const causer = escapeHtml(item.causer_name ?? '');
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
                        html += '<ul class="mt-2 mb-0 ps-3">';
                        changes.forEach(ch => {
                            html += `
                            <li>
                                <strong>${escapeHtml(ch.label)}:</strong>
                                <span class="text-danger">${escapeHtml(ch.old ?? '—')}</span>
                                <span class="text-muted mx-1">→</span>
                                <span class="text-success">${escapeHtml(ch.new ?? '—')}</span>
                            </li>
                        `;
                        });
                        html += '</ul>';
                    }

                    html += '</div>';
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
