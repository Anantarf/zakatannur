export const bootstrapRealtime = (realtime = {}) => {
    if (window.__zakatEcho) {
        return window.__zakatEcho;
    }

    if (!realtime.enabled || typeof window.Pusher === 'undefined' || typeof window.Echo === 'undefined') {
        return null;
    }

    const EchoConstructor = window.Echo;
    window.__zakatEcho = new EchoConstructor({
        broadcaster: 'pusher',
        key: realtime.key,
        cluster: realtime.cluster,
        forceTLS: true,
    });

    return window.__zakatEcho;
};
