export const createRealtime = (realtime = {}, host = (typeof window !== 'undefined' ? window : globalThis)) => {
    let echoInstance = null;

    return () => {
        if (echoInstance) {
            return echoInstance;
        }

        if (!realtime.enabled || typeof host.Pusher === 'undefined' || typeof host.Echo === 'undefined') {
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