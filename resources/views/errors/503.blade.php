<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Service unavailable — 503</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0c0f14;
            --card: rgba(22, 28, 38, 0.72);
            --border: rgba(255, 255, 255, 0.08);
            --text: #e8edf5;
            --muted: #8b98a8;
            --accent: #3dd6c7;
            --accent-dim: rgba(61, 214, 199, 0.15);
            --glow: rgba(61, 214, 199, 0.35);
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
            color: var(--text);
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            overflow-x: hidden;
        }

        /* Фон: мягкий градиент + сетка */
        .bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 50% -20%, rgba(61, 214, 199, 0.12), transparent 55%),
                radial-gradient(ellipse 60% 50% at 100% 50%, rgba(99, 102, 241, 0.08), transparent 50%),
                radial-gradient(ellipse 50% 40% at 0% 80%, rgba(61, 214, 199, 0.06), transparent 45%),
                var(--bg);
        }

        .bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 70% 70% at 50% 50%, black 20%, transparent 70%);
            pointer-events: none;
        }

        .card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 28rem;
            padding: 2.25rem 2rem 2rem;
            border-radius: 1.25rem;
            background: var(--card);
            border: 1px solid var(--border);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, 0.04) inset,
                0 24px 48px -12px rgba(0, 0, 0, 0.45);
        }

        .icon-wrap {
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            background: var(--accent-dim);
            border: 1px solid rgba(61, 214, 199, 0.25);
            box-shadow: 0 0 32px var(--glow);
        }

        .icon-wrap svg {
            width: 2rem;
            height: 2rem;
            color: var(--accent);
            animation: pulse 2.4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.85; transform: scale(0.96); }
        }

        .code {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent);
            text-align: center;
            margin-bottom: 0.5rem;
        }

        h1 {
            font-size: 1.375rem;
            font-weight: 700;
            line-height: 1.3;
            text-align: center;
            margin: 0 0 0.75rem;
            letter-spacing: -0.02em;
        }

        .lead {
            font-size: 0.9375rem;
            line-height: 1.55;
            color: var(--muted);
            text-align: center;
            margin: 0 0 1.5rem;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.5rem 1rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
            font-size: 0.8125rem;
            color: var(--muted);
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
        }

        .pill svg {
            width: 0.9rem;
            height: 0.9rem;
            opacity: 0.8;
        }

        @media (prefers-reduced-motion: reduce) {
            .icon-wrap svg { animation: none; }
        }
    </style>
</head>
<body>
    <div class="bg" aria-hidden="true"></div>
    <main class="card" role="main">
        <div class="icon-wrap" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="code">503</p>
        <h1>Service temporarily unavailable</h1>
        <p class="lead">
            @if(isset($retryAfter) && (int) $retryAfter > 0)
                Scheduled maintenance in progress. Please try again in about {{ (int) $retryAfter }} seconds.
            @else
                We're updating the service or restoring data. Please check back in a few minutes.
            @endif
        </p>
        <div class="meta">
            <span class="pill">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Thank you for your patience
            </span>
        </div>
    </main>
</body>
</html>
