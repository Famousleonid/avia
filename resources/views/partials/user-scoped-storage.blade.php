@php
    $userScopedStoragePayload = ['local' => [], 'session' => []];
    if (auth()->check()) {
        $userScopedStoragePayload['local'] = \App\Models\UserUiSetting::query()
            ->where('user_id', auth()->id())
            ->where('scope', 'browser-storage')
            ->pluck('value', 'key')
            ->all();
        $userScopedStoragePayload['session'] = \App\Models\UserUiSetting::query()
            ->where('user_id', auth()->id())
            ->where('scope', 'browser-session-storage')
            ->pluck('value', 'key')
            ->all();
    }
@endphp
<script>
    (function () {
        const endpoint = @json(auth()->check() ? route('user-ui-settings.store') : null);
        const csrf = @json(csrf_token());
        const initial = @json($userScopedStoragePayload);

        function createStore(scope, values) {
            const data = Object.assign({}, values || {});
            const keys = () => Object.keys(data).filter((key) => data[key] !== null && data[key] !== undefined);

            function persist(key, value) {
                if (!endpoint) return Promise.resolve();
                return fetch(endpoint, {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ scope, key, value }),
                }).catch((error) => console.error('Failed to save user scoped storage', error));
            }

            return {
                get length() {
                    return keys().length;
                },
                key(index) {
                    return keys()[Number(index)] ?? null;
                },
                getItem(key) {
                    key = String(key);
                    return Object.prototype.hasOwnProperty.call(data, key) && data[key] !== null && data[key] !== undefined
                        ? String(data[key])
                        : null;
                },
                setItem(key, value) {
                    key = String(key);
                    data[key] = String(value);
                    return persist(key, data[key]);
                },
                removeItem(key) {
                    key = String(key);
                    data[key] = null;
                    return persist(key, null);
                },
                clear() {
                    keys().forEach((key) => this.removeItem(key));
                },
                __raw() {
                    return Object.assign({}, data);
                },
            };
        }

        window.UserScopedStorage = window.UserScopedStorage || createStore('browser-storage', initial.local);
        window.UserScopedSessionStorage = window.UserScopedSessionStorage || createStore('browser-session-storage', initial.session);
    })();
</script>
