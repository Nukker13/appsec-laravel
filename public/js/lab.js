(function () {
    'use strict';

    var root = document.getElementById('lab');
    if (!root) { return; }

    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var urls = {
        launch: root.dataset.launchUrl,
        status: root.dataset.statusUrl,
        hints: root.dataset.hintsUrl,
        flag: root.dataset.flagUrl
    };
    var $ = function (id) { return document.getElementById(id); };
    var POLL_MS = 2000;
    var OPEN_DELAY_MS = 2500; // Traefik needs a moment to pick up a new instance
    var statusTimer = null;
    var hintTimer = null;
    var readySince = null;

    function getJson(url) {
        return fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify(body || {})
        });
    }

    // ---- Launch / status ----
    function showStatus(data) {
        var label = $('launchStatus');
        var link = $('openLab');
        label.textContent = data.status;

        if (data.status === 'ready' && data.url) {
            if (readySince === null) { readySince = Date.now(); }
            var wait = OPEN_DELAY_MS - (Date.now() - readySince);
            if (wait > 0) {
                label.textContent = 'ready (warming up)';
                schedulePoll(wait);
                return;
            }
            link.href = data.url;
            link.hidden = false;
            $('launchNote').hidden = false;
            $('launchBtn').disabled = false;
            return;
        }

        readySince = null;
        link.hidden = true;
        $('launchNote').hidden = true;

        if (data.status === 'pending' || data.status === 'starting') {
            $('launchBtn').disabled = true;
            schedulePoll(POLL_MS);
        } else {
            $('launchBtn').disabled = false;
        }
    }

    function schedulePoll(ms) {
        clearTimeout(statusTimer);
        statusTimer = setTimeout(pollStatus, ms);
    }

    function pollStatus() {
        getJson(urls.status).then(showStatus).catch(function () { schedulePoll(POLL_MS * 2); });
    }

    $('launchBtn').addEventListener('click', function () {
        this.disabled = true;
        readySince = null;
        postJson(urls.launch).then(function (r) { return r.json(); }).then(function (data) {
            showStatus(data);
            schedulePoll(POLL_MS);
        }).catch(function () {
            $('launchStatus').textContent = 'failed';
            $('launchBtn').disabled = false;
        });
    });

    // ---- Hints (text only ever comes from labs.hints, never from the page HTML) ----
    function renderHints(data) {
        var list = $('hintsList');
        list.textContent = '';
        data.hints.forEach(function (h) {
            var p = document.createElement('div');
            p.className = 'hint-text';
            p.textContent = 'Hint ' + h.n + ': ' + h.text;
            list.appendChild(p);
        });
        $('hintsEmpty').hidden = data.hints.length > 0;

        clearTimeout(hintTimer);
        var delay = 30000; // also covers the case where the lab is launched later
        if (data.next_at) {
            var until = new Date(data.next_at).getTime() - Date.now() + 500;
            delay = Math.max(1000, Math.min(until, 30000));
        } else if (data.hints.length > 0) {
            return; // everything released
        }
        hintTimer = setTimeout(loadHints, delay);
    }

    function loadHints() {
        getJson(urls.hints).then(renderHints).catch(function () {
            hintTimer = setTimeout(loadHints, 30000);
        });
    }

    // ---- Flag ----
    $('flagForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var out = $('flagResult');
        var show = function (cls, msg) {
            out.className = cls;
            out.textContent = msg;
            out.style.display = 'block';
        };

        postJson(urls.flag, { flag: $('flagInput').value }).then(function (r) {
            if (r.status === 429) { show('error', 'Too many attempts, wait a minute.'); return null; }
            if (r.status === 422) { show('error', 'Please enter a flag.'); return null; }
            if (!r.ok) { show('error', 'Something went wrong (' + r.status + ').'); return null; }
            return r.json();
        }).then(function (data) {
            if (!data) { return; }
            show(data.correct ? 'success' : 'error', data.correct ? 'Correct! Lab completed.' : 'Incorrect flag.');
        }).catch(function () { show('error', 'Network error.'); });
    });

    pollStatus();
    loadHints();
})();
