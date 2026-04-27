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
    @php
        $machiningGalleryItems = $photos->values()->map(function (array $p, int $idx) use ($workorder, $photos) {
            return [
                'src' => $p['big_url'],
                'type' => 'image',
                'caption' => 'WO '.$workorder->number.' — Machining — '.($idx + 1).' / '.$photos->count(),
            ];
        });
    @endphp
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
                        <a class="machining-photo-thumb js-machining-gallery-open"
                           href="{{ $p['big_url'] }}"
                           data-gallery-index="{{ $loop->index }}"
                           role="button">
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

@section('scripts')
    <script>
        (function () {
            const items = @json($machiningGalleryItems);
            const opts = {
                Toolbar: ['zoom', 'fullscreen', 'close'],
                dragToClose: true,
                placeFocusBack: false,
                trapFocus: false,
                showClass: 'fancybox-fadeIn',
                hideClass: 'fancybox-fadeOut',
            };

            document.querySelectorAll('.js-machining-gallery-open').forEach(function (el) {
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (!items.length || typeof Fancybox === 'undefined') {
                        return;
                    }
                    const raw = el.getAttribute('data-gallery-index');
                    const start = Math.max(0, Math.min(Number.parseInt(raw || '0', 10) || 0, items.length - 1));
                    const rotated = items.slice(start).concat(items.slice(0, start));
                    Fancybox.show(rotated, opts);
                });
            });
        })();
    </script>
@endsection
