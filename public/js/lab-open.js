(function () {
    'use strict';

    var root = document.getElementById('labOpen');
    if (!root) { return; }

    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var launchUrl = root.dataset.launchUrl;
    var statusUrl = root.dataset.statusUrl;
    var spinner = document.getElementById('loaderSpinner');
    var label = document.getElementById('loaderStatus');
    var link = document.getElementById('loaderLink');
    var POLL_MS = 2000;
    var WARMUP_MS = 2500; // Traefik needs a moment to pick up a new instance
    var timer = null;
    var readySince = null;
    var done = false;

    function stop(msg) {
        done = true;
        clearTimeout(timer);
        spinner.hidden = true;
        label.textContent = msg;
    }

    function schedule(ms) {
        if (!done) { timer = setTimeout(poll, ms); }
    }

    function show(data) {
        if (done) { return; }
        if (data.status === 'capacity') { stop('No free lab slots, try again shortly.'); return; }
        if (data.status === 'failed') { stop('Lab failed to start.'); return; }

        if (data.status === 'ready' && data.url) {
            link.href = data.url;
            link.hidden = false;
            if (readySince === null) { readySince = Date.now(); }
            var wait = WARMUP_MS - (Date.now() - readySince);
            label.textContent = 'Almost ready...';
            if (wait > 0) { schedule(wait); return; }
            done = true;
            window.location.replace(data.url);
            return;
        }

        readySince = null;
        link.hidden = true;
        label.textContent = data.status === 'pending' ? 'Queued, starting soon...' : 'Starting lab...';
        schedule(POLL_MS);
    }

    function poll() {
        fetch(statusUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) { stop('Could not check the lab (' + r.status + ').'); return null; }
                return r.json();
            })
            .then(function (data) { if (data) { show(data); } })
            .catch(function () { stop('Network error while starting the lab.'); });
    }

    // Launch exactly once per page load, then poll.
    fetch(launchUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
    }).then(function (r) {
        return r.json().catch(function () { return {}; }).then(function (data) { return { code: r.status, data: data }; });
    }).then(function (res) {
        if (res.code === 503 || res.data.status === 'capacity') { stop('No free lab slots, try again shortly.'); return; }
        if (res.code >= 400) { stop('Could not start the lab (' + res.code + ').'); return; }
        show(res.data);
    }).catch(function () {
        stop('Network error while starting the lab.');
    });
})();
