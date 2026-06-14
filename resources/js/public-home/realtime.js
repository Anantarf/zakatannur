export const createRealtime = (realtime = {}, host = (typeof window !== 'undefined' ? window : globalThis)) => {
    let echoInstance = null;
    let loaderPromise = null;

    const loadScript = (src) => new Promise((resolve) => {
        const existing = host.document?.querySelector(`script[src="${src}"]`);
        if (existing) {
            if (existing.dataset.loaded === 'true') {
                resolve(true);
                return;
            }
            existing.addEventListener('load', () => resolve(true), { once: true });
            existing.addEventListener('error', () => resolve(false), { once: true });
            return;
        }

        const script = host.document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => {
            script.dataset.loaded = 'true';
            resolve(true);
        };
        script.onerror = () => resolve(false);
        host.document.head.appendChild(script);
    });

    const loadRealtimeScripts = async () => {
        if (typeof host.Pusher !== 'undefined' && typeof host.Echo !== 'undefined') {
            return true;
        }

        if (!host.document) {
            return false;
        }

        if (!loaderPromise) {
            loaderPromise = (async () => {
                const pusherLoaded = typeof host.Pusher !== 'undefined'
                    || await loadScript('https://cdnjs.cloudflare.com/ajax/libs/pusher/7.0.3/pusher.min.js');
                const echoLoaded = typeof host.Echo !== 'undefined'
                    || await loadScript('https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js');

                return pusherLoaded && echoLoaded;
            })();
        }

        return loaderPromise;
    };

    return async () => {
        if (echoInstance) {
            return echoInstance;
        }

        if (!realtime.enabled || !await loadRealtimeScripts()) {
            return null;
        }

        const EchoConstructor = host.Echo;
        echoInstance = new EchoConstructor({
            broadcaster: 'pusher',
            key: realtime.key,
            cluster: realtime.cluster,
            forceTLS: true,
        });

        return echoInstance;
    };
};
