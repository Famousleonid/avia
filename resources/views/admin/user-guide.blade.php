<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>User Guide</title>
    <link rel="icon" href="{{ asset('img/favicon.webp') }}" type="image/png">
    @include('partials.user-ui-settings')
    <link rel="stylesheet" href="{{ asset('assets/Bootstrap 5/bootstrap-icons.css') }}">
    <style>
        :root {
            color-scheme: dark;
            --navy-950: #071521;
            --navy-900: #0b1c2a;
            --navy-800: #122c40;
            --cyan-500: #11b5d9;
            --cyan-100: #123d4c;
            --ink: #edf5f8;
            --muted: #aebdc7;
            --line: #294659;
            --paper: #102433;
            --canvas: #071722;
            --shadow: 0 18px 55px rgba(0, 0, 0, .32);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-width: 320px;
            background: var(--canvas);
            color: var(--ink);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            line-height: 1.6;
        }

        button, a { font: inherit; }

        .guide-topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            min-height: 66px;
            display: grid;
            grid-template-columns: minmax(170px, 1fr) auto minmax(170px, 1fr);
            align-items: center;
            gap: 18px;
            padding: 10px clamp(16px, 3vw, 42px);
            border-bottom: 1px solid rgba(255, 255, 255, .09);
            background: var(--navy-950);
            color: #fff;
        }

        .guide-topbar__start,
        .guide-topbar__end {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .guide-topbar__end { justify-content: flex-end; }

        .guide-title {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(1.05rem, 2vw, 1.35rem);
            letter-spacing: .01em;
            white-space: nowrap;
        }

        .guide-button,
        .language-button {
            min-height: 36px;
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 7px;
            background: transparent;
            color: #fff;
            cursor: pointer;
            transition: border-color .18s ease, background .18s ease, color .18s ease;
        }

        .guide-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 6px 11px;
            text-decoration: none;
        }

        .guide-icon {
            font-family: Arial, sans-serif;
            font-size: 1rem;
            line-height: 1;
        }

        .guide-button:hover,
        .guide-button:focus-visible,
        .language-button:hover,
        .language-button:focus-visible {
            border-color: var(--cyan-500);
            outline: none;
        }

        .guide-button:disabled {
            opacity: .35;
            cursor: default;
            border-color: rgba(255, 255, 255, .18);
        }

        .toc-toggle { display: none; }

        .language-list {
            display: flex;
            gap: 2px;
            padding-inline-start: 6px;
            border-inline-start: 1px solid rgba(255, 255, 255, .15);
        }

        .language-button {
            min-width: 31px;
            padding: 4px 5px;
            border-color: transparent;
            font-size: .68rem;
            font-weight: 700;
        }

        .language-button.is-active {
            border-color: var(--cyan-500);
            background: rgba(17, 181, 217, .14);
            color: #79e5f6;
        }

        .guide-shell {
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
            min-height: calc(100vh - 66px);
        }

        .guide-sidebar {
            position: sticky;
            top: 66px;
            align-self: start;
            height: calc(100vh - 66px);
            overflow-y: auto;
            padding: 34px 24px;
            background: var(--navy-900);
            color: #d8e3eb;
        }

        .guide-sidebar__heading {
            margin: 0 0 20px;
            color: #fff;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .toc-group + .toc-group { margin-top: 26px; }

        .toc-group__title {
            margin: 0 0 9px;
            color: #8ea3b2;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .toc-link {
            display: block;
            margin-inline-start: -10px;
            padding: 8px 10px;
            border-inline-start: 2px solid transparent;
            color: #d8e3eb;
            font-size: .88rem;
            line-height: 1.35;
            text-decoration: none;
        }

        .toc-link:hover,
        .toc-link:focus-visible {
            color: #fff;
            outline: none;
        }

        .toc-link.is-active {
            border-inline-start-color: var(--cyan-500);
            background: rgba(17, 181, 217, .08);
            color: #6fe0f2;
        }

        .guide-reading {
            display: flex;
            flex-direction: column;
            min-width: 0;
            padding: clamp(18px, 2.5vw, 36px);
        }

        .guide-page {
            width: min(100%, 1196px);
            min-height: calc(100vh - 128px);
            margin: 0 auto 42px;
            padding: clamp(20px, 3vw, 36px);
            border: 1px solid var(--line);
            border-radius: 3px;
            background: var(--paper);
            box-shadow: var(--shadow);
            scroll-margin-top: 90px;
        }

        .guide-page#sign-in { order: 11; }
        .guide-page#cabinet { order: 12; }
        .guide-page#workorders { order: 21; }
        .guide-page#workorder-start { order: 22; }
        .guide-page#workorder-filters { order: 23; }
        .guide-page#workorder-open { order: 24; }
        .guide-page#workorder-main { order: 25; }
        .guide-page#workorder-main-details { order: 26; }

        .guide-page:last-child { margin-bottom: 42px; }
        .guide-page[data-guide-order="26"] { margin-bottom: 0; }

        .chapter-label {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 13px;
            color: #2a8299;
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        .chapter-label::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #315365;
        }

        .page-title {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(1.05rem, 2vw, 1.35rem);
            font-weight: 400;
            line-height: 1.08;
            letter-spacing: -.025em;
        }

        .page-lead {
            max-width: 760px;
            margin: 16px 0 30px;
            color: var(--muted);
            font-size: clamp(1rem, 1.8vw, 1.14rem);
        }

        .guide-page__title-row {
            display: flex;
            align-items: baseline;
            gap: 12px;
            margin-bottom: 30px;
        }

        .guide-page__title-row .page-lead {
            max-width: none;
            margin: 0;
            font-size: .9rem;
            line-height: 1.35;
        }

        .guide-figure {
            margin: 0 0 34px;
            padding: 13px;
            border: 1px solid #203c50;
            border-radius: 9px;
            background: var(--navy-900);
        }

        .guide-figure img {
            display: block;
            width: 100%;
            height: auto;
            max-height: 610px;
            object-fit: contain;
            border-radius: 4px;
            background: #06131e;
        }

        .guide-figure figcaption {
            padding: 10px 4px 0;
            color: #b9c9d4;
            font-size: .78rem;
            line-height: 1.45;
        }

        .guide-figure--main-header {
            margin-top: clamp(22px, 3vw, 38px);
            margin-bottom: clamp(34px, 4vw, 52px);
            padding: 3px;
        }

        .guide-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .guide-action {
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr);
            gap: 11px;
            align-items: start;
            min-width: 0;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: rgba(7, 21, 33, .28);
        }

        .guide-action__icon {
            display: grid;
            width: 38px;
            height: 38px;
            place-items: center;
            border: 1px solid currentColor;
            border-radius: 7px;
            font-size: 1.15rem;
        }

        .guide-action__icon--tdr { color: #38c778; }
        .guide-action__icon--photos { color: #1ec6e8; }
        .guide-action__icon--pdf { color: #f0c934; }
        .guide-action__icon--training { color: #32b8d7; }
        .guide-action__icon--add-training { color: #4c9cff; }

        .guide-action__title {
            margin: 0 0 3px;
            color: #fff;
            font-size: .88rem;
            font-weight: 700;
            line-height: 1.25;
        }

        .guide-action__text {
            margin: 0;
            color: #cad6dd;
            font-size: .82rem;
            line-height: 1.4;
        }

        .guide-filter-shot {
            position: relative;
        }

        .guide-filter-shot img { display: block; }

        .filter-highlight {
            position: absolute;
            top: 1.7%;
            height: 9.8%;
            border: 3px solid #f0c934;
            border-radius: 48% 54% 46% 57%;
            box-shadow: 0 0 0 2px rgba(5, 21, 31, .48);
            pointer-events: none;
        }

        .filter-highlight--active {
            inset-inline-start: 36.4%;
            width: 18%;
            transform: rotate(-2deg);
        }

        .filter-highlight--mine {
            inset-inline-start: 57.4%;
            width: 22.5%;
            transform: rotate(1.5deg);
        }

        .filter-highlight--approved {
            inset-inline-start: 83.5%;
            width: 16%;
            transform: rotate(-1.2deg);
        }

        .guide-assignment-shot {
            position: relative;
        }

        .guide-assignment-shot img { display: block; }

        .assignment-highlight {
            position: absolute;
            border: 3px solid #f0c934;
            border-radius: 48% 54% 46% 57%;
            box-shadow: 0 0 0 2px rgba(5, 21, 31, .48);
            pointer-events: none;
        }

        .assignment-highlight--mine {
            inset-inline-start: 81.8%;
            top: 1.1%;
            width: 10%;
            height: 4.5%;
            transform: rotate(1.3deg);
        }

        .assignment-highlight--technician {
            inset-inline-start: 86.7%;
            top: 5.3%;
            width: 12.6%;
            height: 89.4%;
            transform: rotate(-.7deg);
        }

        .guide-page__split {
            display: grid;
            grid-template-columns: minmax(0, 3fr) minmax(0, 2fr);
            gap: clamp(20px, 3vw, 42px);
            align-items: start;
        }

        .guide-page__split .guide-figure {
            margin: 0;
        }

        .guide-page__split .guide-copy {
            display: block;
        }

        .guide-page__split .guide-copy section + section {
            margin-top: clamp(28px, 4vw, 48px);
        }

        .guide-page__split + .guide-page__split {
            margin-top: clamp(34px, 5vw, 58px);
            padding-top: clamp(28px, 4vw, 48px);
            border-top: 1px solid var(--line);
        }

        .guide-page__split--workarea {
            margin-top: clamp(34px, 5vw, 58px);
            padding-top: clamp(28px, 4vw, 48px);
            border-top: 1px solid var(--line);
        }

        .guide-open-shot {
            position: relative;
        }

        .guide-open-shot img { display: block; }

        .open-workorder-highlight {
            position: absolute;
            inset-inline-start: .8%;
            top: 16.2%;
            width: 14%;
            height: 7.7%;
            border: 3px solid #f0c934;
            border-radius: 52% 45% 55% 48%;
            box-shadow: 0 0 0 2px rgba(5, 21, 31, .48);
            pointer-events: none;
            transform: rotate(-1.5deg);
        }

        .guide-copy {
            display: grid;
            grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr);
            gap: clamp(28px, 5vw, 64px);
        }

        .guide-copy h2 {
            margin: 0 0 12px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.32rem;
            font-weight: 400;
        }

        .guide-copy p {
            margin: 0;
            color: #cad6dd;
        }

        .guide-steps {
            margin: 0;
            padding: 0;
            list-style: none;
            counter-reset: guide-step;
        }

        .guide-steps li {
            position: relative;
            min-height: 42px;
            padding-inline-start: 53px;
            counter-increment: guide-step;
            color: #d9e4e9;
        }

        .guide-steps li + li {
            margin-top: 19px;
            padding-top: 19px;
            border-top: 1px solid var(--line);
        }

        .guide-steps li::before {
            content: counter(guide-step);
            position: absolute;
            inset-inline-start: 0;
            top: 0;
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--cyan-100);
            color: #087d98;
            font-weight: 800;
        }

        .guide-steps li + li::before { top: 19px; }

        @media (max-width: 1120px) {
            .guide-topbar {
                grid-template-columns: auto 1fr;
            }

            .guide-title { display: none; }
        }

        @media (max-width: 980px) {
            .guide-topbar {
                min-height: 58px;
                padding: 8px 12px;
            }

            .toc-toggle { display: inline-flex; }
            .return-label, .nav-label { display: none; }

            .guide-shell {
                display: block;
                min-height: calc(100vh - 58px);
            }

            .guide-sidebar {
                position: fixed;
                z-index: 35;
                top: 58px;
                inset-inline-start: 0;
                width: min(310px, 86vw);
                height: calc(100vh - 58px);
                transform: translateX(-105%);
                box-shadow: 16px 0 40px rgba(0, 0, 0, .3);
                transition: transform .2s ease;
            }

            .guide-sidebar.is-open { transform: translateX(0); }
            .guide-reading { padding: 24px 16px; }
            .guide-page { min-height: auto; }
            .guide-page__split { grid-template-columns: 1fr; }
        }

        @media (max-width: 620px) {
            .guide-topbar {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .guide-topbar__start,
            .guide-topbar__end {
                justify-content: space-between;
                gap: 5px;
            }

            .guide-button {
                min-width: 34px;
                padding: 5px 8px;
            }

            .language-list {
                max-width: none;
                overflow: visible;
            }

            .language-button { min-width: 28px; }

            .guide-page {
                padding: 24px 18px;
                border-radius: 0;
            }

            .guide-copy {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .guide-actions { grid-template-columns: 1fr; }

            .guide-page__title-row { flex-wrap: wrap; }

            .page-lead { margin-bottom: 22px; }
            .guide-figure { margin-bottom: 27px; padding: 8px; }
        }

        @media print {
            .guide-topbar, .guide-sidebar { display: none; }
            .guide-shell { display: block; }
            .guide-reading { padding: 0; }
            .guide-page {
                min-height: 0;
                margin: 0;
                padding: 18mm;
                border: 0;
                box-shadow: none;
                break-after: page;
            }
        }
    </style>
</head>
<body>
    <header class="guide-topbar">
        <div class="guide-topbar__start">
            <button type="button" class="guide-button toc-toggle" id="toc-toggle" aria-controls="guide-sidebar" aria-expanded="false">
                <span class="guide-icon" aria-hidden="true">☰</span>
                <span data-i18n="menu">Contents</span>
            </button>
            <a class="guide-button" href="{{ route('workorders.index') }}">
                <span class="guide-icon" aria-hidden="true">←</span>
                <span class="return-label" data-i18n="back">Return to system</span>
            </a>
        </div>

        <h1 class="guide-title" data-i18n="pageTitle">User Guide</h1>

        <div class="guide-topbar__end">
            <button type="button" class="guide-button" id="previous-page">
                <span class="guide-icon" aria-hidden="true">←</span>
                <span class="nav-label" data-i18n="previous">Previous</span>
            </button>
            <button type="button" class="guide-button" id="next-page">
                <span class="nav-label" data-i18n="next">Next</span>
                <span class="guide-icon" aria-hidden="true">→</span>
            </button>
            <div class="language-list" aria-label="Language">
                @foreach (['en' => 'EN', 'ru' => 'RU', 'uk' => 'UA', 'he' => 'HE', 'de' => 'DE', 'kk' => 'KZ', 'be' => 'BE'] as $code => $label)
                    <button type="button" class="language-button{{ $code === 'en' ? ' is-active' : '' }}" data-language="{{ $code }}">{{ $label }}</button>
                @endforeach
            </div>
        </div>
    </header>

    <div class="guide-shell">
        <aside class="guide-sidebar" id="guide-sidebar">
            <h2 class="guide-sidebar__heading" data-i18n="contents">Contents</h2>

            <nav aria-label="Guide chapters">
                <section class="toc-group">
                    <h3 class="toc-group__title" data-i18n="startGroup">1. Getting started</h3>
                    <a class="toc-link is-active" href="#sign-in" data-i18n="loginNav">1.1 Sign in</a>
                    <a class="toc-link" href="#cabinet" data-i18n="cabinetNav">1.2 Cabinet</a>
                </section>

                <section class="toc-group">
                    <h3 class="toc-group__title" data-i18n="workorderGroup">2. Workorder</h3>
                    <a class="toc-link" href="#workorders" data-i18n="workorderNav">2.1 Workorders page</a>
                    <a class="toc-link" href="#workorder-start" data-i18n="workorderStartNav">2.2 Getting started with a workorder</a>
                    <a class="toc-link" href="#workorder-filters" data-i18n="filtersNav">2.3 Filters</a>
                    <a class="toc-link" href="#workorder-open" data-i18n="workorderOpenNav">2.4 Open a workorder</a>
                    <a class="toc-link" href="#workorder-main" data-i18n="workorderMainNav">2.5 Main</a>
                    <a class="toc-link" href="#workorder-main-details" data-i18n="workorderMainDetailsNav">2.6 Main: header and work area</a>
                </section>
            </nav>
        </aside>

        <main class="guide-reading">
            <article class="guide-page" id="sign-in" data-guide-page data-guide-order="11">
                <div class="chapter-label" data-i18n="loginChapter">1.1 · Getting started</div>
                <h2 class="page-title" data-i18n="loginTitle">Sign in</h2>
                <p class="page-lead" data-i18n="loginLead">Use your company account to enter the technician area.</p>

                <figure class="guide-figure">
                    <img src="{{ asset('img/user-guide/technician-login.png') }}" alt="AVIA technician sign-in page" data-i18n-alt="loginAlt">
                    <figcaption data-i18n="loginCaption">Technician sign-in page.</figcaption>
                </figure>

                <div class="guide-copy">
                    <section>
                        <h2 data-i18n="whatTitle">What this page is</h2>
                        <p data-i18n="loginWhat">This is the secure entry page for the technician cabinet. Only an account issued by the company can be used here.</p>
                    </section>
                    <section>
                        <h2 data-i18n="howTitle">How to use it</h2>
                        <ol class="guide-steps">
                            <li data-i18n="loginStep1">Enter your company email address.</li>
                            <li data-i18n="loginStep2">Enter your password.</li>
                            <li data-i18n="loginStep3">Press Sign in to open your cabinet.</li>
                        </ol>
                    </section>
                </div>
            </article>

            <article class="guide-page" id="cabinet" data-guide-page data-guide-order="12">
                <div class="chapter-label" data-i18n="cabinetChapter">1.2 · Getting started</div>
                <h2 class="page-title" data-i18n="cabinetTitle">Cabinet</h2>
                <p class="page-lead" data-i18n="cabinetLead">The cabinet is the starting point for all technician tasks.</p>

                <figure class="guide-figure">
                    <img src="{{ asset('img/user-guide/technician-cabinet.png') }}" alt="AVIA technician cabinet" data-i18n-alt="cabinetAlt">
                    <figcaption data-i18n="cabinetCaption">Main technician cabinet.</figcaption>
                </figure>

                <div class="guide-copy">
                    <section>
                        <h2 data-i18n="whatTitle">What this page is</h2>
                        <p data-i18n="cabinetWhat">The cabinet collects the sections available to you. The menu may differ depending on your role and permissions.</p>
                    </section>
                    <section>
                        <h2 data-i18n="howTitle">How to use it</h2>
                        <ol class="guide-steps">
                            <li data-i18n="cabinetStep1">Choose Workorders to find and open a work order.</li>
                            <li data-i18n="cabinetStep2">Use Training and Materials when those tasks are assigned to you.</li>
                            <li data-i18n="cabinetStep3">Use the profile menu to change your account settings or sign out.</li>
                        </ol>
                    </section>
                </div>
            </article>

            <article class="guide-page" id="workorder-start" data-guide-page data-guide-order="22">
                <div class="chapter-label" data-i18n="workorderStartChapter">2.2 · Workorder</div>
                <h2 class="page-title" data-i18n="workorderStartTitle">Getting started with a workorder</h2>
                <p class="page-lead" data-i18n="workorderStartLead">A manager assigns a workorder to a technician. Assigned workorders appear in the Workorders table.</p>

                <figure class="guide-figure">
                    <div class="guide-assignment-shot">
                        <img src="{{ asset('img/user-guide/technician-workorders-assignment.png') }}" alt="AVIA workorders table with the Technician column" data-i18n-alt="workorderStartAlt">
                        <span class="assignment-highlight assignment-highlight--mine" aria-hidden="true"></span>
                        <span class="assignment-highlight assignment-highlight--technician" aria-hidden="true"></span>
                    </div>
                    <figcaption data-i18n="workorderStartCaption">The real Workorders table. The yellow marks show My workorders and the Technician column.</figcaption>
                </figure>

                <div class="guide-copy">
                    <section>
                        <h2 data-i18n="whatTitle">What this page is</h2>
                        <p data-i18n="workorderStartWhat">Every workorder is assigned by a manager. The Technician column shows the person responsible for the workorder.</p>
                    </section>
                    <section>
                        <h2 data-i18n="howTitle">How to use it</h2>
                        <ol class="guide-steps">
                            <li data-i18n="workorderStartStep1">Your manager assigns a workorder to you.</li>
                            <li data-i18n="workorderStartStep2">Find your name in the Technician column. This confirms that the workorder is assigned to you.</li>
                            <li data-i18n="workorderStartStep3">Select My workorders to display only rows assigned to your account.</li>
                            <li data-i18n="workorderStartStep4">Clear My workorders to return to the full list available to your role.</li>
                        </ol>
                    </section>
                </div>
            </article>

            <article class="guide-page" id="workorder-open" data-guide-page data-guide-order="24">
                <div class="chapter-label" data-i18n="workorderOpenChapter">2.4 · Workorder</div>
                <h2 class="page-title" data-i18n="workorderOpenTitle">Open a workorder</h2>
                <p class="page-lead" data-i18n="workorderOpenLead">Click the blue workorder number to open its content.</p>

                <div class="guide-page__split">
                    <figure class="guide-figure">
                        <div class="guide-open-shot">
                            <img src="{{ asset('img/user-guide/technician-workorders-open.png') }}" alt="Blue workorder number in the AVIA table" data-i18n-alt="workorderOpenAlt">
                            <span class="open-workorder-highlight" aria-hidden="true"></span>
                        </div>
                        <figcaption data-i18n="workorderOpenCaption">The blue workorder number opens the selected workorder.</figcaption>
                    </figure>

                    <div class="guide-copy">
                        <section>
                            <h2 data-i18n="whatTitle">What this page is</h2>
                            <p data-i18n="workorderOpenWhat">Each blue workorder number is a link to the content of that workorder.</p>
                        </section>
                        <section>
                            <h2 data-i18n="howTitle">How to use it</h2>
                            <ol class="guide-steps">
                                <li data-i18n="workorderOpenStep1">Find the required workorder in the table.</li>
                                <li data-i18n="workorderOpenStep2">Click its blue number.</li>
                                <li data-i18n="workorderOpenStep3">The selected workorder opens on its own page, where you can review and complete your work.</li>
                            </ol>
                        </section>
                    </div>
                </div>
            </article>

            <article class="guide-page" id="workorders" data-guide-page data-guide-order="21">
                <div class="chapter-label" data-i18n="workorderChapter">2.1 · Workorder</div>
                <h2 class="page-title" data-i18n="workorderTitle">Workorders page</h2>
                <p class="page-lead" data-i18n="workorderLead">Find a workorder, review its status and open it for work.</p>

                <figure class="guide-figure">
                    <img src="{{ asset('img/user-guide/technician-workorders.png') }}" alt="AVIA workorders list" data-i18n-alt="workorderAlt">
                    <figcaption data-i18n="workorderCaption">Workorders available to the technician.</figcaption>
                </figure>

                <div class="guide-copy">
                    <section>
                        <h2 data-i18n="whatTitle">What this page is</h2>
                        <p data-i18n="workorderWhat">This page shows workorders available to you. Each row contains the workorder number, component information and current status.</p>
                    </section>
                    <section>
                        <h2 data-i18n="howTitle">How to use it</h2>
                        <ol class="guide-steps">
                            <li data-i18n="workorderStep1">Use the search field to find a workorder by number or component information.</li>
                            <li data-i18n="workorderStep2">Check the row details and status before opening the workorder.</li>
                            <li data-i18n="workorderStep3">Select the required row to open the workorder and continue the task.</li>
                        </ol>
                    </section>
                </div>
            </article>

            <article class="guide-page" id="workorder-filters" data-guide-page data-guide-order="23">
                <div class="chapter-label" data-i18n="filtersChapter">2.3 · Workorder</div>
                <h2 class="page-title" data-i18n="filtersTitle">Filters</h2>
                <p class="page-lead" data-i18n="filtersLead">Use the filters above the table to display only the workorders needed now.</p>

                <div class="guide-page__split">
                    <figure class="guide-figure">
                        <div class="guide-filter-shot">
                            <img src="{{ asset('img/user-guide/technician-workorders-filters-split.png') }}" alt="Workorder filters above the AVIA table" data-i18n-alt="filtersAlt">
                            <span class="filter-highlight filter-highlight--active" aria-hidden="true"></span>
                            <span class="filter-highlight filter-highlight--mine" aria-hidden="true"></span>
                            <span class="filter-highlight filter-highlight--approved" aria-hidden="true"></span>
                        </div>
                        <figcaption data-i18n="filtersCaption">The real Workorders filter row.</figcaption>
                    </figure>

                    <div class="guide-copy">
                        <section>
                            <h2 data-i18n="whatTitle">What this page is</h2>
                            <p data-i18n="filtersWhat">Filters change only the rows shown in the table. They do not change a workorder or its status.</p>
                        </section>
                        <section>
                            <h2 data-i18n="howTitle">How to use it</h2>
                            <ol class="guide-steps">
                                <li data-i18n="filtersStep1"><strong>WO active</strong> hides completed workorders. For technicians and team leaders it also hides workorders submitted for final inspection.</li>
                                <li data-i18n="filtersStep2"><strong>My workorders</strong> keeps only workorders assigned to your account.</li>
                                <li data-i18n="filtersStep3"><strong>Approved</strong> keeps only workorders that have an approval date.</li>
                                <li data-i18n="filtersStep4">Use any combination of the three checkboxes. The table shows only rows matching every selected filter.</li>
                                <li data-i18n="filtersStep5">Clear a checkbox to remove that filter. Your chosen filters are saved for your account.</li>
                            </ol>
                        </section>
                    </div>
                </div>
            </article>

            <article class="guide-page" id="workorder-main" data-guide-page data-guide-order="25">
                <div class="chapter-label" data-i18n="workorderMainChapter">2.5 · Workorder</div>
                <div class="guide-page__title-row">
                    <h2 class="page-title" data-i18n="workorderMainTitle">Main</h2>
                    <p class="page-lead" data-i18n="workorderMainLead">Main is the work area for the selected workorder.</p>
                </div>

                <div class="guide-page__split">
                    <figure class="guide-figure">
                        <img src="{{ asset('img/user-guide/technician-workorder-main-technician.png') }}" alt="Main page of an AVIA workorder" data-i18n-alt="workorderMainAlt">
                        <figcaption data-i18n="workorderMainCaption">The real Main page of workorder 100000.</figcaption>
                    </figure>

                    <div class="guide-copy">
                        <section>
                            <h2 data-i18n="whatTitle">What this page is</h2>
                            <p data-i18n="workorderMainWhat">Main contains the workorder header, its current status and the work sections available for the selected workorder.</p>
                        </section>
                        <section>
                            <h2 data-i18n="howTitle">How to use it</h2>
                            <ol class="guide-steps">
                                <li data-i18n="workorderMainStep1">Check the workorder number, component, serial number and assigned technician in the header.</li>
                                <li data-i18n="workorderMainStep2">Use the tabs to move between tasks, standard processes, parts and bushing processes.</li>
                                <li data-i18n="workorderMainStep3">Fill in only the sections assigned to your role and task.</li>
                            </ol>
                        </section>
                    </div>
                </div>
            </article>

            <article class="guide-page" id="workorder-main-details" data-guide-page data-guide-order="26">
                <div class="chapter-label" data-i18n="workorderMainDetailsChapter">2.6 · Workorder</div>
                <h2 class="page-title" data-i18n="workorderMainDetailsTitle">Main: header and work area</h2>
                <figure class="guide-figure guide-figure--main-header">
                    <img src="{{ asset('img/user-guide/technician-workorder-main-header-only.png') }}" alt="Main workorder header with technician controls" data-i18n-alt="workorderMainHeaderAlt">
                </figure>

                <ul class="guide-actions" aria-label="Header actions">
                    <li class="guide-action">
                        <span class="guide-action__icon guide-action__icon--tdr" aria-hidden="true"><i class="bi bi-hammer"></i></span>
                        <div><p class="guide-action__title" data-i18n="headerActionTdrTitle">TDR Report</p><p class="guide-action__text" data-i18n="headerActionTdrText">Opens the TDR Report for this workorder.</p></div>
                    </li>
                    <li class="guide-action">
                        <span class="guide-action__icon guide-action__icon--photos" aria-hidden="true"><i class="bi bi-images"></i></span>
                        <div><p class="guide-action__title" data-i18n="headerActionPhotosTitle">Pictures</p><p class="guide-action__text" data-i18n="headerActionPhotosText">Opens all photos of this workorder.</p></div>
                    </li>
                    <li class="guide-action">
                        <span class="guide-action__icon guide-action__icon--pdf" aria-hidden="true"><i class="bi bi-file-earmark-pdf"></i></span>
                        <div><p class="guide-action__title" data-i18n="headerActionPdfTitle">PDF Library</p><p class="guide-action__text" data-i18n="headerActionPdfText">Opens the documents attached to this workorder.</p></div>
                    </li>
                    <li class="guide-action">
                        <span class="guide-action__icon guide-action__icon--training" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
                        <div><p class="guide-action__title" data-i18n="headerActionTrainingTitle">Training</p><p class="guide-action__text" data-i18n="headerActionTrainingText">Shows your training status; hover to view its history.</p></div>
                    </li>
                    <li class="guide-action">
                        <span class="guide-action__icon guide-action__icon--add-training" aria-hidden="true"><i class="bi bi-plus-circle"></i></span>
                        <div><p class="guide-action__title" data-i18n="headerActionAddTrainingTitle">Add training</p><p class="guide-action__text" data-i18n="headerActionAddTrainingText">Opens the form to add or update your training record for this manual.</p></div>
                    </li>
                </ul>

                <div class="guide-page__split guide-page__split--workarea">
                    <figure class="guide-figure">
                        <img src="{{ asset('img/user-guide/technician-workorder-main-workarea.png') }}" alt="Main work area and tabs for a technician" data-i18n-alt="workorderMainWorkareaAlt">
                        <figcaption data-i18n="workorderMainWorkareaCaption">Work area shown at a larger scale.</figcaption>
                    </figure>

                    <div class="guide-copy">
                        <section>
                            <h2 data-i18n="whatTitle">What this page is</h2>
                            <p data-i18n="workorderMainWorkareaWhat">The tabs organize your tasks, notes, standard processes, parts and bushing processes.</p>
                        </section>
                        <section>
                            <h2 data-i18n="howTitle">How to use it</h2>
                            <ol class="guide-steps">
                                <li data-i18n="workorderMainWorkareaStep1">Use All to review the whole workorder and Tasks / Notes for your assigned work and notes.</li>
                                <li data-i18n="workorderMainWorkareaStep2">Open STD Processes, Parts / Processes or Bushing / Processes only when they are required for your task.</li>
                                <li data-i18n="workorderMainWorkareaStep3">Enter dates and notes only after the work has actually been performed.</li>
                            </ol>
                        </section>
                    </div>
                </div>
            </article>
        </main>
    </div>

    <script>
        (() => {
            const translations = {
                en: {
                    pageTitle: 'User Guide', back: 'Return to system', menu: 'Contents', previous: 'Previous', next: 'Next', contents: 'Contents',
                    startGroup: '1. Getting started', loginNav: '1.1 Sign in', cabinetNav: '1.2 Cabinet', workorderGroup: '2. Workorder', workorderNav: '2.1 Workorders page', filtersNav: '2.2 Filters', workorderStartNav: '2.3 Getting started with a workorder', workorderOpenNav: '2.4 Open a workorder',
                    whatTitle: 'What this page is', howTitle: 'How to use it',
                    loginChapter: '1.1 · Getting started', loginTitle: 'Sign in', loginLead: 'Use your company account to enter the technician area.',
                    loginWhat: 'This is the secure entry page for the technician cabinet. Only an account issued by the company can be used here.',
                    loginStep1: 'Enter your company email address.', loginStep2: 'Enter your password.', loginStep3: 'Press Sign in to open your cabinet.',
                    loginCaption: 'Technician sign-in page.', loginAlt: 'AVIA technician sign-in page',
                    cabinetChapter: '1.2 · Getting started', cabinetTitle: 'Cabinet', cabinetLead: 'The cabinet is the starting point for all technician tasks.',
                    cabinetWhat: 'The cabinet collects the sections available to you. The menu may differ depending on your role and permissions.',
                    cabinetStep1: 'Choose Workorders to find and open a work order.', cabinetStep2: 'Use Training and Materials when those tasks are assigned to you.', cabinetStep3: 'Use the profile menu to change your account settings or sign out.',
                    cabinetCaption: 'Main technician cabinet.', cabinetAlt: 'AVIA technician cabinet',
                    workorderStartChapter: '2.1 · Workorder', workorderStartTitle: 'Getting started with a workorder', workorderStartLead: 'A manager assigns a workorder to a technician. Assigned workorders appear in the Workorders table.',
                    workorderStartWhat: 'Every workorder is assigned by a manager. The Technician column shows the person responsible for the workorder.', workorderStartStep1: 'Your manager assigns a workorder to you.', workorderStartStep2: 'Find your name in the Technician column. This confirms that the workorder is assigned to you.', workorderStartStep3: 'Select My workorders to display only rows assigned to your account.', workorderStartStep4: 'Clear My workorders to return to the full list available to your role.', workorderStartCaption: 'The real Workorders table. The yellow marks show My workorders and the Technician column.', workorderStartAlt: 'AVIA workorders table with the Technician column',
                    workorderOpenChapter: '2.2 · Workorder', workorderOpenTitle: 'Open a workorder', workorderOpenLead: 'Click the blue workorder number to open its content.', workorderOpenCaption: 'The blue workorder number opens the selected workorder.', workorderOpenAlt: 'Blue workorder number in the AVIA table',
                    workorderOpenWhat: 'Each blue workorder number is a link to the content of that workorder.', workorderOpenStep1: 'Find the required workorder in the table.', workorderOpenStep2: 'Click its blue number.', workorderOpenStep3: 'The selected workorder opens on its own page, where you can review and complete your work.',
                    workorderChapter: '2.3 · Workorder', workorderTitle: 'Workorders page', workorderLead: 'Find a workorder, review its status and open it for work.',
                    workorderWhat: 'This page shows workorders available to you. Each row contains the workorder number, component information and current status.',
                    workorderStep1: 'Use the search field to find a workorder by number or component information.', workorderStep2: 'Check the row details and status before opening the workorder.', workorderStep3: 'Select the required row to open the workorder and continue the task.',
                    workorderCaption: 'Workorders available to the technician.', workorderAlt: 'AVIA workorders list',
                    filtersChapter: '2.4 · Workorder', filtersTitle: 'Filters', filtersLead: 'Use the filters above the table to display only the workorders needed now.', filtersCaption: 'The real Workorders filter row.', filtersAlt: 'Workorder filters above the AVIA table',
                    filtersWhat: 'Filters change only the rows shown in the table. They do not change a workorder or its status.',
                    filtersStep1: 'WO active hides completed workorders. For technicians and team leaders it also hides workorders submitted for final inspection.', filtersStep2: 'My workorders keeps only workorders assigned to your account.', filtersStep3: 'Approved keeps only workorders that have an approval date.', filtersStep4: 'Use any combination of the three checkboxes. The table shows only rows matching every selected filter.', filtersStep5: 'Clear a checkbox to remove that filter. Your chosen filters are saved for your account.'
                },
                ru: {
                    pageTitle: 'Руководство пользователя', back: 'Вернуться в систему', menu: 'Содержание', previous: 'Назад', next: 'Далее', contents: 'Содержание',
                    startGroup: '1. Начало работы', loginNav: '1.1 Вход', cabinetNav: '1.2 Кабинет', workorderGroup: '2. Workorder', workorderNav: '2.1 Страница Workorders', filtersNav: '2.2 Фильтры', workorderStartNav: '2.3 Начало работы с Workorder', workorderOpenNav: '2.4 Открыть Workorder',
                    whatTitle: 'Что это за страница', howTitle: 'Как с ней работать',
                    loginChapter: '1.1 · Начало работы', loginTitle: 'Вход', loginLead: 'Используйте корпоративную учетную запись для входа в кабинет техника.',
                    loginWhat: 'Это защищенная страница входа в кабинет техника. Здесь можно использовать только учетную запись, выданную компанией.',
                    loginStep1: 'Введите корпоративный адрес электронной почты.', loginStep2: 'Введите пароль.', loginStep3: 'Нажмите «Войти», чтобы открыть кабинет.',
                    loginCaption: 'Страница входа техника.', loginAlt: 'Страница входа техника AVIA',
                    cabinetChapter: '1.2 · Начало работы', cabinetTitle: 'Кабинет', cabinetLead: 'Кабинет — начальная точка для всех задач техника.',
                    cabinetWhat: 'В кабинете собраны доступные вам разделы. Состав меню может отличаться в зависимости от роли и прав доступа.',
                    cabinetStep1: 'Выберите Workorders, чтобы найти и открыть заказ-наряд.', cabinetStep2: 'Используйте Training и Materials, когда вам назначены такие задачи.', cabinetStep3: 'Через меню профиля изменяйте настройки учетной записи или выходите из системы.',
                    cabinetCaption: 'Главный кабинет техника.', cabinetAlt: 'Кабинет техника AVIA',
                    workorderStartChapter: '2.1 · Workorder', workorderStartTitle: 'Начало работы с Workorder', workorderStartLead: 'Менеджер назначает заказ-наряд технику. Назначенные наряды появляются в таблице Workorders.',
                    workorderStartWhat: 'Каждый заказ-наряд назначает менеджер. В колонке Technician показан сотрудник, ответственный за этот наряд.', workorderStartStep1: 'Менеджер назначает вам заказ-наряд.', workorderStartStep2: 'Найдите своё имя в колонке Technician. Это подтверждает, что заказ-наряд назначен вам.', workorderStartStep3: 'Включите My workorders, чтобы показать только наряды, назначенные вашей учётной записи.', workorderStartStep4: 'Снимите My workorders, чтобы вернуться к полному списку, доступному вашей роли.', workorderStartCaption: 'Реальная таблица Workorders. Жёлтым выделены My workorders и колонка Technician.', workorderStartAlt: 'Таблица заказ-нарядов AVIA с колонкой Technician',
                    workorderOpenChapter: '2.2 · Workorder', workorderOpenTitle: 'Открыть Workorder', workorderOpenLead: 'Нажмите на синий номер Workorder, чтобы открыть его содержание.', workorderOpenCaption: 'Синий номер Workorder открывает выбранный заказ-наряд.', workorderOpenAlt: 'Синий номер Workorder в таблице AVIA',
                    workorderOpenWhat: 'Каждый синий номер Workorder — это ссылка на содержание данного заказ-наряда.', workorderOpenStep1: 'Найдите нужный заказ-наряд в таблице.', workorderOpenStep2: 'Нажмите на его синий номер.', workorderOpenStep3: 'Выбранный заказ-наряд откроется на отдельной странице, где можно просмотреть и выполнить свою работу.',
                    workorderChapter: '2.3 · Workorder', workorderTitle: 'Страница Workorders', workorderLead: 'Найдите заказ-наряд, проверьте его статус и откройте для работы.',
                    workorderWhat: 'На странице показаны доступные вам заказ-наряды. В каждой строке указаны номер, сведения о компоненте и текущий статус.',
                    workorderStep1: 'Используйте строку поиска, чтобы найти заказ-наряд по номеру или данным компонента.', workorderStep2: 'Перед открытием проверьте сведения и статус в строке.', workorderStep3: 'Выберите нужную строку, чтобы открыть заказ-наряд и продолжить работу.',
                    workorderCaption: 'Заказ-наряды, доступные технику.', workorderAlt: 'Список заказ-нарядов AVIA',
                    filtersChapter: '2.4 · Workorder', filtersTitle: 'Фильтры', filtersLead: 'Используйте фильтры над таблицей, чтобы показать только нужные сейчас заказ-наряды.', filtersCaption: 'Реальная строка фильтров Workorders.', filtersAlt: 'Фильтры Workorder над таблицей AVIA',
                    filtersWhat: 'Фильтры меняют только состав строк в таблице. Они не изменяют заказ-наряд и его статус.',
                    filtersStep1: 'WO active скрывает завершённые заказ-наряды. Для Technician и Team Leader также скрываются наряды, отправленные на финальную инспекцию.', filtersStep2: 'My workorders оставляет только заказ-наряды, назначенные вашей учётной записи.', filtersStep3: 'Approved оставляет только заказ-наряды с датой одобрения.', filtersStep4: 'Любые три флажка можно сочетать. В таблице останутся только строки, подходящие под все выбранные фильтры.', filtersStep5: 'Снимите флажок, чтобы убрать фильтр. Выбранные фильтры сохраняются для вашей учётной записи.'
                },
                uk: {
                    pageTitle: 'Посібник користувача', back: 'Повернутися до системи', menu: 'Зміст', previous: 'Назад', next: 'Далі', contents: 'Зміст',
                    startGroup: '1. Початок роботи', loginNav: '1.1 Вхід', cabinetNav: '1.2 Кабінет', workorderGroup: '2. Workorder', workorderNav: '2.1 Сторінка Workorders', filtersNav: '2.2 Фільтри', workorderStartNav: '2.3 Початок роботи з Workorder', workorderOpenNav: '2.4 Відкрити Workorder',
                    whatTitle: 'Що це за сторінка', howTitle: 'Як нею користуватися',
                    loginChapter: '1.1 · Початок роботи', loginTitle: 'Вхід', loginLead: 'Використовуйте корпоративний обліковий запис для входу в кабінет техніка.',
                    loginWhat: 'Це захищена сторінка входу в кабінет техніка. Тут можна використовувати лише обліковий запис, виданий компанією.',
                    loginStep1: 'Введіть корпоративну адресу електронної пошти.', loginStep2: 'Введіть пароль.', loginStep3: 'Натисніть «Увійти», щоб відкрити кабінет.',
                    loginCaption: 'Сторінка входу техніка.', loginAlt: 'Сторінка входу техніка AVIA',
                    cabinetChapter: '1.2 · Початок роботи', cabinetTitle: 'Кабінет', cabinetLead: 'Кабінет — початкова точка для всіх завдань техніка.',
                    cabinetWhat: 'У кабінеті зібрано доступні вам розділи. Меню може відрізнятися залежно від ролі та прав доступу.',
                    cabinetStep1: 'Виберіть Workorders, щоб знайти та відкрити наряд.', cabinetStep2: 'Використовуйте Training і Materials, коли вам призначено такі завдання.', cabinetStep3: 'Через меню профілю змінюйте налаштування або виходьте із системи.',
                    cabinetCaption: 'Головний кабінет техніка.', cabinetAlt: 'Кабінет техніка AVIA',
                    workorderStartChapter: '2.1 · Workorder', workorderStartTitle: 'Початок роботи з Workorder', workorderStartLead: 'Менеджер призначає наряд техніку. Призначені наряди з’являються в таблиці Workorders.',
                    workorderStartWhat: 'Кожен наряд призначає менеджер. У колонці Technician показано працівника, відповідального за цей наряд.', workorderStartStep1: 'Менеджер призначає вам наряд.', workorderStartStep2: 'Знайдіть своє ім’я в колонці Technician. Це підтверджує, що наряд призначений вам.', workorderStartStep3: 'Увімкніть My workorders, щоб показати лише наряди, призначені вашому обліковому запису.', workorderStartStep4: 'Зніміть My workorders, щоб повернутися до повного списку, доступного вашій ролі.', workorderStartCaption: 'Реальна таблиця Workorders. Жовтим виділено My workorders і колонку Technician.', workorderStartAlt: 'Таблиця нарядів AVIA з колонкою Technician',
                    workorderOpenChapter: '2.2 · Workorder', workorderOpenTitle: 'Відкрити Workorder', workorderOpenLead: 'Натисніть синій номер Workorder, щоб відкрити його вміст.', workorderOpenCaption: 'Синій номер Workorder відкриває вибраний наряд.', workorderOpenAlt: 'Синій номер Workorder у таблиці AVIA',
                    workorderOpenWhat: 'Кожен синій номер Workorder — це посилання на вміст цього наряду.', workorderOpenStep1: 'Знайдіть потрібний наряд у таблиці.', workorderOpenStep2: 'Натисніть його синій номер.', workorderOpenStep3: 'Вибраний наряд відкриється на окремій сторінці, де можна переглянути й виконати свою роботу.',
                    workorderChapter: '2.3 · Workorder', workorderTitle: 'Сторінка Workorders', workorderLead: 'Знайдіть наряд, перевірте його статус і відкрийте для роботи.',
                    workorderWhat: 'На сторінці показані доступні вам наряди. У кожному рядку вказані номер, дані компонента та поточний статус.',
                    workorderStep1: 'Скористайтеся пошуком, щоб знайти наряд за номером або даними компонента.', workorderStep2: 'Перед відкриттям перевірте дані й статус у рядку.', workorderStep3: 'Виберіть потрібний рядок, щоб відкрити наряд і продовжити роботу.',
                    workorderCaption: 'Наряди, доступні техніку.', workorderAlt: 'Список нарядів AVIA',
                    filtersChapter: '2.4 · Workorder', filtersTitle: 'Фільтри', filtersLead: 'Використовуйте фільтри над таблицею, щоб показати лише потрібні зараз наряди.', filtersCaption: 'Реальний рядок фільтрів Workorders.', filtersAlt: 'Фільтри Workorder над таблицею AVIA',
                    filtersWhat: 'Фільтри змінюють лише рядки, показані в таблиці. Вони не змінюють наряд або його статус.',
                    filtersStep1: 'WO active приховує завершені наряди. Для Technician і Team Leader також приховуються наряди, надіслані на фінальну інспекцію.', filtersStep2: 'My workorders залишає лише наряди, призначені вашому обліковому запису.', filtersStep3: 'Approved залишає лише наряди з датою схвалення.', filtersStep4: 'Можна поєднувати будь-які три прапорці. У таблиці залишаться лише рядки, що відповідають усім вибраним фільтрам.', filtersStep5: 'Зніміть прапорець, щоб прибрати фільтр. Вибрані фільтри зберігаються для вашого облікового запису.'
                },
                he: {
                    pageTitle: 'מדריך למשתמש', back: 'חזרה למערכת', menu: 'תוכן', previous: 'הקודם', next: 'הבא', contents: 'תוכן',
                    startGroup: '1. תחילת העבודה', loginNav: '1.1 כניסה', cabinetNav: '1.2 אזור אישי', workorderGroup: '2. הוראת עבודה', workorderNav: '2.1 דף הוראות עבודה', filtersNav: '2.2 מסננים', workorderStartNav: '2.3 תחילת עבודה עם הוראת עבודה', workorderOpenNav: '2.4 פתיחת הוראת עבודה',
                    whatTitle: 'מהו הדף הזה', howTitle: 'כיצד להשתמש בו',
                    loginChapter: '1.1 · תחילת העבודה', loginTitle: 'כניסה', loginLead: 'השתמשו בחשבון הארגוני כדי להיכנס לאזור הטכנאי.',
                    loginWhat: 'זהו דף כניסה מאובטח לאזור הטכנאי. ניתן להשתמש כאן רק בחשבון שהונפק על ידי החברה.',
                    loginStep1: 'הזינו את כתובת הדוא״ל הארגונית.', loginStep2: 'הזינו את הסיסמה.', loginStep3: 'לחצו על כניסה כדי לפתוח את האזור האישי.',
                    loginCaption: 'דף הכניסה של הטכנאי.', loginAlt: 'דף הכניסה של טכנאי AVIA',
                    cabinetChapter: '1.2 · תחילת העבודה', cabinetTitle: 'אזור אישי', cabinetLead: 'האזור האישי הוא נקודת ההתחלה לכל משימות הטכנאי.',
                    cabinetWhat: 'האזור האישי מרכז את הסעיפים הזמינים לכם. התפריט עשוי להשתנות לפי התפקיד וההרשאות.',
                    cabinetStep1: 'בחרו Workorders כדי למצוא ולפתוח הוראת עבודה.', cabinetStep2: 'השתמשו ב-Training וב-Materials כאשר משימות אלה הוקצו לכם.', cabinetStep3: 'השתמשו בתפריט הפרופיל לשינוי הגדרות או ליציאה.',
                    cabinetCaption: 'האזור האישי הראשי של הטכנאי.', cabinetAlt: 'האזור האישי של טכנאי AVIA',
                    workorderStartChapter: '2.1 · הוראת עבודה', workorderStartTitle: 'תחילת עבודה עם הוראת עבודה', workorderStartLead: 'מנהל מקצה הוראת עבודה לטכנאי. הוראות שהוקצו מופיעות בטבלת Workorders.',
                    workorderStartWhat: 'כל הוראת עבודה מוקצית על ידי מנהל. בעמודת Technician מופיע האחראי על הוראת העבודה.', workorderStartStep1: 'המנהל מקצה לכם הוראת עבודה.', workorderStartStep2: 'מצאו את שמכם בעמודת Technician. כך מאומת שהוראת העבודה הוקצתה לכם.', workorderStartStep3: 'בחרו My workorders כדי להציג רק שורות שהוקצו לחשבונכם.', workorderStartStep4: 'בטלו את My workorders כדי לחזור לרשימה המלאה הזמינה לתפקידכם.', workorderStartCaption: 'טבלת Workorders אמיתית. הסימונים הצהובים מציגים את My workorders ואת עמודת Technician.', workorderStartAlt: 'טבלת הוראות עבודה AVIA עם עמודת Technician',
                    workorderOpenChapter: '2.2 · הוראת עבודה', workorderOpenTitle: 'פתיחת הוראת עבודה', workorderOpenLead: 'לחצו על מספר הוראת העבודה הכחול כדי לפתוח את תוכנה.', workorderOpenCaption: 'מספר הוראת העבודה הכחול פותח את ההוראה שנבחרה.', workorderOpenAlt: 'מספר הוראת עבודה כחול בטבלת AVIA',
                    workorderOpenWhat: 'כל מספר Workorder כחול הוא קישור לתוכן של אותה הוראת עבודה.', workorderOpenStep1: 'מצאו את הוראת העבודה הנדרשת בטבלה.', workorderOpenStep2: 'לחצו על המספר הכחול שלה.', workorderOpenStep3: 'הוראת העבודה שנבחרה נפתחת בדף נפרד, שבו תוכלו לעיין ולהשלים את עבודתכם.',
                    workorderChapter: '2.3 · הוראת עבודה', workorderTitle: 'דף Workorders', workorderLead: 'מצאו הוראת עבודה, בדקו את מצבה ופתחו אותה לביצוע.',
                    workorderWhat: 'בדף מוצגות הוראות העבודה הזמינות לכם. בכל שורה מופיעים המספר, פרטי הרכיב והמצב הנוכחי.',
                    workorderStep1: 'השתמשו בחיפוש כדי למצוא הוראת עבודה לפי מספר או פרטי רכיב.', workorderStep2: 'בדקו את הפרטים והמצב בשורה לפני הפתיחה.', workorderStep3: 'בחרו את השורה הנדרשת כדי לפתוח את הוראת העבודה ולהמשיך.',
                    workorderCaption: 'הוראות עבודה זמינות לטכנאי.', workorderAlt: 'רשימת הוראות העבודה של AVIA',
                    filtersChapter: '2.4 · הוראת עבודה', filtersTitle: 'מסננים', filtersLead: 'השתמשו במסננים שמעל לטבלה כדי להציג רק את הוראות העבודה הנדרשות כעת.', filtersCaption: 'שורת המסננים האמיתית של Workorders.', filtersAlt: 'מסנני Workorder מעל טבלת AVIA',
                    filtersWhat: 'המסננים משנים רק את השורות שמוצגות בטבלה. הם אינם משנים הוראת עבודה או את מצבה.',
                    filtersStep1: 'WO active מסתיר הוראות עבודה שהושלמו. עבור Technician ו-Team Leader הוא גם מסתיר הוראות שנשלחו לבדיקה סופית.', filtersStep2: 'My workorders משאיר רק הוראות עבודה שהוקצו לחשבונכם.', filtersStep3: 'Approved משאיר רק הוראות עבודה שיש להן תאריך אישור.', filtersStep4: 'אפשר לשלב כל אחד משלושת תיבות הסימון. הטבלה תציג רק שורות שמתאימות לכל המסננים שנבחרו.', filtersStep5: 'הסירו את הסימון כדי לבטל מסנן. המסננים שבחרתם נשמרים לחשבונכם.'
                },
                de: {
                    pageTitle: 'Benutzerhandbuch', back: 'Zurück zum System', menu: 'Inhalt', previous: 'Zurück', next: 'Weiter', contents: 'Inhalt',
                    startGroup: '1. Erste Schritte', loginNav: '1.1 Anmeldung', cabinetNav: '1.2 Bereich', workorderGroup: '2. Arbeitsauftrag', workorderNav: '2.1 Workorders-Seite', filtersNav: '2.2 Filter', workorderStartNav: '2.3 Einstieg in einen Arbeitsauftrag', workorderOpenNav: '2.4 Arbeitsauftrag öffnen',
                    whatTitle: 'Was ist diese Seite?', howTitle: 'So verwenden Sie sie',
                    loginChapter: '1.1 · Erste Schritte', loginTitle: 'Anmeldung', loginLead: 'Melden Sie sich mit Ihrem Firmenkonto im Technikerbereich an.',
                    loginWhat: 'Dies ist die geschützte Anmeldung für den Technikerbereich. Hier kann nur ein vom Unternehmen ausgegebenes Konto verwendet werden.',
                    loginStep1: 'Geben Sie Ihre Firmen-E-Mail-Adresse ein.', loginStep2: 'Geben Sie Ihr Passwort ein.', loginStep3: 'Klicken Sie auf Anmelden, um Ihren Bereich zu öffnen.',
                    loginCaption: 'Anmeldeseite für Techniker.', loginAlt: 'AVIA-Anmeldeseite für Techniker',
                    cabinetChapter: '1.2 · Erste Schritte', cabinetTitle: 'Technikerbereich', cabinetLead: 'Der Technikerbereich ist der Ausgangspunkt für alle Aufgaben.',
                    cabinetWhat: 'Hier sind die für Sie verfügbaren Bereiche zusammengefasst. Das Menü kann je nach Rolle und Berechtigung abweichen.',
                    cabinetStep1: 'Wählen Sie Workorders, um einen Arbeitsauftrag zu suchen und zu öffnen.', cabinetStep2: 'Verwenden Sie Training und Materials, wenn Ihnen diese Aufgaben zugewiesen wurden.', cabinetStep3: 'Ändern Sie Kontoeinstellungen oder melden Sie sich über das Profilmenü ab.',
                    cabinetCaption: 'Hauptbereich für Techniker.', cabinetAlt: 'AVIA-Technikerbereich',
                    workorderStartChapter: '2.1 · Arbeitsauftrag', workorderStartTitle: 'Einstieg in einen Arbeitsauftrag', workorderStartLead: 'Ein Manager weist einem Techniker einen Arbeitsauftrag zu. Zugewiesene Arbeitsaufträge erscheinen in der Workorders-Tabelle.',
                    workorderStartWhat: 'Jeder Arbeitsauftrag wird von einem Manager zugewiesen. Die Spalte Technician zeigt die verantwortliche Person.', workorderStartStep1: 'Ihr Manager weist Ihnen einen Arbeitsauftrag zu.', workorderStartStep2: 'Suchen Sie Ihren Namen in der Spalte Technician. Damit ist bestätigt, dass der Arbeitsauftrag Ihnen zugewiesen wurde.', workorderStartStep3: 'Wählen Sie My workorders, um nur die Ihrem Konto zugewiesenen Zeilen anzuzeigen.', workorderStartStep4: 'Deaktivieren Sie My workorders, um zur vollständigen, für Ihre Rolle verfügbaren Liste zurückzukehren.', workorderStartCaption: 'Die echte Workorders-Tabelle. Gelb markiert sind My workorders und die Spalte Technician.', workorderStartAlt: 'AVIA-Arbeitsauftragstabelle mit der Spalte Technician',
                    workorderOpenChapter: '2.2 · Arbeitsauftrag', workorderOpenTitle: 'Arbeitsauftrag öffnen', workorderOpenLead: 'Klicken Sie auf die blaue Arbeitsauftragsnummer, um den Inhalt zu öffnen.', workorderOpenCaption: 'Die blaue Arbeitsauftragsnummer öffnet den ausgewählten Auftrag.', workorderOpenAlt: 'Blaue Arbeitsauftragsnummer in der AVIA-Tabelle',
                    workorderOpenWhat: 'Jede blaue Workorder-Nummer ist ein Link zum Inhalt dieses Arbeitsauftrags.', workorderOpenStep1: 'Suchen Sie den benötigten Arbeitsauftrag in der Tabelle.', workorderOpenStep2: 'Klicken Sie auf seine blaue Nummer.', workorderOpenStep3: 'Der ausgewählte Arbeitsauftrag öffnet sich auf einer eigenen Seite, auf der Sie Ihre Arbeit prüfen und erledigen können.',
                    workorderChapter: '2.3 · Arbeitsauftrag', workorderTitle: 'Workorders-Seite', workorderLead: 'Suchen Sie einen Arbeitsauftrag, prüfen Sie seinen Status und öffnen Sie ihn.',
                    workorderWhat: 'Diese Seite zeigt die für Sie verfügbaren Arbeitsaufträge. Jede Zeile enthält Nummer, Komponentendaten und aktuellen Status.',
                    workorderStep1: 'Suchen Sie nach Nummer oder Komponentendaten.', workorderStep2: 'Prüfen Sie vor dem Öffnen die Angaben und den Status der Zeile.', workorderStep3: 'Wählen Sie die gewünschte Zeile, um den Arbeitsauftrag zu öffnen und fortzufahren.',
                    workorderCaption: 'Für den Techniker verfügbare Arbeitsaufträge.', workorderAlt: 'AVIA-Liste der Arbeitsaufträge',
                    filtersChapter: '2.4 · Arbeitsauftrag', filtersTitle: 'Filter', filtersLead: 'Verwenden Sie die Filter über der Tabelle, um nur die jetzt benötigten Arbeitsaufträge anzuzeigen.', filtersCaption: 'Die echte Workorders-Filterzeile.', filtersAlt: 'Workorder-Filter über der AVIA-Tabelle',
                    filtersWhat: 'Filter ändern nur die in der Tabelle angezeigten Zeilen. Sie ändern keinen Arbeitsauftrag und keinen Status.',
                    filtersStep1: 'WO active blendet abgeschlossene Arbeitsaufträge aus. Für Technician und Team Leader werden außerdem Aufträge ausgeblendet, die zur Endprüfung eingereicht wurden.', filtersStep2: 'My workorders zeigt nur Arbeitsaufträge, die Ihrem Konto zugewiesen sind.', filtersStep3: 'Approved zeigt nur Arbeitsaufträge mit einem Genehmigungsdatum.', filtersStep4: 'Sie können alle drei Kontrollkästchen kombinieren. Die Tabelle zeigt nur Zeilen, die allen gewählten Filtern entsprechen.', filtersStep5: 'Entfernen Sie das Häkchen, um den Filter aufzuheben. Ihre Filterauswahl wird für Ihr Konto gespeichert.'
                },
                kk: {
                    pageTitle: 'Пайдаланушы нұсқаулығы', back: 'Жүйеге оралу', menu: 'Мазмұны', previous: 'Артқа', next: 'Келесі', contents: 'Мазмұны',
                    startGroup: '1. Жұмысты бастау', loginNav: '1.1 Кіру', cabinetNav: '1.2 Кабинет', workorderGroup: '2. Workorder', workorderNav: '2.1 Workorders беті', filtersNav: '2.2 Сүзгілер', workorderStartNav: '2.3 Workorder жұмысын бастау', workorderOpenNav: '2.4 Workorder ашу',
                    whatTitle: 'Бұл қандай бет', howTitle: 'Қалай пайдалану керек',
                    loginChapter: '1.1 · Жұмысты бастау', loginTitle: 'Кіру', loginLead: 'Техник кабинетіне кіру үшін корпоративтік есептік жазбаны пайдаланыңыз.',
                    loginWhat: 'Бұл техник кабинетіне арналған қорғалған кіру беті. Мұнда компания берген есептік жазба ғана қолданылады.',
                    loginStep1: 'Корпоративтік электрондық пошта мекенжайын енгізіңіз.', loginStep2: 'Құпиясөзді енгізіңіз.', loginStep3: 'Кабинетті ашу үшін «Кіру» түймесін басыңыз.',
                    loginCaption: 'Техниктің кіру беті.', loginAlt: 'AVIA технигінің кіру беті',
                    cabinetChapter: '1.2 · Жұмысты бастау', cabinetTitle: 'Кабинет', cabinetLead: 'Кабинет — техниктің барлық тапсырмаларының бастапқы нүктесі.',
                    cabinetWhat: 'Кабинетте сізге қолжетімді бөлімдер жиналған. Мәзір рөл мен рұқсаттарға байланысты өзгеруі мүмкін.',
                    cabinetStep1: 'Нарядты табу және ашу үшін Workorders бөлімін таңдаңыз.', cabinetStep2: 'Тиісті тапсырмалар берілгенде Training және Materials бөлімдерін пайдаланыңыз.', cabinetStep3: 'Профиль мәзірі арқылы баптауларды өзгертіңіз немесе жүйеден шығыңыз.',
                    cabinetCaption: 'Техниктің негізгі кабинеті.', cabinetAlt: 'AVIA технигінің кабинеті',
                    workorderStartChapter: '2.1 · Workorder', workorderStartTitle: 'Workorder жұмысын бастау', workorderStartLead: 'Менеджер нарядты техникке тағайындайды. Тағайындалған нарядтар Workorders кестесінде көрінеді.',
                    workorderStartWhat: 'Әр нарядты менеджер тағайындайды. Technician бағанында нарядқа жауапты қызметкер көрсетіледі.', workorderStartStep1: 'Менеджер сізге наряд тағайындайды.', workorderStartStep2: 'Technician бағанынан өз атыңызды табыңыз. Бұл нарядтың сізге тағайындалғанын растайды.', workorderStartStep3: 'Есептік жазбаңызға тағайындалған жолдарды ғана көрсету үшін My workorders таңдаңыз.', workorderStartStep4: 'Рөліңізге қолжетімді толық тізімге оралу үшін My workorders жалаушасын алып тастаңыз.', workorderStartCaption: 'Нақты Workorders кестесі. Сары түспен My workorders және Technician бағаны белгіленген.', workorderStartAlt: 'Technician бағаны бар AVIA нарядтар кестесі',
                    workorderOpenChapter: '2.2 · Workorder', workorderOpenTitle: 'Workorder ашу', workorderOpenLead: 'Оның мазмұнын ашу үшін көк Workorder нөмірін басыңыз.', workorderOpenCaption: 'Көк Workorder нөмірі таңдалған нарядты ашады.', workorderOpenAlt: 'AVIA кестесіндегі көк Workorder нөмірі',
                    workorderOpenWhat: 'Әр көк Workorder нөмірі осы нарядтың мазмұнына сілтеме болып табылады.', workorderOpenStep1: 'Кестеден қажетті нарядты табыңыз.', workorderOpenStep2: 'Оның көк нөмірін басыңыз.', workorderOpenStep3: 'Таңдалған наряд жеке бетте ашылады, онда жұмысыңызды қарап, орындай аласыз.',
                    workorderChapter: '2.3 · Workorder', workorderTitle: 'Workorders беті', workorderLead: 'Нарядты тауып, күйін тексеріп, жұмыс үшін ашыңыз.',
                    workorderWhat: 'Бұл бетте сізге қолжетімді нарядтар көрсетіледі. Әр жолда нөмір, компонент деректері және ағымдағы күй бар.',
                    workorderStep1: 'Нарядты нөмірі немесе компонент деректері бойынша табу үшін іздеуді пайдаланыңыз.', workorderStep2: 'Ашпас бұрын жолдағы деректер мен күйді тексеріңіз.', workorderStep3: 'Нарядты ашып, жұмысты жалғастыру үшін қажетті жолды таңдаңыз.',
                    workorderCaption: 'Техникке қолжетімді нарядтар.', workorderAlt: 'AVIA нарядтарының тізімі',
                    filtersChapter: '2.4 · Workorder', filtersTitle: 'Сүзгілер', filtersLead: 'Қазір қажет нарядтарды ғана көрсету үшін кестенің үстіндегі сүзгілерді пайдаланыңыз.', filtersCaption: 'Workorders сүзгілерінің нақты жолы.', filtersAlt: 'AVIA кестесінің үстіндегі Workorder сүзгілері',
                    filtersWhat: 'Сүзгілер тек кестеде көрсетілген жолдарды өзгертеді. Олар нарядты немесе оның күйін өзгертпейді.',
                    filtersStep1: 'WO active аяқталған нарядтарды жасырады. Technician және Team Leader үшін финалдық инспекцияға жіберілген нарядтар да жасырылады.', filtersStep2: 'My workorders тек сіздің есептік жазбаңызға тағайындалған нарядтарды қалдырады.', filtersStep3: 'Approved тек мақұлдау күні бар нарядтарды қалдырады.', filtersStep4: 'Үш жалаушаның кез келгенін бірге пайдалануға болады. Кестеде барлық таңдалған сүзгілерге сай жолдар ғана қалады.', filtersStep5: 'Сүзгіні алып тастау үшін жалаушаны өшіріңіз. Таңдалған сүзгілер есептік жазбаңыз үшін сақталады.'
                },
                be: {
                    pageTitle: 'Дапаможнік карыстальніка', back: 'Вярнуцца ў сістэму', menu: 'Змест', previous: 'Назад', next: 'Далей', contents: 'Змест',
                    startGroup: '1. Пачатак працы', loginNav: '1.1 Уваход', cabinetNav: '1.2 Кабінет', workorderGroup: '2. Workorder', workorderNav: '2.1 Старонка Workorders', filtersNav: '2.2 Фільтры', workorderStartNav: '2.3 Пачатак працы з Workorder', workorderOpenNav: '2.4 Адкрыць Workorder',
                    whatTitle: 'Што гэта за старонка', howTitle: 'Як ёю карыстацца',
                    loginChapter: '1.1 · Пачатак працы', loginTitle: 'Уваход', loginLead: 'Выкарыстоўвайце карпаратыўны ўліковы запіс для ўваходу ў кабінет тэхніка.',
                    loginWhat: 'Гэта абароненая старонка ўваходу ў кабінет тэхніка. Тут можна выкарыстоўваць толькі ўліковы запіс, выдадзены кампаніяй.',
                    loginStep1: 'Увядзіце карпаратыўны адрас электроннай пошты.', loginStep2: 'Увядзіце пароль.', loginStep3: 'Націсніце «Увайсці», каб адкрыць кабінет.',
                    loginCaption: 'Старонка ўваходу тэхніка.', loginAlt: 'Старонка ўваходу тэхніка AVIA',
                    cabinetChapter: '1.2 · Пачатак працы', cabinetTitle: 'Кабінет', cabinetLead: 'Кабінет — пачатковая кропка для ўсіх задач тэхніка.',
                    cabinetWhat: 'У кабінеце сабраны даступныя вам раздзелы. Меню можа адрознівацца ў залежнасці ад ролі і правоў.',
                    cabinetStep1: 'Выберыце Workorders, каб знайсці і адкрыць нарад.', cabinetStep2: 'Выкарыстоўвайце Training і Materials, калі вам прызначаны такія задачы.', cabinetStep3: 'Праз меню профілю змяняйце налады або выходзьце з сістэмы.',
                    cabinetCaption: 'Галоўны кабінет тэхніка.', cabinetAlt: 'Кабінет тэхніка AVIA',
                    workorderStartChapter: '2.1 · Workorder', workorderStartTitle: 'Пачатак працы з Workorder', workorderStartLead: 'Менеджар прызначае нарад тэхніку. Прызначаныя нарады з’яўляюцца ў табліцы Workorders.',
                    workorderStartWhat: 'Кожны нарад прызначае менеджар. У калонцы Technician паказаны супрацоўнік, адказны за нарад.', workorderStartStep1: 'Менеджар прызначае вам нарад.', workorderStartStep2: 'Знайдзіце сваё імя ў калонцы Technician. Гэта пацвярджае, што нарад прызначаны вам.', workorderStartStep3: 'Абярыце My workorders, каб паказаць толькі радкі, прызначаныя вашаму ўліковаму запісу.', workorderStartStep4: 'Зніміце My workorders, каб вярнуцца да поўнага спіса, даступнага вашай ролі.', workorderStartCaption: 'Сапраўдная табліца Workorders. Жоўтым пазначаны My workorders і калонка Technician.', workorderStartAlt: 'Табліца нарадаў AVIA з калонкай Technician',
                    workorderOpenChapter: '2.2 · Workorder', workorderOpenTitle: 'Адкрыць Workorder', workorderOpenLead: 'Націсніце сіні нумар Workorder, каб адкрыць яго змест.', workorderOpenCaption: 'Сіні нумар Workorder адкрывае выбраны нарад.', workorderOpenAlt: 'Сіні нумар Workorder у табліцы AVIA',
                    workorderOpenWhat: 'Кожны сіні нумар Workorder — гэта спасылка на змест гэтага нарада.', workorderOpenStep1: 'Знайдзіце патрэбны нарад у табліцы.', workorderOpenStep2: 'Націсніце яго сіні нумар.', workorderOpenStep3: 'Выбраны нарад адкрыецца на асобнай старонцы, дзе можна прагледзець і выканаць сваю працу.',
                    workorderChapter: '2.3 · Workorder', workorderTitle: 'Старонка Workorders', workorderLead: 'Знайдзіце нарад, праверце яго статус і адкрыйце для працы.',
                    workorderWhat: 'На старонцы паказаны даступныя вам нарады. У кожным радку ёсць нумар, даныя кампанента і бягучы статус.',
                    workorderStep1: 'Выкарыстоўвайце пошук, каб знайсці нарад па нумары або даных кампанента.', workorderStep2: 'Перад адкрыццём праверце даныя і статус у радку.', workorderStep3: 'Выберыце патрэбны радок, каб адкрыць нарад і працягнуць працу.',
                    workorderCaption: 'Нарады, даступныя тэхніку.', workorderAlt: 'Спіс нарадаў AVIA',
                    filtersChapter: '2.4 · Workorder', filtersTitle: 'Фільтры', filtersLead: 'Выкарыстоўвайце фільтры над табліцай, каб паказаць толькі патрэбныя цяпер нарады.', filtersCaption: 'Сапраўдні радок фільтраў Workorders.', filtersAlt: 'Фільтры Workorder над табліцай AVIA',
                    filtersWhat: 'Фільтры змяняюць толькі радкі, паказаныя ў табліцы. Яны не змяняюць нарад і яго статус.',
                    filtersStep1: 'WO active хавае завершаныя нарады. Для Technician і Team Leader ён таксама хавае нарады, адпраўленыя на фінальную інспекцыю.', filtersStep2: 'My workorders пакідае толькі нарады, прызначаныя вашаму ўліковаму запісу.', filtersStep3: 'Approved пакідае толькі нарады з датай ухвалення.', filtersStep4: 'Можна спалучаць любыя тры сцяжкі. У табліцы застануцца толькі радкі, якія адпавядаюць усім выбраным фільтрам.', filtersStep5: 'Зніміце сцяжок, каб прыбраць фільтр. Выбраныя фільтры захоўваюцца для вашага ўліковага запісу.'
                }
            };

            const scope = 'user-guide-book';
            const chapterNumbers = {
                workorderChapter: '2.1',
                workorderStartChapter: '2.2',
                filtersChapter: '2.3',
                workorderOpenChapter: '2.4',
                workorderMainChapter: '2.5',
                workorderMainDetailsChapter: '2.6',
            };
            const navigationNumbers = {
                workorderNav: '2.1',
                workorderStartNav: '2.2',
                filtersNav: '2.3',
                workorderOpenNav: '2.4',
                workorderMainNav: '2.5',
                workorderMainDetailsNav: '2.6',
            };
            const mainTranslations = {
                en: { workorderMainNav: '2.5 Main', workorderMainChapter: '2.5 · Workorder', workorderMainTitle: 'Main', workorderMainLead: 'Main is the work area for the selected workorder.', workorderMainWhat: 'Main contains the workorder header, its current status and the work sections available for the selected workorder.', workorderMainStep1: 'Check the workorder number, component, serial number and assigned technician in the header.', workorderMainStep2: 'Use the tabs to move between tasks, standard processes, parts and bushing processes.', workorderMainStep3: 'Fill in only the sections assigned to your role and task.', workorderMainCaption: 'The real Main page of workorder 100000.', workorderMainAlt: 'Main page of an AVIA workorder' },
                ru: { workorderMainNav: '2.5 Main', workorderMainChapter: '2.5 · Workorder', workorderMainTitle: 'Main', workorderMainLead: 'Main — рабочая страница выбранного заказ-наряда.', workorderMainWhat: 'В Main находятся шапка заказ-наряда, его текущий статус и рабочие разделы выбранного заказ-наряда.', workorderMainStep1: 'Проверьте в шапке номер заказ-наряда, компонент, серийный номер и назначенного техника.', workorderMainStep2: 'Используйте вкладки для перехода между задачами, стандартными процессами, деталями и процессами bushings.', workorderMainStep3: 'Заполняйте только разделы, назначенные вашей роли и задаче.', workorderMainCaption: 'Реальная страница Main заказ-наряда 100000.', workorderMainAlt: 'Страница Main заказ-наряда AVIA' },
                uk: { workorderMainNav: '2.5 Main', workorderMainChapter: '2.5 · Workorder', workorderMainTitle: 'Main', workorderMainLead: 'Main — робоча сторінка вибраного наряду.', workorderMainWhat: 'У Main містяться шапка наряду, його поточний статус і робочі розділи вибраного наряду.', workorderMainStep1: 'Перевірте у шапці номер наряду, компонент, серійний номер і призначеного техніка.', workorderMainStep2: 'Використовуйте вкладки для переходу між завданнями, стандартними процесами, деталями та процесами bushings.', workorderMainStep3: 'Заповнюйте лише розділи, призначені вашій ролі та завданню.', workorderMainCaption: 'Реальна сторінка Main наряду 100000.', workorderMainAlt: 'Сторінка Main наряду AVIA' },
                he: { workorderMainNav: '2.5 Main', workorderMainChapter: '2.5 · הוראת עבודה', workorderMainTitle: 'Main', workorderMainLead: 'Main הוא אזור העבודה של הוראת העבודה שנבחרה.', workorderMainWhat: 'ב-Main נמצאים כותרת הוראת העבודה, מצבה הנוכחי וחלקי העבודה הזמינים.', workorderMainStep1: 'בדקו בכותרת את מספר ההוראה, הרכיב, המספר הסידורי והטכנאי שהוקצה.', workorderMainStep2: 'השתמשו בלשוניות למעבר בין משימות, תהליכים תקניים, חלקים ותהליכי bushings.', workorderMainStep3: 'מלאו רק את החלקים שהוקצו לתפקידכם ולמשימה.', workorderMainCaption: 'דף Main אמיתי של הוראת עבודה 100000.', workorderMainAlt: 'דף Main של הוראת עבודה ב-AVIA' },
                de: { workorderMainNav: '2.5 Main', workorderMainChapter: '2.5 · Arbeitsauftrag', workorderMainTitle: 'Main', workorderMainLead: 'Main ist der Arbeitsbereich des ausgewählten Arbeitsauftrags.', workorderMainWhat: 'Main enthält die Kopfzeile, den aktuellen Status und die verfügbaren Arbeitsbereiche des Auftrags.', workorderMainStep1: 'Prüfen Sie in der Kopfzeile Nummer, Komponente, Seriennummer und zugewiesenen Techniker.', workorderMainStep2: 'Nutzen Sie die Registerkarten für Aufgaben, Standardprozesse, Teile und Bushing-Prozesse.', workorderMainStep3: 'Füllen Sie nur die Bereiche aus, die Ihrer Rolle und Aufgabe zugeordnet sind.', workorderMainCaption: 'Die echte Main-Seite des Arbeitsauftrags 100000.', workorderMainAlt: 'Main-Seite eines AVIA-Arbeitsauftrags' },
                kk: { workorderMainNav: '2.5 Main', workorderMainChapter: '2.5 · Workorder', workorderMainTitle: 'Main', workorderMainLead: 'Main — таңдалған нарядтың жұмыс беті.', workorderMainWhat: 'Main бетінде нарядтың тақырыбы, ағымдағы күйі және қолжетімді жұмыс бөлімдері бар.', workorderMainStep1: 'Тақырыптан наряд нөмірін, компонентті, сериялық нөмірді және тағайындалған техникті тексеріңіз.', workorderMainStep2: 'Тапсырмалар, стандартты процестер, бөлшектер және bushing процестері арасында ауысу үшін қойындыларды пайдаланыңыз.', workorderMainStep3: 'Тек рөліңіз бен тапсырмаңызға тағайындалған бөлімдерді толтырыңыз.', workorderMainCaption: '100000 нарядының нақты Main беті.', workorderMainAlt: 'AVIA нарядының Main беті' },
                be: { workorderMainNav: '2.5 Main', workorderMainChapter: '2.5 · Workorder', workorderMainTitle: 'Main', workorderMainLead: 'Main — рабочая старонка выбранага нарада.', workorderMainWhat: 'У Main знаходзяцца шапка нарада, яго бягучы статус і даступныя рабочыя раздзелы.', workorderMainStep1: 'Праверце ў шапцы нумар нарада, кампанент, серыйны нумар і прызначанага тэхніка.', workorderMainStep2: 'Выкарыстоўвайце ўкладкі для пераходу паміж задачамі, стандартнымі працэсамі, дэталямі і працэсамі bushings.', workorderMainStep3: 'Запаўняйце толькі раздзелы, прызначаныя вашай ролі і задачы.', workorderMainCaption: 'Сапраўдная старонка Main нарада 100000.', workorderMainAlt: 'Старонка Main нарада AVIA' },
            };
            const mainDetailsTranslations = {
                en: { workorderMainDetailsNav: '2.6 Main: header and work area', workorderMainDetailsChapter: '2.6 · Workorder', workorderMainDetailsTitle: 'Main: header and work area', workorderMainDetailsLead: 'Use the enlarged fragments to identify the controls before you start work.', workorderMainHeaderAlt: 'Main workorder header with technician controls', workorderMainHeaderCaption: 'Main header shown at a larger scale.', workorderMainHeaderWhat: 'The header confirms that you are working in the correct workorder and gives access to its supporting information.', workorderMainHeaderStep1: 'Check the workorder number, approval status, component and serial number.', workorderMainHeaderStep2: 'Use the green hammer to open the TDR Report.', workorderMainHeaderStep3: 'Use the blue image icon for workorder photos and the yellow icon for PDF Library.', workorderMainWorkareaAlt: 'Main work area and tabs for a technician', workorderMainWorkareaCaption: 'Work area shown at a larger scale.', workorderMainWorkareaWhat: 'The tabs organize your tasks, notes, standard processes, parts and bushing processes.', workorderMainWorkareaStep1: 'Use All to review the whole workorder and Tasks / Notes for your assigned work and notes.', workorderMainWorkareaStep2: 'Open STD Processes, Parts / Processes or Bushing / Processes only when they are required for your task.', workorderMainWorkareaStep3: 'Enter dates and notes only after the work has actually been performed.' },
                ru: { workorderMainDetailsNav: '2.6 Main: шапка и рабочая область', workorderMainDetailsChapter: '2.6 · Workorder', workorderMainDetailsTitle: 'Main: шапка и рабочая область', workorderMainDetailsLead: 'Увеличенные фрагменты помогут определить элементы до начала работы.', workorderMainHeaderAlt: 'Шапка Main с элементами техника', workorderMainHeaderCaption: 'Шапка Main в увеличенном масштабе.', workorderMainHeaderWhat: 'Шапка подтверждает, что открыт нужный заказ-наряд, и даёт доступ к вспомогательной информации.', workorderMainHeaderStep1: 'Проверьте номер заказ-наряда, статус одобрения, компонент и серийный номер.', workorderMainHeaderStep2: 'Зелёный молоток открывает TDR Report.', workorderMainHeaderStep3: 'Синяя иконка изображения открывает фотографии, жёлтая — PDF Library.', workorderMainWorkareaAlt: 'Рабочая область Main и вкладки техника', workorderMainWorkareaCaption: 'Рабочая область в увеличенном масштабе.', workorderMainWorkareaWhat: 'Вкладки организуют задачи, заметки, стандартные процессы, детали и процессы bushings.', workorderMainWorkareaStep1: 'All показывает весь заказ-наряд; Tasks / Notes — назначенную работу и заметки.', workorderMainWorkareaStep2: 'STD Processes, Parts / Processes и Bushing / Processes открывайте только когда это требуется вашей задачей.', workorderMainWorkareaStep3: 'Вносите даты и заметки только после фактического выполнения работы.' },
                uk: { workorderMainDetailsNav: '2.6 Main: шапка та робоча область', workorderMainDetailsChapter: '2.6 · Workorder', workorderMainDetailsTitle: 'Main: шапка та робоча область', workorderMainDetailsLead: 'Збільшені фрагменти допоможуть розпізнати елементи перед початком роботи.', workorderMainHeaderAlt: 'Шапка Main з елементами техніка', workorderMainHeaderCaption: 'Шапка Main у збільшеному масштабі.', workorderMainHeaderWhat: 'Шапка підтверджує, що відкрито потрібний наряд, і дає доступ до допоміжної інформації.', workorderMainHeaderStep1: 'Перевірте номер наряду, статус схвалення, компонент і серійний номер.', workorderMainHeaderStep2: 'Зелений молоток відкриває TDR Report.', workorderMainHeaderStep3: 'Синя іконка зображення відкриває фото, жовта — PDF Library.', workorderMainWorkareaAlt: 'Робоча область Main і вкладки техніка', workorderMainWorkareaCaption: 'Робоча область у збільшеному масштабі.', workorderMainWorkareaWhat: 'Вкладки організовують завдання, нотатки, стандартні процеси, деталі та процеси bushings.', workorderMainWorkareaStep1: 'All показує весь наряд; Tasks / Notes — призначену роботу й нотатки.', workorderMainWorkareaStep2: 'STD Processes, Parts / Processes і Bushing / Processes відкривайте лише коли це потрібно для завдання.', workorderMainWorkareaStep3: 'Вносіть дати й нотатки лише після фактичного виконання роботи.' },
                he: { workorderMainDetailsNav: '2.6 Main: כותרת ואזור עבודה', workorderMainDetailsChapter: '2.6 · הוראת עבודה', workorderMainDetailsTitle: 'Main: כותרת ואזור עבודה', workorderMainDetailsLead: 'הקטעים המוגדלים עוזרים לזהות את הפקדים לפני תחילת העבודה.', workorderMainHeaderAlt: 'כותרת Main עם פקדי טכנאי', workorderMainHeaderCaption: 'כותרת Main בהגדלה.', workorderMainHeaderWhat: 'הכותרת מאשרת שפתחתם את ההוראה הנכונה ונותנת גישה למידע תומך.', workorderMainHeaderStep1: 'בדקו מספר הוראה, סטטוס אישור, רכיב ומספר סידורי.', workorderMainHeaderStep2: 'הפטיש הירוק פותח את TDR Report.', workorderMainHeaderStep3: 'סמל התמונה הכחול פותח תמונות וסמל הצהוב פותח PDF Library.', workorderMainWorkareaAlt: 'אזור העבודה והלשוניות של Main לטכנאי', workorderMainWorkareaCaption: 'אזור העבודה בהגדלה.', workorderMainWorkareaWhat: 'הלשוניות מארגנות משימות, הערות, תהליכים תקניים, חלקים ותהליכי bushings.', workorderMainWorkareaStep1: 'All מציג את כל ההוראה; Tasks / Notes מציג עבודה והערות שהוקצו לכם.', workorderMainWorkareaStep2: 'פתחו STD Processes, Parts / Processes או Bushing / Processes רק כשנדרש למשימה.', workorderMainWorkareaStep3: 'הזינו תאריכים והערות רק לאחר שהעבודה בוצעה בפועל.' },
                de: { workorderMainDetailsNav: '2.6 Main: Kopfbereich und Arbeitsbereich', workorderMainDetailsChapter: '2.6 · Arbeitsauftrag', workorderMainDetailsTitle: 'Main: Kopfbereich und Arbeitsbereich', workorderMainDetailsLead: 'Die vergrößerten Ausschnitte helfen, die Bedienelemente vor der Arbeit zu erkennen.', workorderMainHeaderAlt: 'Main-Kopfbereich mit Techniker-Steuerung', workorderMainHeaderCaption: 'Main-Kopfbereich vergrößert dargestellt.', workorderMainHeaderWhat: 'Der Kopfbereich bestätigt den richtigen Arbeitsauftrag und bietet Zugang zu unterstützenden Informationen.', workorderMainHeaderStep1: 'Prüfen Sie Nummer, Genehmigungsstatus, Komponente und Seriennummer.', workorderMainHeaderStep2: 'Der grüne Hammer öffnet den TDR Report.', workorderMainHeaderStep3: 'Das blaue Bildsymbol öffnet Fotos, das gelbe Symbol die PDF Library.', workorderMainWorkareaAlt: 'Main-Arbeitsbereich und Registerkarten für Techniker', workorderMainWorkareaCaption: 'Arbeitsbereich vergrößert dargestellt.', workorderMainWorkareaWhat: 'Die Registerkarten organisieren Aufgaben, Notizen, Standardprozesse, Teile und Bushing-Prozesse.', workorderMainWorkareaStep1: 'All zeigt den gesamten Auftrag; Tasks / Notes zeigt Ihre zugewiesene Arbeit und Notizen.', workorderMainWorkareaStep2: 'Öffnen Sie STD Processes, Parts / Processes oder Bushing / Processes nur wenn es für Ihre Aufgabe erforderlich ist.', workorderMainWorkareaStep3: 'Tragen Sie Daten und Notizen erst nach der tatsächlichen Ausführung der Arbeit ein.' },
                kk: { workorderMainDetailsNav: '2.6 Main: тақырып және жұмыс аймағы', workorderMainDetailsChapter: '2.6 · Workorder', workorderMainDetailsTitle: 'Main: тақырып және жұмыс аймағы', workorderMainDetailsLead: 'Үлкейтілген бөліктер жұмысты бастамай тұрып элементтерді тануға көмектеседі.', workorderMainHeaderAlt: 'Техник басқару элементтері бар Main тақырыбы', workorderMainHeaderCaption: 'Main тақырыбы үлкейтілген түрде.', workorderMainHeaderWhat: 'Тақырып дұрыс наряд ашылғанын растайды және қосымша ақпаратқа қол жеткізеді.', workorderMainHeaderStep1: 'Наряд нөмірін, мақұлдау күйін, компонентті және сериялық нөмірді тексеріңіз.', workorderMainHeaderStep2: 'Жасыл балға TDR Report ашады.', workorderMainHeaderStep3: 'Көк сурет белгішесі фотоларды, сары белгіше PDF Library ашады.', workorderMainWorkareaAlt: 'Техникке арналған Main жұмыс аймағы және қойындылар', workorderMainWorkareaCaption: 'Жұмыс аймағы үлкейтілген түрде.', workorderMainWorkareaWhat: 'Қойындылар тапсырмаларды, жазбаларды, стандартты процестерді, бөлшектерді және bushing процестерін ұйымдастырады.', workorderMainWorkareaStep1: 'All бүкіл нарядты, Tasks / Notes тағайындалған жұмыс пен жазбаларды көрсетеді.', workorderMainWorkareaStep2: 'STD Processes, Parts / Processes немесе Bushing / Processes бөлімдерін тек тапсырмаңыз қажет еткенде ашыңыз.', workorderMainWorkareaStep3: 'Күндер мен жазбаларды жұмыс нақты орындалғаннан кейін ғана енгізіңіз.' },
                be: { workorderMainDetailsNav: '2.6 Main: шапка і рабочая вобласць', workorderMainDetailsChapter: '2.6 · Workorder', workorderMainDetailsTitle: 'Main: шапка і рабочая вобласць', workorderMainDetailsLead: 'Павялічаныя фрагменты дапамагаюць распазнаць элементы перад пачаткам працы.', workorderMainHeaderAlt: 'Шапка Main з элементамі тэхніка', workorderMainHeaderCaption: 'Шапка Main у павялічаным маштабе.', workorderMainHeaderWhat: 'Шапка пацвярджае, што адкрыты патрэбны нарад, і дае доступ да дапаможнай інфармацыі.', workorderMainHeaderStep1: 'Праверце нумар нарада, статус ухвалення, кампанент і серыйны нумар.', workorderMainHeaderStep2: 'Зялёны малаток адкрывае TDR Report.', workorderMainHeaderStep3: 'Сіні значок выявы адкрывае фота, жоўты — PDF Library.', workorderMainWorkareaAlt: 'Рабочая вобласць Main і ўкладкі тэхніка', workorderMainWorkareaCaption: 'Рабочая вобласць у павялічаным маштабе.', workorderMainWorkareaWhat: 'Укладкі арганізуюць задачы, нататкі, стандартныя працэсы, дэталі і працэсы bushings.', workorderMainWorkareaStep1: 'All паказвае ўвесь нарад; Tasks / Notes — прызначаную працу і нататкі.', workorderMainWorkareaStep2: 'STD Processes, Parts / Processes або Bushing / Processes адкрывайце толькі калі гэта патрабуе задача.', workorderMainWorkareaStep3: 'Уносіце даты і нататкі толькі пасля фактычнага выканання працы.' },
            };
            const mainHeaderPhotoPdfTranslations = {
                en: 'The blue image icon opens all photos for the selected workorder. Review the evidence of completed work and add new images only to the required group. The yellow PDF Library icon opens documents attached to this workorder, including instructions and reference files.',
                ru: 'Синяя иконка изображения открывает все фотографии выбранного заказ-наряда: просматривайте доказательства выполненной работы и добавляйте новые снимки только в нужную группу. Жёлтая иконка PDF Library открывает документы, прикреплённые к этому заказ-наряду: используйте их для просмотра инструкций и справочных файлов.',
                uk: 'Синя іконка зображення відкриває всі фотографії вибраного наряду: переглядайте підтвердження виконаної роботи й додавайте нові знімки лише до потрібної групи. Жовта іконка PDF Library відкриває документи, прикріплені до цього наряду, зокрема інструкції та довідкові файли.',
                he: 'סמל התמונה הכחול פותח את כל התמונות של הוראת העבודה שנבחרה. בדקו את תיעוד העבודה שבוצעה והוסיפו תמונות חדשות רק לקבוצה המתאימה. סמל PDF Library הצהוב פותח מסמכים המצורפים להוראה, כולל הוראות וקובצי עזר.',
                de: 'Das blaue Bildsymbol öffnet alle Fotos des ausgewählten Arbeitsauftrags. Prüfen Sie die Nachweise der ausgeführten Arbeit und fügen Sie neue Bilder nur der passenden Gruppe hinzu. Das gelbe PDF-Library-Symbol öffnet die dem Arbeitsauftrag beigefügten Dokumente, einschließlich Anweisungen und Referenzdateien.',
                kk: 'Көк сурет белгішесі таңдалған нарядтың барлық фотосуреттерін ашады. Орындалған жұмыстың дәлелдерін қарап, жаңа суреттерді тек тиісті топқа қосыңыз. Сары PDF Library белгішесі нарядқа тіркелген құжаттарды, соның ішінде нұсқаулықтар мен анықтамалық файлдарды ашады.',
                be: 'Сіні значок выявы адкрывае ўсе фатаграфіі выбранага нарада. Праглядайце пацвярджэнні выкананай працы і дадавайце новыя здымкі толькі ў патрэбную групу. Жоўты значок PDF Library адкрывае дакументы, далучаныя да нарада, у тым ліку інструкцыі і даведачныя файлы.',
            };
            const mainHeaderActionsTranslations = {
                en: { headerActionTdrTitle: 'TDR Report', headerActionTdrText: 'Opens the TDR Report for this workorder.', headerActionPhotosTitle: 'Pictures', headerActionPhotosText: 'Opens all photos of this workorder.', headerActionPdfTitle: 'PDF Library', headerActionPdfText: 'Opens the documents attached to this workorder.', headerActionTrainingTitle: 'Training', headerActionTrainingText: 'Shows your training status; hover to view its history.', headerActionAddTrainingTitle: 'Add training', headerActionAddTrainingText: 'Opens the form to add or update your training record for this manual.' },
                ru: { headerActionTdrTitle: 'TDR Report', headerActionTdrText: 'Открывает TDR Report этого заказ-наряда.', headerActionPhotosTitle: 'Фотографии', headerActionPhotosText: 'Открывает все фотографии этого заказ-наряда.', headerActionPdfTitle: 'PDF Library', headerActionPdfText: 'Открывает документы, прикреплённые к заказ-наряду.', headerActionTrainingTitle: 'Training', headerActionTrainingText: 'Показывает статус вашего обучения; наведите курсор, чтобы увидеть историю.', headerActionAddTrainingTitle: 'Добавить Training', headerActionAddTrainingText: 'Открывает форму добавления или обновления записи об обучении для этого manual.' },
                uk: { headerActionTdrTitle: 'TDR Report', headerActionTdrText: 'Відкриває TDR Report цього наряду.', headerActionPhotosTitle: 'Фотографії', headerActionPhotosText: 'Відкриває всі фотографії цього наряду.', headerActionPdfTitle: 'PDF Library', headerActionPdfText: 'Відкриває документи, прикріплені до наряду.', headerActionTrainingTitle: 'Training', headerActionTrainingText: 'Показує статус вашого навчання; наведіть курсор, щоб переглянути історію.', headerActionAddTrainingTitle: 'Додати Training', headerActionAddTrainingText: 'Відкриває форму додавання або оновлення запису про навчання для цього manual.' },
                he: { headerActionTdrTitle: 'TDR Report', headerActionTdrText: 'פותח את TDR Report של הוראת עבודה זו.', headerActionPhotosTitle: 'תמונות', headerActionPhotosText: 'פותח את כל התמונות של הוראת עבודה זו.', headerActionPdfTitle: 'PDF Library', headerActionPdfText: 'פותח מסמכים המצורפים להוראת עבודה זו.', headerActionTrainingTitle: 'Training', headerActionTrainingText: 'מציג את סטטוס ההדרכה שלכם; העבירו את הסמן כדי לראות את ההיסטוריה.', headerActionAddTrainingTitle: 'הוספת Training', headerActionAddTrainingText: 'פותח טופס להוספה או עדכון של רישום ההדרכה עבור manual זה.' },
                de: { headerActionTdrTitle: 'TDR Report', headerActionTdrText: 'Öffnet den TDR Report dieses Arbeitsauftrags.', headerActionPhotosTitle: 'Fotos', headerActionPhotosText: 'Öffnet alle Fotos dieses Arbeitsauftrags.', headerActionPdfTitle: 'PDF Library', headerActionPdfText: 'Öffnet die diesem Arbeitsauftrag beigefügten Dokumente.', headerActionTrainingTitle: 'Training', headerActionTrainingText: 'Zeigt Ihren Schulungsstatus; bewegen Sie den Mauszeiger darüber, um die Historie zu sehen.', headerActionAddTrainingTitle: 'Training hinzufügen', headerActionAddTrainingText: 'Öffnet das Formular zum Hinzufügen oder Aktualisieren Ihres Schulungsnachweises für dieses Manual.' },
                kk: { headerActionTdrTitle: 'TDR Report', headerActionTdrText: 'Осы нарядтың TDR Report бетін ашады.', headerActionPhotosTitle: 'Фотосуреттер', headerActionPhotosText: 'Осы нарядтың барлық фотосуреттерін ашады.', headerActionPdfTitle: 'PDF Library', headerActionPdfText: 'Осы нарядқа тіркелген құжаттарды ашады.', headerActionTrainingTitle: 'Training', headerActionTrainingText: 'Оқу күйіңізді көрсетеді; тарихын көру үшін меңзерді үстіне апарыңыз.', headerActionAddTrainingTitle: 'Training қосу', headerActionAddTrainingText: 'Осы manual бойынша оқу жазбасын қосу немесе жаңарту пішінін ашады.' },
                be: { headerActionTdrTitle: 'TDR Report', headerActionTdrText: 'Адкрывае TDR Report гэтага нарада.', headerActionPhotosTitle: 'Фатаграфіі', headerActionPhotosText: 'Адкрывае ўсе фатаграфіі гэтага нарада.', headerActionPdfTitle: 'PDF Library', headerActionPdfText: 'Адкрывае дакументы, далучаныя да гэтага нарада.', headerActionTrainingTitle: 'Training', headerActionTrainingText: 'Паказвае статус вашага навучання; навядзіце курсор, каб убачыць гісторыю.', headerActionAddTrainingTitle: 'Дадаць Training', headerActionAddTrainingText: 'Адкрывае форму дадання або абнаўлення запісу аб навучанні для гэтага manual.' },
            };
            const languageButtons = [...document.querySelectorAll('[data-language]')];
            const pages = [...document.querySelectorAll('[data-guide-page]')]
                .sort((left, right) => Number(left.dataset.guideOrder) - Number(right.dataset.guideOrder));
            const tocLinks = [...document.querySelectorAll('.toc-link')];
            const sidebar = document.getElementById('guide-sidebar');
            const tocToggle = document.getElementById('toc-toggle');
            const previousButton = document.getElementById('previous-page');
            const nextButton = document.getElementById('next-page');
            let activePageIndex = 0;

            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
            }

            function applyLanguage(language, persist = true) {
                const selected = translations[language] ? language : 'en';
                const dictionary = { ...translations[selected], ...mainTranslations[selected], ...mainDetailsTranslations[selected], ...mainHeaderActionsTranslations[selected], workorderMainHeaderStep3: mainHeaderPhotoPdfTranslations[selected] };

                document.documentElement.lang = selected;
                // Hebrew changes the words only. The guide navigation and page layout stay LTR.
                document.documentElement.dir = 'ltr';
                document.title = dictionary.pageTitle;

                document.querySelectorAll('[data-i18n]').forEach((element) => {
                    let value = dictionary[element.dataset.i18n];
                    const chapterNumber = chapterNumbers[element.dataset.i18n];
                    if (value && chapterNumber) value = value.replace(/^2\.\d/, chapterNumber);
                    if (value) element.textContent = value;
                });

                document.querySelectorAll('[data-i18n]').forEach((element) => {
                    let value = dictionary[element.dataset.i18n];
                    const navigationNumber = navigationNumbers[element.dataset.i18n];
                    if (value && navigationNumber) value = value.replace(/^2\.\d/, navigationNumber);
                    if (value) element.textContent = value;
                });

                document.querySelectorAll('[data-i18n-alt]').forEach((element) => {
                    const value = dictionary[element.dataset.i18nAlt];
                    if (value) element.alt = value;
                });

                languageButtons.forEach((button) => {
                    const isActive = button.dataset.language === selected;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', String(isActive));
                });

                if (persist && window.UserUiSettings) {
                    window.UserUiSettings.set(scope, 'language', selected).catch(() => {});
                }
            }

            function setActivePage(index) {
                activePageIndex = Math.max(0, Math.min(index, pages.length - 1));
                const id = pages[activePageIndex].id;

                tocLinks.forEach((link) => {
                    const isActive = link.getAttribute('href') === `#${id}`;
                    link.classList.toggle('is-active', isActive);
                    if (isActive) link.setAttribute('aria-current', 'page');
                    else link.removeAttribute('aria-current');
                });

                previousButton.disabled = activePageIndex === 0;
                nextButton.disabled = activePageIndex === pages.length - 1;
            }

            function openPage(index) {
                const targetIndex = Math.max(0, Math.min(index, pages.length - 1));
                const target = pages[targetIndex];
                const url = new URL(window.location.href);
                url.hash = target.id;
                history.replaceState(null, '', url);
                target.scrollIntoView({ behavior: 'auto', block: 'start' });
                setActivePage(targetIndex);
                sidebar.classList.remove('is-open');
                tocToggle.setAttribute('aria-expanded', 'false');
            }

            languageButtons.forEach((button) => {
                button.addEventListener('click', () => applyLanguage(button.dataset.language));
            });

            tocLinks.forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    const targetId = link.getAttribute('href')?.slice(1);
                    const targetIndex = pages.findIndex((page) => page.id === targetId);
                    if (targetIndex >= 0) openPage(targetIndex);
                });
            });

            previousButton.addEventListener('click', () => openPage(activePageIndex - 1));
            nextButton.addEventListener('click', () => openPage(activePageIndex + 1));

            tocToggle.addEventListener('click', () => {
                const isOpen = sidebar.classList.toggle('is-open');
                tocToggle.setAttribute('aria-expanded', String(isOpen));
            });

            const observer = new IntersectionObserver((entries) => {
                const visible = entries
                    .filter((entry) => entry.isIntersecting)
                    .sort((left, right) => right.intersectionRatio - left.intersectionRatio)[0];
                if (!visible) return;
                setActivePage(pages.indexOf(visible.target));
            }, { rootMargin: '-15% 0px -60% 0px', threshold: [0, .2, .5] });

            pages.forEach((page) => observer.observe(page));

            const initialPageIndex = Math.max(0, pages.findIndex((page) => `#${page.id}` === window.location.hash));
            setActivePage(initialPageIndex);

            function restoreHashPage() {
                const hashPageIndex = pages.findIndex((page) => `#${page.id}` === window.location.hash);
                if (hashPageIndex >= 0) {
                    window.setTimeout(() => openPage(hashPageIndex), 0);
                }
            }

            if (document.readyState === 'complete') {
                restoreHashPage();
            } else {
                window.addEventListener('load', restoreHashPage, { once: true });
            }

            if (window.UserUiSettings) {
                window.UserUiSettings.get(scope, 'language', 'en')
                    .then((language) => applyLanguage(language, false))
                    .catch(() => applyLanguage('en', false));
            } else {
                applyLanguage('en', false);
            }
        })();
    </script>
</body>
</html>
