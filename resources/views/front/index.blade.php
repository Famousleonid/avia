@extends('front.master')

@section('content')

    @include('components.menu')

@endsection

@section('scripts')
<script>
    (function () {
        function setMobileCookie() {
            // берём минимальную из screen.width и window.innerWidth, чтобы не ошибиться с DPR
            let w = Math.min(window.innerWidth || 0, screen.width || 0);
            let isMobile = w > 0 && w < 768; // порог
            document.cookie = "viewport_mobile=" + (isMobile ? "1" : "0") + "; Max-Age=2592000; Path=/; SameSite=Lax";
        }
        setMobileCookie();

        let to;
        window.addEventListener('resize', function(){
            clearTimeout(to);
            to = setTimeout(setMobileCookie, 200);
        });
    })();
</script>
@endsection
