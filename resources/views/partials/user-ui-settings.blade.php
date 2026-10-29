<script>
    window.UserUiSettings = window.UserUiSettings || (function () {
        const indexUrl = @json(route('user-ui-settings.index'));
        const storeUrl = @json(route('user-ui-settings.store'));
        const csrf = @json(csrf_token());
        const cache = @json($userUiSettingsInitial ?? (object) []);
        const loaded = new Set(Object.keys(cache));
        const pending = new Map();

        async function loadScope(scope) {
            if (loaded.has(scope)) {
                return cache[scope];
            }
            if (pending.has(scope)) return pending.get(scope);
            const request = (async () => {
                const controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), 10000);
                try {
                    const response = await fetch(`${indexUrl}?scope=${encodeURIComponent(scope)}`, {
                        method: 'GET',
                        signal: controller.signal,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    if (!response.ok || response.redirected) throw new Error('Settings request failed');

                    const data = await response.json();
                    // Preserve changes made locally while this request was in flight.
                    cache[scope] = Object.assign({}, data.settings && typeof data.settings === 'object' ? data.settings : {}, cache[scope] || {});
                    loaded.add(scope);
                    return cache[scope];
                } catch (error) {
                    console.warn('Could not load UI settings; using current state.', error);
                    return cache[scope] || {};
                } finally {
                    clearTimeout(timeout);
                }
            })().finally(() => pending.delete(scope));
            pending.set(scope, request);
            return request;
        }

        async function get(scope, key, fallback = null) {
            const settings = await loadScope(scope);
            return Object.prototype.hasOwnProperty.call(settings, key) ? settings[key] : fallback;
        }

        async function set(scope, key, value, options = {}) {
            if (!Object.prototype.hasOwnProperty.call(cache, scope)) {
                cache[scope] = {};
            }

            cache[scope][key] = value;

            try {
                await fetch(storeUrl, {
                    method: 'POST',
                    keepalive: Boolean(options.keepalive),
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ scope, key, value }),
                });
            } catch (error) {
                console.error('Failed to save user UI setting', error);
            }
        }

        return { loadScope, get, set };
    })();
</script>
