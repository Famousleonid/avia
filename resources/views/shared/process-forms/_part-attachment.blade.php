@if($partPrintAttachment)
@once
<style>
.process-part-attachment{box-sizing:border-box;background:white;color:black;width:100%;max-width:200mm;height:260mm;margin:16px auto;display:flex;flex-direction:column;padding:0;font:14px Arial,sans-serif}
.process-part-attachment header{display:flex;align-items:center;justify-content:space-between;gap:8mm;flex:0 0 auto;min-height:18mm;border-bottom:1px solid black;padding-bottom:2mm}
.process-part-attachment .attachment-image-area{flex:1;min-height:0;display:flex;padding-top:3mm}
.process-part-attachment .attachment-image{width:100%;height:100%;min-height:0;object-fit:contain}
.attachment-qr svg{width:16mm;height:16mm;display:block}
@media print{
    .process-part-attachment{break-before:page;break-inside:avoid;margin:0 auto;max-width:none;height:calc(279.4mm - 2 * var(--print-page-margin,8mm) - 1mm)}
    html:has(.process-part-attachment) .system-print-qr{position:absolute!important}
}
</style>
@endonce
<section class="process-part-attachment" aria-label="Process image attachment">
    <header><div><strong>W{{ $current_wo->number }}</strong><br>Part No: {{ $partPrintAttachment['part_number'] }}<br>{{ $process_name->name }}</div><div class="attachment-qr"></div></header>
    <div class="attachment-image-area"><img class="attachment-image" src="{{ route('process-print-attachments.image', [$process_name, $partPrintAttachment['media']]) }}" alt="{{ $partPrintAttachment['part_number'] }}"></div>
</section>
@once
<script>
(() => {
    function copyAttachmentQr() {
        if (!@json(app(\App\Services\PrintFormQrSettings::class)->enabled('attachment'))) return;
        document.querySelectorAll('.process-part-attachment').forEach(page => {
            const code = (page.closest('.form-wrapper') || document).querySelector('.system-print-qr__code svg') || document.querySelector('.system-print-qr__code svg');
            const target = page.querySelector('.attachment-qr');
            if (code && !target.firstChild) target.append(code.cloneNode(true));
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', copyAttachmentQr); else copyAttachmentQr();
    window.addEventListener('beforeprint', copyAttachmentQr);
})();
</script>
@endonce
@endif
