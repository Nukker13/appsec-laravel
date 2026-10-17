# Phase 7, fixes batch 3 (frontend): login gutter, lab autostart

## Files
- `public/css/app.css`: `main > :not(.container)` gets 24px side padding (16px under 768px), matching `.container`; added spinner styles.
- `resources/views/lab.blade.php`: Launch button removed; spinner + aria-live status + prominent `#openLab`. All `data-*-url` attributes kept; no hint/flag text embedded.
- `public/js/lab.js`: launch-on-load, loader, auto-open, capacity/failed messaging. Hints and flag code untouched.

## #1 Gutter
Pages with a `.container` already pad it (24px), so a padding on `<main>` itself would double up (40px). The rule targets only direct children of `<main>` that are not containers, so home/login text gets the gutter and container pages are unchanged.

## #2 Behaviour
1. Page load: `launch()` POSTs the launch URL, then `showStatus` drives polling (2s). `loadHints()` unchanged.
2. `pending` -> "Queued, starting soon..."; `starting` (and stopped/unknown right after launch) -> "Starting lab..."; spinner visible.
3. `ready` + url: keeps the 2.5s warm-up ("Almost ready (warming up)..."), then reveals `#openLab` always and calls `window.open(url, '_blank', 'noopener')` once.
4. Popup-blocker reasoning: the call runs from a timer/fetch callback, not a user gesture, so blockers may refuse. With the `noopener` feature string `window.open` returns `null` per spec even on success in some browsers, so a null return cannot be told apart from a block. Consequence: the status text may say "browser blocked the new tab" even if the tab opened. This is why the Open lab link is always revealed and prominent (class `open-fallback` on null return). The `autoOpened` flag is reset only when status leaves `ready`, so polls/re-renders never reopen a tab. Not verified in a real browser (no node/browser run here); reasoned from spec behaviour.
5. Capacity: launch POST 503 or `status === 'capacity'` -> loader stops, polling cleared, "No free lab slots, try again shortly." Other 4xx/5xx -> "Could not start the lab (CODE).". Fetch/network failure -> "Network error while starting the lab.". Status `failed` (poll or launch response) -> "Lab failed to start.".
   Reviewer note: if the 503 body ever lacks JSON, the code check on 503 still covers it.

## Verification
- `php artisan test`: 176 passed (1361 assertions)
- `phpstan analyse`: [OK] No errors
- `pint --test`: PASS, 80 files
- JS not syntax-checked with node (not installed); logic reviewed by hand.

## FLAG
Autostart means that merely opening a lab page now consumes a capacity slot (launch is POSTed on load), even if the user leaves immediately; the reaper frees it after the idle timeout. Not solved here.
