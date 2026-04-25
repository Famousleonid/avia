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
        .machining-photo-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .35rem;
        }
        .machining-photo-cell {
            position: relative;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: .25rem;
            overflow: hidden;
            background: rgba(0, 0, 0, .25);
        }
        .machining-photo-cell a.machining-photo-thumb {
            display: block;
            aspect-ratio: 1;
        }
        .machining-photo-cell img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .machining-photo-del {
            position: absolute;
            right: 2px;
            bottom: 2px;
        }
        .machining-photo-del .btn {
            font-size: .65rem;
            padding: .1rem .25rem;
            line-height: 1;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid machining-media-page">
        <div class="machining-media-title">WO {{ $workorder->number }} — Machining photos</div>

        @if(session('success'))
            <div class="alert alert-success py-1 px-2 small mb-2">{{ session('success') }}</div>
        @endif

        <p class="small text-secondary mb-2">
            <a href="{{ route('mobile.machining.workorder', $workorder) }}" class="text-info">← Back to machining</a>
        </p>

        @if($photos->isEmpty())
            <p class="text-secondary small mb-0">No photos in this collection yet.</p>
        @else
            <div class="machining-photo-grid">
                @foreach($photos as $p)
                    <div class="machining-photo-cell">
                        <a class="machining-photo-thumb" href="{{ $p['big_url'] }}" target="_blank" rel="noopener">
                            <img src="{{ $p['thumb_url'] }}" alt="" loading="lazy">
                        </a>
                        <form class="machining-photo-del"
                              method="POST"
                              action="{{ route('mobile.machining.workorder.media.destroy', [$workorder, $p['id']]) }}"
                              onsubmit="return confirm('Delete this photo?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">×</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
