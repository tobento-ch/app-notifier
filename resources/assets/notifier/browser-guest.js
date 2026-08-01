import notifier from './../js-notifier/notifier.js';

export default function initBrowserNotifier(config) {
    const {
        pullUrl,
        pollInterval = 5000,
        maxFailures = 3,
    } = config;

    startPull(pullUrl, pollInterval, maxFailures);
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
                    "X-Exclude-Previous-Uri": "1",
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