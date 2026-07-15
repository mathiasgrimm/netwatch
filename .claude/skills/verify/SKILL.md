# Verifying the Laravel health dashboard in a real browser

This is a Composer package (no runnable app), but the dashboard view can be
exercised end-to-end by capturing the real route's HTML via Testbench and
serving it statically.

## Recipe

1. Write a temporary Pest test in `tests/Laravel/` (e.g. `TempCaptureHtmlTest.php`)
   that mirrors `HealthHtmlTest.php`'s `beforeEach` setup (register
   `NetwatchServiceProvider`, config probes with `health_route.enabled => true`,
   `health_route.middleware => []`), requests `/netwatch/health?format=html`,
   and `file_put_contents(getenv('NW_CAPTURE_PATH'), $html)`. Use inline
   anonymous-class `ProbeInterface` probes for varied latencies/failures —
   the fixtures in `tests/Fixtures/` return constant values (flat sparklines).
2. `NW_CAPTURE_PATH=<scratchpad>/dashboard.html vendor/bin/pest tests/Laravel/TempCaptureHtmlTest.php`
3. `php -S 127.0.0.1:8899` in the scratchpad dir, open in Chrome via
   claude-in-chrome tools. All JS is inline and self-contained, so the static
   capture behaves identically to the live route.
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
