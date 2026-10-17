(function () {
    'use strict';

    var root = document.getElementById('lab');
    if (!root) { return; }

    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var urls = {
        hints: root.dataset.hintsUrl,
        flag: root.dataset.flagUrl
    };
    var $ = function (id) { return document.getElementById(id); };
    var hintTimer = null;

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

    loadHints();
})();
