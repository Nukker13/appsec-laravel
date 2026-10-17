# Phase 7 fixes, batch 3: content

Scope: Blade views, `docs/labs/*.md`, `config/hints.php`. No lab source, tests or frontend JS touched.

- P4: dom-xss, reflect-xss, stored-xss views now show the real sink (innerHTML from `location.hash`; unescaped `$msg` in the 404 view; unescaped stored `content` on the admin page).
- P5: ssrf view shows the real entry points (`imageUrl` + `action=count`, and `/proxy?url=`).
- #8: proofread; fixed the type-juggle hint wording ("PHP equals operator"). ssrf doc said "Node.js" for PHP services; corrected.
- P7: all ten Flag Delivery sections describe the per-instance flag (`storage/app/instances/{project}/lab/flag.txt`, issued by `FlagService`); the status line now says "Verified by hand (QA)".
- #11: oauth-ato view has a "Test accounts" card with student google/apple credentials only (no admin).
- P6: path-trav hint split into two entries that nudge toward a flag-reading upload without naming a payload.
