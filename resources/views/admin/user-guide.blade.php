<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>User Guide</title>
    <link rel="icon" href="{{ asset('img/favicon.webp') }}" type="image/png">
    @include('partials.user-scoped-storage')
    @include('partials.user-ui-settings')
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom_bootstrap.css') }}?v={{ filemtime(public_path('css/custom_bootstrap.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}?v={{ filemtime(public_path('css/admin-theme.css')) }}">
    <script>
        (function () {
            const theme = window.UserScopedStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <style>
        :root {
            --guide-bg: #141b24;
            --guide-surface: #1b2633;
            --guide-surface-raised: #202d3b;
            --guide-line: rgba(186, 205, 222, .18);
            --guide-text: #f5f8fb;
            --guide-muted: #aebdcb;
            --guide-accent: #0dcaf0;
        }

        html, body { min-height: 100%; background: var(--guide-bg); }
        body { margin: 0; color: var(--guide-text); font-size: 15px; }
        .guide-app { min-height: 100dvh; display: flex; flex-direction: column; background: var(--guide-bg); }
        .guide-topbar { min-height: 64px; display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 20px; padding: 10px 24px; border-bottom: 1px solid var(--guide-line); background: rgba(20, 27, 36, .96); position: sticky; top: 0; z-index: 10; }
        .guide-topbar h1 { margin: 0; font-size: 1.45rem; font-weight: 600; letter-spacing: -.02em; }
        .guide-back { justify-self: start; min-height: 38px; color: var(--guide-text); border-color: rgba(188, 207, 224, .45); }
        .guide-back:hover { color: var(--guide-text); background: rgba(255, 255, 255, .08); border-color: var(--guide-accent); }
        .language-switcher { justify-self: end; display: flex; gap: 4px; }
        .guide-language { color: var(--guide-text); background: transparent; border: 0; border-bottom: 2px solid transparent; border-radius: 0; padding: .35rem .45rem .25rem; font-size: .84rem; font-weight: 600; }
        .guide-language:hover, .guide-language:focus-visible { color: var(--guide-accent); outline: none; }
        .guide-language.is-active { color: var(--guide-accent); border-bottom-color: var(--guide-accent); }
        .guide-layout { width: min(1480px, 100%); margin: 0 auto; flex: 1; display: grid; grid-template-columns: 255px minmax(0, 1fr); }
        .guide-toc { border-right: 1px solid var(--guide-line); padding: 35px 20px; position: sticky; align-self: start; top: 64px; }
        .guide-toc-title { margin: 0 0 18px; color: var(--guide-muted); font-size: .73rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; }
        .guide-toc details { border-bottom: 1px solid var(--guide-line); }
        .guide-toc summary { display: flex; gap: 9px; align-items: center; cursor: pointer; padding: 12px 8px; color: var(--guide-text); font-size: .88rem; font-weight: 600; list-style: none; }
        .guide-toc summary::-webkit-details-marker { display: none; }
        .guide-toc summary .bi-chevron-down { margin-left: auto; color: var(--guide-muted); font-size: .76rem; transition: transform .18s ease; }
        .guide-toc details[open] summary .bi-chevron-down { transform: rotate(180deg); }
        .guide-toc-links { padding: 0 0 10px; }
        .guide-toc a { display: flex; gap: 11px; align-items: center; color: var(--guide-muted); text-decoration: none; padding: 8px 8px; border-left: 3px solid transparent; transition: color .18s ease, background-color .18s ease, border-color .18s ease; }
        .guide-toc a:hover, .guide-toc a.is-current { color: var(--guide-text); background: rgba(255, 255, 255, .035); border-left-color: var(--guide-accent); }
        .guide-toc-number { width: 25px; height: 25px; flex: 0 0 25px; display: grid; place-items: center; border: 1px solid currentColor; border-radius: 50%; font-size: .78rem; }
        .guide-toc-text { font-size: .9rem; line-height: 1.2; }
        .guide-help { margin-top: 55px; padding: 17px; border: 1px solid var(--guide-line); border-radius: 8px; color: var(--guide-muted); }
        .guide-help .bi { color: var(--guide-accent); font-size: 1.35rem; }
        .guide-help strong { display: block; color: var(--guide-text); margin: 8px 0 5px; font-size: .9rem; }
        .guide-help p { margin: 0; font-size: .8rem; line-height: 1.45; }
        .guide-main { min-width: 0; padding: 42px clamp(24px, 4vw, 72px) 64px; }
        .guide-main > section { scroll-margin-top: 86px; }
        .guide-intro { max-width: 900px; margin-bottom: 26px; }
        .guide-intro h2 { margin: 0 0 10px; font-size: clamp(1.75rem, 3vw, 2.35rem); letter-spacing: -.035em; font-weight: 600; }
        .guide-intro p { margin: 0; color: var(--guide-muted); font-size: 1rem; line-height: 1.58; }
        .guide-intro strong { color: var(--guide-accent); font-weight: 600; }
        .guide-shot { margin: 0; overflow: hidden; border: 1px solid rgba(186, 205, 222, .3); border-radius: 9px; background: var(--guide-surface); box-shadow: 0 16px 36px rgba(0, 0, 0, .22); }
        .guide-shot-toolbar { display: flex; justify-content: space-between; align-items: center; min-height: 50px; padding: 9px 14px; border-bottom: 1px solid var(--guide-line); }
        .guide-shot-toolbar strong { font-size: .95rem; }
        .guide-upload-button { color: #09252e; background: var(--guide-accent); border: 0; border-radius: 4px; padding: 7px 11px; font-size: .8rem; font-weight: 600; }
        .guide-shot-body { display: grid; grid-template-columns: 1.3fr 1fr 1fr; min-height: 220px; }
        .guide-shot-group { min-width: 0; padding: 13px; border-right: 1px solid var(--guide-line); }
        .guide-shot-group:last-child { border-right: 0; }
        .guide-shot-group h3 { margin: 0 0 12px; font-size: .82rem; font-weight: 600; }
        .guide-shot-group .count { color: var(--guide-muted); font-weight: 400; }
        .guide-photo-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        .guide-photo { overflow: hidden; border: 1px solid rgba(186, 205, 222, .24); border-radius: 4px; background: #101820; }
        .guide-photo img { display: block; width: 100%; aspect-ratio: 1.35 / 1; object-fit: cover; opacity: .82; }
        .guide-photo span { display: block; overflow: hidden; padding: 5px 6px; color: var(--guide-muted); font-size: .66rem; text-overflow: ellipsis; white-space: nowrap; }
        .guide-drop-hint { min-height: 108px; display: grid; place-items: center; border: 1px dashed rgba(186, 205, 222, .3); color: rgba(186, 205, 222, .55); font-size: .72rem; text-align: center; }
        .guide-shot figcaption { padding: 10px 14px; border-top: 1px solid var(--guide-line); color: var(--guide-muted); font-size: .78rem; }
        .guide-mobile-shots { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: clamp(16px, 2.5vw, 34px); align-items: start; }
        .guide-mobile-shot { margin: 0; padding: 12px; border: 1px solid rgba(186, 205, 222, .3); border-radius: 9px; background: var(--guide-surface); box-shadow: 0 16px 36px rgba(0, 0, 0, .22); }
        .guide-mobile-shot img { display: block; width: min(100%, 390px); margin: 0 auto; border-radius: 5px; border: 1px solid rgba(186, 205, 222, .22); }
        .guide-mobile-shot figcaption { padding: 12px 4px 2px; color: var(--guide-muted); font-size: .84rem; line-height: 1.45; }
        .guide-onboarding { margin-bottom: 48px; padding-bottom: 42px; border-bottom: 1px solid var(--guide-line); }
        .guide-onboarding h2 { margin: 0 0 9px; font-size: clamp(1.65rem, 2.7vw, 2.15rem); font-weight: 600; letter-spacing: -.03em; }
        .guide-onboarding > p { max-width: 850px; margin: 0 0 25px; color: var(--guide-muted); line-height: 1.55; }
        .guide-navigation-screens { display: grid; grid-template-columns: minmax(0, 1.5fr) minmax(250px, .8fr); gap: clamp(18px, 3vw, 36px); align-items: start; }
        .guide-desktop-shot, .guide-phone-shot { margin: 0; padding: 12px; border: 1px solid rgba(186, 205, 222, .3); border-radius: 9px; background: var(--guide-surface); box-shadow: 0 16px 36px rgba(0, 0, 0, .22); }
        .guide-desktop-shot img, .guide-phone-shot img { display: block; width: 100%; border: 1px solid rgba(186, 205, 222, .2); border-radius: 5px; }
        .guide-phone-shot img { width: min(100%, 390px); margin: 0 auto; }
        .guide-desktop-shot figcaption, .guide-phone-shot figcaption { padding: 12px 4px 2px; color: var(--guide-muted); font-size: .84rem; line-height: 1.45; }
        .guide-menu-explanations { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; margin-top: 25px; }
        .guide-menu-explanations h3 { margin: 0 0 10px; color: var(--guide-text); font-size: 1rem; font-weight: 600; }
        .guide-menu-explanations ul { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px 16px; list-style: none; margin: 0; padding: 0; }
        .guide-menu-explanations li { display: flex; gap: 8px; align-items: flex-start; color: var(--guide-muted); font-size: .82rem; line-height: 1.42; }
        .guide-menu-explanations li .bi { flex: 0 0 auto; color: var(--guide-accent); font-size: .96rem; }
        .guide-section-heading { margin: 36px 0 16px; font-size: 1.15rem; font-weight: 600; }
        .guide-steps { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 11px; }
        .guide-step { min-width: 0; border-top: 1px solid var(--guide-line); padding: 14px 6px 4px; }
        .guide-step-number { display: block; color: var(--guide-accent); font-size: .78rem; font-weight: 700; margin-bottom: 8px; }
        .guide-step strong { display: block; font-size: .9rem; margin-bottom: 4px; }
        .guide-step p { color: var(--guide-muted); margin: 0; font-size: .8rem; line-height: 1.45; }
        .guide-demo { margin-top: 38px; padding-top: 31px; border-top: 1px solid var(--guide-line); }
        .guide-demo h2 { margin: 0 0 9px; font-size: 1.4rem; font-weight: 600; }
        .guide-demo > p { max-width: 760px; margin: 0 0 18px; color: var(--guide-muted); line-height: 1.55; }
        .guide-animation { display: grid; grid-template-columns: 1fr auto 1fr auto 1fr; gap: 14px; align-items: center; }
        .guide-animation-frame { min-height: 186px; padding: 14px; border: 1px solid var(--guide-line); border-radius: 7px; background: var(--guide-surface); }
        .guide-animation-frame h3 { margin: 0 0 13px; font-size: .82rem; font-weight: 600; }
        .guide-mini-board { display: flex; gap: 10px; min-height: 125px; }
        .guide-mini-column { flex: 1; min-width: 0; padding: 8px; border-left: 1px solid var(--guide-line); }
        .guide-mini-column:first-child { border-left: 0; }
        .guide-mini-column span { display: block; margin-bottom: 7px; color: var(--guide-muted); font-size: .68rem; }
        .guide-mini-thumb { width: 48px; height: 31px; margin: 5px 0; overflow: hidden; border: 1px solid rgba(186, 205, 222, .32); border-radius: 3px; background: #101820; }
        .guide-mini-thumb img { width: 100%; height: 100%; object-fit: cover; opacity: .8; }
        .guide-mini-drop { height: 92px; display: grid; place-items: center; border: 1px dashed rgba(186, 205, 222, .35); color: rgba(186, 205, 222, .55); font-size: .68rem; text-align: center; }
        .guide-mini-drop.target { border-color: var(--guide-accent); background: rgba(13, 202, 240, .08); color: var(--guide-accent); animation: guidePulse 1.6s infinite ease-in-out; }
        .guide-animation-arrow { color: var(--guide-accent); font-size: 1.55rem; animation: guideArrow 1.6s infinite ease-in-out; }
        .guide-flying-photo { position: relative; width: 48px; height: 31px; margin: 18px auto 0; overflow: hidden; border: 1px solid var(--guide-accent); border-radius: 3px; animation: guideMove 2.6s infinite ease-in-out; }
        .guide-flying-photo img { width: 100%; height: 100%; object-fit: cover; }
        @keyframes guidePulse { 50% { box-shadow: 0 0 0 6px rgba(13, 202, 240, .09); } }
        @keyframes guideArrow { 50% { transform: translateX(5px); } }
        @keyframes guideMove { 0%, 23% { transform: translate(-39px, 0); opacity: 0; } 34%, 60% { transform: translate(0, -9px); opacity: 1; } 83%, 100% { transform: translate(45px, 0); opacity: 0; } }
        .guide-checklist { margin: 40px 0 0; padding: 24px 0 0; border-top: 1px solid var(--guide-line); }
        .guide-checklist h2 { margin: 0 0 12px; font-size: 1.12rem; font-weight: 600; }
        .guide-checklist ul { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .guide-checklist li { display: flex; gap: 9px; color: var(--guide-muted); line-height: 1.4; }
        .guide-checklist .bi { color: var(--guide-accent); }
        html[dir="rtl"] .guide-layout { grid-template-columns: minmax(0, 1fr) 255px; }
        html[dir="rtl"] .guide-toc { border-right: 0; border-left: 1px solid var(--guide-line); grid-column: 2; }
        html[dir="rtl"] .guide-main { grid-column: 1; grid-row: 1; }
        html[dir="rtl"] .guide-toc a { border-left: 0; border-right: 3px solid transparent; }
        html[dir="rtl"] .guide-toc a:hover, html[dir="rtl"] .guide-toc a.is-current { border-right-color: var(--guide-accent); }
        html[dir="rtl"] .guide-toc summary .bi-chevron-down { margin-left: 0; margin-right: auto; }
        html[dir="rtl"] .guide-shot-group { border-right: 0; border-left: 1px solid var(--guide-line); }
        html[dir="rtl"] .guide-shot-group:last-child { border-left: 0; }
        html[dir="rtl"] .guide-mini-column { border-left: 0; border-right: 1px solid var(--guide-line); }
        html[dir="rtl"] .guide-mini-column:first-child { border-right: 0; }
        @media (max-width: 980px) { .guide-layout { grid-template-columns: 1fr; } .guide-toc { display: none; } .guide-steps { grid-template-columns: repeat(3, minmax(0, 1fr)); } html[dir="rtl"] .guide-layout { grid-template-columns: 1fr; } .guide-main { grid-column: auto; grid-row: auto; } }
        @media (max-width: 720px) { .guide-topbar { grid-template-columns: 1fr auto; grid-template-rows: auto auto; padding: 10px 14px; } .guide-topbar h1 { grid-column: 1 / -1; grid-row: 1; text-align: center; font-size: 1.2rem; } .guide-back { grid-column: 1; grid-row: 2; } .language-switcher { grid-column: 2; grid-row: 2; } .guide-main { padding: 28px 16px 40px; } .guide-navigation-screens, .guide-menu-explanations { grid-template-columns: 1fr; } .guide-menu-explanations ul { grid-template-columns: 1fr; } .guide-mobile-shots { grid-template-columns: 1fr; } .guide-shot-body { grid-template-columns: 1fr; } .guide-shot-group { border-right: 0; border-bottom: 1px solid var(--guide-line); } .guide-shot-group:last-child { border-bottom: 0; } .guide-steps { grid-template-columns: 1fr 1fr; } .guide-animation { grid-template-columns: 1fr; } .guide-animation-arrow { text-align: center; transform: rotate(90deg); } html[dir="rtl"] .guide-shot-group { border-left: 0; } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; scroll-behavior: auto !important; } }
    </style>
</head>
<body>
<div class="guide-app" id="userGuide">
    <header class="guide-topbar">
        <a class="btn btn-sm btn-outline-secondary guide-back" href="{{ route('workorders.index') }}" data-guide-back>
            <i class="bi bi-arrow-left"></i> <span data-guide="back">Back</span>
        </a>
        <h1 data-guide="pageTitle">User Guide</h1>
        <div class="language-switcher" aria-label="Guide language">
            <button class="guide-language is-active" type="button" data-language="en">EN</button>
            <button class="guide-language" type="button" data-language="ru">RU</button>
            <button class="guide-language" type="button" data-language="uk">UA</button>
            <button class="guide-language" type="button" data-language="he">HE</button>
        </div>
    </header>

    <div class="guide-layout">
        <aside class="guide-toc" aria-label="Guide contents">
            <p class="guide-toc-title" data-guide="contents">Contents</p>
            <nav class="guide-toc-accordion">
                <details open>
                    <summary><i class="bi bi-box-arrow-in-right"></i><span data-guide="tocGettingStarted">1. Getting started</span><i class="bi bi-chevron-down"></i></summary>
                    <div class="guide-toc-links">
                        <a class="is-current" href="#getting-started"><span class="guide-toc-number">1</span><span class="guide-toc-text" data-guide="tocLogin">Sign in and navigation</span></a>
                    </div>
                </details>
                <details>
                    <summary><i class="bi bi-images"></i><span data-guide="tocPhotos">2. Workorder photos</span><i class="bi bi-chevron-down"></i></summary>
                    <div class="guide-toc-links">
                        <a href="#overview"><span class="guide-toc-number">1</span><span class="guide-toc-text" data-guide="tocOpen">Open work order</span></a>
                        <a href="#upload"><span class="guide-toc-number">2</span><span class="guide-toc-text" data-guide="tocUpload">Add photos</span></a>
                        <a href="#wait"><span class="guide-toc-number">3</span><span class="guide-toc-text" data-guide="tocWait">Wait for upload</span></a>
                        <a href="#move"><span class="guide-toc-number">4</span><span class="guide-toc-text" data-guide="tocMove">Move to group</span></a>
                        <a href="#verify"><span class="guide-toc-number">5</span><span class="guide-toc-text" data-guide="tocVerify">Verify</span></a>
                    </div>
                </details>
            </nav>
            <div class="guide-help">
                <i class="bi bi-question-circle"></i>
                <strong data-guide="helpTitle">Need more help?</strong>
                <p data-guide="helpText">Contact the system administrator if the required group is unavailable.</p>
            </div>
        </aside>

        <main class="guide-main">
            <section id="getting-started" class="guide-onboarding">
                <h2 data-guide="startHeading">Sign in and navigate the program</h2>
                <p data-guide="startIntro">Open AVIA with your assigned account. The desktop sidebar is the main menu; on mobile, the top bar keeps the current work order and its actions within reach.</p>
                <div class="guide-navigation-screens">
                    <figure class="guide-desktop-shot">
                        <img src="{{ asset('img/user-guide/desktop-main-menu.png') }}" alt="AVIA desktop workorders page with sidebar menu">
                        <figcaption data-guide="desktopCaption">Desktop: use the sidebar to open the main working areas. The selected item is highlighted.</figcaption>
                    </figure>
                    <figure class="guide-phone-shot">
                        <img src="{{ asset('img/user-guide/mobile-workorder-add-photo.png') }}" alt="AVIA mobile workorder navigation">
                        <figcaption data-guide="mobileCaption">Mobile: use the top navigation to move between the work order, tasks, parts and processes.</figcaption>
                    </figure>
                </div>
                <div class="guide-menu-explanations">
                    <div>
                        <h3 data-guide="desktopMenuTitle">Desktop sidebar</h3>
                        <ul>
                            <li><i class="bi bi-file-earmark-word"></i><span data-guide="desktopWorkorder">Workorder — list, filters and work order cards.</span></li>
                            <li><i class="bi bi-list-check"></i><span data-guide="desktopTraining">Training — assigned training and progress.</span></li>
                            <li><i class="bi bi-book-half"></i><span data-guide="desktopManuals">Manuals — technical manuals and linked data.</span></li>
                            <li><i class="bi bi-collection"></i><span data-guide="desktopLibrary">Library — reference lists and directories.</span></li>
                            <li><i class="bi bi-bell"></i><span data-guide="desktopNotifications">Bell — notifications and reminders.</span></li>
                            <li><i class="bi bi-moon"></i><span data-guide="desktopTheme">Theme — light or dark interface mode.</span></li>
                        </ul>
                    </div>
                    <div>
                        <h3 data-guide="mobileMenuTitle">Mobile top bar</h3>
                        <ul>
                            <li><i class="bi bi-send"></i><span data-guide="mobileWo">WO — return to the work order list.</span></li>
                            <li><i class="bi bi-circle"></i><span data-guide="mobileWorkorder">Workorder — current work order details and photos.</span></li>
                            <li><i class="bi bi-clock-history"></i><span data-guide="mobileTasks">Tasks — task status and dates.</span></li>
                            <li><i class="bi bi-box-seam"></i><span data-guide="mobileParts">Parts — components and attached records.</span></li>
                            <li><i class="bi bi-activity"></i><span data-guide="mobileProcess">Process — the work process for the order.</span></li>
                        </ul>
                    </div>
                </div>
            </section>
            <section id="overview" class="guide-intro">
                <h2 data-guide="heading">Add and organize photos</h2>
                <p data-guide="intro">Photos are part of the work order record. Add clear files, wait until they finish uploading, then place every photo in the group where it will be easy to find later.</p>
            </section>

            <section id="upload">
                <div class="guide-mobile-shots">
                    <figure class="guide-mobile-shot">
                        <img src="{{ asset('img/user-guide/mobile-workorder-add-photo.png') }}" alt="Mobile work order with the photo groups and camera button">
                        <figcaption data-guide="mobileShotSelect">1. In the mobile Workorder page, select the photo group and tap the camera icon to add files.</figcaption>
                    </figure>
                    <figure class="guide-mobile-shot">
                        <img src="{{ asset('img/user-guide/mobile-workorder-with-photos.png') }}" alt="Mobile work order showing uploaded photo thumbnails and counters">
                        <figcaption data-guide="mobileShotResult">2. After upload, verify the thumbnail and counter in the required group.</figcaption>
                    </figure>
                </div>

                <h2 class="guide-section-heading" data-guide="sequenceTitle">Correct sequence</h2>
                <div class="guide-steps">
                    <article class="guide-step"><span class="guide-step-number">01</span><strong data-guide="step1Title">Open the work order</strong><p data-guide="step1Text">Open its Photos page from Mains.</p></article>
                    <article class="guide-step"><span class="guide-step-number">02</span><strong data-guide="step2Title">Select files</strong><p data-guide="step2Text">Choose clear, relevant JPG, PNG or WEBP files.</p></article>
                    <article id="wait" class="guide-step"><span class="guide-step-number">03</span><strong data-guide="step3Title">Wait for upload</strong><p data-guide="step3Text">Do not move on until all selected files appear.</p></article>
                    <article id="move" class="guide-step"><span class="guide-step-number">04</span><strong data-guide="step4Title">Move by drag and drop</strong><p data-guide="step4Text">Drag each file to the relevant group.</p></article>
                    <article id="verify" class="guide-step"><span class="guide-step-number">05</span><strong data-guide="step5Title">Verify</strong><p data-guide="step5Text">Check that every required photo is in the right group.</p></article>
                </div>
            </section>

            <section class="guide-demo" aria-labelledby="demoTitle">
                <h2 id="demoTitle" data-guide="demoTitle">Move photos between groups</h2>
                <p data-guide="demoText">Drag a thumbnail from its current column to a highlighted drop zone. Release it only when the target group is highlighted.</p>
                <div class="guide-animation" aria-label="Animated example of moving photographs">
                    <article class="guide-animation-frame"><h3 data-guide="frame1">1. Photos were uploaded</h3><div class="guide-mini-board"><div class="guide-mini-column"><span data-guide="unassigned">Unassigned</span><div class="guide-mini-thumb"><img src="{{ asset('img/icons/component.jpg') }}" alt=""></div><div class="guide-mini-thumb"><img src="{{ asset('img/avia190.png') }}" alt=""></div><div class="guide-mini-thumb"><img src="{{ asset('img/icons/components.jpg') }}" alt=""></div></div><div class="guide-mini-column"><span data-guide="general">General</span><div class="guide-mini-drop" data-guide="dropHere">Drop here</div></div></div></article>
                    <i class="bi bi-arrow-right guide-animation-arrow" aria-hidden="true"></i>
                    <article class="guide-animation-frame"><h3 data-guide="frame2">2. Drag to the target</h3><div class="guide-mini-board"><div class="guide-mini-column"><span data-guide="unassigned">Unassigned</span><div class="guide-mini-thumb"><img src="{{ asset('img/avia190.png') }}" alt=""></div><div class="guide-mini-thumb"><img src="{{ asset('img/icons/components.jpg') }}" alt=""></div></div><div class="guide-mini-column"><span data-guide="damage">Damage</span><div class="guide-mini-drop target" data-guide="releaseHere">Release here</div></div></div><div class="guide-flying-photo"><img src="{{ asset('img/icons/component.jpg') }}" alt=""></div></article>
                    <i class="bi bi-arrow-right guide-animation-arrow" aria-hidden="true"></i>
                    <article class="guide-animation-frame"><h3 data-guide="frame3">3. Check the result</h3><div class="guide-mini-board"><div class="guide-mini-column"><span data-guide="unassigned">Unassigned</span><div class="guide-mini-drop" data-guide="empty">Empty</div></div><div class="guide-mini-column"><span data-guide="damage">Damage</span><div class="guide-mini-thumb"><img src="{{ asset('img/icons/component.jpg') }}" alt=""></div><div class="guide-mini-thumb"><img src="{{ asset('img/avia190.png') }}" alt=""></div></div></div></article>
                </div>
            </section>

            <section class="guide-checklist">
                <h2 data-guide="checklistTitle">Before leaving the page</h2>
                <ul>
                    <li><i class="bi bi-check-square"></i><span data-guide="check1">The work order is correct and open.</span></li>
                    <li><i class="bi bi-check-square"></i><span data-guide="check2">Photos are clear, relevant and fully uploaded.</span></li>
                    <li><i class="bi bi-check-square"></i><span data-guide="check3">Every photo is placed in the intended group.</span></li>
                    <li><i class="bi bi-check-square"></i><span data-guide="check4">Required evidence is present before the work order advances.</span></li>
                </ul>
            </section>
        </main>
    </div>
</div>

<script>
    (function () {
        const translations = {
            en: { pageTitle: 'User Guide', back: 'Back', contents: 'Contents', tocOpen: 'Open work order', tocUpload: 'Add photos', tocWait: 'Wait for upload', tocMove: 'Move to group', tocVerify: 'Verify', helpTitle: 'Need more help?', helpText: 'Contact the system administrator if the required group is unavailable.', heading: 'Add and organize photos', intro: 'Photos are part of the work order record. Add clear files, wait until they finish uploading, then place every photo in the group where it will be easy to find later.', screenTitle: 'Photos · work order', uploadButton: 'Add photos', unassigned: 'Unassigned', general: 'General', damage: 'Damage', dropHere: 'Drop here', releaseHere: 'Release here', empty: 'Empty', caption: 'Example: photos remain in “Unassigned” until you drag them into the correct group.', sequenceTitle: 'Correct sequence', step1Title: 'Open the work order', step1Text: 'Open its Photos page from Mains.', step2Title: 'Select files', step2Text: 'Choose clear, relevant JPG, PNG or WEBP files.', step3Title: 'Wait for upload', step3Text: 'Do not move on until all selected files appear.', step4Title: 'Move by drag and drop', step4Text: 'Drag each file to the relevant group.', step5Title: 'Verify', step5Text: 'Check that every required photo is in the right group.', demoTitle: 'Move photos between groups', demoText: 'Drag a thumbnail from its current column to a highlighted drop zone. Release it only when the target group is highlighted.', frame1: '1. Photos were uploaded', frame2: '2. Drag to the target', frame3: '3. Check the result', checklistTitle: 'Before leaving the page', check1: 'The work order is correct and open.', check2: 'Photos are clear, relevant and fully uploaded.', check3: 'Every photo is placed in the intended group.', check4: 'Required evidence is present before the work order advances.' },
            ru: { pageTitle: 'Руководство пользователя', back: 'Назад', contents: 'Содержание', tocOpen: 'Открыть work order', tocUpload: 'Добавить фото', tocWait: 'Дождаться загрузки', tocMove: 'Перенести в группу', tocVerify: 'Проверить', helpTitle: 'Нужна помощь?', helpText: 'Если нужной группы нет, обратитесь к системному администратору.', heading: 'Добавление и организация фотографий', intro: 'Фотографии — часть карточки work order. Добавьте чёткие файлы, дождитесь окончания загрузки и затем поместите каждую фотографию в группу, где её будет легко найти.', screenTitle: 'Фотографии · work order', uploadButton: 'Добавить фото', unassigned: 'Без группы', general: 'Общие', damage: 'Повреждения', dropHere: 'Перетащите сюда', releaseHere: 'Отпустите здесь', empty: 'Пусто', caption: 'Пример: после загрузки фото остаются в «Без группы», пока вы не перетащите их в нужную группу.', sequenceTitle: 'Правильная последовательность', step1Title: 'Откройте work order', step1Text: 'Откройте страницу Photos из Mains.', step2Title: 'Выберите файлы', step2Text: 'Выберите чёткие и относящиеся к работе JPG, PNG или WEBP.', step3Title: 'Дождитесь загрузки', step3Text: 'Не продолжайте, пока не появятся все выбранные файлы.', step4Title: 'Перенесите drag-and-drop', step4Text: 'Перетащите каждый файл в соответствующую группу.', step5Title: 'Проверьте результат', step5Text: 'Убедитесь, что все нужные фото находятся в верной группе.', demoTitle: 'Перенос фотографий между группами', demoText: 'Перетащите миниатюру из её колонки в подсвеченную область назначения. Отпускайте только когда целевая группа подсвечена.', frame1: '1. Фото загружены', frame2: '2. Перетащите в нужную группу', frame3: '3. Проверьте результат', checklistTitle: 'Перед выходом со страницы', check1: 'Открыт правильный work order.', check2: 'Фото чёткие, относятся к работе и полностью загрузились.', check3: 'Каждая фотография находится в нужной группе.', check4: 'Все обязательные подтверждающие фото добавлены до перехода work order дальше.' },
            uk: { pageTitle: 'Посібник користувача', back: 'Назад', contents: 'Зміст', tocOpen: 'Відкрити work order', tocUpload: 'Додати фото', tocWait: 'Дочекатися завантаження', tocMove: 'Перенести до групи', tocVerify: 'Перевірити', helpTitle: 'Потрібна допомога?', helpText: 'Якщо потрібної групи немає, зверніться до системного адміністратора.', heading: 'Додавання та упорядкування фотографій', intro: 'Фотографії є частиною картки work order. Додайте чіткі файли, дочекайтеся завершення завантаження, а потім помістіть кожне фото до групи, де його легко знайти.', screenTitle: 'Фотографії · work order', uploadButton: 'Додати фото', unassigned: 'Без групи', general: 'Загальні', damage: 'Пошкодження', dropHere: 'Перетягніть сюди', releaseHere: 'Відпустіть тут', empty: 'Порожньо', caption: 'Приклад: після завантаження фото залишаються в «Без групи», доки ви не перетягнете їх до потрібної групи.', sequenceTitle: 'Правильна послідовність', step1Title: 'Відкрийте work order', step1Text: 'Відкрийте сторінку Photos із Mains.', step2Title: 'Виберіть файли', step2Text: 'Виберіть чіткі JPG, PNG або WEBP, що стосуються роботи.', step3Title: 'Дочекайтеся завантаження', step3Text: 'Не продовжуйте, доки не з’являться всі вибрані файли.', step4Title: 'Перенесіть drag-and-drop', step4Text: 'Перетягніть кожен файл до відповідної групи.', step5Title: 'Перевірте результат', step5Text: 'Переконайтеся, що всі потрібні фото в правильній групі.', demoTitle: 'Перенесення фотографій між групами', demoText: 'Перетягніть мініатюру з її колонки до підсвіченої області призначення. Відпускайте лише коли цільову групу підсвічено.', frame1: '1. Фото завантажено', frame2: '2. Перетягніть до цілі', frame3: '3. Перевірте результат', checklistTitle: 'Перед виходом зі сторінки', check1: 'Відкрито правильний work order.', check2: 'Фото чіткі, стосуються роботи й повністю завантажені.', check3: 'Кожна фотографія розміщена у призначеній групі.', check4: 'Усі обов’язкові фото додані до переходу work order далі.' },
            he: { pageTitle: 'מדריך למשתמש', back: 'חזרה', contents: 'תוכן העניינים', tocOpen: 'פתיחת הזמנת עבודה', tocUpload: 'הוספת תמונות', tocWait: 'המתנה להעלאה', tocMove: 'העברה לקבוצה', tocVerify: 'בדיקה', helpTitle: 'זקוקים לעזרה?', helpText: 'אם הקבוצה הנדרשת אינה זמינה, פנו למנהל המערכת.', heading: 'הוספה וארגון של תמונות', intro: 'תמונות הן חלק מרשומת הזמנת העבודה. העלו קבצים ברורים, המתינו לסיום ההעלאה ולאחר מכן מקמו כל תמונה בקבוצה שבה יהיה קל למצוא אותה.', screenTitle: 'תמונות · הזמנת עבודה', uploadButton: 'הוספת תמונות', unassigned: 'ללא קבוצה', general: 'כללי', damage: 'נזק', dropHere: 'שחררו כאן', releaseHere: 'שחררו כאן', empty: 'ריק', caption: 'דוגמה: לאחר ההעלאה התמונות נשארות ב״ללא קבוצה״ עד שמעבירים אותן לקבוצה הנכונה.', sequenceTitle: 'הסדר הנכון', step1Title: 'פתחו הזמנת עבודה', step1Text: 'פתחו את דף Photos מתוך Mains.', step2Title: 'בחרו קבצים', step2Text: 'בחרו קובצי JPG, PNG או WEBP ברורים ורלוונטיים.', step3Title: 'המתינו להעלאה', step3Text: 'אל תמשיכו עד שכל הקבצים שנבחרו מופיעים.', step4Title: 'העבירו בגרירה', step4Text: 'גררו כל קובץ לקבוצה המתאימה.', step5Title: 'בדקו את התוצאה', step5Text: 'ודאו שכל תמונה נדרשת נמצאת בקבוצה הנכונה.', demoTitle: 'העברת תמונות בין קבוצות', demoText: 'גררו תמונה ממוזערת מהעמודה שלה לאזור היעד המודגש. שחררו רק כאשר קבוצת היעד מודגשת.', frame1: '1. התמונות הועלו', frame2: '2. גררו אל היעד', frame3: '3. בדקו את התוצאה', checklistTitle: 'לפני היציאה מהדף', check1: 'נפתחה הזמנת העבודה הנכונה.', check2: 'התמונות ברורות, רלוונטיות והועלו במלואן.', check3: 'כל תמונה נמצאת בקבוצה המיועדת לה.', check4: 'כל הראיות הנדרשות קיימות לפני שהזמנת העבודה מתקדמת.' }
        };
        const mobileCaptions = {
            en: { mobileShotSelect: '1. In the mobile Workorder page, select the photo group and tap the camera icon to add files.', mobileShotResult: '2. After upload, verify the thumbnail and counter in the required group.' },
            ru: { mobileShotSelect: '1. На мобильной странице Workorder выберите группу фотографий и нажмите значок камеры, чтобы добавить файлы.', mobileShotResult: '2. После загрузки проверьте миниатюру и счётчик в нужной группе.' },
            uk: { mobileShotSelect: '1. На мобільній сторінці Workorder виберіть групу фотографій і натисніть значок камери, щоб додати файли.', mobileShotResult: '2. Після завантаження перевірте мініатюру та лічильник у потрібній групі.' },
            he: { mobileShotSelect: '1. בדף Workorder בנייד בחרו את קבוצת התמונות ולחצו על סמל המצלמה כדי להוסיף קבצים.', mobileShotResult: '2. לאחר ההעלאה ודאו שהתמונה הממוזערת והמונה מופיעים בקבוצה הנדרשת.' }
        };
        const navigationTranslations = {
            en: { tocGettingStarted: '1. Getting started', tocLogin: 'Sign in and navigation', tocPhotos: '2. Workorder photos', startHeading: 'Sign in and navigate the program', startIntro: 'Open AVIA with your assigned account. The desktop sidebar is the main menu; on mobile, the top bar keeps the current work order and its actions within reach.', desktopCaption: 'Desktop: use the sidebar to open the main working areas. The selected item is highlighted.', mobileCaption: 'Mobile: use the top navigation to move between the work order, tasks, parts and processes.', desktopMenuTitle: 'Desktop sidebar', desktopWorkorder: 'Workorder — list, filters and work order cards.', desktopTraining: 'Training — assigned training and progress.', desktopManuals: 'Manuals — technical manuals and linked data.', desktopLibrary: 'Library — reference lists and directories.', desktopNotifications: 'Bell — notifications and reminders.', desktopTheme: 'Theme — light or dark interface mode.', mobileMenuTitle: 'Mobile top bar', mobileWo: 'WO — return to the work order list.', mobileWorkorder: 'Workorder — current work order details and photos.', mobileTasks: 'Tasks — task status and dates.', mobileParts: 'Parts — components and attached records.', mobileProcess: 'Process — the work process for the order.' },
            ru: { tocGettingStarted: '1. Начало работы', tocLogin: 'Вход и навигация', tocPhotos: '2. Фотографии work order', startHeading: 'Вход в программу и навигация', startIntro: 'Откройте AVIA под своей учётной записью. На desktop основным меню является боковая панель; на mobile верхняя панель всегда оставляет под рукой текущий work order и его действия.', desktopCaption: 'Desktop: открывайте основные разделы через боковое меню. Выбранный пункт подсвечивается.', mobileCaption: 'Mobile: переходите между work order, задачами, деталями и процессами через верхнюю навигацию.', desktopMenuTitle: 'Боковое меню desktop', desktopWorkorder: 'Workorder — список, фильтры и карточки work order.', desktopTraining: 'Training — назначенное обучение и прогресс.', desktopManuals: 'Manuals — технические руководства и связанные данные.', desktopLibrary: 'Library — справочники и каталоги.', desktopNotifications: 'Колокольчик — уведомления и напоминания.', desktopTheme: 'Theme — светлая или тёмная тема интерфейса.', mobileMenuTitle: 'Верхняя панель mobile', mobileWo: 'WO — вернуться к списку work order.', mobileWorkorder: 'Workorder — данные текущего work order и фотографии.', mobileTasks: 'Tasks — статусы и даты задач.', mobileParts: 'Parts — компоненты и связанные записи.', mobileProcess: 'Process — процессы выполнения работы.' },
            uk: { tocGettingStarted: '1. Початок роботи', tocLogin: 'Вхід і навігація', tocPhotos: '2. Фотографії work order', startHeading: 'Вхід до програми та навігація', startIntro: 'Відкрийте AVIA під своїм обліковим записом. На desktop основним меню є бічна панель; на mobile верхня панель залишає поточний work order і його дії під рукою.', desktopCaption: 'Desktop: відкривайте основні розділи через бічне меню. Вибраний пункт підсвічується.', mobileCaption: 'Mobile: переходьте між work order, завданнями, деталями та процесами через верхню навігацію.', desktopMenuTitle: 'Бічне меню desktop', desktopWorkorder: 'Workorder — список, фільтри та картки work order.', desktopTraining: 'Training — призначене навчання і прогрес.', desktopManuals: 'Manuals — технічні керівництва та пов’язані дані.', desktopLibrary: 'Library — довідники та каталоги.', desktopNotifications: 'Дзвіночок — повідомлення й нагадування.', desktopTheme: 'Theme — світла або темна тема інтерфейсу.', mobileMenuTitle: 'Верхня панель mobile', mobileWo: 'WO — повернутися до списку work order.', mobileWorkorder: 'Workorder — дані поточного work order і фотографії.', mobileTasks: 'Tasks — статуси та дати завдань.', mobileParts: 'Parts — компоненти та пов’язані записи.', mobileProcess: 'Process — процеси виконання роботи.' },
            he: { tocGettingStarted: '1. תחילת העבודה', tocLogin: 'כניסה וניווט', tocPhotos: '2. תמונות work order', startHeading: 'כניסה למערכת וניווט', startIntro: 'פתחו את AVIA באמצעות החשבון שהוקצה לכם. ב-desktop סרגל הצד הוא התפריט הראשי; ב-mobile הסרגל העליון משאיר את הזמנת העבודה הנוכחית ואת פעולותיה בהישג יד.', desktopCaption: 'Desktop: פתחו את אזורי העבודה העיקריים דרך סרגל הצד. הפריט הנבחר מודגש.', mobileCaption: 'Mobile: עברו בין work order, משימות, חלקים ותהליכים דרך הניווט העליון.', desktopMenuTitle: 'סרגל צד ב-desktop', desktopWorkorder: 'Workorder — רשימה, מסננים וכרטיסי work order.', desktopTraining: 'Training — הדרכות שהוקצו והתקדמות.', desktopManuals: 'Manuals — מדריכים טכניים ונתונים מקושרים.', desktopLibrary: 'Library — רשימות עזר ומאגרי מידע.', desktopNotifications: 'פעמון — התראות ותזכורות.', desktopTheme: 'Theme — מצב תצוגה בהיר או כהה.', mobileMenuTitle: 'סרגל עליון ב-mobile', mobileWo: 'WO — חזרה לרשימת work order.', mobileWorkorder: 'Workorder — פרטי הזמנת העבודה הנוכחית ותמונות.', mobileTasks: 'Tasks — סטטוס ותאריכי משימות.', mobileParts: 'Parts — רכיבים ורשומות מקושרות.', mobileProcess: 'Process — תהליך העבודה עבור ההזמנה.' }
        };
        const scope = 'admin.user-guide';
        const languageButtons = Array.from(document.querySelectorAll('[data-language]'));
        const setLanguage = (language, persist) => {
            const activeLanguage = translations[language] ? language : 'en';
            const text = Object.assign({}, translations[activeLanguage], mobileCaptions[activeLanguage], navigationTranslations[activeLanguage]);
            document.documentElement.lang = activeLanguage;
            document.documentElement.dir = activeLanguage === 'he' ? 'rtl' : 'ltr';
            document.querySelectorAll('[data-guide]').forEach((element) => {
                const key = element.dataset.guide;
                if (Object.prototype.hasOwnProperty.call(text, key)) element.textContent = text[key];
            });
            languageButtons.forEach((button) => button.classList.toggle('is-active', button.dataset.language === activeLanguage));
            if (persist) window.UserUiSettings.set(scope, 'language', activeLanguage);
        };
        languageButtons.forEach((button) => button.addEventListener('click', () => setLanguage(button.dataset.language, true)));
        document.querySelectorAll('.guide-toc details').forEach((details) => {
            details.addEventListener('toggle', () => {
                if (!details.open) return;
                document.querySelectorAll('.guide-toc details').forEach((other) => {
                    if (other !== details) other.open = false;
                });
            });
        });
        document.querySelectorAll('.guide-toc a').forEach((link) => link.addEventListener('click', () => {
            link.closest('details').open = true;
        }));
        window.UserUiSettings.get(scope, 'language', 'en').then((language) => setLanguage(language, false));
        document.querySelector('[data-guide-back]').addEventListener('click', (event) => {
            if (window.history.length > 1 && document.referrer.startsWith(window.location.origin)) {
                event.preventDefault();
                window.history.back();
            }
        });
    })();
</script>
</body>
</html>
