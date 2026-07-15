# Verifying the Laravel health dashboard in a real browser

This is a Composer package (no runnable app), but the dashboard view can be
exercised end-to-end by capturing the real route's HTML via Testbench and
serving it statically.

## Recipe

1. Write a temporary Pest test in `tests/Laravel/` (e.g. `TempCaptureHtmlTest.php`)
   that mirrors `HealthHtmlTest.php`'s `beforeEach` setup (register
   `NetwatchServiceProvider`, config probes with `health_route.enabled => true`,
   `health_route.middleware => []`), requests `/netwatch/health?format=html`
   and saves it to `getenv('NW_CAPTURE_DIR').'/dashboard.html'`, then requests
   `/netwatch/health/probes/{name}` for each probe and saves each response to
   `probe-{name}.json` — the dashboard loads probe data asynchronously from
   those fragment endpoints, so a bare HTML capture only shows skeletons.
   Use inline anonymous-class `ProbeInterface` probes for varied
   latencies/failures — the fixtures in `tests/Fixtures/` return constant
   values (flat sparklines). Skip capturing one probe's JSON to exercise the
   fetch-error card + Retry path.
2. `NW_CAPTURE_DIR=<scratchpad> vendor/bin/pest tests/Laravel/TempCaptureHtmlTest.php`
3. Write a `router.php` in the scratchpad that serves `probe-*.json` for
   `#/probes/([^/]+)$#` URIs (404 JSON otherwise, plus a random `usleep` so
   skeletons/staggered arrival are visible) and `dashboard.html` for anything
   else. Run `PHP_CLI_SERVER_WORKERS=8 php -S 127.0.0.1:8899 router.php` and
   open `/netwatch/health` in Chrome via claude-in-chrome tools.
4. Delete the temp test when done.

## Gotchas

- Chrome silently blocks repeated automatic downloads from the same origin
  after the first couple (no console error, no visible prompt). To inspect
  Blob downloads after that, restart `php -S` with a `router.php` that accepts
  `POST /save` (`file_put_contents` of `php://input`), stub the page's global
  `downloadBlob` to `fetch('/save', {method:'POST', body: blob})`, and Read
  the saved file. Real click-downloads land in `~/Downloads` (may take ~2s).
- Blade directive args cannot contain nested parentheses (`@json(array_values($x))`
  breaks compilation) — build values in the view's `@php` block first.
- The exported PNG can be inspected visually with the Read tool.
