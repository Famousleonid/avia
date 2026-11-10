<article class="guide-page" id="log-card-mobile" data-guide-page data-guide-order="27.2">
    <div class="chapter-label">Log Card · 1</div>
    <h2 class="page-title" data-i18n="lcMobileTitle">{{ $logCardGuideTranslations['en']['lcMobileTitle'] }}</h2>
    <p class="page-lead" data-i18n="lcMobileLead">{{ $logCardGuideTranslations['en']['lcMobileLead'] }}</p>
    <div class="guide-page__split">
        <div class="guide-log-card-phones">
            <figure class="guide-figure">
                <img src="{{ asset('img/user-guide/log-card-mobile-select.jpg') }}" alt="{{ $logCardGuideTranslations['en']['lcSelectCaption'] }}" data-i18n-alt="lcSelectCaption">
                <figcaption data-i18n="lcSelectCaption">{{ $logCardGuideTranslations['en']['lcSelectCaption'] }}</figcaption>
            </figure>
            <figure class="guide-figure">
                <img src="{{ asset('img/user-guide/log-card-mobile-photo.jpg') }}" alt="{{ $logCardGuideTranslations['en']['lcPhotoCaption'] }}" data-i18n-alt="lcPhotoCaption">
                <figcaption data-i18n="lcPhotoCaption">{{ $logCardGuideTranslations['en']['lcPhotoCaption'] }}</figcaption>
            </figure>
        </div>
        <div class="guide-copy">
            <ol class="guide-log-card-steps">
                @for($step = 1; $step <= 6; $step++)
                    <li data-i18n="lcMobileStep{{ $step }}">{{ $logCardGuideTranslations['en']['lcMobileStep'.$step] }}</li>
                @endfor
            </ol>
        </div>
    </div>
</article>

<article class="guide-page" id="log-card-desktop" data-guide-page data-guide-order="27.3">
    <div class="chapter-label">Log Card · 2</div>
    <h2 class="page-title" data-i18n="lcDesktopTitle">{{ $logCardGuideTranslations['en']['lcDesktopTitle'] }}</h2>
    <p class="page-lead" data-i18n="lcDesktopLead">{{ $logCardGuideTranslations['en']['lcDesktopLead'] }}</p>
    <figure class="guide-figure">
        <img src="{{ asset('img/user-guide/log-card-desktop-select.jpg') }}?v={{ filemtime(public_path('img/user-guide/log-card-desktop-select.jpg')) }}" alt="{{ $logCardGuideTranslations['en']['lcDesktopSelectCaption'] }}" data-i18n-alt="lcDesktopSelectCaption">
        <figcaption data-i18n="lcDesktopSelectCaption">{{ $logCardGuideTranslations['en']['lcDesktopSelectCaption'] }}</figcaption>
    </figure>
    <ol class="guide-log-card-steps guide-log-card-steps--wide">
        @for($step = 1; $step <= 2; $step++)
            <li data-i18n="lcDesktopStep{{ $step }}">{{ $logCardGuideTranslations['en']['lcDesktopStep'.$step] }}</li>
        @endfor
    </ol>
    <figure class="guide-figure">
        <img src="{{ asset('img/user-guide/log-card-desktop-serials.jpg') }}" alt="{{ $logCardGuideTranslations['en']['lcDesktopSerialCaption'] }}" data-i18n-alt="lcDesktopSerialCaption">
        <figcaption data-i18n="lcDesktopSerialCaption">{{ $logCardGuideTranslations['en']['lcDesktopSerialCaption'] }}</figcaption>
    </figure>
    <ol class="guide-log-card-steps guide-log-card-steps--wide" start="3">
        @for($step = 3; $step <= 5; $step++)
            <li data-i18n="lcDesktopStep{{ $step }}">{{ $logCardGuideTranslations['en']['lcDesktopStep'.$step] }}</li>
        @endfor
    </ol>
</article>
