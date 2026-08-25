{{-- Fig: чертежи процесса — печатаются отдельными листами после формы;
     выключаются чекбоксом «Print with Fig.» (#includeFigChk) в тулбаре формы.
     Требует: $figPagesHtml — массив элементов ['caption' => имя процесса, 'html' => страница]
     (из TdrProcessController::figPagesHtmlForRows).
     Опционально:
       $figPrintMaxHeight — max-height картинки при печати (по умолчанию 254mm, portrait-формы);
       $figRotatePrint    — true для landscape-форм (traveler): портретный чертёж
                            разворачивается на листе на 90° вместо ужатия
                            (колонтитул с именем процесса остаётся горизонтальным). --}}
@php $figRotatePrint = $figRotatePrint ?? false; @endphp
@if(!empty($figPagesHtml))
<style>
.fig-attachment .fig-page{background:#fff;max-width:980px;margin:14px auto;box-shadow:0 1px 4px rgba(0,0,0,.15)}
.fig-attachment .pdw-page{position:relative}
.fig-attachment .pdw-page img{width:100%;display:block}
.fig-attachment .pdw-svg{position:absolute;top:0;left:0;width:100%;height:100%}
.fig-attachment .pdw-dot{position:absolute;width:5px;height:5px;margin:-2.5px 0 0 -2.5px;background:#0d9488;border-radius:50%}
.fig-attachment .pdw-el{position:absolute;transform:translate(-50%,-50%);font-size:9pt;font-weight:700;white-space:nowrap;line-height:1.2}
.fig-attachment .pdw-dim{color:#0d6efd;background:rgba(255,255,255,.92);border:1px solid #0d6efd;border-radius:2px;padding:0 3px}
.fig-attachment .pdw-dim.pdw-value{border:none;background:transparent;padding:0}
.fig-attachment .pdw-label{color:#0d9488;background:rgba(255,255,255,.85);padding:0 3px}
{{-- колонтитул листа Fig: имя процесса; горизонтален и в rotate-режиме --}}
.fig-attachment .fig-caption{font:700 12px Arial,sans-serif;color:#000;background:#fff;border-bottom:1px solid #000;padding:3px 10px}
.fig-attachment.fig-hidden{display:none}
@media print{
    {{-- формы с фиксированной высотой body (traveler — один лист) обрезают всё
         после первой страницы; при видимом Fig возвращаем auto, чтобы листы
         чертежа напечатались. Чекбокс снят → правило не действует. --}}
    html:has(.fig-attachment:not(.fig-hidden)){height:auto !important}
    html:has(.fig-attachment:not(.fig-hidden)) body{height:auto !important}
    {{-- QR и фиксированный колонтитул формы иначе повторяются на каждом листе,
         включая листы Fig — прижимаем их к первой странице --}}
    html:has(.fig-attachment:not(.fig-hidden)) .system-print-qr{position:absolute !important}
    html:has(.fig-attachment:not(.fig-hidden)) footer{position:static !important}
    .fig-attachment .fig-page{box-shadow:none;margin:0 auto;max-width:none;page-break-before:always;page-break-inside:avoid}
@if($figRotatePrint)
    {{-- альбомный лист (letter landscape, printable ≈ 263×200mm): портретный чертёж
         разворачиваем на 90° — ширина чертежа ложится на высоту листа
         (за вычетом колонтитула). Без position:absolute — Chrome не отрисовывает
         abs+transform контент на страницах после первой при печати. --}}
    .fig-attachment .fig-page{height:196mm;overflow:hidden}
    {{-- −90°: сканы CMM хранят чертёж повёрнутым на +90° внутри портретной
         страницы, поэтому поворот в обратную сторону даёт читаемый текст --}}
    .fig-attachment .fig-page .pdw-page{width:188mm;transform:rotate(-90deg) translateX(-100%);transform-origin:top left}
@else
    .fig-attachment .fig-page .pdw-page img{max-height:{{ $figPrintMaxHeight ?? '254mm' }};width:auto;max-width:100%;margin:0 auto}
@endif
}
</style>
<div class="fig-attachment" id="figAttachment">
    @foreach($figPagesHtml as $figPage)
        @php
            $figHtml = is_array($figPage) ? ($figPage['html'] ?? '') : $figPage;
            $figCaption = is_array($figPage) ? trim((string) ($figPage['caption'] ?? '')) : '';
        @endphp
        <div class="fig-page">
            @if($figCaption !== '')<div class="fig-caption">{{ $figCaption }}</div>@endif
            {!! $figHtml !!}
        </div>
    @endforeach
</div>
<script>
(function () {
    var chk = document.getElementById('includeFigChk');
    var block = document.getElementById('figAttachment');
    if (chk && block) {
        chk.addEventListener('change', function () { block.classList.toggle('fig-hidden', !chk.checked); });
    }
})();
</script>
@endif
