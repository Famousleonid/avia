@extends('admin.master')

@section('style')
    <style>
        .qa-card {
            border: 1px solid #2d3640;
            border-radius: 14px;
            background: #171c22;
            box-shadow: 0 14px 30px rgba(0, 0, 0, .18);
        }

        .qa-status-pill {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .35rem .7rem;
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 600;
        }

        .qa-status-pill.passed {
            background: rgba(25, 135, 84, .16);
            color: #8be0ae;
            border: 1px solid rgba(25, 135, 84, .35);
        }

        .qa-status-pill.failed {
            background: rgba(220, 53, 69, .16);
            color: #ff9aa5;
            border: 1px solid rgba(220, 53, 69, .35);
        }

        .qa-status-pill.unknown {
            background: rgba(108, 117, 125, .16);
            color: #c8cdd3;
            border: 1px solid rgba(108, 117, 125, .35);
        }

        .qa-output {
            white-space: pre-wrap;
            font-family: Consolas, monospace;
            font-size: .84rem;
            line-height: 1.5;
            max-height: 360px;
            overflow: auto;
            background: #0d1117;
            color: #c9d1d9;
            border-radius: 10px;
            padding: 1rem;
            border: 1px solid #222b36;
        }

        .qa-hero {
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(13,110,253,.18), rgba(32,201,151,.12));
            border: 1px solid rgba(13,110,253,.18);
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-3">
        <div class="qa-hero p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h2 class="mb-2 text-white">QA Test Dashboard</h2>
                    <p class="mb-0 text-white-50">
                        Запуск smoke и полного feature-набора из админки с сохранением последнего результата.
                    </p>
                </div>
                <div class="text-end text-white-50 small">
                    Команда для cron/ручного запуска:
                    <div><code>php artisan qa:run-tests smoke</code></div>
                    <div><code>php artisan qa:run-tests feature</code></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            @foreach($suites as $suiteKey => $suite)
                <div class="col-12 col-xl-6">
                    <div class="qa-card h-100 p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h4 class="mb-1 text-white">{{ $suite['label'] }}</h4>
                                <div class="text-white-50">{{ $suite['description'] }}</div>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-2">
                                <span class="qa-status-pill {{ $suite['status'] }}">
                                    @if($suite['status'] === 'passed')
                                        <i class="bi bi-check-circle-fill"></i>
                                    @elseif($suite['status'] === 'failed')
                                        <i class="bi bi-x-circle-fill"></i>
                                    @else
                                        <i class="bi bi-dash-circle-fill"></i>
                                    @endif
                                    {{ strtoupper($suite['status']) }}
                                </span>

                                <form method="POST" action="{{ route('admin.tests.run', $suiteKey) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-play-fill"></i> Запустить
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <div class="small text-white-50">Summary</div>
                                <div class="text-white">{{ $suite['summary'] }}</div>
                            </div>
                            <div class="col-sm-4">
                                <div class="small text-white-50">Duration</div>
                                <div class="text-white">
                                    {{ $suite['duration_ms'] !== null ? number_format($suite['duration_ms']) . ' ms' : '—' }}
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="small text-white-50">Last run</div>
                                <div class="text-white">{{ $suite['finished_at'] ?? '—' }}</div>
                            </div>
                        </div>

                        <details>
                            <summary class="text-info mb-3" style="cursor:pointer;">Показать вывод</summary>
                            <div class="qa-output">{{ $suite['output'] !== '' ? $suite['output'] : 'Вывод пока отсутствует.' }}</div>
                        </details>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
