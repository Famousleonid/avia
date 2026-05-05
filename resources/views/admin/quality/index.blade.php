@extends('admin.master')

@section('content')
    <style>
        .qa-page {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            flex: 1 1 auto;
            overflow: hidden;
            background: #2B3035;
        }

        .content,
        .content-inner {
            background: #2B3035 !important;
        }

        .qa-header {
            flex: 0 0 auto;
            display: grid;
            grid-template-columns: max-content max-content minmax(320px, 520px) 1fr;
            align-items: center;
            column-gap: clamp(1rem, 3vw, 3.5rem);
        }

        .qa-current-wo {
            min-width: 6.5rem;
            color: var(--bs-body-color);
        }

        .qa-current-wo a {
            color: var(--bs-body-color);
            text-decoration: none;
        }

        .qa-current-wo a:hover .text-info {
            text-decoration: underline;
        }

        .qa-search-row {
            margin-left: clamp(1rem, 4vw, 4.5rem);
        }

        #qaMessage {
            flex: 0 0 auto;
        }

        #qaResult {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }

        .qa-workorder-layout {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            overflow: hidden;
        }

        .qa-workorder-layout > .qa-block {
            flex: 0 0 auto;
            margin-bottom: .65rem !important;
        }

        .qa-search-row {
            display: grid;
            grid-template-columns: max-content minmax(0, 1fr);
            align-items: center;
            column-gap: .65rem;
        }

        .qa-search-wrap {
            position: relative;
        }

        .qa-search-wrap .form-control {
            padding-right: 2.3rem;
        }

        .qa-search-clear {
            position: absolute;
            top: 50%;
            right: .45rem;
            display: none;
            width: 1.7rem;
            height: 1.7rem;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: var(--bs-secondary-color);
            transform: translateY(-50%);
        }

        .qa-search-clear.is-visible {
            display: inline-flex;
        }

        .qa-dot-spinner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .3rem;
            min-height: 1.25rem;
        }

        .qa-dot-spinner span {
            width: .42rem;
            height: .42rem;
            border-radius: 50%;
            background: var(--bs-secondary-color);
            opacity: .5;
            animation: qaDotJump .72s ease-in-out infinite;
        }

        .qa-dot-spinner span:nth-child(2) {
            animation-delay: .12s;
        }

        .qa-dot-spinner span:nth-child(3) {
            animation-delay: .24s;
        }

        .qa-page-loading {
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 1080;
            display: none;
            transform: translate(-50%, -50%);
        }

        .qa-page-loading.is-visible {
            display: flex;
        }

        @keyframes qaDotJump {
            0%,
            80%,
            100% {
                transform: translateY(0);
                opacity: .42;
            }

            40% {
                transform: translateY(-.35rem);
                opacity: .95;
            }
        }

        .qa-block {
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: .65rem;
            background: rgba(255, 255, 255, .025);
        }

        .qa-block-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .45rem .7rem;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .qa-top-row {
            flex: 0 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 1fr) max-content;
            align-items: stretch;
            gap: .75rem;
            margin-bottom: .65rem;
        }

        .qa-top-row > .qa-block {
            min-width: 0;
            margin-bottom: 0 !important;
        }

        .qa-forms-block {
            width: max-content;
            justify-self: end;
        }

        .qa-repair-block {
            flex: 1 1 auto !important;
            display: flex;
            flex-direction: column;
            min-height: 0;
            margin-bottom: 0 !important;
        }

        .qa-table-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
        }

        .qa-table-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #111;
        }

        .qa-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: .35rem .75rem;
            padding: .5rem .7rem;
        }

        .qa-info-item {
            min-width: 0;
            line-height: 1.25;
        }

        .qa-info-label {
            font-size: .74rem;
            color: var(--bs-secondary-color);
        }

        .qa-info-value {
            overflow-wrap: anywhere;
        }

        .qa-checks-line {
            padding: 0 .7rem .55rem;
        }

        .qa-check-separator {
            color: var(--bs-secondary-color);
            margin: 0 .45rem;
        }

        .qa-photo-groups {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: .65rem;
            padding: .85rem;
        }

        .qa-photo-group {
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: .45rem;
            background: rgba(0, 0, 0, .12);
            color: var(--bs-body-color);
            text-align: left;
            padding: .65rem;
        }

        .qa-photo-thumb {
            width: 78px;
            height: 58px;
            object-fit: cover;
            border-radius: .35rem;
        }

        .qa-form-grid {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: flex-end;
            gap: .65rem;
            padding: .65rem .75rem;
        }

        .qa-form-paper {
            width: 90px;
            height: 120px;
            flex: 0 0 auto;
            cursor: default;
            text-decoration: none;
        }

        .qa-form-paper svg {
            display: block;
            width: 90px;
            height: 120px;
            --paper: #d5d5d5;
            --fold: #0d6efd;
            --stroke: #0d6efd;
            --text: #084298;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, .18));
        }

        .qa-form-paper[href] {
            cursor: pointer;
        }

        .qa-form-paper[href]:hover svg {
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, .3));
        }

        .qa-form-paper.is-success svg {
            --fold: #198754;
            --stroke: #198754;
            --text: #0f5132;
        }

        .qa-form-paper .paper {
            fill: var(--paper);
            stroke: var(--stroke);
            stroke-width: 1;
        }

        .qa-form-paper .fold {
            fill: var(--fold);
        }

        .qa-form-paper .line {
            stroke: var(--stroke);
            stroke-width: 2;
            fill: none;
        }

        .qa-form-paper foreignObject div {
            color: var(--text);
            font-weight: 700;
            line-height: 1.02;
            text-align: center;
            overflow-wrap: anywhere;
            hyphens: auto;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qa-empty {
            padding: 1.5rem 1rem;
            color: var(--bs-secondary-color);
            text-align: center;
        }

        @media (max-width: 1199.98px) {
            .qa-top-row {
                grid-template-columns: 1fr;
            }

            .qa-forms-block {
                width: 100%;
            }

            .qa-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .qa-header {
                grid-template-columns: 1fr;
                row-gap: .65rem;
            }

            .qa-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div id="qaPageLoading" class="qa-page-loading" aria-hidden="true">
        <span class="qa-dot-spinner"><span></span><span></span><span></span></span>
    </div>

    <div class="container-fluid px-3 qa-page pt-1">
        <div class="qa-header mb-2">
            <h4 class="mb-0 text-info">Quality Assurance</h4>
            <h5 id="qaCurrentWorkorder" class="qa-current-wo mb-0"></h5>
            <div class="qa-search-row">
                <label class="form-label small mb-0" for="qaWorkorderSearch">WO #</label>
                <div class="qa-search-wrap">
                    <input type="text" id="qaWorkorderSearch" class="form-control form-control-sm" placeholder="Enter full workorder number" autocomplete="off" inputmode="numeric">
                    <button type="button" id="qaWorkorderSearchClear" class="qa-search-clear" aria-label="Clear search">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="qaMessage" class="small text-secondary mb-2"></div>
        <div id="qaResult">
            <div class="qa-empty qa-block">Enter a full workorder number and press Enter.</div>
        </div>
    </div>

    <div class="modal fade" id="qaPhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content bg-dark text-light">
                <div class="modal-header">
                    <h5 class="modal-title" id="qaPhotoModalTitle">Photos</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="qaPhotoModalBody" class="d-flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const storageKey = 'qualityAssurance.singleWorkorderSearch';
            const searchInput = document.getElementById('qaWorkorderSearch');
            const clearButton = document.getElementById('qaWorkorderSearchClear');
            const currentWorkorderLabel = document.getElementById('qaCurrentWorkorder');
            const result = document.getElementById('qaResult');
            const message = document.getElementById('qaMessage');
            const pageLoading = document.getElementById('qaPageLoading');
            const photoModalEl = document.getElementById('qaPhotoModal');
            const photoModalTitle = document.getElementById('qaPhotoModalTitle');
            const photoModalBody = document.getElementById('qaPhotoModalBody');
            const endpoint = @json(route('quality.workorder'));
            const spinnerHtml = '<span class="qa-dot-spinner" aria-label="Loading"><span></span><span></span><span></span></span>';
            let currentPhotoGroups = [];

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const normalizeWorkorderSearch = (value) => {
                const query = String(value ?? '').trim();
                return /\d/.test(query) ? query.replace(/\D+/g, '') : query;
            };

            const showClear = () => {
                clearButton.classList.toggle('is-visible', searchInput.value.trim() !== '');
            };

            const setCurrentWorkorder = (number = '', url = '') => {
                currentWorkorderLabel.innerHTML = number
                    ? `<a href="${escapeHtml(url || '#')}">WO <span class="text-info">${escapeHtml(number)}</span></a>`
                    : '';
            };

            const setLoading = (loading) => {
                pageLoading.classList.toggle('is-visible', loading);
            };

            const saveSearch = (value) => {
                try {
                    localStorage.setItem(storageKey, value);
                } catch (error) {
                    // localStorage can be unavailable in private browser modes.
                }
            };

            const readSearch = () => {
                try {
                    return localStorage.getItem(storageKey) || '';
                } catch (error) {
                    return '';
                }
            };

            const fieldHtml = (label, value, html = false) => `
                <div class="qa-info-item">
                    <div class="qa-info-label">${escapeHtml(label)}</div>
                    <div class="qa-info-value">${html ? value : escapeHtml(value || '-')}</div>
                </div>
            `;

            const manualHtml = (value) => {
                const text = String(value || '-');
                const match = text.match(/^(.*?)\s*(\([^)]*\))$/);

                if (!match) {
                    return escapeHtml(text);
                }

                return `${escapeHtml(match[1].trim())} <span class="text-secondary">${escapeHtml(match[2])}</span>`;
            };

            const checksHtml = (checks) => {
                if (!checks || checks.length === 0) {
                    return '';
                }

                return `
                    <div class="qa-checks-line small">
                        ${checks.map((check, index) => `
                            ${index ? '<span class="qa-check-separator">&middot;</span>' : ''}
                            <span class="${check.ok ? 'text-info' : 'text-danger'} fw-semibold">${escapeHtml(check.label)}</span>
                        `).join('')}
                    </div>
                `;
            };

            const blockHtml = (title, body, titleMeta = '', className = '') => `
                <section class="qa-block ${escapeHtml(className)} mb-3">
                    <div class="qa-block-title">
                        <h6 class="mb-0 text-info">${escapeHtml(title)}</h6>
                        ${titleMeta}
                    </div>
                    ${body}
                </section>
            `;

            const renderTop = (wo) => {
                const top = wo.top || {};
                const approvedDate = top.approved_at && top.approved_at !== '-' ? top.approved_at : '';
                const approvalMeta = `
                    <span class="d-inline-flex align-items-center gap-2">
                        <span class="${top.approved ? 'text-info' : 'text-secondary'} fw-semibold">${top.approved ? 'Approved' : 'Not approved'}</span>
                        ${approvedDate ? `<span class="small text-secondary">${escapeHtml(approvedDate)}</span>` : ''}
                    </span>
                `;
                const fields = [
                    ['WO #', wo.number],
                    ['Customer', top.customer],
                    ['Instruction', top.instruction],
                    ['Technician', top.technician],
                    ['Unit PN', top.unit],
                    ['Serial #', top.serial],
                    ['Manual', manualHtml(top.manual), true],
                    ['Manual Rev.', top.manual_revision],
                    ['Open Date', top.open_date],
                    ['Customer PO', top.customer_po],
                ].map(([label, value, html]) => fieldHtml(label, value, html)).join('');

                return blockHtml('Workorder', `<div class="qa-info-grid">${fields}</div>${checksHtml(wo.checks)}`, approvalMeta);
            };

            const renderPhotos = (groups) => {
                currentPhotoGroups = groups || [];

                if (currentPhotoGroups.length === 0) {
                    return blockHtml('Photos', '<div class="qa-empty">No image photos found.</div>');
                }

                const cards = currentPhotoGroups.map((group, index) => `
                    <button type="button" class="qa-photo-group" data-photo-group="${index}">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold">${escapeHtml(group.label)}</div>
                                <div class="small text-secondary">${escapeHtml(group.collection)}</div>
                            </div>
                            <span class="text-info fw-semibold">${escapeHtml(group.count)}</span>
                        </div>
                    </button>
                `).join('');

                return blockHtml('Photos', `<div class="qa-photo-groups">${cards}</div>`);
            };

            const renderSubmitted = (rows) => {
                if (!rows || rows.length === 0) {
                    return blockHtml('Submitted WO', '<div class="qa-empty">No submitted inspections waiting for QA.</div>');
                }

                const body = `
                    <div class="table-responsive qa-table-scroll">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Submitted Step</th>
                                <th>Submitted Date</th>
                                <th>Missing Inspection</th>
                                <th>Component PN</th>
                                <th>Serial #</th>
                            </tr>
                            </thead>
                            <tbody>
                            ${rows.map(row => `
                                <tr>
                                    <td>${escapeHtml(row.submitted_step)}</td>
                                    <td>${escapeHtml(row.submitted_date || '-')}</td>
                                    <td><span class="text-warning fw-semibold">${escapeHtml(row.missing_inspection)}</span></td>
                                    <td>${escapeHtml(row.component_pn)}</td>
                                    <td>${escapeHtml(row.serial_number)}</td>
                                </tr>
                            `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;

                return blockHtml('Submitted WO', body, `<span class="text-warning fw-semibold">${rows.length}</span>`);
            };

            const renderRepairOrders = (rows) => {
                if (!rows || rows.length === 0) {
                    return blockHtml('Repair order', '<div class="qa-empty">No processes found.</div>');
                }

                const missing = rows.filter(row => !row.ok).length;
                const body = `
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Component</th>
                                <th>Process</th>
                                <th>RO</th>
                                <th>Date Send</th>
                                <th>Date Receive</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            ${rows.map(row => `
                                <tr>
                                    <td>${escapeHtml(row.component)}</td>
                                    <td>${escapeHtml(row.process_name)}</td>
                                    <td>${escapeHtml(row.repair_order)}</td>
                                    <td>${escapeHtml(row.date_start)}</td>
                                    <td>${escapeHtml(row.date_finish)}</td>
                                    <td><span class="${row.ok ? 'text-info' : 'text-danger'} fw-semibold">${row.ok ? 'OK' : 'Missing'}</span></td>
                                </tr>
                            `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;

                return blockHtml('Repair order', body, `<span class="${missing ? 'text-danger' : 'text-info'} fw-semibold">${missing ? `${missing} missing` : 'OK'}</span>`, 'qa-repair-block');
            };

            const fitPaperLabels = (root = document) => {
                root.querySelectorAll('.qa-form-paper-label').forEach((label) => {
                    label.style.fontSize = '24px';

                    for (let size = 24; size >= 9; size -= 1) {
                        label.style.fontSize = `${size}px`;

                        if (label.scrollWidth <= label.clientWidth && label.scrollHeight <= label.clientHeight) {
                            break;
                        }
                    }
                });
            };

            const paperButtonHtml = (form, index) => {
                const title = form.title || '';
                const colorClass = index < 2 ? 'is-success' : '';
                const url = form.url || '';
                const tag = url ? 'a' : 'div';
                const href = url ? ` href="${escapeHtml(url)}" target="_blank" rel="noopener"` : '';

                return `
                    <${tag} class="qa-form-paper ${colorClass}"${href} title="${escapeHtml(title)}" aria-label="${escapeHtml(title)}">
                        <svg viewBox="0 0 190 270" role="img" aria-hidden="true">
                            <path class="paper" d="M10 10 H140 L180 50 V240 H10 Z"></path>
                            <polygon class="fold" points="140,10 140,50 180,50"></polygon>
                            <path class="line" d="M140 12 V50 H180"></path>
                            <foreignObject x="14" y="56" width="162" height="150">
                                <div class="qa-form-paper-label" xmlns="http://www.w3.org/1999/xhtml">${escapeHtml(title)}</div>
                            </foreignObject>
                        </svg>
                    </${tag}>
                `;
            };

            const renderForms = (forms) => {
                const body = `
                    <div class="qa-form-grid">
                        ${(forms || []).map((form, index) => paperButtonHtml(form, index)).join('')}
                    </div>
                `;

                return blockHtml('Forms', body, '', 'qa-forms-block');
            };

            const renderWorkorder = (wo) => {
                setCurrentWorkorder(wo.number, wo.url);
                result.innerHTML = `<div class="qa-workorder-layout">${[
                    `<div class="qa-top-row">${renderTop(wo)}${renderForms(wo.forms)}</div>`,
                    renderPhotos(wo.photos),
                    renderSubmitted(wo.submitted),
                    renderRepairOrders(wo.repair_orders),
                ].join('')}</div>`;
                fitPaperLabels(result);
            };

            const loadWorkorder = async () => {
                const normalized = normalizeWorkorderSearch(searchInput.value);
                searchInput.value = normalized;
                showClear();
                saveSearch(normalized);

                if (!/^\d{6}$/.test(normalized)) {
                    setCurrentWorkorder();
                    message.textContent = 'Enter full 6-digit workorder number.';
                    result.innerHTML = '<div class="qa-empty qa-block">Waiting for full workorder number.</div>';
                    return;
                }

                setLoading(true);
                message.innerHTML = spinnerHtml;

                try {
                    const url = new URL(endpoint, window.location.origin);
                    url.searchParams.set('q', normalized);

                    const response = await fetch(url, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json();

                    if (!response.ok || !data.found) {
                        throw new Error(data.message || 'Workorder not found.');
                    }

                    message.textContent = '';
                    renderWorkorder(data.workorder);
                } catch (error) {
                    setCurrentWorkorder();
                    message.textContent = error.message || 'Could not load workorder.';
                    result.innerHTML = '<div class="qa-empty qa-block">No workorder loaded.</div>';
                } finally {
                    setLoading(false);
                }
            };

            searchInput.value = readSearch();
            showClear();

            if (/^\d{6}$/.test(normalizeWorkorderSearch(searchInput.value))) {
                loadWorkorder();
            }

            searchInput.addEventListener('input', () => {
                saveSearch(normalizeWorkorderSearch(searchInput.value));
                setCurrentWorkorder();
                showClear();
            });

            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    loadWorkorder();
                }
            });

            clearButton.addEventListener('click', () => {
                searchInput.value = '';
                saveSearch('');
                setCurrentWorkorder();
                showClear();
                message.textContent = '';
                result.innerHTML = '<div class="qa-empty qa-block">Enter a full workorder number and press Enter.</div>';
                searchInput.focus();
            });

            result.addEventListener('click', (event) => {
                const button = event.target.closest('[data-photo-group]');
                if (!button) return;

                const group = currentPhotoGroups[Number(button.dataset.photoGroup)];
                if (!group) return;

                photoModalTitle.textContent = `${group.label} (${group.count})`;
                photoModalBody.innerHTML = (group.items || []).map(item => `
                    <a href="${escapeHtml(item.big)}" data-fancybox="qa-${escapeHtml(group.collection)}" data-caption="${escapeHtml(item.name)}">
                        <img src="${escapeHtml(item.thumb || item.big)}" class="qa-photo-thumb" alt="${escapeHtml(item.name)}" data-full-src="${escapeHtml(item.big)}">
                    </a>
                `).join('');

                photoModalBody.querySelectorAll('img[data-full-src]').forEach((image) => {
                    image.addEventListener('error', () => {
                        if (image.src !== image.dataset.fullSrc) {
                            image.src = image.dataset.fullSrc;
                        }
                    }, { once: true });
                });

                bootstrap.Modal.getOrCreateInstance(photoModalEl).show();
            });
        })();
    </script>
@endsection
