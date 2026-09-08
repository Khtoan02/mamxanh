import { onLCP, onINP, onCLS, onFCP, onTTFB } from 'web-vitals';

function send(metric) {
    const body = JSON.stringify({
        metric: metric.name,
        value: metric.value,
        rating: metric.rating,
        path: location.pathname,
    });

    if (navigator.sendBeacon) {
        navigator.sendBeacon('/rum', new Blob([body], { type: 'application/json' }));
    } else {
        fetch('/rum', { method: 'POST', body, headers: { 'Content-Type': 'application/json' }, keepalive: true });
    }
}

onLCP(send);
onINP(send);
onCLS(send);
onFCP(send);
onTTFB(send);
