<script>
    window.appSessionConfig = {
        enabled: {{ auth()->check() ? 'true' : 'false' }},
        heartbeatUrl: @json(route('session.heartbeat')),
        loginUrl: @json(route('login')),
        lifetimeMinutes: {{ (int) config('session.lifetime', 120) }},
    };
</script>
