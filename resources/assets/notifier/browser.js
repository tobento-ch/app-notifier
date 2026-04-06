import notifier from './../js-notifier/notifier.js';

export default function initBrowserNotifier(config) {
    const {
        pullUrl,
        streamUrl,
        pollInterval = 5000,
        maxFailures = 3,
    } = config;
    
    // Stream mode
    if (streamUrl && window.EventSource) {
        startStream(streamUrl);
        return;
    }
    
    // Pull mode (no stream available)
    if (pullUrl) {
        startPull(pullUrl, pollInterval, maxFailures);
    }
}

function startPull(url, interval, maxFailures) {
    let failures = 0;

    const timer = setInterval(async () => {
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    "Content-Type": "application/json",
                },
            });

            if (!res.ok) {
                failures++;
                if (failures >= maxFailures) {
                    console.error('Stopping pull: too many failed responses');
                    clearInterval(timer);
                }
                return;
            }

            failures = 0; // reset on success

            const data = await res.json();

            if (!data.notifications?.length) return;

            notifyAll(data.notifications);

        } catch (err) {
            failures++;
            console.error('Browser pull error:', err);

            if (failures >= maxFailures) {
                console.error('Stopping pull: too many errors');
                clearInterval(timer);
            }
        }
    }, interval);
}

function startStream(url) {
    const es = new EventSource(url);

    es.onmessage = (event) => {
        try {
            const data = JSON.parse(event.data);
            if (!data.notifications?.length) return;

            notifyAll(data.notifications);
        } catch (err) {
            console.error('Browser Stream error:', err);
        }
    };

    es.onerror = () => {
        if (es.readyState === EventSource.CONNECTING) {
            // Normal reconnect, no need to log
            return;
        }

        console.error("Browser Stream: SSE connection failed permanently");
    };
}

async function notifyAll(notifications) {
    for (const n of notifications) {
        notify(n);
        await sleep(300); // 300ms delay between notifications
    }
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function notify(notification) {
    // Ensure required fields exist
    const mapped = {
        status: notification.status || 'info',
        title: notification.title || '',
        text: notification.text || '',
        action: notification.action || {},
        autotimeout: notification.autotimeout ?? 5000,
        showCloseButton: notification.showCloseButton ?? true,
        classes: notification.classes || ['notification', 'notification-fade'],
    };

    notifier.send(mapped);
}