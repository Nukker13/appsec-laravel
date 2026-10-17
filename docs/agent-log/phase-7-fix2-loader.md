# Phase 7 fix 2: Launch button + loader tab

Reverted auto-launch. The lab view has a "Launch lab" link (`labs.open`, new tab). `labs.open` only renders `lab-open.blade.php`; no server-side launch.

`public/js/lab-open.js`:
- Launch fires once: a single POST at script start, outside the poll loop. Polling only GETs the status URL.
- Warm-up: on `ready` + `url`, `readySince` is set once; navigation via `location.replace` happens after 2.5 s. The fallback link is shown meanwhile.
- Capacity (HTTP 503 or `status: capacity`) and `failed` stop polling without redirect. Other HTTP/network errors stop with a message.

`lab.js` keeps only hints and flag. Removed the `#openLab.open-fallback` CSS rule (unused).

Strings: "Queued, starting soon...", "Starting lab...", "Almost ready...", "No free lab slots, try again shortly.", "Lab failed to start.", "Could not start the lab (N).", "Could not check the lab (N).", "Network error while starting the lab."
