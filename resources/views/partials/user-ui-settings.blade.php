<script>
    window.UserUiSettings = window.UserUiSettings || (function () {
        const indexUrl = @json(route('user-ui-settings.index'));
        const storeUrl = @json(route('user-ui-settings.store'));
        const csrf = @json(csrf_token());
        const cache = {};

        async function loadScope(scope) {
            if (Object.prototype.hasOwnProperty.call(cache, scope)) {
                return cache[scope];
            }

            const response = await fetch(`${indexUrl}?scope=${encodeURIComponent(scope)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                cache[scope] = {};
                return cache[scope];
            }

            const data = await response.json();
            cache[scope] = data.settings && typeof data.settings === 'object' ? data.settings : {};

            return cache[scope];
        }

        async function get(scope, key, fallback = null) {
            const settings = await loadScope(scope);
            return Object.prototype.hasOwnProperty.call(settings, key) ? settings[key] : fallback;
        }

        async function set(scope, key, value) {
            if (!Object.prototype.hasOwnProperty.call(cache, scope)) {
                cache[scope] = {};
            }

            cache[scope][key] = value;

            try {
                await fetch(storeUrl, {
                    method: 'POST',
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
