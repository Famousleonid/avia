@if(isset($travelerImages) && $travelerImages->isNotEmpty())
    <style>
        .traveler-image-page { box-sizing: border-box; width: 100%; max-width: 680px; aspect-ratio: 8.5 / 11; margin: 16px auto; padding: 8px; background: #fff; border: 1px solid #ddd; }
        .traveler-image-page img { display: block; width: 100%; height: 100%; object-fit: contain; }
        .traveler-image-error { color: #b02a37; padding: 1rem; }
        @media print {
            @page traveler-image { size: letter portrait; margin: 8mm; }
            .traveler-image-page { page: traveler-image; break-before: page; page-break-before: always; break-inside: avoid; page-break-inside: avoid; width: 100%; max-width: none; height: 262mm; aspect-ratio: auto; padding: 0; margin: 0; border: 0; }
            html:has(.traveler-image-page), html:has(.traveler-image-page) body { height: auto !important; }
            html:has(.traveler-image-page) .system-print-qr { position: absolute !important; }
            html:has(.traveler-image-page) footer { position: static !important; }
        }
    </style>
    <div class="traveler-image-attachments">
        @foreach($travelerImages as $image)
            @if($image->mime === 'application/pdf')
                <div data-traveler-pdf="{{ (($templatePreview ?? false) ? route('traveler-template-images.show', $image) : route('tdr.traveler-template-images.show', [$current_tdr, $image])) }}" data-name="{{ $image->name }}">
                    <p class="no-print">Loading PDF: {{ $image->name }}</p>
                </div>
            @else
            <div class="traveler-image-page">
                <img src="{{ (($templatePreview ?? false) ? route('traveler-template-images.show', $image) : route('tdr.traveler-template-images.show', [$current_tdr, $image])) }}" alt="{{ $image->name }}" data-traveler-print-image>
            </div>
            @endif
        @endforeach
    </div>
    <div class="traveler-image-error no-print" role="alert" hidden data-traveler-image-error></div>
    <script>
        window.travelerAttachmentsReady = (async () => {
            const files = [...document.querySelectorAll('[data-traveler-pdf]')];
            if (!files.length) return;
            if (!window.pdfjsLib) {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = @json(asset('assets/pdfjs/pdf.min.js'));
                    script.onload = resolve;
                    script.onerror = () => reject(new Error('PDF reader could not be loaded.'));
                    document.head.appendChild(script);
                });
            }
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = @json(asset('assets/pdfjs/pdf.worker.min.js'));
            for (const file of files) {
                const pdf = await window.pdfjsLib.getDocument({url: file.dataset.travelerPdf, isEvalSupported: false}).promise;
                try {
                    for (let number = 1; number <= pdf.numPages; number++) {
                        const page = await pdf.getPage(number);
                        const original = page.getViewport({scale: 1});
                        const viewport = page.getViewport({scale: Math.min(2, 2400 / Math.max(original.width, original.height))});
                        const canvas = document.createElement('canvas');
                        canvas.width = Math.ceil(viewport.width); canvas.height = Math.ceil(viewport.height);
                        await page.render({canvasContext: canvas.getContext('2d'), viewport}).promise;
                        const image = document.createElement('img');
                        image.alt = file.dataset.name + ' — page ' + number;
                        image.dataset.travelerPrintImage = '';
                        image.src = canvas.toDataURL('image/png');
                        await image.decode();
                        const sheet = document.createElement('div'); sheet.className = 'traveler-image-page';
                        sheet.append(image); file.before(sheet);
                        canvas.width = canvas.height = 0;
                        page.cleanup();
                    }
                    file.remove();
                } finally { await pdf.destroy(); }
            }
        })();
        window.travelerAttachmentsReady.catch(() => {
            const error = document.querySelector('[data-traveler-image-error]');
            error.hidden = false;
            error.textContent = 'A PDF could not be loaded. Check that it is valid and not password-protected, then reload before printing.';
        });
        document.querySelectorAll('[data-traveler-print-image]').forEach(image => {
            image.addEventListener('error', () => {
                const error = document.querySelector('[data-traveler-image-error]');
                error.hidden = false;
                error.textContent = 'A Traveler image could not be loaded. Reload the page before printing.';
            });
        });
    </script>
@endif
