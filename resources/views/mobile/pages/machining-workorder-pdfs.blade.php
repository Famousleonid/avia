@extends('mobile.master')

@section('style')
    <style>
        .machining-media-page {
            padding: 0;
        }
        .machining-media-title {
            font-size: .95rem;
            font-weight: 600;
            color: #e9ecef;
            margin-bottom: .5rem;
        }
        .machining-pdf-row {
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: .3rem;
            padding: .45rem .4rem;
            margin-bottom: .45rem;
            background: rgba(0, 0, 0, .2);
            font-size: .75rem;
        }
        .machining-pdf-name {
            word-break: break-word;
            color: #e9ecef;
            margin-bottom: .25rem;
        }
        .machining-pdf-meta {
            color: #9fb0c0;
            font-size: .68rem;
            margin-bottom: .35rem;
        }
        .machining-pdf-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            align-items: center;
        }
        .machining-pdf-actions .btn {
            font-size: .72rem;
            padding: .15rem .4rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid machining-media-page">
        <div class="machining-media-title">WO {{ $workorder->number }} — PDFs</div>

        @if(session('success'))
            <div class="alert alert-success py-1 px-2 small mb-2">{{ session('success') }}</div>
        @endif

        <p class="small text-secondary mb-2">
            <a href="{{ route('mobile.machining.workorder', $workorder) }}" class="text-info">← Back to machining</a>
        </p>

        @if($pdfs->isEmpty())
            <p class="text-secondary small mb-0">No PDFs on this work order yet.</p>
        @else
            @foreach($pdfs as $pdf)
                <div class="machining-pdf-row">
                    <div class="machining-pdf-name">{{ $pdf['label'] }}</div>
                    <div class="machining-pdf-meta">{{ $pdf['created_at'] }}</div>
                    <div class="machining-pdf-actions">
                        <a href="{{ $pdf['show_url'] }}" target="_blank" rel="noopener" class="btn btn-outline-info btn-sm">Open</a>
                        <a href="{{ $pdf['download_url'] }}" class="btn btn-outline-secondary btn-sm">Download</a>
                        <form method="POST"
                              action="{{ route('mobile.machining.workorder.media.destroy', [$workorder, $pdf['id']]) }}"
                              class="d-inline"
                              onsubmit="return confirm('Delete this PDF?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
