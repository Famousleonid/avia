<link rel="stylesheet" href="{{ asset('css/training-week-calendar.css') }}?v={{ filemtime(public_path('css/training-week-calendar.css')) }}">
<script src="{{ asset('js/training-week-calendar.js') }}?v={{ filemtime(public_path('js/training-week-calendar.js')) }}"
        data-endpoint="{{ route('trainings.weekCalendar') }}" defer></script>
<script src="{{ asset('js/training-week-availability.js') }}?v={{ filemtime(public_path('js/training-week-availability.js')) }}"
        data-endpoint="{{ route('trainings.weekAvailability') }}"
        data-user-id="{{ $selectedUserId ?? $userId ?? auth()->id() }}" defer></script>
