<script>
    window.MainPartsConfig = {
        number: @json((string) $current_workorder->number),
        loadingHtml: document.getElementById('mainPartsLoadingTemplate').innerHTML,
        partsUrl: @json(route('mains.parts', $current_workorder)),
        receiptUrl: @json(route('workorders.part-receipt.update', $current_workorder)),
        transferFormUrl: @json(route('transfers.transferForm', ':id')),
        transferSourcesUrl: @json(route('workorders.log-card-transfer.sources', $current_workorder)),
        transferStoreUrl: @json(route('workorders.log-card-transfer.store', $current_workorder)),
        transferDestroyUrl: @json(route('workorders.log-card-transfer.destroy', $current_workorder)),
    };
    (() => {
        let scriptPromise;
        const openParts = () => {
            const modal = document.getElementById('partsModal' + window.MainPartsConfig.number);
            if (!window.MainParts?.loaded && modal.getAttribute('aria-busy') !== 'true') {
                modal.querySelector('.modal-body').innerHTML = window.MainPartsConfig.loadingHtml;
                modal.setAttribute('aria-busy', 'true');
            }
            if (!scriptPromise) {
                scriptPromise = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = @json(asset('js/mains/parts.js')) + '?v={{ filemtime(public_path('js/mains/parts.js')) }}';
                    script.onload = resolve;
                    script.onerror = () => { script.remove(); scriptPromise = null; reject(new Error('Could not load parts controls.')); };
                    document.head.appendChild(script);
                });
            }
            scriptPromise.then(() => window.MainParts.load()).catch(() => {
                modal.setAttribute('aria-busy', 'false');
                const body = document.querySelector('#partsModal' + window.MainPartsConfig.number + ' .modal-body');
                if (body) body.innerHTML = '<p class="text-danger" role="alert">Could not load parts.</p><button type="button" class="btn btn-outline-info" data-main-parts-retry>Retry</button>';
                window.showNotification('Could not load parts. Please retry.', 'error');
            });
        };
        document.addEventListener('show.bs.modal', event => {
            if (event.target.id === 'partsModal' + window.MainPartsConfig.number) openParts();
        });
        document.addEventListener('click', event => {
            if (event.target.closest('[data-main-parts-retry]')) openParts();
        });
    })();
</script>
