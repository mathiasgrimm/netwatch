<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $statusDots = ['healthy' => '🟢', 'degraded' => '🟡', 'unhealthy' => '🔴', 'checking' => '🔵'];
    @endphp
    <title>{{ $statusDots[$overallStatus] ?? '' }} Netwatch Health Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%230d1526'/%3E%3Cg transform='translate(2.6 7.4) scale(0.68)' fill='none' stroke='%2338e1f5' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M1 12h7l2.5-7 4 14 3-9.5 1.8 2.5H27'/%3E%3C/g%3E%3Ccircle cx='23.7' cy='15.6' r='2' fill='%2338e1f5'/%3E%3Ccircle cx='23.7' cy='15.6' r='3.8' stroke='%2338e1f5' stroke-opacity='0.35' fill='none'/%3E%3C/svg%3E">
    @php
        $probeCount = count($probeNames);
        $probeNamesList = array_values($probeNames);
        $netwatchMeta = [
            'status' => $overallStatus,
            'checkedAt' => $checkedAt,
            'probeCount' => $probeCount,
            'iterationCount' => 0,
            'disabledProbes' => array_values($disabledProbes),
        ];
    @endphp
    <script>
        (function () {
            var stored = null;
            try { stored = localStorage.getItem('netwatch-theme'); } catch (e) {}
            var mode = (stored === 'light' || stored === 'dark') ? stored : 'system';
            var dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme-mode', mode);
        })();
    </script>
    <style>
        :root {
            color-scheme: dark;
            --bg-0: #0a0f1d;
            --surface-1: #111a2e;
            --surface-2: #0d1526;
            --border: rgba(148, 170, 220, 0.12);
            --border-strong: rgba(148, 170, 220, 0.22);
            --ink: #f8fafc;
            --ink-2: #9fb0c7;
            --ink-3: #7c8db0;
            --cyan: #22d3ee;
            --cyan-hi: #38e1f5;
            --cyan-lo: #06b6d4;
            --ok: #34d399;
            --warn: #fbbf24;
            --crit: #f87171;
            --bg-glow: rgba(34, 211, 238, 0.07);
            --bg-grad-1: #131a2b;
            --bg-grad-2: #0d1424;
            --bg-grad-3: #0a0f1d;
            --ok-bg: rgba(52, 211, 153, 0.10);
            --ok-border: rgba(52, 211, 153, 0.30);
            --warn-bg: rgba(251, 191, 36, 0.10);
            --warn-border: rgba(251, 191, 36, 0.30);
            --crit-bg: rgba(248, 113, 113, 0.10);
            --crit-bg-soft: rgba(248, 113, 113, 0.06);
            --crit-border: rgba(248, 113, 113, 0.30);
            --crit-border-strong: rgba(248, 113, 113, 0.40);
            --crit-ink: #fca5a5;
            --muted-bg: rgba(124, 141, 176, 0.10);
            --muted-border: rgba(124, 141, 176, 0.25);
            --cyan-bg: rgba(34, 211, 238, 0.06);
            --cyan-bg-strong: rgba(34, 211, 238, 0.12);
            --accent-border: rgba(34, 211, 238, 0.4);
            --btn-bg: rgba(148, 170, 220, 0.06);
            --seg-bg: rgba(13, 21, 38, 0.6);
            --card-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
            --border-faint: rgba(148, 170, 220, 0.06);
            --surface-disabled: rgba(17, 26, 46, 0.45);
            --code-ink: #cbd5e1;
            --scrollbar: rgba(124, 141, 176, 0.25);
            --mono: ui-monospace, 'SF Mono', 'Cascadia Code', Menlo, Consolas, monospace;
            --sans: system-ui, -apple-system, 'Segoe UI', Roboto, Ubuntu, Cantarell, sans-serif;
        }
        :root[data-theme="light"] {
            color-scheme: light;
            --bg-0: #eef2f8;
            --surface-1: #ffffff;
            --surface-2: #f1f5f9;
            --border: rgba(28, 50, 90, 0.14);
            --border-strong: rgba(28, 50, 90, 0.26);
            --ink: #0f172a;
            --ink-2: #3b4a63;
            --ink-3: #5b6b84;
            --cyan: #0891b2;
            --cyan-hi: #06b6d4;
            --cyan-lo: #0e7490;
            --ok: #047857;
            --warn: #b45309;
            --crit: #dc2626;
            --bg-glow: rgba(8, 145, 178, 0.09);
            --bg-grad-1: #f2f6fb;
            --bg-grad-2: #e9eff7;
            --bg-grad-3: #e2eaf3;
            --ok-bg: rgba(4, 120, 87, 0.10);
            --ok-border: rgba(4, 120, 87, 0.38);
            --warn-bg: rgba(180, 83, 9, 0.12);
            --warn-border: rgba(180, 83, 9, 0.38);
            --crit-bg: rgba(220, 38, 38, 0.10);
            --crit-bg-soft: rgba(220, 38, 38, 0.06);
            --crit-border: rgba(220, 38, 38, 0.38);
            --crit-border-strong: rgba(220, 38, 38, 0.48);
            --crit-ink: #b91c1c;
            --muted-bg: rgba(100, 116, 139, 0.12);
            --muted-border: rgba(100, 116, 139, 0.32);
            --cyan-bg: rgba(8, 145, 178, 0.09);
            --cyan-bg-strong: rgba(8, 145, 178, 0.15);
            --accent-border: rgba(8, 145, 178, 0.48);
            --btn-bg: rgba(255, 255, 255, 0.75);
            --seg-bg: rgba(28, 50, 90, 0.06);
            --card-shadow: 0 1px 2px rgba(15, 23, 42, 0.05), 0 4px 14px rgba(15, 23, 42, 0.07);
            --border-faint: rgba(28, 50, 90, 0.07);
            --surface-disabled: rgba(241, 245, 249, 0.6);
            --code-ink: #334155;
            --scrollbar: rgba(100, 116, 139, 0.30);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--sans);
            color: var(--ink-2);
            background:
                radial-gradient(1100px 480px at 50% -120px, var(--bg-glow), transparent 60%),
                linear-gradient(160deg, var(--bg-grad-1) 0%, var(--bg-grad-2) 55%, var(--bg-grad-3) 100%);
            background-attachment: fixed;
            min-height: 100vh;
            line-height: 1.55;
            font-size: 0.9375rem;
        }
        .container { margin: 0 auto; padding: clamp(1rem, 3vw, 2rem); }

        /* ---------- header ---------- */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .brand { display: flex; align-items: center; gap: 0.75rem; }
        .brand-mark { flex: none; filter: drop-shadow(0 0 6px rgba(34, 211, 238, 0.4)); }
        :root[data-theme="light"] .brand-mark { filter: drop-shadow(0 1px 2px rgba(8, 145, 178, 0.3)); }
        .wordmark {
            font-size: 1.375rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.2;
            display: flex;
            align-items: baseline;
            gap: 0.6rem;
            white-space: nowrap;
        }
        .wm-net { color: var(--ink); }
        .wm-watch { color: var(--cyan); }
        @supports (-webkit-background-clip: text) or (background-clip: text) {
            .wm-watch {
                background: linear-gradient(90deg, var(--cyan-hi), var(--cyan-lo));
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }
        }
        .wm-label {
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--ink-3);
            padding-left: 0.6rem;
            border-left: 1px solid var(--border-strong);
        }
        .meta {
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--ink-3);
            margin-top: 0.25rem;
        }
        .toolbar { display: flex; align-items: center; flex-wrap: wrap; gap: 0.6rem; }

        /* ---------- badges ---------- */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.3rem 0.85rem 0.3rem 0.6rem;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            white-space: nowrap;
        }
        .badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            flex: none;
        }
        .badge-healthy { color: var(--ok); background: var(--ok-bg); border-color: var(--ok-border); }
        .badge-healthy .dot { box-shadow: 0 0 8px rgba(52, 211, 153, 0.6); }
        @media (prefers-reduced-motion: no-preference) {
            .badge-healthy .dot { animation: nw-pulse 2.5s ease-in-out infinite; }
            @keyframes nw-pulse {
                0%, 100% { box-shadow: 0 0 4px rgba(52, 211, 153, 0.35); }
                50% { box-shadow: 0 0 10px rgba(52, 211, 153, 0.75); }
            }
        }
        .badge-degraded { color: var(--warn); background: var(--warn-bg); border-color: var(--warn-border); }
        .badge-unhealthy, .badge-failures { color: var(--crit); background: var(--crit-bg); border-color: var(--crit-border); }
        .badge-disabled { color: var(--ink-3); background: var(--muted-bg); border-color: var(--muted-border); }
        .badge-checking { color: var(--cyan); background: var(--cyan-bg); border-color: var(--accent-border); }
        .card .badge {
            font-size: 0.625rem;
            padding: 0.12rem 0.5rem 0.12rem 0.4rem;
            gap: 0.3rem;
            letter-spacing: 0.06em;
        }
        .card .badge .dot { width: 6px; height: 6px; }
        @media (prefers-reduced-motion: no-preference) {
            .badge-checking .dot { animation: nw-blink 1.2s ease-in-out infinite; }
            @keyframes nw-blink {
                0%, 100% { opacity: 0.35; }
                50% { opacity: 1; }
            }
        }

        /* ---------- buttons / toggle ---------- */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: var(--btn-bg);
            border: 1px solid var(--border-strong);
            color: var(--ink-2);
            border-radius: 8px;
            padding: 0.4rem 0.85rem;
            font-family: var(--sans);
            font-size: 0.8125rem;
            font-weight: 500;
            cursor: pointer;
            transition: color 0.15s, border-color 0.15s, background 0.15s;
        }
        .btn:hover { color: var(--ink); border-color: var(--accent-border); }
        .btn-icon { padding: 0.4rem 0.5rem; }
        #theme-toggle .icon-light { display: none; }
        :root[data-theme="light"] #theme-toggle .icon-light { display: block; }
        :root[data-theme="light"] #theme-toggle .icon-dark { display: none; }
        .btn-success { color: var(--ok); border-color: var(--ok-border); background: var(--ok-bg); }
        .seg {
            display: inline-flex;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--seg-bg);
            padding: 2px;
        }
        .seg .btn { border: none; background: transparent; color: var(--ink-3); border-radius: 6px; }
        .seg .btn:hover { color: var(--ink-2); }
        .seg .btn.active { background: var(--cyan-bg-strong); color: var(--cyan); }
        :is(button, summary, a):focus-visible { outline: 2px solid var(--cyan); outline-offset: 2px; }

        /* ---------- cards ---------- */
        .card {
            background: var(--surface-1);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        .card-failing { border-color: var(--crit-border); }
        .card-wide { grid-column: 1 / -1; }
        .card-refreshing { opacity: 0.55; transition: opacity 0.2s; }
        .card-skeleton .card-body { display: grid; gap: 0.85rem; align-content: start; min-height: 96px; }
        .skel-line { height: 12px; border-radius: 6px; background: var(--muted-bg); }
        @media (prefers-reduced-motion: no-preference) {
            .skel-line { animation: nw-shimmer 1.4s ease-in-out infinite; }
            @keyframes nw-shimmer {
                0%, 100% { opacity: 0.45; }
                50% { opacity: 1; }
            }
        }
        .probe-dot-pending { background: var(--cyan); }
        .probe-dot-warn { background: var(--warn); }
        tr.row-detail { display: none; }
        .metrics-open tr.row-detail { display: table-row; }
        .metrics-toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-top: 0.6rem;
            padding: 0.25rem 0;
            background: none;
            border: none;
            color: var(--ink-3);
            font-family: var(--sans);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
        }
        .metrics-toggle:hover { color: var(--ink-2); }
        .metrics-toggle::before {
            content: '';
            width: 0;
            height: 0;
            border-left: 5px solid currentColor;
            border-top: 4px solid transparent;
            border-bottom: 4px solid transparent;
            transition: transform 0.15s;
        }
        .metrics-open .metrics-toggle::before { transform: rotate(90deg); }
        .stat-value.stat-warn { color: var(--warn); }
        .stat-value.stat-crit { color: var(--crit); }
        .stat-delta-up { color: var(--crit); }
        .stat-delta-down { color: var(--ok); }
        .spark-sep { flex: none; width: 1px; align-self: stretch; background: var(--border-strong); }
        .status-summary { font-family: var(--mono); font-size: 0.75rem; color: var(--ink-3); }
        .fetch-error {
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--crit-ink);
            background: var(--crit-bg-soft);
            border-left: 2px solid var(--crit-border-strong);
            border-radius: 0 6px 6px 0;
            padding: 0.4rem 0.6rem;
            overflow-wrap: anywhere;
            margin-bottom: 0.85rem;
        }
        .btn-on { color: var(--cyan); border-color: var(--accent-border); background: var(--cyan-bg-strong); }
        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }
        .probe-title { display: flex; align-items: center; gap: 0.55rem; flex-wrap: wrap; }
        .probe-title h2 { font-size: 1rem; font-weight: 600; color: var(--ink); }
        .probe-dot { width: 8px; height: 8px; border-radius: 50%; flex: none; }
        .probe-dot-ok { background: var(--ok); }
        .probe-dot-crit { background: var(--crit); }
        .probe-dot-off { background: var(--ink-3); }
        .probe-name {
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--ink-3);
            overflow-wrap: anywhere;
            margin-top: 0.3rem;
        }
        .chip {
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--ink-3);
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 2px 8px;
            white-space: nowrap;
        }
        .card-body { padding: 1.25rem; }

        /* ---------- stat row ---------- */
        .stat-row {
            display: flex;
            align-items: flex-end;
            gap: 2rem;
            flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }
        .stat-label {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--ink-3);
        }
        .stat-value { font-size: 1.75rem; font-weight: 650; color: var(--ink); line-height: 1.2; }
        .stat-value .unit { font-size: 0.875rem; font-weight: 500; color: var(--ink-3); margin-left: 0.15rem; }
        .stat-context {
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--ink-3);
            font-variant-numeric: tabular-nums;
            margin-top: 0.15rem;
        }
        .spark {
            position: relative;
            display: flex;
            align-items: flex-end;
            gap: 2px;
            height: 44px;
            border-bottom: 1px solid var(--border);
            flex: 1;
            min-width: 120px;
        }
        .spark-line {
            position: absolute;
            left: 0;
            right: 0;
            border-top: 1px dashed;
            pointer-events: none;
        }
        .spark-line-warn { border-color: var(--warn); opacity: 0.55; }
        .spark-line-crit { border-color: var(--crit); opacity: 0.55; }
        .spark-bar {
            flex: 0 1 8px;
            min-width: 2px;
            border-radius: 2px 2px 0 0;
            background: var(--cyan);
            opacity: 0.85;
        }
        .spark-bar:hover { opacity: 1; }
        .spark-fail { background: var(--crit); }
        .spark-warn { background: var(--warn); }
        .spark-crit { background: var(--crit); }
        .spark-tip {
            display: none;
            position: fixed;
            z-index: 10;
            pointer-events: none;
            transform: translate(-50%, -100%);
            font-family: var(--mono);
            font-size: 0.6875rem;
            color: var(--ink);
            background: var(--surface-2);
            border: 1px solid var(--border-strong);
            border-radius: 6px;
            padding: 3px 8px;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }
        .spark-tip-fail { color: var(--crit-ink); border-color: var(--crit-border); }

        /* ---------- stats table ---------- */
        .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; min-width: 420px; border-collapse: collapse; font-size: 0.8125rem; }
        th, td { padding: 0.5rem 0.75rem; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        th {
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--ink-3);
            border-bottom: 1px solid var(--border-strong);
        }
        td {
            color: var(--ink-2);
            border-bottom: 1px solid var(--border-faint);
            font-variant-numeric: tabular-nums;
        }
        tr:last-child td { border-bottom: none; }
        tr.row-total td { color: var(--ink); font-weight: 600; }
        th.col-p95, td.col-p95 { background: var(--cyan-bg); }

        /* ---------- errors ---------- */
        details.errors { margin-top: 1rem; }
        details.errors summary {
            cursor: pointer;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--crit);
            padding: 0.4rem 0;
            list-style: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        details.errors summary::-webkit-details-marker { display: none; }
        details.errors summary::before {
            content: '';
            width: 0;
            height: 0;
            border-left: 5px solid currentColor;
            border-top: 4px solid transparent;
            border-bottom: 4px solid transparent;
            transition: transform 0.15s;
        }
        details.errors[open] summary::before { transform: rotate(90deg); }
        .error-list { list-style: none; padding: 0.25rem 0 0; display: grid; gap: 0.4rem; }
        .error-list li {
            font-family: var(--mono);
            font-size: 0.75rem;
            color: var(--crit-ink);
            background: var(--crit-bg-soft);
            border-left: 2px solid var(--crit-border-strong);
            border-radius: 0 6px 6px 0;
            padding: 0.4rem 0.6rem;
            overflow-wrap: anywhere;
        }
        .error-list .iter { color: var(--ink-3); margin-right: 0.5rem; }

        /* ---------- disabled / empty ---------- */
        .section-label {
            grid-column: 1 / -1;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--ink-3);
            margin: 0.75rem 0 0;
        }
        .card-disabled {
            background: var(--surface-disabled);
            border: 1px dashed var(--border-strong);
            box-shadow: none;
        }
        .card-disabled .card-body { padding: 0.85rem 1.25rem; font-size: 0.8125rem; color: var(--ink-3); }
        code {
            font-family: var(--mono);
            font-size: 0.85em;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 0.1em 0.4em;
            color: var(--ink-2);
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        .empty-state svg { margin-bottom: 1.25rem; }
        .empty-state h2 { font-size: 1.125rem; font-weight: 600; color: var(--ink); margin-bottom: 0.5rem; }
        .empty-state p { font-size: 0.875rem; color: var(--ink-3); max-width: 34rem; margin: 0 auto; }

        /* ---------- JSON panel ---------- */
        .json-panel {
            display: none;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 1.25rem;
            overflow: hidden;
        }
        .json-panel.visible { display: block; }
        .json-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid var(--border);
        }
        .json-panel-header > span {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--ink-3);
            text-transform: uppercase;
            letter-spacing: 0.14em;
        }
        .json-actions { display: flex; gap: 0.5rem; }
        .json-content { padding: 1.25rem; overflow: auto; max-height: 640px; }
        .json-content pre {
            margin: 0;
            font-family: var(--mono);
            font-size: 0.75rem;
            line-height: 1.6;
            color: var(--code-ink);
            white-space: pre;
        }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-thumb { background: var(--scrollbar); border-radius: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }

        .footer { text-align: center; margin-top: 2.5rem; font-size: 0.75rem; color: var(--ink-3); }
        .footer a { color: var(--ink-2); text-decoration: none; }
        .footer a:hover { color: var(--cyan); }
        .footer .heart { color: var(--crit); }
        .dashboard-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(420px, 100%), 1fr));
            gap: 1.25rem;
            align-items: start;
            margin-bottom: 1.25rem;
        }
        .dashboard-view.hidden { display: none; }

        @media (max-width: 640px) {
            .card-body { padding: 1rem; }
            .card-header { padding: 0.85rem 1rem; }
            .stat-row { gap: 1.25rem; }
            .stat-row .spark { flex-basis: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="brand">
                <svg class="brand-mark" width="40" height="24" viewBox="0 0 40 24" aria-hidden="true">
                    <defs>
                        <linearGradient id="nw-accent" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0" stop-color="#38e1f5"/>
                            <stop offset="1" stop-color="#06b6d4"/>
                        </linearGradient>
                    </defs>
                    <path d="M1 12h7l2.5-7 4 14 3-9.5 1.8 2.5H27" stroke="url(#nw-accent)"
                          stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <circle cx="31" cy="12" r="2.4" fill="#38e1f5"/>
                    <circle cx="31" cy="12" r="4.6" stroke="#38e1f5" stroke-opacity="0.35" fill="none"/>
                </svg>
                <div>
                    <div class="wordmark">
                        <span><span class="wm-net">Net</span><span class="wm-watch">watch</span></span>
                        <span class="wm-label">Health</span>
                    </div>
                    <div class="meta">
                        Checked <time id="checked-at" datetime="{{ $checkedAt }}">{{ $checkedAt }}</time>
                        · {{ $probeCount }} {{ $probeCount === 1 ? 'probe' : 'probes' }}
                        · <span id="iterations-meta">0 iterations</span>
                    </div>
                </div>
            </div>
            <div class="toolbar">
                <span class="status-summary" id="status-summary"></span>
                <span class="badge badge-{{ $overallStatus }}" id="overall-badge"><span class="dot"></span><span>{{ $overallStatus }}</span></span>
                <div class="seg" role="group" aria-label="View">
                    <button class="btn active" id="btn-dashboard" aria-pressed="true" onclick="showView('dashboard')">Dashboard</button>
                    <button class="btn" id="btn-json" aria-pressed="false" onclick="showView('json')">JSON</button>
                </div>
                <button class="btn" onclick="exportImage()">Export image</button>
                <button class="btn" onclick="runAll()">Run again</button>
                <button class="btn" id="btn-auto-refresh" aria-pressed="false" onclick="toggleAutoRefresh()"
                        title="Re-run all probes every 30 seconds">Auto 30s</button>
                <button class="btn btn-icon" id="theme-toggle" onclick="toggleTheme()"
                        aria-label="Switch theme" title="Switch theme">
                    <svg class="icon-light" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    <svg class="icon-dark" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                </button>
            </div>
        </div>

        <div class="dashboard-view" id="view-dashboard">
            @if ($probeCount === 0 && count($disabledProbes) === 0)
                <div class="card card-wide">
                    <div class="empty-state">
                        <svg width="96" height="96" viewBox="0 0 96 96" aria-hidden="true">
                            <circle cx="48" cy="48" r="18" stroke="#22d3ee" stroke-opacity="0.28" fill="none"/>
                            <circle cx="48" cy="48" r="30" stroke="#22d3ee" stroke-opacity="0.18" fill="none"/>
                            <circle cx="48" cy="48" r="42" stroke="#22d3ee" stroke-opacity="0.10" fill="none"/>
                            <circle cx="48" cy="48" r="3" fill="#38e1f5"/>
                        </svg>
                        <h2>No probes configured</h2>
                        <p>Add probes to <code>config/netwatch.php</code> and set their <code>enabled</code> flag to <code>true</code> to start monitoring latency.</p>
                    </div>
                </div>
            @endif

            @foreach ($probeNames as $name)
                <div class="card card-skeleton" data-probe="{{ $name }}" aria-busy="true">
                    <div class="card-header">
                        <div class="probe-title">
                            <span class="probe-dot probe-dot-pending"></span>
                            <h2>{{ $name }}</h2>
                            <span class="badge badge-checking"><span class="dot"></span><span>checking</span></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="skel-line" style="width: 34%"></div>
                        <div class="skel-line" style="width: 82%"></div>
                        <div class="skel-line" style="width: 64%"></div>
                    </div>
                </div>
            @endforeach

            @if (count($disabledProbes) > 0)
                <div class="section-label">Disabled probes</div>
                @foreach ($disabledProbes as $name)
                    <div class="card card-disabled">
                        <div class="card-header">
                            <div class="probe-title">
                                <span class="probe-dot probe-dot-off"></span>
                                <h2>{{ $name }}</h2>
                            </div>
                            <span class="badge badge-disabled"><span>disabled</span></span>
                        </div>
                        <div class="card-body">
                            This probe is disabled. Set its <code>enabled</code> flag to <code>true</code> in your config to activate it.
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="json-panel" id="view-json">
            <div class="json-panel-header">
                <span>JSON Response</span>
                <div class="json-actions">
                    <button class="btn" id="btn-copy" onclick="copyJson()">Copy</button>
                    <button class="btn" onclick="downloadJson()">Download</button>
                </div>
            </div>
            <div class="json-content">
                <pre id="json-output">{{ $jsonData }}</pre>
            </div>
        </div>

        <div class="footer">Powered by Netwatch · Made with <span class="heart">♥</span> by <a href="https://mathiasgrimm.com" target="_blank" rel="noopener">Mathias Grimm</a></div>
    </div>

    <script>
        const NETWATCH_META = @json($netwatchMeta);
        const NETWATCH_PROBES = @json($probeNamesList);
        const NETWATCH_THRESHOLDS = @json($thresholds);

        var collected = {};
        var fetchErrors = {};
        var history = {};
        var runSeq = {};
        var prevRunP95 = {};
        var expandedMetrics = {};
        var pendingCount = 0;
        var skeletonHtml = {};
        var checkedAtMs = NaN;
        var autoOn = false;
        var autoTimer = null;
        var AUTO_REFRESH_MS = 30000;
        var HISTORY_MAX = 60;

        function escapeName(name) {
            return window.CSS && CSS.escape ? CSS.escape(name) : name.replace(/"/g, '\\"');
        }

        function cardFor(name) {
            return document.querySelector('.card[data-probe="' + escapeName(name) + '"]');
        }

        function probeUrl(name) {
            var url = location.pathname.replace(/\/+$/, '') + '/probes/' + encodeURIComponent(name);
            var token = new URLSearchParams(location.search).get('token');
            if (token) {
                url += '?token=' + encodeURIComponent(token);
            }
            return url;
        }

        function replaceCard(name, html) {
            var card = cardFor(name);
            if (!card) return;
            var tpl = document.createElement('template');
            tpl.innerHTML = html.trim();
            card.replaceWith(tpl.content);
        }

        function renderErrorCard(name, message) {
            var card = cardFor(name);
            if (!card) return;

            var el = document.createElement('div');
            el.className = 'card card-failing';
            el.setAttribute('data-probe', name);

            var header = document.createElement('div');
            header.className = 'card-header';
            var title = document.createElement('div');
            title.className = 'probe-title';
            var dot = document.createElement('span');
            dot.className = 'probe-dot probe-dot-crit';
            var h2 = document.createElement('h2');
            h2.textContent = name;
            var badge = document.createElement('span');
            badge.className = 'badge badge-failures';
            var badgeText = document.createElement('span');
            badgeText.textContent = 'fetch failed';
            badge.appendChild(badgeText);
            title.append(dot, h2, badge);
            header.appendChild(title);

            var body = document.createElement('div');
            body.className = 'card-body';
            var error = document.createElement('div');
            error.className = 'fetch-error';
            error.textContent = message;
            var retry = document.createElement('button');
            retry.className = 'btn';
            retry.textContent = 'Retry';
            retry.setAttribute('data-retry', name);
            body.append(error, retry);

            el.append(header, body);
            card.replaceWith(el);
        }

        function captureCardState(name) {
            var card = cardFor(name);
            var state = { errorsOpen: false, focus: null };
            if (!card) return state;
            var errors = card.querySelector('details.errors');
            state.errorsOpen = !!(errors && errors.open);
            var active = document.activeElement;
            if (active && card.contains(active)) {
                state.focus = active.hasAttribute('data-retry') ? '[data-retry]'
                    : active.tagName === 'SUMMARY' ? 'details.errors summary'
                    : '[data-metrics-toggle]';
            }
            return state;
        }

        function restoreCardState(name, state) {
            var card = cardFor(name);
            if (!card) return;
            if (expandedMetrics[name]) {
                card.classList.add('metrics-open');
                var toggle = card.querySelector('[data-metrics-toggle]');
                if (toggle) toggle.setAttribute('aria-expanded', 'true');
            }
            var errors = card.querySelector('details.errors');
            if (errors && state.errorsOpen) errors.open = true;
            if (state.focus) {
                var target = card.querySelector(state.focus) || card.querySelector('[data-retry]');
                if (target) target.focus();
            }
        }

        function fetchProbe(name) {
            return fetch(probeUrl(name), { headers: { 'Accept': 'application/json' } })
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    return res.json();
                })
                .then(function (payload) {
                    var state = captureCardState(name);
                    delete fetchErrors[name];
                    prevRunP95[name] = collected[name] ? collected[name].stats.total_ms.p95 : null;
                    collected[name] = payload.result;
                    runSeq[name] = (runSeq[name] || 0) + 1;
                    var stamped = (payload.result.results || []).map(function (r, i) {
                        return Object.assign({}, r, { run: runSeq[name], seq: i + 1, at: payload.checked_at });
                    });
                    history[name] = (history[name] || []).concat(stamped).slice(-HISTORY_MAX);
                    replaceCard(name, payload.html);
                    renderSparkHistory(name);
                    applyHistoryStats(name);
                    restoreCardState(name, state);
                })
                .catch(function (err) {
                    var state = captureCardState(name);
                    delete collected[name];
                    fetchErrors[name] = 'Request failed (' + (err && err.message ? err.message : 'network error') + ')';
                    renderErrorCard(name, fetchErrors[name]);
                    restoreCardState(name, state);
                })
                .then(function () {
                    pendingCount--;
                    sync();
                });
        }

        // The card fragment only charts its own run; rebuild the sparkline
        // from the accumulated history so bars persist across refreshes.
        function renderSparkHistory(name) {
            var card = cardFor(name);
            var hist = history[name] || [];
            if (!card || hist.length === 0) return;
            var spark = card.querySelector('.spark');
            if (!spark) return;

            var maxTotal = 0;
            hist.forEach(function (r) {
                if (r.total_ms > maxTotal) maxTotal = r.total_ms;
            });
            if (maxTotal <= 0) maxTotal = 1;

            spark.textContent = '';
            hist.forEach(function (r, i) {
                if (i > 0 && r.run !== hist[i - 1].run) {
                    var sep = document.createElement('span');
                    sep.className = 'spark-sep';
                    spark.appendChild(sep);
                }
                var status = r.status || (r.success ? 'ok' : 'failing');
                var bar = document.createElement('span');
                bar.className = 'spark-bar'
                    + (status === 'failing' ? ' spark-fail' : status === 'crit' ? ' spark-crit' : status === 'warn' ? ' spark-warn' : '');
                bar.style.height = Math.max(8, Math.round(r.total_ms / maxTotal * 100)) + '%';
                var when = r.at ? ' · ' + new Date(r.at).toLocaleTimeString() : '';
                var suffix = status === 'failing' ? ' · failed' : status === 'crit' ? ' · ≥ crit' : status === 'warn' ? ' · ≥ warn' : '';
                bar.setAttribute('data-tip', '#' + (r.seq || i + 1) + ' · ' + Number(r.total_ms).toFixed(2) + ' ms' + when + suffix);
                spark.appendChild(bar);
            });

            // Dashed guides at the warn/crit thresholds, when they fall inside the scale
            var th = NETWATCH_THRESHOLDS[name] || null;
            if (th) {
                ['warn', 'crit'].forEach(function (key) {
                    var value = th[key];
                    if (value === null || value === undefined || value > maxTotal) return;
                    var line = document.createElement('span');
                    line.className = 'spark-line spark-line-' + key;
                    line.style.bottom = Math.min(100, Math.round(value / maxTotal * 100)) + '%';
                    spark.appendChild(line);
                });
            }
        }

        function nwNumber(v, decimals) {
            return Number(v).toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        }

        // Mirrors Runner::percentile() — linear interpolation over sorted values.
        function percentile(sorted, p) {
            var count = sorted.length;
            if (count === 1) return sorted[0];
            var index = (p / 100) * (count - 1);
            var lower = Math.floor(index);
            var upper = Math.ceil(index);
            var fraction = index - lower;
            if (lower === upper) return sorted[lower];
            return sorted[lower] + fraction * (sorted[upper] - sorted[lower]);
        }

        // Mirrors Runner::computeStats() — successful iterations only, zeros when none.
        function computeHistoryStats(hist) {
            var ok = hist.filter(function (r) { return r.success; });
            var stats = {};
            ['connect_ms', 'request_ms', 'total_ms'].forEach(function (key) {
                if (ok.length === 0) {
                    stats[key] = { min: 0, max: 0, avg: 0, p50: 0, p95: 0, p99: 0 };
                    return;
                }
                var values = ok.map(function (r) { return r[key]; }).sort(function (a, b) { return a - b; });
                var sum = values.reduce(function (a, b) { return a + b; }, 0);
                stats[key] = {
                    min: values[0],
                    max: values[values.length - 1],
                    avg: sum / values.length,
                    p50: percentile(values, 50),
                    p95: percentile(values, 95),
                    p99: percentile(values, 99)
                };
            });
            return stats;
        }

        // The card fragment's table and headline only cover its own run;
        // recompute them over the accumulated history so the metrics reflect
        // every iteration charted in the sparkline.
        function applyHistoryStats(name) {
            var card = cardFor(name);
            var hist = history[name] || [];
            if (!card || hist.length === 0) return;

            var stats = computeHistoryStats(hist);
            var total = stats.total_ms;

            var fmtStat = function (v) { return nwNumber(v, v < 10 ? 2 : (v < 100 ? 1 : 0)); };
            var th = NETWATCH_THRESHOLDS[name] || null;
            var statValue = card.querySelector('.stat-value');
            if (statValue) {
                var unit = statValue.querySelector('.unit');
                statValue.textContent = fmtStat(total.p95);
                if (unit) statValue.appendChild(unit);
                statValue.classList.remove('stat-warn', 'stat-crit');
                if (th && th.crit !== null && total.p95 >= th.crit) {
                    statValue.classList.add('stat-crit');
                } else if (th && th.warn !== null && total.p95 >= th.warn) {
                    statValue.classList.add('stat-warn');
                }
                if (th && (th.warn !== null || th.crit !== null)) {
                    statValue.title = 'Thresholds:'
                        + (th.warn !== null ? ' warn ≥ ' + th.warn + ' ms' : '')
                        + (th.warn !== null && th.crit !== null ? ' ·' : '')
                        + (th.crit !== null ? ' crit ≥ ' + th.crit + ' ms' : '');
                }
            }
            // Badge, dot and border follow the history-based status too;
            // a failures badge from the latest run keeps priority.
            var latest = collected[name];
            if (latest && latest.failures === 0) {
                var cardStatus = th && th.crit !== null && total.p95 >= th.crit ? 'crit'
                    : th && th.warn !== null && total.p95 >= th.warn ? 'warn'
                    : 'ok';
                var badge = card.querySelector('[data-probe-badge]');
                if (badge) {
                    badge.className = 'badge '
                        + (cardStatus === 'crit' ? 'badge-unhealthy' : cardStatus === 'warn' ? 'badge-degraded' : 'badge-healthy');
                    var badgeLabel = badge.querySelector('span');
                    if (badgeLabel) {
                        badgeLabel.textContent = cardStatus === 'crit' ? 'critical' : cardStatus === 'warn' ? 'slow' : 'healthy';
                    }
                }
                var dot = card.querySelector('.probe-dot');
                if (dot) {
                    dot.className = 'probe-dot '
                        + (cardStatus === 'crit' ? 'probe-dot-crit' : cardStatus === 'warn' ? 'probe-dot-warn' : 'probe-dot-ok');
                }
                card.classList.toggle('card-failing', cardStatus === 'crit');
            }

            var statContext = card.querySelector('.stat-context');
            if (statContext) {
                statContext.textContent = 'p50 ' + fmtStat(total.p50) + ' · avg ' + fmtStat(total.avg);
                var prev = prevRunP95[name];
                var curr = collected[name] ? collected[name].stats.total_ms.p95 : null;
                if (prev && curr !== null && prev > 0) {
                    var delta = Math.round((curr - prev) / prev * 100);
                    if (Math.abs(delta) >= 1) {
                        var deltaEl = document.createElement('span');
                        deltaEl.className = delta > 0 ? 'stat-delta-up' : 'stat-delta-down';
                        deltaEl.textContent = ' · ' + (delta > 0 ? '▲' : '▼') + Math.abs(delta) + '% p95 vs prev';
                        statContext.appendChild(deltaEl);
                    }
                }
            }

            var keys = ['connect_ms', 'request_ms', 'total_ms'];
            card.querySelectorAll('tbody tr').forEach(function (row, i) {
                var metric = stats[keys[i]];
                if (!metric) return;
                var cells = row.querySelectorAll('td');
                ['min', 'max', 'avg', 'p50', 'p95', 'p99'].forEach(function (stat, j) {
                    if (cells[j + 1]) cells[j + 1].textContent = nwNumber(metric[stat], 2);
                });
            });

            var chip = card.querySelector('.chip');
            if (chip) {
                chip.textContent = '×' + hist.length;
                chip.title = hist.length + ' iterations';
            }
        }

        var STATUS_DOTS = { healthy: '🟢', degraded: '🟡', unhealthy: '🔴', checking: '🔵' };

        // Mirror the overall status in the browser tab title; the favicon
        // stays the Netwatch brand mark.
        function updateTabStatus(status) {
            document.title = (STATUS_DOTS[status] ? STATUS_DOTS[status] + ' ' : '') + 'Netwatch Health Dashboard';
        }

        function setBadge(status) {
            var badge = document.getElementById('overall-badge');
            badge.className = 'badge badge-' + status;
            badge.querySelector('span:last-child').textContent = status;
            NETWATCH_META.status = status;
            updateTabStatus(status);
        }

        function probeStatuses() {
            var statuses = {};
            NETWATCH_PROBES.forEach(function (name) {
                if (fetchErrors[name]) { statuses[name] = 'error'; return; }
                var result = collected[name];
                if (!result) { statuses[name] = 'pending'; return; }
                if (result.failures > 0) { statuses[name] = 'failing'; return; }
                var th = NETWATCH_THRESHOLDS[name] || null;
                var p95 = computeHistoryStats(history[name] || []).total_ms.p95;
                if (th && th.crit !== null && p95 >= th.crit) { statuses[name] = 'crit'; return; }
                if (th && th.warn !== null && p95 >= th.warn) { statuses[name] = 'warn'; return; }
                statuses[name] = 'ok';
            });
            return statuses;
        }

        function countStatuses(statuses) {
            var counts = { error: 0, failing: 0, crit: 0, warn: 0, ok: 0, pending: 0 };
            NETWATCH_PROBES.forEach(function (name) { counts[statuses[name]]++; });
            return counts;
        }

        function updateSummary(counts) {
            var el = document.getElementById('status-summary');
            var total = NETWATCH_PROBES.length;
            if (total === 0) {
                el.textContent = '';
                return;
            }
            if (pendingCount > 0) {
                el.textContent = (total - counts.pending) + '/' + total + ' checked';
                return;
            }
            var failing = counts.error + counts.failing;
            var parts = [];
            if (failing > 0) parts.push(failing + ' failing');
            if (counts.crit > 0) parts.push(counts.crit + ' critical');
            if (counts.warn > 0) parts.push(counts.warn + ' slow');
            parts.push(counts.ok + ' healthy');
            el.textContent = parts.join(' · ');
        }

        var SEVERITY = { error: 4, failing: 3, crit: 2, warn: 1, ok: 0, pending: 0 };

        function sortCards(statuses) {
            var container = document.getElementById('view-dashboard');
            var marker = container.querySelector('.section-label, .card-disabled');
            var sorted = NETWATCH_PROBES.slice()
                .sort(function (a, b) {
                    return SEVERITY[statuses[b]] - SEVERITY[statuses[a]]
                        || NETWATCH_PROBES.indexOf(a) - NETWATCH_PROBES.indexOf(b);
                });
            var current = Array.prototype.map.call(
                container.querySelectorAll('.card[data-probe]'),
                function (c) { return c.getAttribute('data-probe'); }
            );
            if (sorted.join('\n') === current.join('\n')) return;

            var focused = document.activeElement;
            sorted.forEach(function (name) {
                var card = cardFor(name);
                if (card) container.insertBefore(card, marker);
            });
            // Moving a focused element drops focus to <body>; give it back.
            if (focused && focused !== document.body && document.activeElement !== focused && document.contains(focused)) {
                focused.focus();
            }
        }

        function sync() {
            var json = {};
            var iterations = 0;

            NETWATCH_PROBES.forEach(function (name) {
                var result = collected[name];
                if (fetchErrors[name] || !result) return;
                json[name] = result;
                iterations += result.iterations;
            });

            document.getElementById('json-output').textContent = JSON.stringify(json, null, 4);
            document.getElementById('iterations-meta').textContent =
                iterations + (iterations === 1 ? ' iteration' : ' iterations');
            NETWATCH_META.iterationCount = iterations;

            var statuses = probeStatuses();
            var counts = countStatuses(statuses);
            var failing = counts.error + counts.failing;
            updateSummary(counts);

            var total = NETWATCH_PROBES.length;
            if (pendingCount > 0) {
                setBadge(failing + counts.crit === 0 ? 'checking' : 'degraded');
                return;
            }

            if (total === 0) {
                setBadge('healthy');
            } else if (failing === total) {
                setBadge('unhealthy');
            } else if (failing > 0 || counts.crit > 0) {
                setBadge('degraded');
            } else {
                setBadge('healthy');
            }

            if (total > 0) {
                sortCards(statuses);
                checkedAtMs = Date.now();
                NETWATCH_META.checkedAt = new Date(checkedAtMs).toISOString();
                scheduleAutoRefresh();
            }
        }

        function runAll() {
            if (NETWATCH_PROBES.length === 0 || pendingCount > 0) return;
            clearTimeout(autoTimer);
            autoTimer = null;
            pendingCount = NETWATCH_PROBES.length;
            setBadge('checking');
            NETWATCH_PROBES.forEach(function (name) {
                var card = cardFor(name);
                if (card && !card.classList.contains('card-skeleton')) {
                    card.classList.add('card-refreshing');
                    card.setAttribute('aria-busy', 'true');
                }
                fetchProbe(name);
            });
        }

        function scheduleAutoRefresh() {
            clearTimeout(autoTimer);
            autoTimer = null;
            if (!autoOn || document.hidden || NETWATCH_PROBES.length === 0) return;
            autoTimer = setTimeout(runAll, AUTO_REFRESH_MS);
        }

        function toggleAutoRefresh() {
            autoOn = !autoOn;
            try {
                if (autoOn) {
                    localStorage.setItem('netwatch-auto-refresh', 'on');
                } else {
                    localStorage.removeItem('netwatch-auto-refresh');
                }
            } catch (e) {}
            syncAutoRefreshButton();
            if (autoOn && pendingCount === 0) {
                scheduleAutoRefresh();
            } else if (!autoOn) {
                clearTimeout(autoTimer);
                autoTimer = null;
            }
        }

        function syncAutoRefreshButton() {
            var btn = document.getElementById('btn-auto-refresh');
            btn.classList.toggle('btn-on', autoOn);
            btn.setAttribute('aria-pressed', String(autoOn));
        }

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                clearTimeout(autoTimer);
                autoTimer = null;
            } else if (autoOn && pendingCount === 0) {
                scheduleAutoRefresh();
            }
        });

        document.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('[data-retry]') : null;
            if (!btn) return;
            var name = btn.getAttribute('data-retry');
            if (skeletonHtml[name]) {
                replaceCard(name, skeletonHtml[name]);
            }
            pendingCount++;
            setBadge('checking');
            fetchProbe(name);
        });

        document.addEventListener('click', function (e) {
            var btn = e.target.closest ? e.target.closest('[data-metrics-toggle]') : null;
            if (!btn) return;
            var card = btn.closest('.card');
            if (!card) return;
            var open = card.classList.toggle('metrics-open');
            btn.setAttribute('aria-expanded', String(open));
            expandedMetrics[card.getAttribute('data-probe')] = open;
        });

        (function () {
            try {
                autoOn = localStorage.getItem('netwatch-auto-refresh') === 'on';
            } catch (e) {}
            syncAutoRefreshButton();

            NETWATCH_PROBES.forEach(function (name) {
                var card = cardFor(name);
                if (card) skeletonHtml[name] = card.outerHTML;
            });

            if (NETWATCH_PROBES.length === 0) {
                checkedAtMs = new Date(NETWATCH_META.checkedAt).getTime();
            }

            updateTabStatus(NETWATCH_META.status);
            runAll();
        })();

        function showView(view) {
            var dashboard = document.getElementById('view-dashboard');
            var json = document.getElementById('view-json');
            var btnDashboard = document.getElementById('btn-dashboard');
            var btnJson = document.getElementById('btn-json');
            var isJson = view === 'json';

            dashboard.classList.toggle('hidden', isJson);
            json.classList.toggle('visible', isJson);
            btnDashboard.classList.toggle('active', !isJson);
            btnJson.classList.toggle('active', isJson);
            btnDashboard.setAttribute('aria-pressed', String(!isJson));
            btnJson.setAttribute('aria-pressed', String(isJson));
        }

        function copyJson() {
            var text = document.getElementById('json-output').textContent;
            var btn = document.getElementById('btn-copy');

            function confirmCopied() {
                btn.textContent = 'Copied!';
                btn.classList.add('btn-success');
                setTimeout(function () {
                    btn.textContent = 'Copy';
                    btn.classList.remove('btn-success');
                }, 2000);
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(confirmCopied);
            } else {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                confirmCopied();
            }
        }

        function nwFilename(ext) {
            return 'netwatch-health-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.' + ext;
        }

        function downloadBlob(blob, filename) {
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(url);
        }

        function downloadJson() {
            var text = document.getElementById('json-output').textContent;
            downloadBlob(new Blob([text], { type: 'application/json' }), nwFilename('json'));
        }

        function exportImage() {
            var data = JSON.parse(document.getElementById('json-output').textContent);
            var probeKeys = Object.keys(data);
            var disabled = NETWATCH_META.disabledProbes || [];

            var W = 1200;
            var PAD = 48;
            var HEADER_H = 120;
            var ROW_H = 168;
            var GAP = 16;
            var FOOTER_H = 64;

            var H = PAD + HEADER_H + FOOTER_H + probeKeys.length * (ROW_H + GAP);
            if (probeKeys.length === 0 && disabled.length === 0) {
                H += 156;
            }
            if (disabled.length > 0) {
                H += 40 + disabled.length * 52;
            }

            var S = Math.min(Math.max(window.devicePixelRatio || 1, 2), 3);
            if (H * S > 16384) {
                S = 1;
            }

            var canvas = document.createElement('canvas');
            canvas.width = W * S;
            canvas.height = H * S;
            var ctx = canvas.getContext('2d');
            ctx.scale(S, S);

            var root = getComputedStyle(document.documentElement);
            function token(name) {
                return root.getPropertyValue(name).trim();
            }

            // Canvas colors that have no CSS token equivalent, per theme.
            var isDark = document.documentElement.getAttribute('data-theme') !== 'light';
            var T = isDark ? {
                bgTop: '#131a2b',
                bgBottom: '#0a0f1d',
                glow: 'rgba(34, 211, 238, 0.08)',
                glowEnd: 'rgba(34, 211, 238, 0)',
                border: 'rgba(148, 170, 220, 0.18)',
                ring: 'rgba(56, 225, 245, 0.35)',
                spark: 'rgba(34, 211, 238, 0.75)',
                disabledFill: 'rgba(13, 21, 38, 0.5)',
                statusRgb: { healthy: '52, 211, 153', degraded: '251, 191, 36', unhealthy: '248, 113, 113' }
            } : {
                bgTop: '#f2f6fb',
                bgBottom: '#e2eaf3',
                glow: 'rgba(8, 145, 178, 0.07)',
                glowEnd: 'rgba(8, 145, 178, 0)',
                border: 'rgba(28, 50, 90, 0.18)',
                ring: 'rgba(8, 145, 178, 0.35)',
                spark: 'rgba(8, 145, 178, 0.8)',
                disabledFill: 'rgba(241, 245, 249, 0.7)',
                statusRgb: { healthy: '4, 120, 87', degraded: '180, 83, 9', unhealthy: '220, 38, 38' }
            };

            var C = {
                ink: token('--ink'),
                ink2: token('--ink-2'),
                ink3: token('--ink-3'),
                cyan: token('--cyan'),
                cyanHi: token('--cyan-hi'),
                cyanLo: token('--cyan-lo'),
                ok: token('--ok'),
                crit: token('--crit'),
                surface: token('--surface-1'),
                border: T.border,
                sans: token('--sans'),
                mono: token('--mono')
            };

            function rr(x, y, w, h, r) {
                ctx.beginPath();
                ctx.moveTo(x + r, y);
                ctx.arcTo(x + w, y, x + w, y + h, r);
                ctx.arcTo(x + w, y + h, x, y + h, r);
                ctx.arcTo(x, y + h, x, y, r);
                ctx.arcTo(x, y, x + w, y, r);
                ctx.closePath();
            }

            function truncate(text, maxWidth) {
                text = String(text);
                if (ctx.measureText(text).width <= maxWidth) return text;
                while (text.length > 1 && ctx.measureText(text + '…').width > maxWidth) {
                    text = text.slice(0, -1);
                }
                return text + '…';
            }

            function fmtMs(v) {
                if (v === null || v === undefined || isNaN(v)) return '—';
                var decimals = v < 10 ? 2 : (v < 100 ? 1 : 0);
                return Number(v).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            var statusColor = { healthy: C.ok, degraded: token('--warn'), unhealthy: C.crit };
            var statusRgb = T.statusRgb;

            // background: gradient + faint cyan bloom, echoing the page body
            var bg = ctx.createLinearGradient(0, 0, 0, H);
            bg.addColorStop(0, T.bgTop);
            bg.addColorStop(1, T.bgBottom);
            ctx.fillStyle = bg;
            ctx.fillRect(0, 0, W, H);
            var glow = ctx.createRadialGradient(W / 2, -100, 0, W / 2, -100, 700);
            glow.addColorStop(0, T.glow);
            glow.addColorStop(1, T.glowEnd);
            ctx.fillStyle = glow;
            ctx.fillRect(0, 0, W, Math.min(H, 420));

            // brand mark: the header SVG pulse line redrawn at 1.5x
            var mx = PAD, my = PAD + 4, k = 1.5;
            var accent = ctx.createLinearGradient(mx, 0, mx + 40 * k, 0);
            accent.addColorStop(0, C.cyanHi);
            accent.addColorStop(1, C.cyanLo);
            ctx.strokeStyle = accent;
            ctx.lineWidth = 2 * k;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.beginPath();
            var pts = [[1, 12], [8, 12], [10.5, 5], [14.5, 19], [17.5, 9.5], [19.3, 12], [27, 12]];
            for (var i = 0; i < pts.length; i++) {
                var px = mx + pts[i][0] * k;
                var py = my + pts[i][1] * k;
                if (i === 0) { ctx.moveTo(px, py); } else { ctx.lineTo(px, py); }
            }
            ctx.stroke();
            ctx.fillStyle = C.cyanHi;
            ctx.beginPath();
            ctx.arc(mx + 31 * k, my + 12 * k, 2.4 * k, 0, Math.PI * 2);
            ctx.fill();
            ctx.strokeStyle = T.ring;
            ctx.lineWidth = k;
            ctx.beginPath();
            ctx.arc(mx + 31 * k, my + 12 * k, 4.6 * k, 0, Math.PI * 2);
            ctx.stroke();

            // wordmark
            var wx = mx + 40 * k + 16;
            var wy = my + 12 * k + 9;
            ctx.textBaseline = 'alphabetic';
            ctx.font = '700 28px ' + C.sans;
            ctx.fillStyle = C.ink;
            ctx.fillText('Net', wx, wy);
            var netW = ctx.measureText('Net').width;
            var watchW = ctx.measureText('watch').width;
            var wmGrad = ctx.createLinearGradient(wx + netW, 0, wx + netW + watchW, 0);
            wmGrad.addColorStop(0, C.cyanHi);
            wmGrad.addColorStop(1, C.cyanLo);
            ctx.fillStyle = wmGrad;
            ctx.fillText('watch', wx + netW, wy);
            ctx.font = '600 11px ' + C.sans;
            ctx.fillStyle = C.ink3;
            ctx.fillText('H E A L T H', wx + netW + watchW + 14, wy);

            // meta line
            ctx.font = '12px ' + C.mono;
            ctx.fillStyle = C.ink3;
            ctx.fillText(
                'Checked ' + NETWATCH_META.checkedAt
                + ' · ' + NETWATCH_META.probeCount + (NETWATCH_META.probeCount === 1 ? ' probe' : ' probes')
                + ' · ' + NETWATCH_META.iterationCount + (NETWATCH_META.iterationCount === 1 ? ' iteration' : ' iterations'),
                wx, wy + 24
            );

            // status badge, right-aligned
            var status = NETWATCH_META.status;
            var srgb = statusRgb[status] || '124, 141, 176';
            ctx.font = '600 12px ' + C.sans;
            var label = status.toUpperCase();
            var bw = ctx.measureText(label).width + 46;
            var bx = W - PAD - bw;
            var by = PAD + 8;
            var bh = 32;
            ctx.fillStyle = 'rgba(' + srgb + ', 0.10)';
            rr(bx, by, bw, bh, bh / 2);
            ctx.fill();
            ctx.strokeStyle = 'rgba(' + srgb + ', 0.30)';
            ctx.lineWidth = 1;
            ctx.stroke();
            ctx.fillStyle = statusColor[status] || C.ink3;
            ctx.beginPath();
            ctx.arc(bx + 17, by + bh / 2, 4, 0, Math.PI * 2);
            ctx.fill();
            ctx.fillText(label, bx + 28, by + bh / 2 + 4);

            var y = PAD + HEADER_H;

            if (probeKeys.length === 0 && disabled.length === 0) {
                ctx.fillStyle = C.surface;
                rr(PAD, y, W - PAD * 2, 140, 12);
                ctx.fill();
                ctx.strokeStyle = C.border;
                ctx.lineWidth = 1;
                ctx.stroke();
                ctx.textAlign = 'center';
                ctx.font = '600 18px ' + C.sans;
                ctx.fillStyle = C.ink;
                ctx.fillText('No probes configured', W / 2, y + 66);
                ctx.font = '13px ' + C.sans;
                ctx.fillStyle = C.ink3;
                ctx.fillText('Add probes to config/netwatch.php to start monitoring latency.', W / 2, y + 92);
                ctx.textAlign = 'left';
                y += 156;
            }

            var statCols = [
                { label: 'MIN', key: 'min' },
                { label: 'AVG', key: 'avg' },
                { label: 'P95', key: 'p95' },
                { label: 'MAX', key: 'max' }
            ];
            var colW = 92;

            probeKeys.forEach(function (key) {
                var p = data[key];
                var failing = p.failures > 0;

                ctx.fillStyle = C.surface;
                rr(PAD, y, W - PAD * 2, ROW_H, 12);
                ctx.fill();
                ctx.strokeStyle = failing ? 'rgba(248, 113, 113, 0.35)' : C.border;
                ctx.lineWidth = 1;
                ctx.stroke();

                ctx.fillStyle = failing ? C.crit : C.ok;
                ctx.beginPath();
                ctx.arc(PAD + 26, y + 36, 4, 0, Math.PI * 2);
                ctx.fill();

                ctx.font = '600 16px ' + C.sans;
                ctx.fillStyle = C.ink;
                var keyText = truncate(key, 240);
                ctx.fillText(keyText, PAD + 40, y + 41);
                var keyW = ctx.measureText(keyText).width;

                var chip = failing
                    ? p.failures + (p.failures > 1 ? ' failures' : ' failure')
                    : (p.iterations - p.failures) + '/' + p.iterations + ' ok';
                ctx.font = '600 11px ' + C.sans;
                ctx.fillStyle = failing ? C.crit : C.ok;
                ctx.fillText(chip.toUpperCase(), PAD + 40 + keyW + 12, y + 40);

                ctx.font = '12px ' + C.mono;
                ctx.fillStyle = C.ink3;
                ctx.fillText(truncate(p.name, 330), PAD + 40, y + 66);

                if (Array.isArray(p.results) && p.results.length > 0) {
                    var sx = PAD + 380;
                    var sw = 200;
                    var sh = 52;
                    var sb = y + ROW_H / 2 + 26;
                    var samples = p.results.slice(-40);
                    var maxTotal = 0;
                    samples.forEach(function (r) {
                        if (r.total_ms > maxTotal) maxTotal = r.total_ms;
                    });
                    if (maxTotal <= 0) maxTotal = 1;
                    var step = sw / samples.length;
                    var barW = Math.max(2, Math.min(6, step - 2));
                    samples.forEach(function (r, idx) {
                        var barH = Math.max(3, (r.total_ms / maxTotal) * sh);
                        ctx.fillStyle = r.success ? T.spark : C.crit;
                        ctx.fillRect(sx + idx * step, sb - barH, barW, barH);
                    });
                }

                var metricRows = [
                    { label: 'Connect', key: 'connect_ms' },
                    { label: 'Request', key: 'request_ms' },
                    { label: 'Total', key: 'total_ms' }
                ];
                var tableRight = W - PAD - 20;
                var labelX = tableRight - statCols.length * colW - 78;
                statCols.forEach(function (col, idx) {
                    var cx = tableRight - (statCols.length - idx) * colW + colW / 2;
                    ctx.textAlign = 'center';
                    ctx.font = '600 10px ' + C.sans;
                    ctx.fillStyle = C.ink3;
                    ctx.fillText(col.label, cx, y + 44);
                    ctx.textAlign = 'left';
                });
                metricRows.forEach(function (row, rIdx) {
                    var ry = y + 74 + rIdx * 30;
                    var isTotal = row.key === 'total_ms';
                    var stats = (p.stats && p.stats[row.key]) || {};
                    ctx.font = (isTotal ? '600 ' : '') + '12px ' + C.sans;
                    ctx.fillStyle = isTotal ? C.ink : C.ink3;
                    ctx.fillText(row.label, labelX, ry);
                    statCols.forEach(function (col, idx) {
                        var cx = tableRight - (statCols.length - idx) * colW + colW / 2;
                        ctx.textAlign = 'center';
                        ctx.font = (isTotal ? '600' : '500') + ' 14px ' + C.sans;
                        ctx.fillStyle = col.key === 'p95' ? C.cyan : (isTotal ? C.ink : C.ink2);
                        var value = fmtMs(stats[col.key]);
                        ctx.fillText(value === '—' ? value : value + ' ms', cx, ry);
                        ctx.textAlign = 'left';
                    });
                });

                y += ROW_H + GAP;
            });

            if (disabled.length > 0) {
                ctx.font = '600 11px ' + C.sans;
                ctx.fillStyle = C.ink3;
                ctx.fillText('DISABLED PROBES', PAD, y + 24);
                y += 40;
                disabled.forEach(function (name) {
                    ctx.fillStyle = T.disabledFill;
                    rr(PAD, y, W - PAD * 2, 44, 10);
                    ctx.fill();
                    ctx.setLineDash([4, 4]);
                    ctx.strokeStyle = C.border;
                    ctx.lineWidth = 1;
                    ctx.stroke();
                    ctx.setLineDash([]);
                    ctx.fillStyle = C.ink3;
                    ctx.beginPath();
                    ctx.arc(PAD + 26, y + 22, 4, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.font = '600 14px ' + C.sans;
                    ctx.fillStyle = C.ink2;
                    ctx.fillText(truncate(name, 400), PAD + 40, y + 27);
                    ctx.font = '600 11px ' + C.sans;
                    ctx.fillStyle = C.ink3;
                    ctx.textAlign = 'right';
                    ctx.fillText('DISABLED', W - PAD - 20, y + 26);
                    ctx.textAlign = 'left';
                    y += 52;
                });
            }

            ctx.font = '12px ' + C.sans;
            ctx.fillStyle = C.ink3;
            ctx.textAlign = 'center';
            ctx.fillText('Powered by Netwatch · ' + NETWATCH_META.checkedAt, W / 2, H - 28);
            ctx.textAlign = 'left';

            // Browsers without WebP encoding (e.g. older Safari) silently fall
            // back to PNG, so name the file from the blob's actual type.
            canvas.toBlob(function (blob) {
                if (blob) {
                    downloadBlob(blob, nwFilename(blob.type === 'image/webp' ? 'webp' : 'png'));
                } else {
                    var a = document.createElement('a');
                    a.href = canvas.toDataURL('image/png');
                    a.download = nwFilename('png');
                    a.click();
                }
            }, 'image/webp', 0.92);
        }

        (function () {
            var el = document.getElementById('checked-at');

            function tick() {
                if (isNaN(checkedAtMs)) {
                    el.textContent = 'checking…';
                    return;
                }
                el.setAttribute('datetime', new Date(checkedAtMs).toISOString());
                var seconds = Math.max(0, Math.round((Date.now() - checkedAtMs) / 1000));
                if (seconds < 60) {
                    el.textContent = seconds + 's ago';
                } else if (seconds < 3600) {
                    el.textContent = Math.floor(seconds / 60) + 'm ' + (seconds % 60) + 's ago';
                } else {
                    el.textContent = new Date(checkedAtMs).toLocaleString();
                }
            }

            tick();
            setInterval(tick, 1000);
        })();

        (function () {
            var media = window.matchMedia('(prefers-color-scheme: dark)');
            var root = document.documentElement;
            var btn = document.getElementById('theme-toggle');

            function apply(mode) {
                var dark = mode === 'dark' || (mode === 'system' && media.matches);
                root.setAttribute('data-theme', dark ? 'dark' : 'light');
                root.setAttribute('data-theme-mode', mode);
                btn.setAttribute('aria-label', 'Switch to ' + (dark ? 'light' : 'dark') + ' theme');
                btn.title = 'Switch to ' + (dark ? 'light' : 'dark') + ' theme';
                try {
                    if (mode === 'system') {
                        localStorage.removeItem('netwatch-theme');
                    } else {
                        localStorage.setItem('netwatch-theme', mode);
                    }
                } catch (e) {}
            }

            window.toggleTheme = function () {
                apply(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
            };

            media.addEventListener('change', function () {
                if ((root.getAttribute('data-theme-mode') || 'system') === 'system') {
                    apply('system');
                }
            });

            apply(root.getAttribute('data-theme-mode') || 'system');
        })();

        (function () {
            var tip = document.createElement('div');
            tip.className = 'spark-tip';
            document.body.appendChild(tip);

            document.addEventListener('mouseover', function (e) {
                var bar = e.target.closest ? e.target.closest('.spark-bar') : null;
                if (!bar) return;
                tip.textContent = bar.getAttribute('data-tip');
                tip.classList.toggle('spark-tip-fail', bar.classList.contains('spark-fail'));
                tip.style.display = 'block';
                var rect = bar.getBoundingClientRect();
                var half = tip.offsetWidth / 2;
                var left = rect.left + rect.width / 2;
                left = Math.max(half + 4, Math.min(left, window.innerWidth - half - 4));
                tip.style.left = left + 'px';
                tip.style.top = (rect.top - 6) + 'px';
            });

            document.addEventListener('mouseout', function (e) {
                if (e.target.closest && e.target.closest('.spark-bar')) {
                    tip.style.display = 'none';
                }
            });
        })();
    </script>
</body>
</html>
