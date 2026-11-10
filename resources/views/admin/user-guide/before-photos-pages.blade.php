@php($bp = $beforePhotosGuideTranslations['en'])
<article class="guide-page guide-page--photos" id="workorder-mobile-photos" data-guide-page data-guide-order="26.5">
    <div class="chapter-label" data-i18n="workflowPhotos">Before photos</div>
    <h2 class="page-title" data-i18n="bpSelectTitle">{{ $bp['bpSelectTitle'] }}</h2>
    <div class="guide-page__split">
        <div class="guide-log-card-phones">
            <figure class="guide-figure">
                <div class="guide-photo-shot">
                    <img src="{{ asset('img/user-guide/before-photos-mobile-groups.jpg') }}" alt="{{ $bp['bpGroupCaption'] }}" data-i18n-alt="bpGroupCaption" width="430" height="900">
                    <span class="guide-photo-ring guide-photo-ring--group" aria-hidden="true"></span>
                </div>
                <figcaption data-i18n="bpGroupCaption">{{ $bp['bpGroupCaption'] }}</figcaption>
            </figure>
            <figure class="guide-figure">
                <div class="guide-photo-shot">
                    <img src="{{ asset('img/user-guide/before-photos-mobile-camera.jpg') }}" alt="{{ $bp['bpCameraCaption'] }}" data-i18n-alt="bpCameraCaption" width="430" height="900">
                    <span class="guide-photo-ring guide-photo-ring--camera" aria-hidden="true"></span>
                </div>
                <figcaption data-i18n="bpCameraCaption">{{ $bp['bpCameraCaption'] }}</figcaption>
            </figure>
        </div>
        <div class="guide-copy">
            <ol class="guide-log-card-steps">
                @for($step = 1; $step <= 3; $step++)
                    <li data-i18n="bpSelect{{ $step }}">{{ $bp['bpSelect'.$step] }}</li>
                @endfor
            </ol>
        </div>
    </div>
</article>

<article class="guide-page guide-page--photos" id="workorder-mobile-camera" data-guide-page data-guide-order="26.53">
    <div class="chapter-label" data-i18n="workflowPhotos">Before photos</div>
    <h2 class="page-title" data-i18n="bpRotateTitle">{{ $bp['bpRotateTitle'] }}</h2>
    <div class="guide-photo-rotation">
        <svg viewBox="0 0 340 130" aria-hidden="true" focusable="false">
            <g fill="none" stroke="#ffd34d" stroke-width="4">
                <rect x="12" y="8" width="62" height="110" rx="11"/>
                <path d="M32 20h22M37 106h12"/>
                <path d="M100 68c10-40 52-46 80-16m-20-3 21 4-2-21"/>
                <rect x="199" y="33" width="125" height="70" rx="11"/>
                <path d="M211 56v22M312 58v18"/>
                <path d="m248 67 12 12 24-26"/>
            </g>
        </svg>
        <p data-i18n="bpRotateCaption">{{ $bp['bpRotateCaption'] }}</p>
    </div>
    <figure class="guide-figure">
        <div class="guide-photo-shot">
            <img src="{{ asset('img/user-guide/before-photos-mobile-landscape.jpg') }}" alt="{{ $bp['bpLandscapeCaption'] }}" data-i18n-alt="bpLandscapeCaption" width="900" height="430">
            <span class="guide-photo-ring guide-photo-ring--landscape" aria-hidden="true"></span>
        </div>
        <figcaption data-i18n="bpLandscapeCaption">{{ $bp['bpLandscapeCaption'] }}</figcaption>
    </figure>
    <ol class="guide-log-card-steps guide-log-card-steps--wide">
        @for($step = 1; $step <= 3; $step++)
            <li data-i18n="bpRotate{{ $step }}">{{ $bp['bpRotate'.$step] }}</li>
        @endfor
    </ol>
</article>

<article class="guide-page guide-page--photos" id="workorder-mobile-photo-review" data-guide-page data-guide-order="26.56">
    <div class="chapter-label" data-i18n="workflowPhotos">Before photos</div>
    <h2 class="page-title" data-i18n="bpReviewTitle">{{ $bp['bpReviewTitle'] }}</h2>
    <div class="guide-page__split">
        <div class="guide-log-card-phones">
            <figure class="guide-figure">
                <div class="guide-photo-shot">
                    <img src="{{ asset('img/user-guide/before-photos-mobile-camera.jpg') }}" alt="{{ $bp['bpGroupCaption'] }}" data-i18n-alt="bpGroupCaption" width="430" height="900">
                    <span class="guide-photo-ring guide-photo-ring--thumbnail" aria-hidden="true"></span>
                </div>
                <figcaption>Photos of the unit → 3</figcaption>
            </figure>
            <figure class="guide-figure">
                <img src="{{ asset('img/user-guide/before-photos-mobile-review.jpg') }}" alt="{{ $bp['bpReviewCaption'] }}" data-i18n-alt="bpReviewCaption" width="430" height="900">
                <figcaption data-i18n="bpReviewCaption">{{ $bp['bpReviewCaption'] }}</figcaption>
            </figure>
        </div>
        <div class="guide-copy">
            <ol class="guide-log-card-steps">
                @for($step = 1; $step <= 3; $step++)
                    <li data-i18n="bpReview{{ $step }}">{{ $bp['bpReview'.$step] }}</li>
                @endfor
            </ol>
        </div>
    </div>
</article>

<article class="guide-page guide-page--photos" id="workorder-resources" data-guide-page data-guide-order="26.6">
    <div class="chapter-label" data-i18n="workflowPhotos">Before photos</div>
    <h2 class="page-title" data-i18n="bpDesktopTitle">{{ $bp['bpDesktopTitle'] }}</h2>
    <figure class="guide-figure">
        <img src="{{ asset('img/user-guide/technician-workorder-main-header-only.png') }}" alt="Pictures — Main">
    </figure>
    <p class="guide-photo-wide-copy" data-i18n="bpDesktop1">{{ $bp['bpDesktop1'] }}</p>
    <figure class="guide-figure">
        <img src="{{ asset('img/user-guide/before-photos-desktop-gallery.jpg') }}" alt="{{ $bp['bpDesktopCaption'] }}" data-i18n-alt="bpDesktopCaption" width="1244" height="770">
        <figcaption data-i18n="bpDesktopCaption">{{ $bp['bpDesktopCaption'] }}</figcaption>
    </figure>
    <p class="guide-photo-wide-copy" data-i18n="bpDesktop2">{{ $bp['bpDesktop2'] }}</p>
</article>
