<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
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
        :root { --guide-bg:#141b24; --guide-panel:#1b2633; --guide-line:rgba(186,205,222,.18); --guide-text:#f5f8fb; --guide-muted:#aebdcb; --guide-accent:#0dcaf0; --guide-current:#ffd54a; }
        :root { scroll-behavior:auto; }
        html { min-height:100%; background:var(--guide-bg); }
        body { min-height:100%; background:var(--guide-bg); }
        body { margin:0; color:var(--guide-text); font-size:15px; }
        body.guide-loading { overflow:hidden; }
        body.guide-loading .guide-app { visibility:hidden; }
        .guide-loading-overlay { position:fixed; z-index:2147483647; inset:0; display:grid; place-items:center; background:var(--guide-bg); opacity:1; visibility:visible; transition:opacity .16s ease, visibility 0s linear .16s; }
        .guide-loading-dots { display:flex; align-items:center; gap:8px; }
        .guide-loading-dots span { width:10px; height:10px; border-radius:50%; background:var(--guide-accent); animation:guideLoadingDot 1s ease-in-out infinite; }
        .guide-loading-dots span:nth-child(2) { animation-delay:.14s; }
        .guide-loading-dots span:nth-child(3) { animation-delay:.28s; }
        body:not(.guide-loading) .guide-loading-overlay { opacity:0; visibility:hidden; pointer-events:none; }
        @keyframes guideLoadingDot { 0%, 80%, 100% { opacity:.25; transform:scale(.65); } 40% { opacity:1; transform:scale(1); } }
        .guide-app { min-height:100dvh; background:var(--guide-bg); }
        .guide-topbar { position:sticky; top:0; z-index:30; min-height:48px; display:grid; grid-template-columns:1fr auto 1fr; gap:12px; align-items:center; padding:5px 18px; background:rgba(20,27,36,.97); border-bottom:1px solid var(--guide-line); }
        .guide-header-left, .guide-header-right { display:flex; align-items:center; gap:10px; min-width:0; }
        .guide-header-right { justify-self:end; }
        .guide-page-title { justify-self:center; display:flex; align-items:center; gap:28px; }
        .guide-topbar h1 { margin:0; font-size:1.16rem; font-weight:600; letter-spacing:-.02em; }
        .guide-previous, .guide-next { padding:3px 8px; color:var(--guide-text); background:transparent !important; border:1px solid rgba(188,207,224,.48); border-radius:4px; box-shadow:none !important; font-size:.84rem; white-space:nowrap; }
        .guide-previous:hover, .guide-next:hover, .guide-previous:focus-visible, .guide-next:focus-visible { color:var(--guide-accent); border-color:var(--guide-accent); outline:0; }
        .guide-previous:disabled, .guide-next:disabled { color:var(--guide-muted); opacity:.45; }
        .guide-back, .guide-toc-toggle { color:var(--guide-text); border-color:rgba(188,207,224,.45); }
        .guide-back:hover, .guide-toc-toggle:hover { color:var(--guide-text); background:rgba(255,255,255,.08); border-color:var(--guide-accent); }
        .guide-toc-toggle { display:none; }
        .language-switcher { justify-self:end; display:flex; gap:4px; }
        .guide-language { padding:.35rem .45rem .25rem; color:var(--guide-text); background:transparent; border:0; border-bottom:2px solid transparent; border-radius:0; font-size:.84rem; font-weight:600; }
        .guide-language:hover, .guide-language:focus-visible { color:var(--guide-accent); outline:0; }
        .guide-language.is-active { color:var(--guide-accent); border-bottom-color:var(--guide-accent); }
        .guide-layout { width:100%; min-height:calc(100dvh - 48px); display:grid; grid-template-columns:minmax(158px,185px) minmax(0,1fr); }
        .guide-toc { z-index:20; align-self:start; position:sticky; top:48px; max-height:calc(100dvh - 48px); overflow-y:auto; padding:16px 10px 20px; background:var(--guide-bg); border-right:1px solid var(--guide-line); }
        .guide-toc-head { display:flex; align-items:center; justify-content:space-between; gap:6px; margin:0 2px 10px; }
        .guide-toc-title { margin:0; color:var(--guide-muted); font-size:.68rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase; }
        .guide-open-all { padding:2px 4px; color:var(--guide-accent); background:transparent; border:0; font-size:.7rem; white-space:nowrap; }
        .guide-open-all:hover { text-decoration:underline; }
        .guide-toc details { border-bottom:1px solid var(--guide-line); }
        .guide-toc summary { display:flex; align-items:center; gap:6px; padding:9px 4px; cursor:pointer; list-style:none; color:var(--guide-text); font-size:.78rem; font-weight:600; line-height:1.25; }
        .guide-toc summary::-webkit-details-marker { display:none; }
        .guide-toc summary .bi-chevron-down { margin-left:auto; color:var(--guide-muted); font-size:.65rem; transition:transform .18s ease; }
        .guide-toc details[open] summary .bi-chevron-down { transform:rotate(180deg); }
        .guide-toc-links { padding:0 0 7px; }
        .guide-toc a { display:block; padding:5px 4px 5px 12px; border-left:2px solid transparent; color:var(--guide-muted); text-decoration:none; font-size:.74rem; line-height:1.28; }
        .guide-toc a:hover, .guide-toc a:focus-visible, .guide-toc a.is-current { color:var(--guide-current); background:transparent; border-left-color:transparent; outline:0; }
        .guide-toc-level-3 { padding-left:24px !important; font-size:.7rem !important; }
        .guide-main { min-width:0; padding:12px clamp(20px,3.6vw,72px) 0; }
        .guide-main > section { max-width:none; scroll-margin-top:58px; }
        .guide-kicker { margin:0 0 9px; color:var(--guide-accent); font-size:.76rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
        .guide-section-title { grid-column:2; grid-row:1; margin:0; font-size:clamp(1.25rem,2vw,1.75rem); font-weight:600; letter-spacing:-.025em; white-space:nowrap; }
        .guide-section-intro { grid-column:3; grid-row:1; width:auto; min-width:0; max-width:none; margin:0; overflow:hidden; color:var(--guide-muted); font-size:.78rem; line-height:1.25; text-overflow:ellipsis; white-space:nowrap; }
        .guide-section-intro::before { content:'—'; margin-right:8px; }
        .guide-section { display:grid; grid-template-columns:auto auto minmax(0,1fr); grid-template-rows:auto minmax(0,1fr); column-gap:10px; align-items:baseline; height:calc(100dvh - 60px); min-height:0; margin:0; padding-bottom:12px; border-bottom:1px solid var(--guide-line); }
        .guide-section-number { grid-column:1; grid-row:1; margin:0; color:var(--guide-accent); font-size:.78rem; font-weight:700; letter-spacing:.08em; }
        .guide-title-with-link { display:inline-flex; align-items:baseline; gap:.55em; white-space:nowrap; }
        .guide-title-with-link a { color:var(--guide-accent); font-size:.48em; font-weight:600; letter-spacing:0; }
        .guide-title-with-link a:hover { color:#7ce6f8; }
        .guide-shot { display:flex; grid-column:1 / -1; grid-row:2; align-self:stretch; flex-direction:column; align-items:center; justify-content:center; min-height:0; margin:12px 0; padding:8px; overflow:hidden; border:1px solid rgba(186,205,222,.3); border-radius:9px; background:var(--guide-panel); box-shadow:0 16px 36px rgba(0,0,0,.22); }
        .guide-shot > img, .guide-animated-shot-stage img { display:block; width:auto; max-width:100%; max-height:calc(100% - 42px); height:auto; border:1px solid rgba(186,205,222,.2); border-radius:5px; object-fit:contain; }
        .guide-shot > img.guide-full-bleed-image { flex:1 1 auto; width:100%; max-width:none; min-height:0; max-height:none; height:auto; border:0; border-radius:0; object-fit:cover; object-position:top center; }
        .guide-animated-shot-stage { position:relative; align-self:center; display:inline-block; flex:0 1 auto; max-width:100%; max-height:calc(100% - 42px); overflow:hidden; border-radius:5px; }
        .guide-animated-shot-stage img { max-height:100%; }
        .guide-animated-shot-stage img { border:0; border-radius:0; }
        .guide-workorder-focus { position:absolute; top:25.7%; left:.25%; width:11.9%; height:4.25%; border:2px solid rgba(255,213,74,.9); border-radius:4px; background:rgba(255,213,74,.12); opacity:0; pointer-events:none; animation:guideWorkorderFocus 4s infinite ease-in-out; }
        .guide-workorder-cursor { position:absolute; top:27%; left:58%; z-index:2; color:#ffd54a; font-size:31px; line-height:1; filter:drop-shadow(0 2px 2px rgba(0,0,0,.9)); pointer-events:none; animation:guideCursorToWorkorder 4s infinite cubic-bezier(.22,.65,.3,1); }
        .guide-workorder-click { position:absolute; top:25.3%; left:1.52%; width:36px; height:36px; border:2px solid #ffd54a; border-radius:50%; opacity:0; pointer-events:none; animation:guideWorkorderClick 4s infinite ease-out; }
        @keyframes guideCursorToWorkorder { 0%, 50% { top:27%; left:58%; opacity:1; } 73%, 82% { top:25.5%; left:2.1%; opacity:1; } 100% { top:27%; left:58%; opacity:1; } }
        @keyframes guideWorkorderFocus { 0%, 68% { opacity:0; } 74%, 86% { opacity:1; } 92%, 100% { opacity:0; } }
        @keyframes guideWorkorderClick { 0%, 70% { transform:scale(.25); opacity:0; } 74% { transform:scale(.25); opacity:1; } 88% { transform:scale(1.45); opacity:0; } 100% { transform:scale(1.45); opacity:0; } }
        .guide-workorders-stage { position:relative; align-self:center; display:inline-block; flex:0 1 auto; max-width:100%; max-height:calc(100% - 42px); overflow:hidden; border-radius:5px; }
        .guide-workorders-stage img { display:block; width:auto; max-width:100%; max-height:100%; height:auto; border:0; border-radius:0; }
        .guide-workorders-cursor { position:absolute; z-index:5; top:49%; left:53%; color:#ffd54a; font-size:31px; line-height:1; filter:drop-shadow(0 2px 2px rgba(0,0,0,.9)); pointer-events:none; transition:top 1s cubic-bezier(.22,.65,.3,1), left 1s cubic-bezier(.22,.65,.3,1); }
        .guide-filter-choice { position:absolute; z-index:3; top:1.25%; width:8.7%; height:4.15%; border:2px solid transparent; border-radius:4px; background:rgba(255,213,74,0); pointer-events:none; transition:border-color .25s ease, background .25s ease, box-shadow .25s ease; }
        .guide-filter-choice::after { content:'✓'; position:absolute; left:8%; top:7%; display:grid; place-items:center; width:19px; height:19px; border-radius:3px; background:#ffd54a; color:#1a222c; font-size:15px; font-weight:800; line-height:1; opacity:0; transform:scale(.4); transition:opacity .2s ease, transform .2s ease; }
        .guide-filter-active { left:76.8%; }
        .guide-filter-mine { left:84.1%; }
        .guide-filter-approved { left:93.55%; width:6.1%; }
        .guide-filtered-above, .guide-filtered-below { position:absolute; z-index:2; left:12.5%; right:.5%; background:rgba(8,16,25,.72); opacity:0; pointer-events:none; }
        .guide-filtered-above { top:8.6%; height:1.5%; }
        .guide-filtered-below { top:14.3%; bottom:4.8%; display:flex; align-items:flex-start; justify-content:center; padding-top:2%; clip-path:inset(0 0 100% 0); transition:opacity .2s ease, clip-path 4.2s cubic-bezier(.2,.75,.25,1); }
        .guide-collapse-label { padding:4px 8px; border:1px solid rgba(255,213,74,.7); border-radius:4px; background:rgba(12,25,37,.92); color:#ffe58d; font-size:clamp(9px,.75vw,13px); font-weight:600; opacity:0; transition:opacity .25s ease 3.7s; }
        .guide-selected-workorder { position:absolute; z-index:4; top:10.1%; left:12.8%; width:86.5%; height:4.05%; border:2px solid #ffd54a; border-radius:4px; background:rgba(255,213,74,.08); opacity:0; pointer-events:none; transition:opacity .28s ease; }
        .guide-workorder-statuses { position:absolute; z-index:5; top:10.7%; right:3%; display:flex; gap:5px; opacity:0; pointer-events:none; transition:opacity .25s ease .3s; }
        .guide-workorder-statuses span { padding:3px 6px; border:1px solid #ffd54a; border-radius:4px; background:rgba(12,25,37,.94); color:#ffe58d; font-size:clamp(8px,.66vw,12px); font-weight:700; letter-spacing:.02em; }
        .guide-filter-status { position:absolute; z-index:6; top:8.1%; left:14.1%; display:flex; align-items:center; gap:6px; max-width:62%; padding:4px 8px; border:1px solid rgba(255,213,74,.75); border-radius:4px; background:rgba(12,25,37,.94); color:#ffe58d; font-size:clamp(9px,.75vw,13px); font-weight:600; opacity:0; transform:translateY(-4px); pointer-events:none; transition:opacity .25s ease, transform .25s ease; }
        .guide-filter-status i { font-size:1.05em; }
        .guide-filter-status span { display:none; }
        .guide-workorders-stage[data-workorder-step="0"] .guide-filter-status-start,
        .guide-workorders-stage[data-workorder-step="1"] .guide-filter-status-active,
        .guide-workorders-stage[data-workorder-step="2"] .guide-filter-status-mine,
        .guide-workorders-stage[data-workorder-step="3"] .guide-filter-status-approved,
        .guide-workorders-stage[data-workorder-step="4"] .guide-filter-status-open { display:inline; }
        .guide-workorders-stage[data-workorder-step="0"] .guide-filter-status,
        .guide-workorders-stage[data-workorder-step="1"] .guide-filter-status,
        .guide-workorders-stage[data-workorder-step="2"] .guide-filter-status,
        .guide-workorders-stage[data-workorder-step="3"] .guide-filter-status,
        .guide-workorders-stage[data-workorder-step="4"] .guide-filter-status { opacity:1; transform:translateY(0); }
        .guide-workorders-stage[data-workorder-step="1"] .guide-filter-active,
        .guide-workorders-stage[data-workorder-step="2"] .guide-filter-active,
        .guide-workorders-stage[data-workorder-step="2"] .guide-filter-mine,
        .guide-workorders-stage[data-workorder-step="3"] .guide-filter-active,
        .guide-workorders-stage[data-workorder-step="3"] .guide-filter-mine,
        .guide-workorders-stage[data-workorder-step="3"] .guide-filter-approved,
        .guide-workorders-stage[data-workorder-step="4"] .guide-filter-active,
        .guide-workorders-stage[data-workorder-step="4"] .guide-filter-mine,
        .guide-workorders-stage[data-workorder-step="4"] .guide-filter-approved { border-color:#ffd54a; background:rgba(255,213,74,.12); box-shadow:0 0 12px rgba(255,213,74,.35); }
        .guide-workorders-stage[data-workorder-step="1"] .guide-filter-active::after,
        .guide-workorders-stage[data-workorder-step="2"] .guide-filter-active::after,
        .guide-workorders-stage[data-workorder-step="2"] .guide-filter-mine::after,
        .guide-workorders-stage[data-workorder-step="3"] .guide-filter-active::after,
        .guide-workorders-stage[data-workorder-step="3"] .guide-filter-mine::after,
        .guide-workorders-stage[data-workorder-step="3"] .guide-filter-approved::after,
        .guide-workorders-stage[data-workorder-step="4"] .guide-filter-active::after,
        .guide-workorders-stage[data-workorder-step="4"] .guide-filter-mine::after,
        .guide-workorders-stage[data-workorder-step="4"] .guide-filter-approved::after { opacity:1; transform:scale(1); }
        .guide-workorders-stage[data-workorder-step="1"] .guide-workorders-cursor { top:1.65%; left:77.15%; }
        .guide-workorders-stage[data-workorder-step="2"] .guide-workorders-cursor { top:1.65%; left:84.45%; }
        .guide-workorders-stage[data-workorder-step="3"] .guide-workorders-cursor { top:1.65%; left:93.8%; }
        .guide-workorders-stage[data-workorder-step="4"] .guide-workorders-cursor { top:10.5%; left:14.5%; }
        .guide-workorders-stage[data-workorder-step="3"] .guide-filtered-above,
        .guide-workorders-stage[data-workorder-step="3"] .guide-filtered-below,
        .guide-workorders-stage[data-workorder-step="4"] .guide-filtered-above,
        .guide-workorders-stage[data-workorder-step="4"] .guide-filtered-below,
        .guide-workorders-stage[data-workorder-step="3"] .guide-selected-workorder,
        .guide-workorders-stage[data-workorder-step="4"] .guide-selected-workorder { opacity:1; }
        .guide-workorders-stage[data-workorder-step="3"] .guide-filtered-below,
        .guide-workorders-stage[data-workorder-step="4"] .guide-filtered-below { clip-path:inset(0 0 0 0); }
        .guide-workorders-stage[data-workorder-step="3"] .guide-collapse-label,
        .guide-workorders-stage[data-workorder-step="4"] .guide-collapse-label,
        .guide-workorders-stage[data-workorder-step="4"] .guide-workorder-statuses { opacity:1; }
        .guide-workorders-stage[data-workorder-step="4"] .guide-selected-workorder { animation:guideSelectedWorkorderPulse .85s ease-in-out infinite alternate; }
        @keyframes guideSelectedWorkorderPulse { from { box-shadow:0 0 0 rgba(255,213,74,0); } to { box-shadow:0 0 18px rgba(255,213,74,.68); } }
        /* Approved: one clear action — a real checkbox and a filtered real list. */
        .guide-approved-demo .guide-workorders-source { position:relative; z-index:1; }
        .guide-approved-demo .guide-filtered-result { position:absolute; z-index:2; top:11.9%; right:0; left:0; height:100%; overflow:hidden; background:#1b2633; clip-path:inset(0 0 100% 0); opacity:0; pointer-events:none; transition:opacity .2s ease, clip-path 4.2s cubic-bezier(.2,.75,.25,1); }
        .guide-approved-demo .guide-filtered-result img { display:block; width:100%; max-width:none; max-height:none; height:auto; border:0; border-radius:0; }
        .guide-approved-demo .guide-filtered-result::after { position:absolute; z-index:1; top:45.6%; right:0; bottom:0; left:0; content:""; background:#1b2633; }
        .guide-approved-demo .guide-filtered-result img { position:relative; z-index:2; }
        .guide-approved-demo .guide-filter-approved { top:-.25%; left:79.2%; width:3.2%; height:5.8%; }
        .guide-approved-demo .guide-filter-approved::after { left:3px; top:2px; }
        .guide-approved-demo .guide-workorders-cursor { top:51%; left:52%; }
        .guide-approved-demo .guide-filter-status { top:13.4%; left:2%; max-width:54%; }
        .guide-approved-demo .guide-selected-workorder { top:11.9%; left:0; width:100%; height:6.45%; }
        .guide-approved-demo .guide-filter-status span { display:none; }
        .guide-approved-demo[data-workorder-step="0"] .guide-filter-status-start,
        .guide-approved-demo[data-workorder-step="1"] .guide-filter-status-approved,
        .guide-approved-demo[data-workorder-step="2"] .guide-filter-status-collapsed,
        .guide-approved-demo[data-workorder-step="3"] .guide-filter-status-open { display:inline; }
        .guide-approved-demo[data-workorder-step="1"] .guide-filter-approved,
        .guide-approved-demo[data-workorder-step="2"] .guide-filter-approved,
        .guide-approved-demo[data-workorder-step="3"] .guide-filter-approved { border-color:#ffd54a; background:rgba(255,213,74,.12); box-shadow:0 0 12px rgba(255,213,74,.35); }
        .guide-approved-demo[data-workorder-step="1"] .guide-filter-approved::after,
        .guide-approved-demo[data-workorder-step="2"] .guide-filter-approved::after,
        .guide-approved-demo[data-workorder-step="3"] .guide-filter-approved::after { opacity:1; transform:scale(1); }
        .guide-approved-demo[data-workorder-step="1"] .guide-workorders-cursor,
        .guide-approved-demo[data-workorder-step="2"] .guide-workorders-cursor { top:1%; left:79.8%; }
        .guide-approved-demo[data-workorder-step="3"] .guide-workorders-cursor { top:13%; left:5%; }
        .guide-approved-demo[data-workorder-step="2"] .guide-filtered-result,
        .guide-approved-demo[data-workorder-step="3"] .guide-filtered-result { opacity:1; clip-path:inset(0 0 0 0); }
        .guide-approved-demo[data-workorder-step="3"] .guide-selected-workorder { opacity:1; animation:guideSelectedWorkorderPulse .85s ease-in-out infinite alternate; }
        /* Full real Workorders screens: filters, then the real Approved result, then a workorder click. */
        .guide-workorders-full-demo .guide-workorders-source { position:relative; z-index:1; width:100%; max-width:none; max-height:none; }
        .guide-workorders-full-demo .guide-filtered-result { position:absolute; z-index:2; top:5.2%; right:0; bottom:0; left:0; overflow:hidden; opacity:0; clip-path:inset(0 0 100% 0); pointer-events:none; transition:opacity .2s ease, clip-path 4.2s cubic-bezier(.2,.75,.25,1); }
        .guide-workorders-full-demo .guide-filtered-result img { position:absolute; top:-5.2%; left:0; width:100%; max-width:none; max-height:none; height:auto; }
        .guide-workorders-full-demo .guide-filter-choice { top:.15%; height:5.2%; }
        .guide-workorders-full-demo .guide-filter-choice::after { left:4px; top:3px; }
        .guide-workorders-full-demo .guide-filter-active { left:76.3%; width:7.3%; }
        .guide-workorders-full-demo .guide-filter-mine { left:84%; width:8.8%; }
        .guide-workorders-full-demo .guide-filter-approved { left:93.6%; width:6%; }
        .guide-workorders-full-demo .guide-workorders-cursor { top:50%; left:51%; }
        .guide-workorders-full-demo .guide-filter-status { top:6%; left:18%; max-width:53%; }
        .guide-workorders-full-demo .guide-selected-workorder { top:11%; left:13%; width:86%; height:4.35%; }
        .guide-workorders-full-demo .guide-filter-status span { display:none !important; }
        .guide-workorders-full-demo[data-workorder-step="0"] .guide-filter-status-start,
        .guide-workorders-full-demo[data-workorder-step="1"] .guide-filter-status-active,
        .guide-workorders-full-demo[data-workorder-step="2"] .guide-filter-status-mine,
        .guide-workorders-full-demo[data-workorder-step="3"] .guide-filter-status-approved,
        .guide-workorders-full-demo[data-workorder-step="4"] .guide-filter-status-collapsed,
        .guide-workorders-full-demo[data-workorder-step="5"] .guide-filter-status-open { display:inline !important; }
        .guide-workorders-full-demo[data-workorder-step="1"] .guide-filter-active,
        .guide-workorders-full-demo[data-workorder-step="2"] .guide-filter-active,
        .guide-workorders-full-demo[data-workorder-step="2"] .guide-filter-mine,
        .guide-workorders-full-demo[data-workorder-step="3"] .guide-filter-active,
        .guide-workorders-full-demo[data-workorder-step="3"] .guide-filter-mine,
        .guide-workorders-full-demo[data-workorder-step="3"] .guide-filter-approved,
        .guide-workorders-full-demo[data-workorder-step="4"] .guide-filter-active,
        .guide-workorders-full-demo[data-workorder-step="4"] .guide-filter-mine,
        .guide-workorders-full-demo[data-workorder-step="4"] .guide-filter-approved,
        .guide-workorders-full-demo[data-workorder-step="5"] .guide-filter-active,
        .guide-workorders-full-demo[data-workorder-step="5"] .guide-filter-mine,
        .guide-workorders-full-demo[data-workorder-step="5"] .guide-filter-approved { border-color:#ffd54a; background:rgba(255,213,74,.12); box-shadow:0 0 12px rgba(255,213,74,.35); }
        .guide-workorders-full-demo[data-workorder-step="1"] .guide-filter-active::after,
        .guide-workorders-full-demo[data-workorder-step="2"] .guide-filter-active::after,
        .guide-workorders-full-demo[data-workorder-step="2"] .guide-filter-mine::after,
        .guide-workorders-full-demo[data-workorder-step="3"] .guide-filter-active::after,
        .guide-workorders-full-demo[data-workorder-step="3"] .guide-filter-mine::after,
        .guide-workorders-full-demo[data-workorder-step="3"] .guide-filter-approved::after,
        .guide-workorders-full-demo[data-workorder-step="4"] .guide-filter-active::after,
        .guide-workorders-full-demo[data-workorder-step="4"] .guide-filter-mine::after,
        .guide-workorders-full-demo[data-workorder-step="4"] .guide-filter-approved::after,
        .guide-workorders-full-demo[data-workorder-step="5"] .guide-filter-active::after,
        .guide-workorders-full-demo[data-workorder-step="5"] .guide-filter-mine::after,
        .guide-workorders-full-demo[data-workorder-step="5"] .guide-filter-approved::after { opacity:1; transform:scale(1); }
        .guide-workorders-full-demo[data-workorder-step="1"] .guide-workorders-cursor { top:.7%; left:77.2%; }
        .guide-workorders-full-demo[data-workorder-step="2"] .guide-workorders-cursor { top:.7%; left:84.9%; }
        .guide-workorders-full-demo[data-workorder-step="3"] .guide-workorders-cursor,
        .guide-workorders-full-demo[data-workorder-step="4"] .guide-workorders-cursor { top:.7%; left:94.5%; }
        .guide-workorders-full-demo[data-workorder-step="5"] .guide-workorders-cursor { top:11.6%; left:14%; }
        .guide-workorders-full-demo[data-workorder-step="4"] .guide-filtered-result,
        .guide-workorders-full-demo[data-workorder-step="5"] .guide-filtered-result { opacity:1; clip-path:inset(0 0 0 0); }
        .guide-workorders-full-demo[data-workorder-step="5"] .guide-selected-workorder { opacity:1; animation:guideSelectedWorkorderPulse .85s ease-in-out infinite alternate; }
        /* Interactive HTML copy of the real Workorders table: rows can actually collapse on each guide step. */
        .guide-workorders-demo-html { position:relative; align-self:stretch; display:flex; flex:1 1 auto; flex-direction:column; width:100%; min-height:0; margin-top:0; overflow:hidden; border:1px solid rgba(77,138,190,.5); border-radius:5px; background:#182633; color:#e8f3fb; font-size:clamp(8px,.68vw,13px); }
        .guide-html-toolbar { display:grid; grid-template-columns:auto minmax(110px,1fr) auto auto auto; align-items:center; gap:clamp(6px,1vw,18px); min-height:44px; padding:6px 10px; border-bottom:1px solid rgba(114,159,195,.42); background:#203040; }
        .guide-html-toolbar-title { color:#49a9f2; font-size:clamp(13px,1.15vw,21px); font-weight:600; white-space:nowrap; }
        .guide-html-toolbar-title strong { color:#79b9e6; font-size:.78em; }
        .guide-html-search { justify-self:center; width:min(100%,230px); padding:5px 10px; border:1px solid rgba(124,165,196,.45); border-radius:4px; color:#93aabb; background:#14212d; }
        .guide-html-filter { position:relative; display:inline-flex; align-items:center; gap:6px; color:#eef8ff; cursor:pointer; white-space:nowrap; }
        .guide-html-filter::before { content:""; display:grid; place-items:center; width:17px; height:17px; border:1px solid #91a0ad; border-radius:2px; background:#2b3036; color:#172432; box-shadow:inset 0 0 0 1px rgba(0,0,0,.2); font-size:14px; font-weight:900; line-height:1; }
        .guide-html-filter::after, .guide-html-number::after, .guide-approve-mark::after, .guide-reject-mark::after { content:""; position:absolute; inset:-5px -7px; border:2px solid transparent; border-radius:48% 52% 45% 55% / 55% 43% 57% 45%; transform:rotate(-3deg); pointer-events:none; transition:opacity .25s ease, border-color .25s ease, box-shadow .25s ease; }
        .guide-html-table { min-height:0; overflow:visible; background:#1c2b39; }
        .guide-html-row { display:grid; grid-template-columns:8% 5% 4% 16% 16% 10% 15% 14% 12%; align-items:center; min-height:29px; height:29px; overflow:visible; border-bottom:1px solid rgba(103,145,177,.44); transition:height .78s cubic-bezier(.25,.8,.3,1), min-height .78s cubic-bezier(.25,.8,.3,1), opacity .35s ease, transform .78s cubic-bezier(.25,.8,.3,1), border-color .25s ease; }
        .guide-html-row > span, .guide-html-row > button { min-width:0; height:100%; display:flex; align-items:center; justify-content:center; padding:0 5px; overflow:hidden; border-right:1px solid rgba(103,145,177,.44); color:#f2f8fc; background:transparent; font:inherit; text-overflow:ellipsis; white-space:nowrap; }
        .guide-html-row > :nth-child(4) { display:none; }
        .guide-html-row > :nth-child(5), .guide-html-row > :nth-child(6), .guide-html-row > :nth-child(8) { justify-content:flex-start; }
        .guide-html-row.is-selected { background:rgba(73,107,136,.72); }
        .guide-html-row.is-closed .guide-html-number { color:#9aa6b2; }
        .guide-html-number, .guide-approve-mark, .guide-reject-mark { position:relative; font-weight:600; }
        .guide-html-number.is-active { color:#05d9ff; }
        .guide-approve-mark { justify-self:stretch; border:0; cursor:default; color:#90cf2c !important; font-size:1.2em !important; }
        .guide-approve-mark:hover { z-index:25; overflow:visible; }
        .guide-reject-mark { color:#7f8b97 !important; font-size:1.1em !important; }
        .guide-approve-mark[data-tooltip]:hover::before { content:attr(data-tooltip); position:absolute; z-index:20; top:calc(100% + 7px); left:50%; width:max-content; max-width:210px; padding:5px 8px; border:1px solid #ffd54a; border-radius:4px; transform:translateX(-30%); color:#ffe58d; background:#10202d; box-shadow:0 6px 18px rgba(0,0,0,.45); font-size:.86em; white-space:normal; }
        .guide-html-step-note { position:absolute; z-index:15; top:-40px; left:50%; width:max-content; max-width:88%; padding:8px 15px; border:1px solid #ffd54a; border-radius:5px; color:#ffe58d; background:#080d12; box-shadow:0 8px 18px rgba(0,0,0,.38); font-size:clamp(12px,.92vw,16px); font-weight:700; text-align:center; opacity:0; pointer-events:none; transform:translate(-50%,-4px); transition:opacity .25s ease, transform .25s ease; }
        .guide-workorders-demo-html::after { position:absolute; z-index:16; top:13px; left:calc(77% + 4px); width:26px; height:26px; content:""; border:2px solid #ffd54a; border-radius:50%; opacity:0; pointer-events:none; transform:scale(.24); }
        @keyframes guideHtmlFilterClick { 0%, 16% { opacity:0; transform:scale(.22); } 28% { opacity:1; transform:scale(.3); } 78% { opacity:0; transform:scale(1.32); } 100% { opacity:0; transform:scale(1.32); } }
        .guide-workorders-demo-html .guide-workorders-cursor { top:54%; left:51%; }
        .guide-workorders-demo-html .guide-html-step-note span { display:none; }
        .guide-workorders-demo-html[data-workorder-step="0"] .guide-filter-status-start,
        .guide-workorders-demo-html[data-workorder-step="1"] .guide-filter-status-active,
        .guide-workorders-demo-html[data-workorder-step="2"] .guide-filter-status-closed,
        .guide-workorders-demo-html[data-workorder-step="3"] .guide-filter-status-active-filter,
        .guide-workorders-demo-html[data-workorder-step="4"] .guide-filter-status-approved,
        .guide-workorders-demo-html[data-workorder-step="5"] .guide-filter-status-rejected,
        .guide-workorders-demo-html[data-workorder-step="6"] .guide-filter-status-approved-filter,
        .guide-workorders-demo-html[data-workorder-step="7"] .guide-filter-status-mine-filter,
        .guide-workorders-demo-html[data-workorder-step="8"] .guide-filter-status-mine-active-filter { display:inline; }
        .guide-workorders-demo-html[data-workorder-step] .guide-html-step-note { opacity:1; transform:translate(-50%,0); }
        .guide-workorders-demo-html[data-workorder-step="1"] .guide-html-row.is-active .guide-html-number::after { border-color:#ffd54a; box-shadow:0 0 9px rgba(255,213,74,.4); }
        .guide-workorders-demo-html[data-workorder-step="2"] .guide-html-row.is-closed .guide-html-number::after { border-color:#ffd54a; box-shadow:0 0 9px rgba(255,213,74,.4); }
        .guide-workorders-demo-html[data-workorder-step="3"] .guide-html-filter-active::after,
        .guide-workorders-demo-html[data-workorder-step="6"] .guide-html-filter-approved::after,
        .guide-workorders-demo-html[data-workorder-step="7"] .guide-html-filter-mine::after,
        .guide-workorders-demo-html[data-workorder-step="8"] .guide-html-filter-active::after { border-color:#ffd54a; box-shadow:0 0 12px rgba(255,213,74,.45); }
        .guide-workorders-demo-html[data-workorder-step="3"] .guide-html-filter-active::before,
        .guide-workorders-demo-html[data-workorder-step="6"] .guide-html-filter-approved::before,
        .guide-workorders-demo-html[data-workorder-step="7"] .guide-html-filter-mine::before,
        .guide-workorders-demo-html[data-workorder-step="8"] .guide-html-filter-mine::before,
        .guide-workorders-demo-html[data-workorder-step="8"] .guide-html-filter-active::before { content:"✓"; border-color:#ffd54a; background:#ffd54a; box-shadow:0 0 10px rgba(255,213,74,.45); }
        .guide-workorders-demo-html[data-workorder-step="4"] .guide-approve-mark::after,
        .guide-workorders-demo-html[data-workorder-step="5"] .guide-reject-mark::after { border-color:#ffd54a; box-shadow:0 0 9px rgba(255,213,74,.4); }
        .guide-workorders-demo-html[data-workorder-step="3"]::after { animation:guideHtmlFilterClick .46s 1s ease-out both; }
        .guide-workorders-demo-html[data-workorder-step="6"]::after { left:calc(94% + 4px); animation:guideHtmlFilterClick .46s 1s ease-out both; }
        .guide-workorders-demo-html[data-workorder-step="7"]::after { left:calc(84% + 4px); animation:guideHtmlFilterClick .46s 1s ease-out both; }
        .guide-workorders-demo-html[data-workorder-step="8"]::after { left:calc(77% + 4px); animation:guideHtmlFilterClick .46s 1s ease-out both; }
        .guide-workorders-demo-html[data-workorder-step="3"] .guide-html-row.is-closed,
        .guide-workorders-demo-html[data-workorder-step="6"] .guide-html-row.is-unapproved { min-height:0; height:0; overflow:hidden; border-bottom-color:transparent; opacity:0; transform:scaleY(.08); transition-delay:calc(2.46s + var(--collapse-order) * 115ms); }
        .guide-workorders-demo-html[data-workorder-step="7"] .guide-html-row.is-not-mine { min-height:0; height:0; overflow:hidden; border-bottom-color:transparent; opacity:0; transform:scaleY(.08); transition-delay:calc(2.46s + var(--collapse-order) * 115ms); }
        .guide-workorders-demo-html[data-workorder-step="8"] .guide-html-row.is-not-mine,
        .guide-workorders-demo-html[data-workorder-step="8"] .guide-html-row.is-inactive-mine { min-height:0; height:0; overflow:hidden; border-bottom-color:transparent; opacity:0; transform:scaleY(.08); transition-delay:calc(2.46s + var(--collapse-order) * 115ms); }
        .guide-workorders-demo-html.is-user-active .guide-html-filter-active::before,
        .guide-workorders-demo-html.is-user-approved .guide-html-filter-approved::before,
        .guide-workorders-demo-html.is-user-mine .guide-html-filter-mine::before { content:"✓"; border-color:#ffd54a; background:#ffd54a; box-shadow:0 0 10px rgba(255,213,74,.45); }
        .guide-workorders-demo-html.is-user-active .guide-html-row.is-closed,
        .guide-workorders-demo-html.is-user-approved .guide-html-row.is-unapproved,
        .guide-workorders-demo-html.is-user-mine .guide-html-row.is-not-mine { min-height:0; height:0; overflow:hidden; border-bottom-color:transparent; opacity:0; transform:scaleY(.08); transition-delay:0s; }
        .guide-workorders-demo-html[data-workorder-step="4"] .guide-approve-mark[data-tooltip]:hover::before { content:"Approved by Manager"; }
        .guide-workorders-demo-html .guide-html-row:not(.guide-html-row-head) { cursor:pointer; }
        .guide-workorders-demo-html .guide-html-row.is-user-selected { outline:1px solid #ffd54a; outline-offset:-1px; background:transparent; }
        .guide-workorders-demo-html[data-workorder-step="3"] .guide-workorders-cursor { top:15px; left:77%; }
        .guide-workorders-demo-html[data-workorder-step="6"] .guide-workorders-cursor { top:15px; left:94%; }
        .guide-workorders-demo-html[data-workorder-step="7"] .guide-workorders-cursor { top:15px; left:84%; }
        .guide-workorders-demo-html[data-workorder-step="8"] .guide-workorders-cursor { top:15px; left:77%; }
        .guide-workorders-demo-html[data-workorder-step="1"] .guide-workorders-cursor { top:25%; left:7%; }
        .guide-workorders-demo-html[data-workorder-step="2"] .guide-workorders-cursor { top:57%; left:7%; }
        .guide-workorders-demo-html[data-workorder-step="4"] .guide-workorders-cursor { top:30%; left:10%; }
        .guide-workorders-demo-html[data-workorder-step="5"] .guide-workorders-cursor { top:59%; left:10%; }
        /* Interactive copy of Main for W107736: the controls are local to the guide and never save production data. */
        .guide-main-demo { align-self:stretch; display:flex; flex:1 1 auto; flex-direction:column; width:100%; min-height:0; overflow:hidden; border:1px solid #1597b7; border-radius:7px; background:#1b2a38; color:#edf7ff; font-size:clamp(8px,.68vw,13px); box-shadow:0 0 0 2px rgba(13,202,240,.12) inset; }
        .guide-main-demo-header { display:grid; grid-template-columns:96px auto minmax(150px,1fr) auto auto; align-items:center; gap:12px; min-height:102px; padding:7px 12px; border-bottom:1px solid rgba(115,165,195,.42); background:#1d2b39; }
        .guide-main-demo-paper { grid-row:1 / span 2; align-self:stretch; display:grid; place-items:center; padding:8px; border:1px solid rgba(46,198,227,.52); border-radius:5px; background:#172532; }
        .guide-main-demo-paper img { width:58px; max-height:78px; object-fit:contain; filter:grayscale(1) brightness(1.35); }
        .guide-main-demo-wo { color:#fff; font-size:clamp(15px,1.3vw,23px); font-weight:700; white-space:nowrap; }
        .guide-main-demo-badge { margin-left:6px; padding:3px 7px; border-radius:4px; color:#fff; background:#2ebf79; font-size:.62em; vertical-align:middle; }
        .guide-main-demo-component { min-width:0; color:#f8fbff; font-size:clamp(14px,1.2vw,22px); font-weight:500; text-align:center; text-overflow:ellipsis; overflow:hidden; white-space:nowrap; }
        .guide-main-demo-training { padding:6px 9px; border:1px solid rgba(79,172,205,.45); border-radius:5px; color:#35c6df; white-space:nowrap; }
        .guide-main-demo-actions { display:flex; gap:5px; }
        .guide-main-demo-action { min-width:31px; min-height:30px; padding:4px 8px; border:1px solid #087ba8; border-radius:4px; color:#30c9ed; background:#172532; font:inherit; cursor:pointer; }
        .guide-main-demo-action:hover, .guide-main-demo-action:focus-visible { border-color:#19c8ed; color:#e8fbff; outline:0; }
        .guide-main-demo-meta { display:grid; grid-template-columns:1.25fr 1.25fr 1fr; gap:4px 16px; padding:7px 12px 7px 122px; border-bottom:1px solid rgba(115,165,195,.32); color:#f1f8fd; background:#213140; }
        .guide-main-demo-meta strong { color:#55bde9; font-weight:600; }
        .guide-main-demo-tabs { display:flex; gap:2px; padding:7px 8px 0; border-bottom:1px solid rgba(111,160,191,.45); background:#1b2937; }
        .guide-main-demo-tab { padding:7px 13px; border:1px solid transparent; border-bottom:2px solid transparent; border-radius:5px 5px 0 0; color:#aebdca; background:transparent; font:inherit; cursor:pointer; }
        .guide-main-demo-tab:hover, .guide-main-demo-tab:focus-visible, .guide-main-demo-tab.is-active { border-bottom-color:#12c6e8; color:#f7fcff; outline:0; }
        .guide-main-demo-body { min-height:0; flex:1 1 auto; padding:7px; overflow:auto; background:#192937; }
        .guide-main-demo-pane { display:none; min-height:100%; }
        .guide-main-demo-pane.is-active { display:grid; }
        .guide-main-demo-all { grid-template-columns:1fr 1fr; gap:8px; }
        .guide-main-demo-card { min-width:0; overflow:hidden; border:1px solid #1597b7; border-radius:5px; background:#253544; }
        .guide-main-demo-card h3 { margin:0; padding:7px 9px; border-bottom:1px solid rgba(111,160,191,.45); color:#63c8ef; font-size:1.05em; font-weight:600; }
        .guide-main-demo-statuses { display:grid; grid-template-columns:repeat(5,1fr); gap:6px; padding:8px; }
        .guide-main-demo-status { min-height:34px; border:2px solid #bd3853; border-radius:4px; color:#df4262; background:#253443; font:inherit; cursor:pointer; }
        .guide-main-demo-status.is-active { border-color:#22bd7a; color:#35d58e; box-shadow:0 0 0 1px rgba(53,213,142,.2) inset; }
        .guide-main-demo-note { width:100%; min-height:90px; padding:8px; border:1px solid rgba(122,159,184,.48); color:#dce8ef; background:#172532; font:inherit; resize:vertical; }
        .guide-main-demo-list { display:grid; grid-template-columns:1fr auto auto; gap:1px; padding:7px; background:#1a2a37; }
        .guide-main-demo-list > span { min-height:30px; display:flex; align-items:center; padding:6px; border:1px solid rgba(102,143,173,.35); background:#253443; }
        .guide-main-demo-list-head { color:#8da2b2; }
        .guide-main-demo-accordion { display:grid; gap:6px; padding:8px; }
        .guide-main-demo-accordion button { display:flex; justify-content:space-between; width:100%; padding:9px 12px; border:1px solid rgba(100,143,175,.45); border-radius:5px; color:#eaf5fb; background:#2b3b4b; font:inherit; text-align:left; cursor:pointer; }
        .guide-main-demo-accordion button[aria-expanded="true"] { border-color:#25c984; color:#55d99c; }
        .guide-main-demo-accordion div { display:none; padding:8px 12px; color:#bcd0dc; background:#1b2a37; }
        .guide-main-demo-accordion button[aria-expanded="true"] + div { display:block; }
        .guide-main-demo-single { grid-template-columns:1fr; }
        .guide-main-demo-form-card { padding:10px; }
        .guide-main-demo-form-card h4 { margin:0 0 9px; color:#61cbed; font-size:1.08em; }
        .guide-main-demo-task-table { display:grid; gap:1px; background:#172532; }
        .guide-main-demo-task-row { display:grid; grid-template-columns:28px minmax(160px,1fr) 120px 120px 105px; align-items:center; gap:6px; min-height:36px; padding:4px 7px; background:#263747; }
        .guide-main-demo-task-row.is-head { min-height:28px; color:#9eb4c3; background:#1d2d3a; font-size:.9em; }
        .guide-main-demo-task-row input[type="text"], .guide-main-demo-task-row select, .guide-main-demo-tdr input, .guide-main-demo-tdr select { width:100%; min-height:27px; padding:3px 6px; border:1px solid rgba(117,159,186,.55); border-radius:3px; color:#eff9ff; background:#172532; font:inherit; }
        .guide-main-demo-task-row input[type="checkbox"] { width:16px; height:16px; accent-color:#20b975; }
        .guide-main-demo-task-hint { margin:8px 0 0; color:#92aab9; font-size:.9em; }
        .guide-main-demo-photos { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .guide-main-demo-photo-group { min-height:130px; padding:10px; border:1px dashed rgba(81,179,215,.68); border-radius:5px; background:#1b2c39; }
        .guide-main-demo-photo-group h4 { margin:0 0 8px; color:#61cbed; font-size:1em; }
        .guide-main-demo-photo-group p { margin:0 0 10px; color:#aebfca; font-size:.9em; }
        .guide-main-demo-photo-group input { width:100%; color:#cce8f5; font-size:.85em; }
        .guide-main-demo-tdr { display:grid; grid-template-columns:100px minmax(160px,1fr) 150px 130px; gap:7px; align-items:center; }
        .guide-main-demo-tdr > label { color:#9cb5c5; }
        .guide-live-main, .guide-live-tdr, .guide-live-page { align-self:stretch; display:flex; flex:1 1 auto; width:100%; min-height:0; overflow:hidden; border:1px solid #1597b7; border-radius:6px; background:#172532; }
        .guide-live-main iframe, .guide-live-tdr iframe, .guide-live-page iframe { flex:1 1 auto; width:100%; min-height:0; border:0; background:#172532; }
        .guide-live-mobile { align-self:center; display:flex; flex:1 1 auto; width:min(100%, 480px); min-height:0; overflow:hidden; border:1px solid #1597b7; border-radius:6px; background:#172532; }
        .guide-live-mobile iframe { flex:1 1 auto; width:100%; min-height:0; border:0; background:#172532; }
        .guide-live-main-message { display:grid; place-items:center; flex:1 1 auto; padding:28px; color:var(--guide-muted); text-align:center; }
        @media (max-width: 900px) { .guide-main-demo-header { grid-template-columns:1fr auto; min-height:0; } .guide-main-demo-paper { display:none; } .guide-main-demo-component { order:3; grid-column:1 / -1; text-align:left; } .guide-main-demo-meta { padding-left:12px; } .guide-main-demo-all { grid-template-columns:1fr; } .guide-main-demo-task-row { grid-template-columns:24px minmax(120px,1fr) 100px; } .guide-main-demo-task-row > :nth-child(4), .guide-main-demo-task-row > :nth-child(5) { grid-column:2; } .guide-main-demo-photos { grid-template-columns:1fr; } .guide-main-demo-tdr { grid-template-columns:1fr; } }
        .guide-mobile-app-shot { width:min(100%, 520px); justify-self:center; }
        .guide-shot figcaption { align-self:stretch; padding:8px 4px 2px; color:var(--guide-muted); font-size:.84rem; line-height:1.4; }
        .guide-step-note { margin:18px 0 0; padding:15px 18px; border-left:3px solid var(--guide-accent); background:rgba(13,202,240,.07); color:var(--guide-muted); line-height:1.55; }
        .guide-step-note strong { color:var(--guide-text); }
        .guide-subsection { margin-top:38px; }
        .guide-subsection h3 { margin:0 0 8px; font-size:1.18rem; font-weight:600; }
        .guide-subsection p { max-width:1040px; margin:0; color:var(--guide-muted); line-height:1.55; }
        .guide-photo-steps { grid-column:1 / -1; grid-row:2; align-self:start; display:grid; gap:10px; max-width:1050px; margin:12px 0 0; padding:0; list-style:none; counter-reset:photo-step; }
        .guide-photo-steps li { position:relative; padding:14px 16px 14px 54px; border:1px solid var(--guide-line); border-radius:7px; color:var(--guide-muted); line-height:1.48; }
        .guide-photo-steps li::before { position:absolute; left:15px; top:14px; content:counter(photo-step); counter-increment:photo-step; display:grid; place-items:center; width:24px; height:24px; color:#09252e; background:var(--guide-accent); border-radius:50%; font-size:.76rem; font-weight:700; }
        .guide-mobile-note { display:none; }
        html[dir="rtl"] .guide-toc { border-right:0; border-left:1px solid var(--guide-line); grid-column:2; }
        html[dir="rtl"] .guide-main { grid-column:1; grid-row:1; }
        html[dir="rtl"] .guide-toc a { padding:5px 12px 5px 4px; border-left:0; border-right:2px solid transparent; }
        html[dir="rtl"] .guide-toc-level-3 { padding-right:24px !important; padding-left:4px !important; }
        html[dir="rtl"] .guide-toc a:hover, html[dir="rtl"] .guide-toc a:focus-visible { border-right-color:var(--guide-accent); }
        html[dir="rtl"] .guide-toc summary .bi-chevron-down { margin-left:0; margin-right:auto; }
        html[dir="rtl"] .guide-section-intro::before { margin-right:0; margin-left:8px; }
        html[dir="rtl"] .guide-photo-steps li { padding:14px 54px 14px 16px; }
        html[dir="rtl"] .guide-photo-steps li::before { left:auto; right:15px; }
        @media (max-width:900px) {
            .guide-layout { display:block; }
            .guide-toc-toggle { display:inline-flex; align-items:center; gap:6px; }
            .guide-toc { position:fixed; top:0; bottom:0; left:0; width:min(82vw,290px); max-height:none; padding-top:82px; transform:translateX(-102%); transition:transform .2s ease; box-shadow:16px 0 34px rgba(0,0,0,.4); }
            .guide-app.is-toc-open .guide-toc { transform:translateX(0); }
            .guide-app::after { content:""; position:fixed; inset:0; z-index:15; background:rgba(0,0,0,.48); opacity:0; pointer-events:none; transition:opacity .2s ease; }
            .guide-app.is-toc-open::after { opacity:1; pointer-events:auto; }
            .guide-main { padding:18px 18px 0; }
            .guide-section { height:calc(100dvh - 66px); }
            .guide-mobile-note { display:none; }
            html[dir="rtl"] .guide-toc { left:auto; right:0; transform:translateX(102%); }
            html[dir="rtl"] .guide-app.is-toc-open .guide-toc { transform:translateX(0); }
        }
        @media (max-width:620px) {
            .guide-topbar { min-height:44px; grid-template-columns:auto minmax(0,1fr) auto; padding:4px 8px; gap:6px; }
            .guide-header-left { grid-column:1; gap:5px; }
            .guide-header-right { grid-column:3; gap:4px; }
            .guide-page-title { grid-column:2; min-width:0; justify-self:stretch; justify-content:center; gap:5px; }
            .guide-topbar h1 { min-width:0; overflow:hidden; font-size:1.03rem; text-align:center; text-overflow:ellipsis; white-space:nowrap; }
            .guide-previous span, .guide-next span { display:none; }
            .guide-previous, .guide-next { padding:.18rem; }
            .language-switcher { gap:1px; }
            .guide-language:not([data-language="en"]):not([data-language="ru"]) { display:none; }
            .guide-back span { display:none; }
            .guide-language { padding:.28rem .32rem .2rem; font-size:.72rem; }
            .guide-main { padding:12px 12px 0; }
            .guide-shot { padding:7px; border-radius:7px; }
            .guide-shot figcaption { font-size:.82rem; }
            .guide-section { height:calc(100dvh - 56px); margin:0; padding-bottom:12px; }
        }
        @media (prefers-reduced-motion:reduce) { *, *::before, *::after { transition-duration:.01ms !important; scroll-behavior:auto !important; } }
    </style>
</head>
<body class="guide-loading">
<div class="guide-loading-overlay" id="guideLoading" role="status" aria-label="Loading guide">
    <div class="guide-loading-dots" aria-hidden="true"><span></span><span></span><span></span></div>
</div>
<div class="guide-app" id="userGuide">
    <header class="guide-topbar">
        <div class="guide-header-left">
            <button class="btn btn-sm btn-outline-secondary guide-toc-toggle" type="button" data-toc-toggle aria-controls="guideContents" aria-expanded="false"><i class="bi bi-list"></i><span data-guide="menu">Contents</span></button>
            <a class="btn btn-sm btn-outline-secondary guide-back" href="{{ route('workorders.index') }}"><i class="bi bi-arrow-left"></i> <span data-guide="back">Return to system</span></a>
        </div>
        <div class="guide-page-title"><button class="guide-previous" type="button" data-guide-previous><i class="bi bi-arrow-left"></i> <span data-guide="previous">Previous</span></button><h1 data-guide="pageTitle">User Guide</h1><button class="guide-next" type="button" data-guide-next><span data-guide="next">Next</span> <i class="bi bi-arrow-right"></i></button></div>
        <div class="guide-header-right"><div class="language-switcher" aria-label="Guide language">
            <button class="guide-language is-active" type="button" data-language="en">EN</button><button class="guide-language" type="button" data-language="ru">RU</button><button class="guide-language" type="button" data-language="uk">UA</button><button class="guide-language" type="button" data-language="he">HE</button><button class="guide-language" type="button" data-language="de">DE</button><button class="guide-language" type="button" data-language="kk">KZ</button><button class="guide-language" type="button" data-language="be">BE</button>
        </div></div>
    </header>
    <div class="guide-layout">
        <aside class="guide-toc" id="guideContents" aria-label="Guide contents">
            <div class="guide-toc-head"><p class="guide-toc-title" data-guide="contents">Contents</p><button class="guide-open-all" type="button" data-open-all data-guide="openAll">Open all</button></div>
            <nav class="guide-toc-accordion">
                <details open><summary><span data-guide="tocOne">1. Start</span><i class="bi bi-chevron-down"></i></summary><div class="guide-toc-links"><a href="#login-screen" data-guide="tocOneOne">1.1 Sign in</a><a href="#cabinet" data-guide="tocOneTwo">1.2 Cabinet</a></div></details>
                <details><summary><span data-guide="tocTwo">2. Main menu</span><i class="bi bi-chevron-down"></i></summary><div class="guide-toc-links"><a href="#workorders" data-guide="tocTwoOne">2.1 Workorder</a><a class="guide-toc-level-3" href="#workorder-main" data-guide="tocTwoOneOne">2.1.1 Main</a><a class="guide-toc-level-3" href="#workorder-tdr" data-guide="tocTwoOneTwo">2.1.2 TDR</a><a class="guide-toc-level-3" href="#workorder-pictures" data-guide="tocTwoOneThree">2.1.3 Pictures</a><a href="#training" data-guide="tocTwoTwo">2.2 Training</a><a href="#technicians" data-guide="tocTwoThree">2.3 Technician</a><a href="#materials" data-guide="tocTwoFour">2.4 Materials</a></div></details>
                <details><summary><span data-guide="tocThree">3. Mobile</span><i class="bi bi-chevron-down"></i></summary><div class="guide-toc-links"><a href="#mobile-workorders" data-guide="tocThreeOne">3.1 Workorders</a><a href="#mobile-workorder" data-guide="tocThreeTwo">3.2 Workorder</a><a href="#mobile-photos" data-guide="tocThreeThree">3.3 Photos</a></div></details>
            </nav>
        </aside>
        <main class="guide-main">
            <p class="guide-mobile-note" data-guide="mobileNote">Use the Contents button to open this navigation on a phone.</p>
            <section class="guide-section" id="login-screen"><span class="guide-section-number">1.1</span><h2 class="guide-section-title guide-title-with-link"><span data-guide="loginTitle">Sign in</span><a href="https://www.aviatechnik.ca" target="_blank" rel="noopener noreferrer">www.aviatechnik.ca</a></h2><p class="guide-section-intro" data-guide="loginText">Sign in with your assigned account.</p><figure class="guide-shot"><img src="{{ asset('img/user-guide/technician-login.png') }}" alt="AVIA login screen"><figcaption data-guide="loginCaption">The blue login screen is the first screen of the program.</figcaption></figure></section>
            <section class="guide-section" id="cabinet"><span class="guide-section-number">1.2</span><h2 class="guide-section-title" data-guide="cabinetTitle">Cabinet</h2><p class="guide-section-intro" data-guide="cabinetText">Check your role and menu.</p><figure class="guide-shot"><div class="guide-animated-shot-stage"><img src="{{ asset('img/user-guide/technician-cabinet.png') }}" alt="AVIA Technician cabinet"><span class="guide-workorder-focus" aria-hidden="true"></span><i class="bi bi-cursor-fill guide-workorder-cursor" aria-hidden="true"></i><span class="guide-workorder-click" aria-hidden="true"></span></div><figcaption data-guide="cabinetCaption">Check your name and role in the sidebar header before starting work.</figcaption></figure></section>
            <section class="guide-section" id="workorders"><span class="guide-section-number">2.1</span><h2 class="guide-section-title" data-guide="workordersTitle">Workorder</h2><p class="guide-section-intro" data-guide="workordersText">Filter and open the work order.</p><figure class="guide-shot"><div class="guide-workorders-demo-html" data-workorders-stage data-workorder-step="0" aria-label="Interactive Workorders table demonstration"><div class="guide-html-toolbar"><span class="guide-html-toolbar-title">Workorders <strong>150 of 365</strong></span><span class="guide-html-search">Search...</span><span class="guide-html-filter guide-html-filter-active">WO active</span><span class="guide-html-filter">My workorders</span><span class="guide-html-filter guide-html-filter-approved">Approved</span></div><div class="guide-html-table" role="table"><div class="guide-html-row guide-html-row-head" role="row"><span>Number ↓</span><span>Approve</span><span>EC</span><span>Stages</span><span>Component</span><span>Description</span><span>Serial No.</span><span>Manual</span><span>Customer</span><span>Technik</span></div><div class="guide-html-row is-active" style="--collapse-order:0"><span class="guide-html-number is-active">w 107820</span><span class="guide-approve-mark" data-tooltip="Approved by Richard Aigbovia · 02/Jul/2026">✓</span><span>×</span><span>■■□□□</span><span>2225-0014</span><span>Cylinder Tube</span><span>HM00920</span><span>32-11-14RM</span><span>Liebherr Aerospace</span><span>Richard Aigbovia</span></div><div class="guide-html-row is-active is-unapproved" style="--collapse-order:1"><span class="guide-html-number is-active">w 107819</span><span class="guide-reject-mark">×</span><span class="text-success fw-bold">EC</span><span>■■□□□</span><span>2225-0014</span><span>Cylinder Tube</span><span>HM00915</span><span>32-11-14RM</span><span>Liebherr Aerospace</span><span>Richard Aigbovia</span></div><div class="guide-html-row is-active is-unapproved" style="--collapse-order:2"><span class="guide-html-number is-active">w 107818</span><span class="guide-reject-mark">×</span><span class="text-success fw-bold">EC</span><span>■■□□□</span><span>2225-0014</span><span>Cylinder Tube</span><span>HM00887</span><span>32-11-14RM</span><span>Liebherr Aerospace</span><span>Oleksii Sharapov</span></div><div class="guide-html-row is-active" style="--collapse-order:3"><span class="guide-html-number is-active">w 107817</span><span class="guide-approve-mark" data-tooltip="Approved by Dmytro Suhako · 02/Jul/2026">✓</span><span>×</span><span>■■□□□</span><span>2225-0014</span><span>Cylinder Tube</span><span>HM00892</span><span>32-11-14RM</span><span>Liebherr Aerospace</span><span>Dmytro Suhako</span></div><div class="guide-html-row is-active" style="--collapse-order:4"><span class="guide-html-number is-active">w 107816</span><span class="guide-approve-mark" data-tooltip="Approved by Roman Ivankiv · 30/Jun/2026">✓</span><span>×</span><span>■■□□□</span><span>52163-3</span><span>Piston/Metering Pin Assy</span><span>SPP513336</span><span>32-21-06</span><span>Jazz Aviation LP</span><span>Roman Ivankiv</span></div><div class="guide-html-row is-active" style="--collapse-order:5"><span class="guide-html-number is-active">w 107815</span><span class="guide-approve-mark" data-tooltip="Approved by Serhii Petrukhin · 29/Jun/2026">✓</span><span>×</span><span>■■□□□</span><span>52141-1</span><span>PIN</span><span>ACR111050</span><span>32-21-06</span><span>Jazz Aviation LP</span><span>Serhii Petrukhin</span></div><div class="guide-html-row is-active" style="--collapse-order:6"><span class="guide-html-number is-active">w 107814</span><span class="guide-approve-mark" data-tooltip="Approved by Serhii Petrukhin · 29/Jun/2026">✓</span><span>×</span><span>■■□□□</span><span>52141-1</span><span>PIN</span><span>ACR111083</span><span>32-21-06</span><span>Jazz Aviation LP</span><span>Serhii Petrukhin</span></div><div class="guide-html-row is-closed is-unapproved" style="--collapse-order:7"><span class="guide-html-number">107813</span><span class="guide-reject-mark">×</span><span>×</span><span>■□□□■</span><span>2821A2600-01</span><span>Bolt</span><span>1409</span><span>32-11-14RM</span><span>Liebherr Aerospace</span><span>Dmytro Suhako</span></div><div class="guide-html-row is-closed is-unapproved" style="--collapse-order:8"><span class="guide-html-number">107812</span><span class="guide-reject-mark">×</span><span>×</span><span>■□□□■</span><span>49101-11</span><span>PIN</span><span>SPP230767</span><span>32-11-06</span><span>Jazz Aviation LP</span><span>Vasyl Medvid</span></div><div class="guide-html-row is-closed is-unapproved" style="--collapse-order:9"><span class="guide-html-number">107811</span><span class="guide-reject-mark">×</span><span>×</span><span>■□□□■</span><span>49131-3</span><span>Pin</span><span>SD061569</span><span>32-11-06</span><span>Jazz Aviation LP</span><span>Vasyl Medvid</span></div><div class="guide-html-row is-closed is-unapproved" style="--collapse-order:10"><span class="guide-html-number">107810</span><span class="guide-reject-mark">×</span><span>×</span><span>■□□□■</span><span>49131-3</span><span>Pin</span><span>SD061568</span><span>32-11-06</span><span>Jazz Aviation LP</span><span>Vasyl Medvid</span></div><div class="guide-html-row is-closed" style="--collapse-order:11"><span class="guide-html-number">107809</span><span class="guide-approve-mark" data-tooltip="Approved by Vasyl Medvid · 22/Jun/2026">✓</span><span class="text-success fw-bold">EC</span><span>■■□□□</span><span>49101-13</span><span>Trunnion Pin, FWD</span><span>SPP013540</span><span>32-11-06</span><span>Jazz Aviation LP</span><span>Vasyl Medvid</span></div><div class="guide-html-row is-active is-unapproved" style="--collapse-order:12"><span class="guide-html-number is-active">w 107808</span><span class="guide-reject-mark">×</span><span>×</span><span>■■□□□</span><span>52163-3</span><span>Piston/Metering Pin Assy</span><span>SPP513388</span><span>32-21-06</span><span>Jazz Aviation LP</span><span>Volodymyr Akulov</span></div><div class="guide-html-row is-active is-unapproved" style="--collapse-order:13"><span class="guide-html-number is-active">w 107807</span><span class="guide-reject-mark">×</span><span>×</span><span>□□□</span><span>49205-9</span><span>Upper Torque Link</span><span>MK030409</span><span>32-11-06</span><span>Jazz Aviation LP</span><span>Richard Aigbovia</span></div><div class="guide-html-row is-closed" style="--collapse-order:14"><span class="guide-html-number">107806</span><span class="guide-approve-mark" data-tooltip="Approved by Serhii Petrukhin · 17/Jun/2026">✓</span><span>×</span><span>■■□□□</span><span>52355-3</span><span>Piston</span><span>SPP018496</span><span>32-51-03</span><span>Jazz Aviation LP</span><span>Serhii Petrukhin</span></div><div class="guide-html-row is-closed" style="--collapse-order:15"><span class="guide-html-number">107805</span><span class="guide-approve-mark" data-tooltip="Approved by leonid Blinov · 17/Jun/2026">✓</span><span>×</span><span>■■□□□</span><span>52354-1</span><span>End Assembly, Rod</span><span>SPP013657</span><span>32-51-03</span><span>Jazz Aviation LP</span><span>leonid Blinov</span></div><div class="guide-html-row is-closed" style="--collapse-order:16"><span class="guide-html-number">107804</span><span class="guide-approve-mark" data-tooltip="Approved by Volodymyr Akulov · 17/Jun/2026">✓</span><span>×</span><span>■■□□□</span><span>52352-7</span><span>Cylinder Assembly</span><span>SPP016432</span><span>32-51-03</span><span>Jazz Aviation LP</span><span>Volodymyr Akulov</span></div><div class="guide-html-row is-active is-unapproved" style="--collapse-order:17"><span class="guide-html-number is-active">w 107803</span><span class="guide-reject-mark">×</span><span>×</span><span>■■□□□</span><span>47181-5</span><span>Nosewheel Power Steering</span><span>KEP0371</span><span>32-50-01</span><span>Regional One</span><span>Oleksandr Akymov</span></div><div class="guide-html-row is-active" style="--collapse-order:18"><span class="guide-html-number is-active">w 107802</span><span class="guide-approve-mark" data-tooltip="Approved by Serhii Petrukhin · 16/Jun/2026">✓</span><span>×</span><span>■■□□□</span><span>52141-1</span><span>PIN</span><span>ACR111004</span><span>32-21-06</span><span>Jazz Aviation LP</span><span>Serhii Petrukhin</span></div><div class="guide-html-row is-active" style="--collapse-order:19"><span class="guide-html-number is-active">w 107801</span><span class="guide-approve-mark" data-tooltip="Approved by Serhii Petrukhin · 16/Jun/2026">✓</span><span>×</span><span>■■□□□</span><span>52141-1</span><span>PIN</span><span>ACR111003</span><span>32-21-06</span><span>Jazz Aviation LP</span><span>Serhii Petrukhin</span></div></div><p class="guide-html-step-note" aria-live="polite"><span class="guide-filter-status-start" data-guide="filterStart">Click or press Space to start.</span><span class="guide-filter-status-active" data-guide="filterActive">Blue workorders are active.</span><span class="guide-filter-status-closed" data-guide="filterClosed">Grey numbers are closed.</span><span class="guide-filter-status-active-filter" data-guide="filterActiveFilter">WO active hides closed workorders.</span><span class="guide-filter-status-approved" data-guide="filterApproved">Green check: approved. Hover for details.</span><span class="guide-filter-status-rejected" data-guide="filterRejected">Grey cross: not approved.</span><span class="guide-filter-status-approved-filter" data-guide="filterApprovedFilter">Approved hides unapproved workorders.</span></p><i class="bi bi-cursor-fill guide-workorders-cursor" aria-hidden="true"></i></div><figcaption data-guide="workordersCaption">Click or press Space to move through the filters.</figcaption></figure></section>
            <section class="guide-section" id="workorder-main"><span class="guide-section-number">2.1.1</span><h2 class="guide-section-title" data-guide="workorderMainTitle">Main</h2><p class="guide-section-intro" data-guide="workorderMainText">Review the selected workorder.</p><figure class="guide-shot"><div class="guide-main-demo" data-main-demo aria-label="Interactive copy of Main for workorder W107736"><header class="guide-main-demo-header"><span class="guide-main-demo-wo">w 107736 <span class="guide-main-demo-badge">Approved 15-Jun-26</span></span><span class="guide-main-demo-component">MLG Shock Strut</span><span class="guide-main-demo-training">Training: Medvid Vasyl</span></header><div class="guide-main-demo-meta"><span><strong>Component PN:</strong> 2801A0000-03</span><span><strong>Serial:</strong> 00220</span><span><strong>Instruction:</strong> Overhaul</span><span><strong>Technik:</strong> Vasyl Medvid</span><span><strong>Customer:</strong> Regional One</span><span><strong>Manual:</strong> 32-11-01RM</span></div><nav class="guide-main-demo-tabs" role="tablist" aria-label="Main sections"><button class="guide-main-demo-tab is-active" type="button" data-main-demo-tab="all" role="tab" aria-selected="true">All</button><button class="guide-main-demo-tab" type="button" data-main-demo-tab="notes" role="tab" aria-selected="false">Tasks / Notes</button><button class="guide-main-demo-tab" type="button" data-main-demo-tab="std" role="tab" aria-selected="false">STD Processes</button><button class="guide-main-demo-tab" type="button" data-main-demo-tab="parts" role="tab" aria-selected="false">Parts / Processes</button><button class="guide-main-demo-tab" type="button" data-main-demo-tab="bushing" role="tab" aria-selected="false">Bushing / Processes</button></nav><div class="guide-main-demo-body"><section class="guide-main-demo-pane guide-main-demo-all is-active" data-main-demo-pane="all"><div class="guide-main-demo-card"><div class="guide-main-demo-statuses"><button class="guide-main-demo-status is-active" type="button">Disassembly</button><button class="guide-main-demo-status is-active" type="button">Approved</button><button class="guide-main-demo-status" type="button">Assembly</button><button class="guide-main-demo-status" type="button">Final Test</button><button class="guide-main-demo-status" type="button">Complete</button></div><h3>Workorder Notes</h3><textarea class="guide-main-demo-note" aria-label="Workorder notes" placeholder="Type notes..."></textarea><h3>WO bushing → WO bushing process: 6 pcs.</h3><div class="guide-main-demo-accordion"><button type="button" aria-expanded="false">Machining <span>0 / 5⌄</span></button><div>Five machining operations are listed here.</div><button type="button" aria-expanded="false">NDT <span>0 / 4⌄</span></button><div>Four NDT operations are listed here.</div></div></div><div class="guide-main-demo-card"><h3>STD Processes</h3><div class="guide-main-demo-list"><span class="guide-main-demo-list-head">Technik / List</span><span class="guide-main-demo-list-head">RO</span><span class="guide-main-demo-list-head">Sent</span><span>Quantum — NDT list</span><span>R8907</span><span>25/May/2026</span><span>Quantum — CAD list</span><span>R9002</span><span>16/Jun/2026</span><span>Vasyl Medvid — Paint list</span><span>AT</span><span>19/Jun/2026</span></div><h3>Parts &amp; Repair Processes</h3><div class="guide-main-demo-list"><span class="guide-main-demo-list-head">Technik / Process</span><span class="guide-main-demo-list-head">RO</span><span class="guide-main-demo-list-head">Returned</span><span>Vasyl Medvid — Machining</span><span>AT</span><span>23/Jun/2026</span><span>Quantum — NDT-1</span><span>R9025</span><span>30/Jun/2026</span></div></div></section><section class="guide-main-demo-pane guide-main-demo-single" data-main-demo-pane="notes"><div class="guide-main-demo-card"><h3>Tasks / Notes</h3><textarea class="guide-main-demo-note" aria-label="Tasks and notes" placeholder="Type notes..."></textarea></div></section><section class="guide-main-demo-pane guide-main-demo-single" data-main-demo-pane="std"><div class="guide-main-demo-card"><h3>STD Processes</h3><div class="guide-main-demo-list"><span class="guide-main-demo-list-head">Technik / List</span><span class="guide-main-demo-list-head">RO</span><span class="guide-main-demo-list-head">Sent</span><span>Quantum — NDT list</span><span>R8907</span><span>25/May/2026</span><span>Quantum — CAD list</span><span>R9002</span><span>16/Jun/2026</span><span>Vasyl Medvid — Paint list</span><span>AT</span><span>19/Jun/2026</span></div></div></section><section class="guide-main-demo-pane guide-main-demo-single" data-main-demo-pane="parts"><div class="guide-main-demo-card"><h3>Parts &amp; Repair Processes</h3><div class="guide-main-demo-list"><span class="guide-main-demo-list-head">Technik / Process</span><span class="guide-main-demo-list-head">RO</span><span class="guide-main-demo-list-head">Returned</span><span>Vasyl Medvid — Machining</span><span>AT</span><span>23/Jun/2026</span><span>Quantum — NDT-1</span><span>R9025</span><span>30/Jun/2026</span></div></div></section><section class="guide-main-demo-pane guide-main-demo-single" data-main-demo-pane="bushing"><div class="guide-main-demo-card"><h3>WO bushing → WO bushing process: 6 pcs.</h3><div class="guide-main-demo-accordion"><button type="button" aria-expanded="false">Machining <span>0 / 5⌄</span></button><div>Five machining operations are listed here.</div><button type="button" aria-expanded="false">NDT <span>0 / 4⌄</span></button><div>Four NDT operations are listed here.</div></div></div></section></div></div><figcaption data-guide="workorderMainCaption">Main of workorder W107736: status, component and work sections.</figcaption></figure></section>
            <section class="guide-section" id="workorder-tdr"><span class="guide-section-number">2.1.2</span><h2 class="guide-section-title" data-guide="tdrTitle">TDR</h2><p class="guide-section-intro" data-guide="tdrText">Review the TDR report for the selected workorder.</p><figure class="guide-shot"><div class="guide-live-tdr">@if($canUseLiveTrainingWorkorder ?? false)<iframe src="{{ route('admin.user-guide.tdr-report') }}" title="Workorder 100000 — TDR Report"></iframe>@else<p class="guide-live-main-message" data-guide="tdrUnavailable">The TDR report opens here for the training workorder.</p>@endif</div><figcaption data-guide="tdrCaption">TDR report for training workorder 100000.</figcaption></figure></section>
            <section class="guide-section" id="workorder-pictures"><span class="guide-section-number">2.1.3</span><h2 class="guide-section-title" data-guide="picturesTitle">Pictures</h2><p class="guide-section-intro" data-guide="picturesText">Review photos of the selected workorder.</p><figure class="guide-shot"><div class="guide-live-page">@if($canUseLiveTrainingWorkorder ?? false)<iframe src="{{ route('admin.user-guide.workorder-pictures') }}" title="Workorder 100000 — Pictures"></iframe>@else<p class="guide-live-main-message" data-guide="livePageUnavailable">This page opens here for the training workorder.</p>@endif</div><figcaption data-guide="picturesCaption">Pictures of training workorder 100000.</figcaption></figure></section>
            <section class="guide-section" id="training"><span class="guide-section-number">2.2</span><h2 class="guide-section-title" data-guide="trainingTitle">Training</h2><p class="guide-section-intro" data-guide="trainingText">Check assigned training.</p><figure class="guide-shot"><div class="guide-live-page">@if($canUseLiveTrainingWorkorder ?? false)<iframe src="{{ route('admin.user-guide.training') }}" title="Technician — Training"></iframe>@else<p class="guide-live-main-message" data-guide="livePageUnavailable">This page opens here for the training workorder.</p>@endif</div><figcaption data-guide="trainingCaption">The second item in the Technician menu: Training.</figcaption></figure></section>
            <section class="guide-section" id="technicians"><span class="guide-section-number">2.3</span><h2 class="guide-section-title" data-guide="techniciansTitle">Technician</h2><p class="guide-section-intro" data-guide="techniciansText">Find the responsible technician.</p><figure class="guide-shot"><div class="guide-live-page">@if($canUseLiveTrainingWorkorder ?? false)<iframe src="{{ route('admin.user-guide.technicians') }}" title="Technician — Technician"></iframe>@else<p class="guide-live-main-message" data-guide="livePageUnavailable">This page opens here for the training workorder.</p>@endif</div><figcaption data-guide="techniciansCaption">The third item in the Technician menu: Technician.</figcaption></figure></section>
            <section class="guide-section" id="materials"><span class="guide-section-number">2.4</span><h2 class="guide-section-title" data-guide="materialsTitle">Materials</h2><p class="guide-section-intro" data-guide="materialsText">Find the approved material.</p><figure class="guide-shot"><div class="guide-live-page">@if($canUseLiveTrainingWorkorder ?? false)<iframe src="{{ route('admin.user-guide.materials') }}" title="Technician — Materials"></iframe>@else<p class="guide-live-main-message" data-guide="livePageUnavailable">This page opens here for the training workorder.</p>@endif</div><figcaption data-guide="materialsCaption">The fourth item in the Technician menu: Materials.</figcaption></figure></section>
            <section class="guide-section" id="mobile-workorders"><span class="guide-section-number">3.1</span><h2 class="guide-section-title" data-guide="mobileTitle">Mobile application</h2><p class="guide-section-intro" data-guide="mobileText">Technician screens on a phone.</p><figure class="guide-shot"><div class="guide-live-mobile">@if($canUseLiveTrainingWorkorder ?? false)<iframe src="{{ route('admin.user-guide.mobile-workorders') }}" title="Mobile — Workorders"></iframe>@else<p class="guide-live-main-message" data-guide="livePageUnavailable">This page opens here for the training workorder.</p>@endif</div><figcaption data-guide="mobileShotList">Mobile: find the required workorder in the list.</figcaption></figure></section>
            <section class="guide-section" id="mobile-workorder"><span class="guide-section-number">3.2</span><h2 class="guide-section-title" data-guide="mobileWorkorderTitle">Open a workorder</h2><p class="guide-section-intro" data-guide="mobileWorkorderText">Open the work order details.</p><figure class="guide-shot"><div class="guide-live-mobile">@if($canUseLiveTrainingWorkorder ?? false)<iframe src="{{ route('admin.user-guide.mobile-workorder') }}" title="Mobile — Workorder 100000"></iframe>@else<p class="guide-live-main-message" data-guide="livePageUnavailable">This page opens here for the training workorder.</p>@endif</div><figcaption data-guide="mobileShotWorkorder">Mobile: the opened workorder and its working sections.</figcaption></figure></section>
            <section class="guide-section" id="mobile-photos"><span class="guide-section-number">3.3</span><h2 class="guide-section-title" data-guide="mobilePhotosTitle">Photos on mobile</h2><p class="guide-section-intro" data-guide="mobilePhotosText">Add photos to the required group.</p><figure class="guide-shot"><div class="guide-live-mobile">@if($canUseLiveTrainingWorkorder ?? false)<iframe src="{{ route('admin.user-guide.mobile-workorder-pictures') }}" title="Mobile — Workorder 100000 Pictures"></iframe>@else<p class="guide-live-main-message" data-guide="livePageUnavailable">This page opens here for the training workorder.</p>@endif</div><figcaption data-guide="mobileShotPhotos">Mobile: photo groups, Add buttons and drop areas.</figcaption></figure></section>
        </main>
    </div>
</div>
<script>
    (() => {
        const scope = 'admin.user-guide';
        const guide = document.getElementById('userGuide');
        const toc = document.getElementById('guideContents');
        const toggle = document.querySelector('[data-toc-toggle]');
        const details = Array.from(document.querySelectorAll('.guide-toc details'));
        const openAllButton = document.querySelector('[data-open-all]');
        const previousButton = document.querySelector('[data-guide-previous]');
        const nextButton = document.querySelector('[data-guide-next]');
        const workordersStage = document.querySelector('[data-workorders-stage]');
        const guideMain = document.querySelector('.guide-main');
        const guideLoading = document.getElementById('guideLoading');
        let guidePageLoaded = document.readyState === 'complete';
        let guideStateApplied = false;
        const revealGuide = () => {
            if (!guidePageLoaded || !guideStateApplied) return;

            window.requestAnimationFrame(() => {
                document.body.classList.remove('guide-loading');
                guide?.setAttribute('aria-busy', 'false');
                guideLoading?.setAttribute('aria-hidden', 'true');
            });
        };
        if (!guidePageLoaded) {
            window.addEventListener('load', () => {
                guidePageLoaded = true;
                revealGuide();
            }, { once:true });
        }
        guide?.setAttribute('aria-busy', 'true');
        const dictionaries = {
            en: { pageTitle:'User Guide', back:'Return to system', menu:'Contents', contents:'Contents', openAll:'Open all', closeAll:'Close all', tocOne:'1. Start', tocOneOne:'1.1 Sign in', tocOneOneOne:'1.1.1 Login screen', tocOneTwo:'1.2 Cabinet', tocTwo:'2. Main menu', tocTwoOne:'2.1 Workorder', tocTwoTwo:'2.2 Training', tocTwoThree:'2.3 Technician', tocTwoFour:'2.4 Materials', tocThree:'3. Mobile', tocThreeOne:'3.1 Workorders', tocThreeTwo:'3.2 Workorder', tocThreeThree:'3.3 Photos', tocFour:'4. Workorder photos', tocFourOne:'4.1 Add and group photos', mobileNote:'Use the Contents button to open this navigation on a phone.', kicker:'Technician workflow', introTitle:'Start with the correct sequence', introText:'This guide follows the Technician menu from top to bottom. Each real program screen is shown separately at full width so that controls and details remain readable.', loginTitle:'Sign in', loginText:'Open AVIA and enter the email address and password provided by your administrator. Use only your own account: the available menu items depend on your role.', loginCaption:'The blue login screen is the first screen of the program.', cabinetTitle:'Cabinet', cabinetText:'After sign-in, Cabinet shows your account area. The left sidebar contains only the Technician items available to this role.', cabinetCaption:'Check your name and role in the sidebar header before starting work.', workordersTitle:'Workorder', workordersText:'This is the main working list. Find the required work order, open it, and complete the required tabs and records before moving on.', workordersCaption:'The first item in the Technician menu: Workorder.', trainingTitle:'Training', trainingText:'Open Training to review assigned training and its status. Complete the assigned items before working on tasks that require the qualification.', trainingCaption:'The second item in the Technician menu: Training.', techniciansTitle:'Technician', techniciansText:'The Technician section contains the technician directory and the information available for this role. Use it when you need to identify the responsible person.', techniciansCaption:'The third item in the Technician menu: Technician.', materialsTitle:'Materials', materialsText:'Use Materials to find the approved material code, material name, specification and description. Search first; do not create a duplicate material record.', materialsCaption:'The fourth item in the Technician menu: Materials.', mobileTitle:'Mobile application', mobileText:'The following screens show the real Technician interface on a phone. The hamburger button in this guide appears only when the guide itself is viewed on mobile and opens the guide contents.', mobileShotList:'Mobile: find the required workorder in the list.', mobileWorkorderTitle:'Open a workorder', mobileWorkorderText:'Open the selected workorder to see its main data, training, tasks and work sections.', mobileShotWorkorder:'Mobile: the opened workorder and its working sections.', mobilePhotosTitle:'Photos on mobile', mobilePhotosText:'Use the Photos button in the opened workorder to add images and place them in the required group.', mobileShotPhotos:'Mobile: photo groups, Add buttons and drop areas.', mobileNoteTitle:'Tip.', mobileNoteText:'Choose a section, then tap its numbered item. The contents drawer closes automatically and takes you directly to the screen.', photosTitle:'Add and group workorder photos', photosText:'Open the required work order and its Photos area. Keep the evidence organized by group so that the next technician can verify the work without guessing.', photoOne:'Open the correct work order and choose the Photos area.', photoTwo:'Add clear JPG, PNG or WEBP files; wait until every selected file appears.', photoThree:'Drag each uploaded photo into the appropriate group only after the target group is highlighted.', photoFour:'Confirm that each group contains the required evidence before leaving the work order.' },
            ru: { pageTitle:'Руководство пользователя', back:'Вернуться в систему', menu:'Содержание', contents:'Содержание', openAll:'Открыть всё', closeAll:'Свернуть всё', tocOne:'1. Начало работы', tocOneOne:'1.1 Вход', tocOneOneOne:'1.1.1 Экран входа', tocOneTwo:'1.2 Кабинет', tocTwo:'2. Главное меню', tocTwoOne:'2.1 Workorder', tocTwoTwo:'2.2 Training', tocTwoThree:'2.3 Technician', tocTwoFour:'2.4 Materials', tocThree:'3. Мобильная версия', tocThreeOne:'3.1 Меню содержания', tocFour:'4. Фото workorder', tocFourOne:'4.1 Добавление и группы фото', mobileNote:'Нажмите «Содержание», чтобы открыть навигацию на телефоне.', kicker:'Работа техника', introTitle:'Начните с правильной последовательности', introText:'Руководство повторяет меню Technician сверху вниз. Каждый реальный экран программы показан отдельно на всю ширину, чтобы были видны кнопки и детали.', loginTitle:'Вход в программу', loginText:'Откройте AVIA и введите email и пароль, выданные администратором. Используйте только свою учётную запись: доступные пункты меню зависят от роли.', loginCaption:'Синий экран входа — первый экран программы.', cabinetTitle:'Кабинет', cabinetText:'После входа открывается кабинет. В левом меню показаны только доступные роли Technician разделы.', cabinetCaption:'Перед началом работы проверьте своё имя и роль в шапке бокового меню.', workordersTitle:'Workorder', workordersText:'Основной рабочий список. Найдите нужный work order, откройте его и заполните требуемые вкладки и записи.', workordersCaption:'Первый пункт меню Technician: Workorder.', trainingTitle:'Training', trainingText:'В разделе Training проверяйте назначенное обучение и его статус. Завершите обязательные пункты до выполнения работ, требующих квалификации.', trainingCaption:'Второй пункт меню Technician: Training.', techniciansTitle:'Technician', techniciansText:'В разделе Technician доступен справочник техников и сведения, разрешённые вашей роли. Используйте его для определения ответственного сотрудника.', techniciansCaption:'Третий пункт меню Technician: Technician.', materialsTitle:'Materials', materialsText:'В Materials ищите утверждённые код, наименование, спецификацию и описание материала. Сначала используйте поиск, не создавайте дубликат.', materialsCaption:'Четвёртый пункт меню Technician: Materials.', mobileTitle:'Содержание на мобильном', mobileText:'На телефоне нажмите кнопку-гамбургер в левом верхнем углу. Откроется то же нумерованное содержание, а текст руководства останется широким и читаемым.', mobileNoteTitle:'Совет.', mobileNoteText:'Выберите раздел и нажмите его нумерованный пункт. Меню закроется и сразу переведёт к нужному экрану.', photosTitle:'Добавление и группировка фотографий workorder', photosText:'Откройте нужный work order и область Photos. Раскладывайте доказательства по группам, чтобы следующий техник мог проверить работу без догадок.', photoOne:'Откройте правильный work order и выберите область Photos.', photoTwo:'Добавьте чёткие файлы JPG, PNG или WEBP и дождитесь появления всех выбранных файлов.', photoThree:'Перетащите каждую загруженную фотографию в нужную группу только после подсветки целевой группы.', photoFour:'Перед выходом из work order убедитесь, что в каждой группе есть требуемые доказательства.' },
            uk: { pageTitle:'Посібник користувача', back:'Повернутися до системи', menu:'Зміст', contents:'Зміст', openAll:'Відкрити все', closeAll:'Згорнути все', tocOne:'1. Початок роботи', tocOneOne:'1.1 Вхід', tocOneOneOne:'1.1.1 Екран входу', tocOneTwo:'1.2 Кабінет', tocTwo:'2. Головне меню', tocTwoOne:'2.1 Workorder', tocTwoTwo:'2.2 Training', tocTwoThree:'2.3 Technician', tocTwoFour:'2.4 Materials', tocThree:'3. Мобільна версія', tocThreeOne:'3.1 Меню змісту', tocFour:'4. Фото workorder', tocFourOne:'4.1 Додавання та групи фото', mobileNote:'Натисніть «Зміст», щоб відкрити навігацію на телефоні.', kicker:'Робота техніка', introTitle:'Почніть із правильної послідовності', introText:'Посібник повторює меню Technician зверху вниз. Кожен реальний екран програми показано окремо на всю ширину, щоб були видимі кнопки й деталі.', loginTitle:'Вхід до програми', loginText:'Відкрийте AVIA та введіть email і пароль, надані адміністратором. Використовуйте лише свій обліковий запис: доступні пункти меню залежать від ролі.', loginCaption:'Синій екран входу — перший екран програми.', cabinetTitle:'Кабінет', cabinetText:'Після входу відкривається кабінет. У лівому меню показано лише розділи, доступні ролі Technician.', cabinetCaption:'Перед початком роботи перевірте своє ім’я та роль у шапці бокового меню.', workordersTitle:'Workorder', workordersText:'Основний робочий список. Знайдіть потрібний work order, відкрийте його і заповніть необхідні вкладки та записи.', workordersCaption:'Перший пункт меню Technician: Workorder.', trainingTitle:'Training', trainingText:'У Training перевіряйте призначене навчання та його статус. Завершіть обов’язкові пункти перед роботами, що вимагають кваліфікації.', trainingCaption:'Другий пункт меню Technician: Training.', techniciansTitle:'Technician', techniciansText:'У розділі Technician доступний довідник техніків та відомості, дозволені вашій ролі.', techniciansCaption:'Третій пункт меню Technician: Technician.', materialsTitle:'Materials', materialsText:'У Materials знайдіть затверджені код, назву, специфікацію та опис матеріалу. Спершу використовуйте пошук.', materialsCaption:'Четвертий пункт меню Technician: Materials.', mobileTitle:'Зміст на мобільному', mobileText:'На телефоні натисніть кнопку-гамбургер у лівому верхньому куті. Відкриється той самий нумерований зміст, а текст залишиться широким і читабельним.', mobileNoteTitle:'Порада.', mobileNoteText:'Виберіть розділ і натисніть його нумерований пункт. Меню закриється та одразу перейде до потрібного екрана.', photosTitle:'Додавання та групування фотографій workorder', photosText:'Відкрийте потрібний work order і область Photos. Розкладайте докази по групах, щоб наступний технік міг перевірити роботу без здогадок.', photoOne:'Відкрийте правильний work order і виберіть область Photos.', photoTwo:'Додайте чіткі JPG, PNG або WEBP та дочекайтеся появи всіх вибраних файлів.', photoThree:'Перетягніть кожне завантажене фото у потрібну групу лише після підсвічування цільової групи.', photoFour:'Перед виходом із work order переконайтеся, що в кожній групі є потрібні докази.' },
            he: { pageTitle:'מדריך למשתמש', back:'חזרה למערכת', menu:'תוכן', contents:'תוכן', openAll:'פתח הכול', closeAll:'כווץ הכול', tocOne:'1. התחלה', tocOneOne:'1.1 כניסה', tocOneOneOne:'1.1.1 מסך כניסה', tocOneTwo:'1.2 אזור אישי', tocTwo:'2. תפריט ראשי', tocTwoOne:'2.1 Workorder', tocTwoTwo:'2.2 Training', tocTwoThree:'2.3 Technician', tocTwoFour:'2.4 Materials', tocThree:'3. נייד', tocThreeOne:'3.1 תפריט תוכן', tocFour:'4. תמונות workorder', tocFourOne:'4.1 הוספה וקיבוץ תמונות', mobileNote:'לחצו על ״תוכן״ כדי לפתוח את הניווט בטלפון.', kicker:'תהליך עבודה לטכנאי', introTitle:'התחילו ברצף הנכון', introText:'המדריך עוקב אחר תפריט Technician מלמעלה למטה. כל מסך אמיתי מוצג בנפרד וברוחב מלא, כך שהפרטים נשארים קריאים.', loginTitle:'כניסה לתוכנית', loginText:'פתחו את AVIA והזינו את כתובת הדוא״ל והסיסמה שקיבלתם מהמנהל. השתמשו רק בחשבון שלכם: הפריטים בתפריט תלויים בתפקיד.', loginCaption:'מסך הכניסה הכחול הוא המסך הראשון בתוכנית.', cabinetTitle:'אזור אישי', cabinetText:'לאחר הכניסה נפתח האזור האישי. בסרגל הצד מוצגים רק הפריטים הזמינים לתפקיד Technician.', cabinetCaption:'לפני תחילת העבודה ודאו את שמכם ואת התפקיד בכותרת סרגל הצד.', workordersTitle:'Workorder', workordersText:'זוהי רשימת העבודה הראשית. מצאו את הזמנת העבודה, פתחו אותה והשלימו את הלשוניות והרשומות הנדרשות.', workordersCaption:'הפריט הראשון בתפריט Technician: Workorder.', trainingTitle:'Training', trainingText:'פתחו את Training כדי לבדוק הכשרות שהוקצו ואת מצבן. השלימו פריטים נדרשים לפני עבודה הדורשת הסמכה.', trainingCaption:'הפריט השני בתפריט Technician: Training.', techniciansTitle:'Technician', techniciansText:'סעיף Technician מכיל את ספר הטכנאים ואת המידע הזמין לתפקיד זה.', techniciansCaption:'הפריט השלישי בתפריט Technician: Technician.', materialsTitle:'Materials', materialsText:'ב-Materials חפשו את הקוד, השם, המפרט והתיאור המאושרים. חפשו לפני יצירת רשומה כפולה.', materialsCaption:'הפריט הרביעי בתפריט Technician: Materials.', mobileTitle:'תוכן בנייד', mobileText:'בטלפון לחצו על כפתור ההמבורגר בפינה השמאלית העליונה. אותו תוכן ממוספר ייפתח כמגירה והטקסט יישאר רחב וקריא.', mobileNoteTitle:'עצה.', mobileNoteText:'בחרו סעיף ולחצו על הפריט הממוספר. מגירת התוכן תיסגר ותעביר אתכם ישירות למסך הנכון.', photosTitle:'הוספה וקיבוץ תמונות workorder', photosText:'פתחו את הזמנת העבודה ואת אזור Photos. ארגנו את הראיות בקבוצות כדי שהטכנאי הבא יוכל לאמת את העבודה ללא ניחושים.', photoOne:'פתחו את הזמנת העבודה הנכונה ובחרו באזור Photos.', photoTwo:'הוסיפו קובצי JPG, PNG או WEBP ברורים והמתינו להופעת כל הקבצים שנבחרו.', photoThree:'גררו כל תמונה שהועלתה לקבוצה המתאימה רק לאחר שהקבוצה מודגשת.', photoFour:'לפני היציאה מה-work order ודאו שכל קבוצה כוללת את הראיות הנדרשות.' }
        };
        Object.assign(dictionaries, {
            de: Object.assign({}, dictionaries.en, { pageTitle:'Benutzerhandbuch', back:'Zurück zum System', menu:'Inhalt', contents:'Inhalt', openAll:'Alle öffnen', closeAll:'Alle schließen', tocOne:'1. Erste Schritte', tocOneOne:'1.1 Anmeldung', tocOneTwo:'1.2 Profil', tocTwo:'2. Hauptmenü', tocTwoOne:'2.1 Workorder', tocTwoTwo:'2.2 Schulung', tocTwoThree:'2.3 Techniker', tocTwoFour:'2.4 Materialien', tocThree:'3. Mobil', tocThreeOne:'3.1 Workorders', tocThreeTwo:'3.2 Workorder', tocThreeThree:'3.3 Fotos', tocFour:'4. Workorder-Fotos', tocFourOne:'4.1 Fotos hinzufügen und gruppieren', loginTitle:'Anmelden', loginCaption:'Der blaue Anmeldebildschirm ist der erste Programm-Bildschirm.', cabinetTitle:'Profil', cabinetCaption:'Prüfen Sie vor Arbeitsbeginn Namen und Rolle im Seitenmenü.', workordersCaption:'Erster Punkt im Technician-Menü: Workorder.', trainingTitle:'Schulung', trainingCaption:'Zweiter Punkt im Technician-Menü: Schulung.', techniciansTitle:'Techniker', techniciansCaption:'Dritter Punkt im Technician-Menü: Techniker.', materialsTitle:'Materialien', materialsCaption:'Vierter Punkt im Technician-Menü: Materialien.', mobileTitle:'Mobile Anwendung', mobileWorkorderTitle:'Workorder öffnen', mobilePhotosTitle:'Fotos mobil', photosTitle:'Workorder-Fotos hinzufügen und gruppieren', photoOne:'Öffnen Sie den richtigen Workorder und den Bereich Photos.', photoTwo:'Fügen Sie klare JPG-, PNG- oder WEBP-Dateien hinzu.', photoThree:'Ziehen Sie jedes Foto in die markierte Zielgruppe.', photoFour:'Prüfen Sie die erforderlichen Nachweise vor dem Verlassen.' }),
            kk: Object.assign({}, dictionaries.en, { pageTitle:'Пайдаланушы нұсқаулығы', back:'Жүйеге оралу', menu:'Мазмұны', contents:'Мазмұны', openAll:'Барлығын ашу', closeAll:'Барлығын жабу', tocOne:'1. Жұмысты бастау', tocOneOne:'1.1 Кіру', tocOneTwo:'1.2 Кабинет', tocTwo:'2. Негізгі мәзір', tocTwoOne:'2.1 Workorder', tocTwoTwo:'2.2 Оқыту', tocTwoThree:'2.3 Техник', tocTwoFour:'2.4 Материалдар', tocThree:'3. Мобильді нұсқа', tocThreeOne:'3.1 Workorders', tocThreeTwo:'3.2 Workorder', tocThreeThree:'3.3 Фотосуреттер', tocFour:'4. Workorder фотосы', tocFourOne:'4.1 Фотосуреттерді қосу және топтау', loginTitle:'Бағдарламаға кіру', loginCaption:'Көк кіру экраны — бағдарламаның бірінші экраны.', cabinetTitle:'Кабинет', cabinetCaption:'Жұмысты бастамас бұрын атыңыз бен рөліңізді тексеріңіз.', workordersCaption:'Technician мәзіріндегі бірінші бөлім: Workorder.', trainingTitle:'Оқыту', trainingCaption:'Technician мәзіріндегі екінші бөлім: Оқыту.', techniciansTitle:'Техник', techniciansCaption:'Technician мәзіріндегі үшінші бөлім: Техник.', materialsTitle:'Материалдар', materialsCaption:'Technician мәзіріндегі төртінші бөлім: Материалдар.', mobileTitle:'Мобильді қосымша', mobileWorkorderTitle:'Workorder ашу', mobilePhotosTitle:'Мобильді фотосуреттер', photosTitle:'Workorder фотосуреттерін қосу және топтау', photoOne:'Дұрыс workorder мен Photos бөлімін ашыңыз.', photoTwo:'Анық JPG, PNG немесе WEBP файлдарын қосыңыз.', photoThree:'Әр фотоны белгіленген топқа жылжытыңыз.', photoFour:'Шығудан бұрын қажетті дәлелдерді тексеріңіз.' }),
            be: Object.assign({}, dictionaries.en, { pageTitle:'Кіраўніцтва карыстальніка', back:'Вярнуцца ў сістэму', menu:'Змест', contents:'Змест', openAll:'Адкрыць усё', closeAll:'Згарнуць усё', tocOne:'1. Пачатак працы', tocOneOne:'1.1 Уваход', tocOneTwo:'1.2 Кабінет', tocTwo:'2. Галоўнае меню', tocTwoOne:'2.1 Workorder', tocTwoTwo:'2.2 Навучанне', tocTwoThree:'2.3 Тэхнік', tocTwoFour:'2.4 Матэрыялы', tocThree:'3. Мабільная версія', tocThreeOne:'3.1 Workorders', tocThreeTwo:'3.2 Workorder', tocThreeThree:'3.3 Фатаграфіі', tocFour:'4. Фота workorder', tocFourOne:'4.1 Дадаванне і групы фота', loginTitle:'Уваход у праграму', loginCaption:'Сіні экран уваходу — першы экран праграмы.', cabinetTitle:'Кабінет', cabinetCaption:'Перад пачаткам праверце імя і ролю ў бакавым меню.', workordersCaption:'Першы пункт меню Technician: Workorder.', trainingTitle:'Навучанне', trainingCaption:'Другі пункт меню Technician: Навучанне.', techniciansTitle:'Тэхнік', techniciansCaption:'Трэці пункт меню Technician: Тэхнік.', materialsTitle:'Матэрыялы', materialsCaption:'Чацвёрты пункт меню Technician: Матэрыялы.', mobileTitle:'Мабільнае прыкладанне', mobileWorkorderTitle:'Адкрыццё workorder', mobilePhotosTitle:'Фота на mobile', photosTitle:'Дадаванне і групаванне фота workorder', photoOne:'Адкрыйце патрэбны workorder і раздзел Photos.', photoTwo:'Дадайце выразныя файлы JPG, PNG або WEBP.', photoThree:'Перацягніце фота ў выдзеленую патрэбную групу.', photoFour:'Праверце неабходныя доказы перад выхадам.' }),
        });
        const mobileApplicationTranslations = {
            en: {},
            ru: { tocThreeOne:'3.1 Список workorder', tocThreeTwo:'3.2 Workorder', tocThreeThree:'3.3 Фотографии', mobileTitle:'Мобильное приложение', mobileText:'Ниже показаны реальные экраны интерфейса Technician на телефоне. Кнопка-гамбургер в этом руководстве появляется только при просмотре самого руководства на мобильном устройстве и открывает его содержание.', mobileShotList:'Mobile: найдите нужный workorder в списке.', mobileWorkorderTitle:'Открытие workorder', mobileWorkorderText:'Откройте выбранный workorder, чтобы увидеть основные данные, обучение, задачи и рабочие разделы.', mobileShotWorkorder:'Mobile: открытый workorder и его рабочие разделы.', mobilePhotosTitle:'Фотографии на mobile', mobilePhotosText:'Нажмите Photos в открытом workorder, чтобы добавить изображения и разложить их по нужным группам.', mobileShotPhotos:'Mobile: группы фотографий, кнопки Add и области для загрузки.' },
            uk: { tocThreeOne:'3.1 Список workorder', tocThreeTwo:'3.2 Workorder', tocThreeThree:'3.3 Фотографії', mobileTitle:'Мобільний застосунок', mobileText:'Нижче показано реальні екрани інтерфейсу Technician на телефоні. Кнопка-гамбургер у цьому посібнику з’являється лише під час перегляду самого посібника на мобільному пристрої та відкриває його зміст.', mobileShotList:'Mobile: знайдіть потрібний workorder у списку.', mobileWorkorderTitle:'Відкриття workorder', mobileWorkorderText:'Відкрийте вибраний workorder, щоб побачити основні дані, навчання, завдання та робочі розділи.', mobileShotWorkorder:'Mobile: відкритий workorder та його робочі розділи.', mobilePhotosTitle:'Фотографії на mobile', mobilePhotosText:'Натисніть Photos у відкритому workorder, щоб додати зображення та розкласти їх по потрібних групах.', mobileShotPhotos:'Mobile: групи фотографій, кнопки Add і області для завантаження.' },
            he: { tocThreeOne:'3.1 רשימת workorder', tocThreeTwo:'3.2 Workorder', tocThreeThree:'3.3 תמונות', mobileTitle:'יישום נייד', mobileText:'להלן מסכים אמיתיים של ממשק Technician בטלפון. כפתור ההמבורגר במדריך זה מופיע רק כאשר המדריך עצמו נצפה במכשיר נייד ופותח את התוכן שלו.', mobileShotList:'בנייד: מצאו את הזמנת העבודה הנדרשת ברשימה.', mobileWorkorderTitle:'פתיחת workorder', mobileWorkorderText:'פתחו את הזמנת העבודה שנבחרה כדי לראות את הנתונים, ההדרכה, המשימות ואזורי העבודה.', mobileShotWorkorder:'בנייד: הזמנת עבודה פתוחה והאזורים שלה.', mobilePhotosTitle:'תמונות בנייד', mobilePhotosText:'לחצו על Photos בהזמנת העבודה הפתוחה כדי להוסיף תמונות ולמקם אותן בקבוצות הנדרשות.', mobileShotPhotos:'בנייד: קבוצות תמונות, לחצני Add ואזורי העלאה.' },
        };
        const compactDescriptions = {
            en: { loginText:'Sign in with your assigned account.', cabinetText:'Check your role and menu.', workordersText:'Find and open the work order.', trainingText:'Check assigned training.', techniciansText:'Find the responsible technician.', materialsText:'Find the approved material.', mobileText:'Technician screens on a phone.', mobileWorkorderText:'Open the work order details.', mobilePhotosText:'Add photos to the required group.', photosText:'Add and group workorder evidence.' },
            ru: { loginText:'Войдите по выданной учётной записи.', cabinetText:'Проверьте роль и меню.', workordersText:'Найдите и откройте work order.', trainingText:'Проверьте назначенное обучение.', techniciansText:'Найдите ответственного техника.', materialsText:'Найдите утверждённый материал.', mobileText:'Экраны Technician на телефоне.', mobileWorkorderText:'Откройте данные workorder.', mobilePhotosText:'Добавляйте фото в нужную группу.', photosText:'Добавляйте и группируйте доказательства.' },
            uk: { loginText:'Увійдіть за виданим обліковим записом.', cabinetText:'Перевірте роль і меню.', workordersText:'Знайдіть і відкрийте work order.', trainingText:'Перевірте призначене навчання.', techniciansText:'Знайдіть відповідального техніка.', materialsText:'Знайдіть затверджений матеріал.', mobileText:'Екрани Technician на телефоні.', mobileWorkorderText:'Відкрийте дані workorder.', mobilePhotosText:'Додавайте фото до потрібної групи.', photosText:'Додавайте та групуйте докази.' },
            he: { loginText:'היכנסו עם החשבון שהוקצה לכם.', cabinetText:'בדקו את התפקיד והתפריט.', workordersText:'מצאו ופתחו את הזמנת העבודה.', trainingText:'בדקו הדרכות שהוקצו.', techniciansText:'מצאו את הטכנאי האחראי.', materialsText:'מצאו את החומר המאושר.', mobileText:'מסכי Technician בטלפון.', mobileWorkorderText:'פתחו את פרטי הזמנת העבודה.', mobilePhotosText:'הוסיפו תמונות לקבוצה הנדרשת.', photosText:'הוסיפו וקבצו ראיות.' },
            de: { loginText:'Mit zugewiesenem Konto anmelden.', cabinetText:'Rolle und Menü prüfen.', workordersText:'Workorder finden und öffnen.', trainingText:'Zugewiesene Schulung prüfen.', techniciansText:'Verantwortlichen Techniker finden.', materialsText:'Freigegebenes Material finden.', mobileText:'Technician-Bildschirme am Telefon.', mobileWorkorderText:'Workorder-Details öffnen.', mobilePhotosText:'Fotos zur erforderlichen Gruppe hinzufügen.', photosText:'Workorder-Nachweise hinzufügen und gruppieren.' },
            kk: { loginText:'Берілген тіркелгімен кіріңіз.', cabinetText:'Рөл мен мәзірді тексеріңіз.', workordersText:'Work order тауып, ашыңыз.', trainingText:'Тағайындалған оқытуды тексеріңіз.', techniciansText:'Жауапты техникті табыңыз.', materialsText:'Бекітілген материалды табыңыз.', mobileText:'Телефондағы Technician экрандары.', mobileWorkorderText:'Workorder деректерін ашыңыз.', mobilePhotosText:'Фотоны қажетті топқа қосыңыз.', photosText:'Дәлелдерді қосып, топтаңыз.' },
            be: { loginText:'Увайдзіце з выдадзеным уліковым запісам.', cabinetText:'Праверце ролю і меню.', workordersText:'Знайдзіце і адкрыйце work order.', trainingText:'Праверце прызначанае навучанне.', techniciansText:'Знайдзіце адказнага тэхніка.', materialsText:'Знайдзіце зацверджаны матэрыял.', mobileText:'Экраны Technician на тэлефоне.', mobileWorkorderText:'Адкрыйце даныя workorder.', mobilePhotosText:'Дадайце фота ў патрэбную групу.', photosText:'Дадавайце і групуйце доказы.' },
        };
        const workorderGuideTranslations = {
            en: { tocTwoOneOne:'2.1.1 Main', tocTwoOneTwo:'2.1.2 TDR', tocTwoOneThree:'2.1.3 Pictures', workordersText:'Explore Workorders step by step.', workordersCaption:'Click or press Space to move through the filters.', workorderMainTitle:'Main', workorderMainText:'Review the selected workorder.', workorderMainCaption:'Main of the selected Technician workorder: status, component and work sections.', tdrTitle:'TDR', tdrText:'Review the TDR report for the selected workorder.', tdrCaption:'TDR report for training workorder 100000.', tdrUnavailable:'The TDR report opens here for the training workorder.', picturesTitle:'Pictures', picturesText:'Review photos of the selected workorder.', picturesCaption:'Pictures of training workorder 100000.', livePageUnavailable:'This page opens here for the training workorder.', filterStart:'Click or press Space to start.', filterActive:'Blue workorders are active.', filterClosed:'Grey numbers are closed.', filterActiveFilter:'WO active hides closed workorders.', filterApproved:'Green check: approved. Hover for details.', filterRejected:'Grey cross: not approved.', filterApprovedFilter:'Approved hides unapproved workorders.', filterMineFilter:'My workorders shows all Vasyl Medvid workorders.', filterMineActiveFilter:'My workorders + WO active leaves Vasyl Medvid active workorders.', autoAnimation:'Animations play automatically', manualAnimation:'Step through animations with click or Space', autoAnimationLabel:'Auto', manualAnimationLabel:'Step' },
            ru: { tocTwoOneOne:'2.1.1 Main', tocTwoOneTwo:'2.1.2 TDR', tocTwoOneThree:'2.1.3 Pictures', workordersText:'Изучите Workorders по шагам.', workordersCaption:'Кликните мышью или нажмите Пробел для следующего шага.', workorderMainTitle:'Main', workorderMainText:'Проверьте выбранный workorder.', workorderMainCaption:'Main выбранного workorder техника: статус, компонент и рабочие разделы.', tdrTitle:'TDR', tdrText:'Проверьте TDR Report выбранного workorder.', tdrCaption:'TDR Report учебного workorder 100000.', tdrUnavailable:'TDR Report учебного workorder откроется здесь.', picturesTitle:'Pictures', picturesText:'Проверьте фотографии выбранного workorder.', picturesCaption:'Фотографии учебного workorder 100000.', livePageUnavailable:'Эта страница откроется здесь для учебного workorder.', filterStart:'Кликните мышью или нажмите Пробел для начала.', filterActive:'Синие workorders — активные в работе.', filterClosed:'Серые номера — закрытые workorders.', filterActiveFilter:'WO active скрывает закрытые workorders.', filterApproved:'Зелёная галочка — Approved. Наведите для деталей.', filterRejected:'Серый крестик — не Approved.', filterApprovedFilter:'Approved скрывает workorders без approval.', filterMineFilter:'My workorders показывает все workorders Vasyl Medvid.', filterMineActiveFilter:'My workorders + WO active оставляет активные workorders Vasyl Medvid.', autoAnimation:'Анимации проигрываются автоматически', manualAnimation:'Кадры: клик мышью или Пробел', autoAnimationLabel:'Авто', manualAnimationLabel:'Шаги' },
        };
        workorderGuideTranslations.en.workordersText = 'Use the filters, then open a workorder number.';
        workorderGuideTranslations.en.workordersCaption = 'Select any workorder number to open Main.';
        workorderGuideTranslations.ru.workordersText = '\u0418\u0441\u043f\u043e\u043b\u044c\u0437\u0443\u0439\u0442\u0435 \u0444\u0438\u043b\u044c\u0442\u0440\u044b, \u0437\u0430\u0442\u0435\u043c \u043e\u0442\u043a\u0440\u043e\u0439\u0442\u0435 \u043d\u043e\u043c\u0435\u0440 workorder.';
        workorderGuideTranslations.ru.workordersCaption = '\u0412\u044b\u0431\u0435\u0440\u0438\u0442\u0435 \u043b\u044e\u0431\u043e\u0439 \u043d\u043e\u043c\u0435\u0440 workorder, \u0447\u0442\u043e\u0431\u044b \u043e\u0442\u043a\u0440\u044b\u0442\u044c Main.';
        const guideUiLabels = { en: { previous: 'Previous', next: 'Next' }, ru: { previous: 'Предыдущая', next: 'Далее' }, uk: { previous: 'Попередня', next: 'Далі' }, he: { previous: 'הקודם', next: 'הבא' }, de: { previous: 'Zurück', next: 'Weiter' }, kk: { previous: 'Алдыңғы', next: 'Келесі' }, be: { previous: 'Папярэдняя', next: 'Далей' } };
        let language = 'en';
        let keepAllTocSectionsOpen = false;
        let synchronizingToc = false;
        let tocStateLoaded = false;
        let guidePositionStateLoaded = false;
        let restoringGuidePosition = true;
        let workorderInteractiveStateLoaded = false;
        let guideScrollSaveTimer = null;
        const mainDemoInitialStatePromises = [];
        const workorderInteractiveState = { active:false, approved:false, mine:false, selected:null };
        const updateInteractiveWorkorders = (persist = true) => {
            if (!workordersStage) return;
            workordersStage.classList.toggle('is-user-active', workorderInteractiveState.active);
            workordersStage.classList.toggle('is-user-approved', workorderInteractiveState.approved);
            workordersStage.classList.toggle('is-user-mine', workorderInteractiveState.mine);
            workordersStage.querySelectorAll('.guide-html-row:not(.guide-html-row-head)').forEach((row) => {
                row.classList.toggle('is-user-selected', row.querySelector('.guide-html-number')?.textContent.trim() === workorderInteractiveState.selected);
            });
            workordersStage.querySelector('.guide-html-filter-active')?.setAttribute('aria-pressed', String(workorderInteractiveState.active));
            workordersStage.querySelector('.guide-html-filter-approved')?.setAttribute('aria-pressed', String(workorderInteractiveState.approved));
            workordersStage.querySelector('.guide-html-filter-mine')?.setAttribute('aria-pressed', String(workorderInteractiveState.mine));
            if (persist && workorderInteractiveStateLoaded) window.UserUiSettings.set(scope, 'workorder-interactive-state', workorderInteractiveState);
        };
        if (workordersStage) {
            workordersStage.removeAttribute('data-workorder-step');
            workordersStage.querySelector('.guide-html-step-note')?.remove();
            workordersStage.querySelector('.guide-workorders-cursor')?.remove();
            const guideWorkorderRows = [
                { number:'w 107820', component:'2225-0014', description:'Cylinder Tube', serial:'HM00920', customer:'Liebherr Aerospace', technik:'Richard Aigbovia', active:true, approved:true },
                { number:'w 107818', component:'2225-0014', description:'Cylinder Tube', serial:'HM00887', customer:'Liebherr Aerospace', technik:'Oleksii Sharapov', active:true, approved:false },
                { number:'w 107817', component:'2225-0014', description:'Cylinder Tube', serial:'HM00892', customer:'Liebherr Aerospace', technik:'Dmytro Suhako', active:true, approved:true },
                { number:'w 107813', component:'2821A2600-01', description:'Bolt', serial:'1409', customer:'Liebherr Aerospace', technik:'Dmytro Suhako', active:false, approved:false },
                { number:'107870', component:'MLG-SS-01', description:'MLG Shock Strut Assy', serial:'—', customer:'Regional One', technik:'Vasyl Medvid', active:true, approved:false, mine:true },
                { number:'107840', component:'SSB-01', description:'Side Stay Bolt, Assy', serial:'—', customer:'Regional One', technik:'Vasyl Medvid', active:true, approved:true, mine:true },
                { number:'107839', component:'SSB-02', description:'Side Stay Bolt, Assy', serial:'—', customer:'Regional One', technik:'Vasyl Medvid', active:true, approved:true, mine:true },
                { number:'107835', component:'SSB-03', description:'Side Stay Bolt, Assy', serial:'—', customer:'Regional One', technik:'Vasyl Medvid', active:false, approved:true, mine:true },
                { number:'107826', component:'SLT-01', description:'Sliding Tube', serial:'—', customer:'Regional One', technik:'Vasyl Medvid', active:true, approved:false, mine:true },
                { number:'107812', component:'49101-11', description:'Trunnion Pin, FWD', serial:'SPP230767', customer:'Jazz Aviation LP', technik:'Vasyl Medvid', active:false, approved:false, mine:true },
                { number:'107811', component:'49131-3', description:'Pin', serial:'SD061569', customer:'Jazz Aviation LP', technik:'Vasyl Medvid', active:false, approved:false, mine:true },
                { number:'107810', component:'49131-3', description:'Pin', serial:'SD061568', customer:'Jazz Aviation LP', technik:'Vasyl Medvid', active:false, approved:false, mine:true },
                { number:'107809', component:'49101-13', description:'Trunnion Pin, FWD', serial:'SPP013540', customer:'Jazz Aviation LP', technik:'Vasyl Medvid', active:true, approved:true, mine:true },
                { number:'107790', component:'—', description:'—', serial:'—', customer:'Regional One', technik:'Vasyl Medvid', active:true, approved:true, mine:true },
                { number:'107770', component:'MLG-SS-02', description:'MLG Side Stay', serial:'—', customer:'Regional One', technik:'Vasyl Medvid', active:true, approved:true, mine:true },
                { number:'107742', component:'SLT-02', description:'Sliding Tube', serial:'—', customer:'Regional One', technik:'Vasyl Medvid', active:false, approved:true, mine:true },
                { number:'107736', component:'2801A0000-03', description:'MLG Shock Strut', serial:'00220', customer:'Regional One', technik:'Vasyl Medvid', active:true, approved:true, mine:true },
                { number:'106985', component:'SS-985', description:'Shock Strut Assembly, Dressed', serial:'—', customer:'Regional One', technik:'Vasyl Medvid', active:true, approved:true, mine:true },
            ];
            const guideWorkordersTable = workordersStage.querySelector('.guide-html-table');
            if (guideWorkordersTable) {
                const header = '<div class="guide-html-row guide-html-row-head" role="row"><span>Number ↓</span><span>Approve</span><span>EC</span><span>Stages</span><span>Component</span><span>Description</span><span>Serial No.</span><span>Manual</span><span>Customer</span><span>Technik</span></div>';
                guideWorkordersTable.innerHTML = header + guideWorkorderRows.map((row, index) => {
                    const stateClass = row.active ? 'is-active' : 'is-closed';
                    const mineClass = row.mine ? (row.active ? 'is-mine' : 'is-mine is-inactive-mine') : 'is-not-mine';
                    const approval = row.approved ? '<span class="guide-approve-mark" data-tooltip="Approved by Manager">✓</span>' : '<span class="guide-reject-mark">×</span>';
                    return `<div class="guide-html-row ${stateClass} ${mineClass} ${row.approved ? '' : 'is-unapproved'}" style="--collapse-order:${index}"><span class="guide-html-number ${row.active ? 'is-active' : ''}">${row.number}</span>${approval}<span>${row.active ? 'EC' : '×'}</span><span>■■□□□</span><span>${row.component}</span><span>${row.description}</span><span>${row.serial}</span><span>32-11-06</span><span>${row.customer}</span><span>${row.technik}</span></div>`;
                }).join('');
            }
            const workorderFilters = workordersStage.querySelectorAll('.guide-html-filter');
            workorderFilters[1]?.classList.add('guide-html-filter-mine');
            workordersStage.querySelectorAll('.guide-html-row:not(.guide-html-row-head)').forEach((row) => {
                if (!row.lastElementChild?.textContent.includes('Vasyl Medvid')) row.classList.add('is-not-mine');
            });
            workordersStage.querySelectorAll('.guide-html-filter-active, .guide-html-filter-mine, .guide-html-filter-approved').forEach((filter) => {
                filter.tabIndex = 0;
                filter.setAttribute('role', 'button');
            });
            workordersStage.querySelectorAll('.guide-html-row:not(.guide-html-row-head)').forEach((row) => {
                row.tabIndex = 0;
                row.setAttribute('aria-label', `Select ${row.querySelector('.guide-html-number')?.textContent.trim() || 'workorder'}`);
                const number = row.querySelector('.guide-html-number');
                if (number) {
                    number.tabIndex = 0;
                    number.setAttribute('role', 'button');
                    number.setAttribute('aria-label', `Open workorder ${number.textContent.trim()}`);
                }
            });
            const activateWorkorderControl = (target) => {
                const filter = target.closest('.guide-html-filter-active, .guide-html-filter-mine, .guide-html-filter-approved');
                if (filter) {
                    if (filter.classList.contains('guide-html-filter-active')) workorderInteractiveState.active = !workorderInteractiveState.active;
                    if (filter.classList.contains('guide-html-filter-mine')) workorderInteractiveState.mine = !workorderInteractiveState.mine;
                    if (filter.classList.contains('guide-html-filter-approved')) workorderInteractiveState.approved = !workorderInteractiveState.approved;
                    updateInteractiveWorkorders();
                    return true;
                }
                const workorderNumber = target.closest('.guide-html-number');
                if (workorderNumber) {
                    workorderInteractiveState.selected = workorderNumber.textContent.trim();
                    updateInteractiveWorkorders();
                    const mainSection = document.getElementById('workorder-main');
                    if (mainSection) {
                        setCurrentTocLink('#workorder-main');
                        scrollToGuideSection(mainSection);
                    }
                    return true;
                }
                const row = target.closest('.guide-html-row:not(.guide-html-row-head)');
                if (row) {
                    workorderInteractiveState.selected = row.querySelector('.guide-html-number')?.textContent.trim() || null;
                    updateInteractiveWorkorders();
                    return true;
                }
                return false;
            };
            workordersStage.addEventListener('click', (event) => { activateWorkorderControl(event.target); });
            workordersStage.addEventListener('keydown', (event) => {
                if ((event.key === 'Enter' || event.key === ' ') && activateWorkorderControl(event.target)) event.preventDefault();
            });
        }
        const mainDemo = document.querySelector('[data-main-demo]');
        if (mainDemo) {
            const liveMain = document.createElement('div');
            liveMain.className = 'guide-live-main';
            if (@json($canUseLiveTrainingWorkorder ?? false)) {
                const frame = document.createElement('iframe');
                frame.src = @json(route('admin.user-guide.workorder-main'));
                frame.title = 'Workorder 100000 — Technician Main';
                liveMain.append(frame);
            } else {
                liveMain.innerHTML = '<p class="guide-live-main-message">Workorder 100000 opens here for its assigned Technician.</p>';
            }
            mainDemo.replaceWith(liveMain);
            document.querySelector('#workorder-main figcaption')?.replaceChildren(
                document.createTextNode('Main of training workorder 100000.')
            );
        }
        if (mainDemo && mainDemo.isConnected) {
            const mainDemoHeader = mainDemo.querySelector('.guide-main-demo-header');
            if (!mainDemoHeader?.querySelector('.guide-main-demo-paper')) {
                const mainDemoPaper = document.createElement('div');
                mainDemoPaper.className = 'guide-main-demo-paper';
                mainDemoPaper.innerHTML = '<img src="{{ asset('img/icons/workorder_paper.png') }}" alt="Workorder paper">';
                mainDemoHeader?.prepend(mainDemoPaper);
            }
            if (!mainDemoHeader?.querySelector('[data-main-demo-open]')) {
                const mainDemoActions = document.createElement('div');
                mainDemoActions.className = 'guide-main-demo-actions';
                mainDemoActions.innerHTML = '<button class="guide-main-demo-action" type="button" data-main-demo-open="photos"><i class="bi bi-images"></i> Photos</button><button class="guide-main-demo-action" type="button" data-main-demo-open="tdr"><i class="bi bi-clipboard-check"></i> TDR</button><button class="guide-main-demo-action" type="button" data-main-demo-open="parts"><i class="bi bi-wrench-adjustable"></i> Parts</button>';
                mainDemoHeader?.insertBefore(mainDemoActions, mainDemoHeader.querySelector('.guide-main-demo-training'));
            }
            const mainDemoTabs = mainDemo.querySelector('.guide-main-demo-tabs');
            const mainDemoBody = mainDemo.querySelector('.guide-main-demo-body');
            const addMainDemoPane = (tab, label, markup) => {
                const button = document.createElement('button');
                button.className = 'guide-main-demo-tab';
                button.type = 'button';
                button.dataset.mainDemoTab = tab;
                button.setAttribute('role', 'tab');
                button.setAttribute('aria-selected', 'false');
                button.textContent = label;
                mainDemoTabs?.append(button);
                const pane = document.createElement('section');
                pane.className = 'guide-main-demo-pane guide-main-demo-single';
                pane.dataset.mainDemoPane = tab;
                pane.innerHTML = markup;
                mainDemoBody?.append(pane);
            };
            const partsPane = mainDemo.querySelector('[data-main-demo-pane="parts"]');
            if (partsPane) partsPane.innerHTML = '<div class="guide-main-demo-card guide-main-demo-form-card"><h4>Parts &amp; Repair Processes — technician tasks</h4><div class="guide-main-demo-task-table"><div class="guide-main-demo-task-row is-head"><span></span><span>Task</span><span>Start</span><span>Finish</span><span>Status</span></div><label class="guide-main-demo-task-row"><input type="checkbox" data-main-demo-field="part-machining-done"><span>Machining — BRACKET</span><input type="text" data-main-demo-field="part-machining-start" value="18/Jun/2026"><input type="text" data-main-demo-field="part-machining-finish" value="23/Jun/2026"><select data-main-demo-field="part-machining-status"><option>In progress</option><option>Complete</option></select></label><label class="guide-main-demo-task-row"><input type="checkbox" data-main-demo-field="part-ndt-done"><span>NDT-1 — BRACKET</span><input type="text" data-main-demo-field="part-ndt-start" value="23/Jun/2026"><input type="text" data-main-demo-field="part-ndt-finish" value="30/Jun/2026"><select data-main-demo-field="part-ndt-status"><option>In progress</option><option>Complete</option></select></label><label class="guide-main-demo-task-row"><input type="checkbox" data-main-demo-field="part-paint-done"><span>Paint list — MLG Shock Strut</span><input type="text" data-main-demo-field="part-paint-start" value="19/Jun/2026"><input type="text" data-main-demo-field="part-paint-finish" value="26/Jun/2026"><select data-main-demo-field="part-paint-status"><option>In progress</option><option>Complete</option></select></label></div><p class="guide-main-demo-task-hint">Only technician tasks are editable in this training copy. Changes stay in this demo and never reach the live workorder.</p></div>';
            addMainDemoPane('photos', 'Photos', '<div class="guide-main-demo-card guide-main-demo-form-card"><h4>Photos — W107736</h4><div class="guide-main-demo-photos"><div class="guide-main-demo-photo-group"><h4>Before repair</h4><p data-photo-count="before">No files selected.</p><input type="file" multiple accept="image/png,image/jpeg,image/webp" data-photo-input="before"></div><div class="guide-main-demo-photo-group"><h4>Repair process</h4><p data-photo-count="repair">No files selected.</p><input type="file" multiple accept="image/png,image/jpeg,image/webp" data-photo-input="repair"></div><div class="guide-main-demo-photo-group"><h4>Final inspection</h4><p data-photo-count="final">No files selected.</p><input type="file" multiple accept="image/png,image/jpeg,image/webp" data-photo-input="final"></div></div><p class="guide-main-demo-task-hint">Choose JPG, PNG or WEBP files. The training copy only shows the selected-file count.</p></div>');
            addMainDemoPane('tdr', 'TDR', '<div class="guide-main-demo-card guide-main-demo-form-card"><h4>TDR — technician record</h4><div class="guide-main-demo-tdr"><label>TDR number</label><input type="text" data-main-demo-field="tdr-number" value="TDR-107736-01"><label>Process</label><select data-main-demo-field="tdr-process"><option>Disassembly</option><option>Machining</option><option>Final inspection</option></select><label>Due date</label><input type="text" data-main-demo-field="tdr-due-date" value="26/Jun/2026"><label>Status</label><select data-main-demo-field="tdr-status"><option>Open</option><option>Submitted</option><option>Complete</option></select></div><textarea class="guide-main-demo-note" data-main-demo-field="tdr-note" aria-label="TDR note" placeholder="Add TDR note..."></textarea></div>');
            const setMainDemoTab = (tab, persist = true) => {
                const target = mainDemo.querySelector(`[data-main-demo-pane="${tab}"]`) ? tab : 'all';
                mainDemo.querySelectorAll('[data-main-demo-tab]').forEach((button) => {
                    const active = button.dataset.mainDemoTab === target;
                    button.classList.toggle('is-active', active);
                    button.setAttribute('aria-selected', String(active));
                });
                mainDemo.querySelectorAll('[data-main-demo-pane]').forEach((pane) => pane.classList.toggle('is-active', pane.dataset.mainDemoPane === target));
                if (persist) window.UserUiSettings.set(scope, 'main-demo-tab', target);
            };
            mainDemo.querySelectorAll('[data-main-demo-tab]').forEach((button) => button.addEventListener('click', () => setMainDemoTab(button.dataset.mainDemoTab)));
            mainDemo.querySelectorAll('[data-main-demo-open]').forEach((button) => button.addEventListener('click', () => setMainDemoTab(button.dataset.mainDemoOpen)));
            mainDemo.querySelectorAll('.guide-main-demo-note').forEach((field, index) => {
                if (!field.dataset.mainDemoField) field.dataset.mainDemoField = `workorder-note-${index + 1}`;
            });
            mainDemo.querySelectorAll('.guide-main-demo-status').forEach((button) => button.addEventListener('click', () => {
                mainDemo.querySelectorAll('.guide-main-demo-status').forEach((status) => status.classList.remove('is-active'));
                button.classList.add('is-active');
                window.UserUiSettings.set(scope, 'main-demo-status', button.textContent.trim());
            }));
            mainDemo.querySelectorAll('.guide-main-demo-accordion button').forEach((button) => button.addEventListener('click', () => {
                button.setAttribute('aria-expanded', String(button.getAttribute('aria-expanded') !== 'true'));
            }));
            const saveMainDemoDraft = () => {
                const draft = {};
                mainDemo.querySelectorAll('[data-main-demo-field]').forEach((field) => { draft[field.dataset.mainDemoField] = field.type === 'checkbox' ? field.checked : field.value; });
                window.UserUiSettings.set(scope, 'main-demo-draft', draft);
            };
            mainDemo.querySelectorAll('[data-main-demo-field]').forEach((field) => {
                field.addEventListener('input', saveMainDemoDraft);
                field.addEventListener('change', saveMainDemoDraft);
            });
            mainDemo.querySelectorAll('[data-photo-input]').forEach((input) => input.addEventListener('change', () => {
                const count = input.files?.length || 0;
                const label = mainDemo.querySelector(`[data-photo-count="${input.dataset.photoInput}"]`);
                if (label) label.textContent = count ? `${count} file(s) selected.` : 'No files selected.';
            }));
            mainDemoInitialStatePromises.push(window.UserUiSettings.get(scope, 'main-demo-draft', {}).then((draft) => {
                if (!draft || typeof draft !== 'object') return;
                mainDemo.querySelectorAll('[data-main-demo-field]').forEach((field) => {
                    if (!Object.prototype.hasOwnProperty.call(draft, field.dataset.mainDemoField)) return;
                    if (field.type === 'checkbox') field.checked = Boolean(draft[field.dataset.mainDemoField]);
                    else field.value = draft[field.dataset.mainDemoField];
                });
            }));
            mainDemoInitialStatePromises.push(window.UserUiSettings.get(scope, 'main-demo-tab', 'all').then((savedTab) => setMainDemoTab(savedTab, false)));
            mainDemoInitialStatePromises.push(window.UserUiSettings.get(scope, 'main-demo-status', 'Disassembly').then((savedStatus) => {
                mainDemo.querySelectorAll('.guide-main-demo-status').forEach((button) => button.classList.toggle('is-active', button.textContent.trim() === savedStatus));
            }));
        }
        const persistTocState = () => {
            if (!tocStateLoaded) return;
            window.UserUiSettings.set(scope, 'open-sections', details.map((detail) => detail.open));
            window.UserUiSettings.set(scope, 'toc-open-all', keepAllTocSectionsOpen);
        };
        const updateOpenAllLabel = () => { openAllButton.textContent = keepAllTocSectionsOpen ? dictionaries[language].closeAll : dictionaries[language].openAll; };
        const setLanguage = (selected, persist) => { const isMobileGuide = window.matchMedia('(max-width: 620px)').matches; language = isMobileGuide && !['en', 'ru'].includes(selected) ? 'en' : (dictionaries[selected] ? selected : 'en'); const translation = Object.assign({}, dictionaries[language], mobileApplicationTranslations[language], compactDescriptions[language], workorderGuideTranslations.en, workorderGuideTranslations[language], guideUiLabels[language]); document.documentElement.lang = language; document.documentElement.dir = language === 'he' ? 'rtl' : 'ltr'; document.querySelectorAll('[data-guide]').forEach((element) => { const value = translation[element.dataset.guide]; if (value) element.textContent = value; }); document.querySelectorAll('[data-language]').forEach((button) => button.classList.toggle('is-active', button.dataset.language === language)); updateOpenAllLabel(); if (persist) window.UserUiSettings.set(scope, 'language', language); };
        window.matchMedia('(max-width: 620px)').addEventListener('change', (event) => { if (event.matches && !['en', 'ru'].includes(language)) setLanguage('en', false); });
        document.querySelectorAll('[data-language]').forEach((button) => button.addEventListener('click', () => setLanguage(button.dataset.language, true)));
        details.forEach((item) => item.addEventListener('toggle', () => {
            if (!synchronizingToc) keepAllTocSectionsOpen = details.every((detail) => detail.open);
            updateOpenAllLabel();
            persistTocState();
        }));
        openAllButton.addEventListener('click', () => {
            keepAllTocSectionsOpen = !keepAllTocSectionsOpen;
            synchronizingToc = true;
            details.forEach((item) => { item.open = keepAllTocSectionsOpen; });
            synchronizingToc = false;
            updateOpenAllLabel();
            persistTocState();
        });
        const setTocOpen = (open) => { guide.classList.toggle('is-toc-open', open); toggle.setAttribute('aria-expanded', String(open)); };
        toggle.addEventListener('click', () => setTocOpen(!guide.classList.contains('is-toc-open')));
        document.addEventListener('click', (event) => { if (guide.classList.contains('is-toc-open') && !toc.contains(event.target) && !toggle.contains(event.target)) setTocOpen(false); });
        const tocLinks = Array.from(toc.querySelectorAll('a'));
        const centerCurrentGuideSection = new URLSearchParams(window.location.search).get('center') === '1';
        let currentSectionHash = '#login-screen';
        const setCurrentTocLink = (hash) => {
            currentSectionHash = hash;
            if (guidePositionStateLoaded) window.UserUiSettings.set(scope, 'last-section', currentSectionHash);
            tocLinks.forEach((link) => link.classList.toggle('is-current', link.getAttribute('href') === hash));
            const activeDetail = tocLinks.find((link) => link.getAttribute('href') === hash)?.closest('details');
            if (activeDetail && !keepAllTocSectionsOpen) {
                synchronizingToc = true;
                details.forEach((detail) => { detail.open = detail === activeDetail; });
                synchronizingToc = false;
                updateOpenAllLabel();
                persistTocState();
            }
            updateNavigationButtons();
        };
        tocLinks.forEach((link) => link.addEventListener('click', () => { setTocOpen(false); setCurrentTocLink(link.getAttribute('href')); }));
        const observedSections = Array.from(document.querySelectorAll('.guide-main section[id]'));
        const hasInitialSectionHash = observedSections.some((section) => `#${section.id}` === window.location.hash);
        const initialSectionHash = hasInitialSectionHash ? window.location.hash : '#login-screen';
        const updateNavigationButtons = () => {
            const currentIndex = observedSections.findIndex((section) => `#${section.id}` === currentSectionHash);
            previousButton.disabled = currentIndex <= 0;
            nextButton.disabled = currentIndex >= observedSections.length - 1;
        };
        const scrollToGuideSection = (target) => {
            if (!target) return Promise.resolve();
            const scrollRoot = document.scrollingElement || document.documentElement;
            const start = scrollRoot.scrollTop;
            const destination = window.scrollY + target.getBoundingClientRect().top - 58;
            const duration = 260;
            const startedAt = performance.now();

            return new Promise((resolve) => {
                const animate = (now) => {
                    const progress = Math.min(1, (now - startedAt) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    scrollRoot.scrollTop = start + (destination - start) * eased;
                    if (progress < 1) {
                        window.requestAnimationFrame(animate);
                    } else {
                        resolve();
                    }
                };
                window.requestAnimationFrame(animate);
            });
        };
        const centerGuideSection = (target) => {
            if (!target) return;
            const scrollRoot = document.scrollingElement || document.documentElement;
            const sectionTop = window.scrollY + target.getBoundingClientRect().top;
            const destination = Math.max(0, sectionTop + (target.offsetHeight / 2) - (window.innerHeight / 2));
            scrollRoot.scrollTop = destination;
        };
        const moveToSection = (offset) => {
            const currentIndex = observedSections.findIndex((section) => `#${section.id}` === currentSectionHash);
            const target = observedSections[currentIndex + offset];
            if (!target) return;
            setCurrentTocLink(`#${target.id}`);
            scrollToGuideSection(target);
        };
        previousButton.addEventListener('click', () => moveToSection(-1));
        nextButton.addEventListener('click', () => {
            if (window.location.hash !== '#workorder-main') {
                moveToSection(1);
                return;
            }

            const tdrSection = document.getElementById('workorder-tdr');
            if (!tdrSection) return;

            window.location.hash = '#workorder-tdr';
            setCurrentTocLink('#workorder-tdr');
            scrollToGuideSection(tdrSection);
        });
        document.addEventListener('wheel', (event) => {
            if (event.target instanceof Element && event.target.closest('.guide-toc')) return;

            event.preventDefault();
        }, { passive:false });
        const sectionObserver = new IntersectionObserver((entries) => {
            if (restoringGuidePosition) return;
            const visible = entries.filter((entry) => entry.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
            if (visible[0]) setCurrentTocLink(`#${visible[0].target.id}`);
        }, { rootMargin: '-22% 0px -67% 0px', threshold: 0 });
        observedSections.forEach((section) => sectionObserver.observe(section));
        setCurrentTocLink(initialSectionHash);
        updateNavigationButtons();
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') setTocOpen(false);
        });
        window.addEventListener('pagehide', () => {
            if (!guidePositionStateLoaded) return;
            window.UserUiSettings.set(scope, 'scroll-top', Math.max(0, Math.round(window.scrollY)));
            window.UserUiSettings.set(scope, 'last-section', currentSectionHash);
        });
        window.addEventListener('scroll', () => {
            if (!guidePositionStateLoaded || restoringGuidePosition) return;
            window.clearTimeout(guideScrollSaveTimer);
            guideScrollSaveTimer = window.setTimeout(() => {
                window.UserUiSettings.set(scope, 'scroll-top', Math.max(0, Math.round(window.scrollY)));
            }, 250);
        }, { passive:true });
        const languageStatePromise = window.UserUiSettings.get(scope, 'language', 'en')
            .then((saved) => setLanguage(saved, false));
        const workorderStatePromise = window.UserUiSettings.get(scope, 'workorder-interactive-state', workorderInteractiveState).then((saved) => {
            if (saved && typeof saved === 'object') {
                workorderInteractiveState.active = Boolean(saved.active);
                workorderInteractiveState.approved = Boolean(saved.approved);
                workorderInteractiveState.mine = Boolean(saved.mine);
                workorderInteractiveState.selected = typeof saved.selected === 'string' ? saved.selected : null;
            }
            workorderInteractiveStateLoaded = true;
            updateInteractiveWorkorders(false);
        });
        const tocStatePromise = Promise.all([
            window.UserUiSettings.get(scope, 'open-sections', [true, false, false, false]),
            window.UserUiSettings.get(scope, 'toc-open-all', false),
            window.UserUiSettings.get(scope, 'last-section', ''),
            window.UserUiSettings.get(scope, 'scroll-top', 0),
        ]).then(([savedSections, savedOpenAll, savedSection, savedScrollTop]) => {
            keepAllTocSectionsOpen = Boolean(savedOpenAll);
            if (Array.isArray(savedSections)) details.forEach((detail, index) => { detail.open = keepAllTocSectionsOpen || Boolean(savedSections[index]); });
            tocStateLoaded = true;
            const restoredSectionHash = hasInitialSectionHash
                ? initialSectionHash
                : (observedSections.some((section) => `#${section.id}` === savedSection) ? savedSection : '#login-screen');
            setCurrentTocLink(restoredSectionHash);
            guidePositionStateLoaded = true;
            updateOpenAllLabel();
            return new Promise((resolve) => window.requestAnimationFrame(() => window.requestAnimationFrame(async () => {
                const scrollTop = Number(savedScrollTop);
                if (centerCurrentGuideSection) centerGuideSection(document.querySelector(restoredSectionHash));
                else if (Number.isFinite(scrollTop) && scrollTop > 0) window.scrollTo(0, scrollTop);
                else if (restoredSectionHash !== '#login-screen') await scrollToGuideSection(document.querySelector(restoredSectionHash));
                window.requestAnimationFrame(() => {
                    restoringGuidePosition = false;
                    resolve();
                });
            })));
        });
        Promise.allSettled([
            languageStatePromise,
            workorderStatePromise,
            tocStatePromise,
            ...mainDemoInitialStatePromises,
        ]).then(() => {
            guideStateApplied = true;
            revealGuide();
        });
    })();
</script>
</body>
</html>
