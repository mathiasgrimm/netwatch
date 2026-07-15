<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netwatch Health Dashboard</title>
    @php
        $fmt = fn (float $v) => number_format($v, $v < 10 ? 2 : ($v < 100 ? 1 : 0));
        $probeCount = count($results);
        $iterationCount = array_sum(array_map(fn ($r) => $r['iterations'], $results));
        $netwatchMeta = [
            'status' => $overallStatus,
            'checkedAt' => $checkedAt,
            'probeCount' => $probeCount,
            'iterationCount' => $iterationCount,
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
            --bg-0: #f4f7fb;
            --surface-1: #ffffff;
            --surface-2: #f1f5f9;
            --border: rgba(28, 50, 90, 0.12);
            --border-strong: rgba(28, 50, 90, 0.22);
            --ink: #0f172a;
            --ink-2: #3b4a63;
            --ink-3: #5b6b84;
            --cyan: #0891b2;
            --cyan-hi: #06b6d4;
            --cyan-lo: #0e7490;
            --ok: #059669;
            --warn: #b45309;
            --crit: #dc2626;
            --bg-glow: rgba(8, 145, 178, 0.06);
            --bg-grad-1: #ffffff;
            --bg-grad-2: #f5f8fc;
            --bg-grad-3: #eef2f8;
            --ok-bg: rgba(5, 150, 105, 0.10);
            --ok-border: rgba(5, 150, 105, 0.35);
            --warn-bg: rgba(180, 83, 9, 0.10);
            --warn-border: rgba(180, 83, 9, 0.35);
            --crit-bg: rgba(220, 38, 38, 0.08);
            --crit-bg-soft: rgba(220, 38, 38, 0.05);
            --crit-border: rgba(220, 38, 38, 0.35);
            --crit-border-strong: rgba(220, 38, 38, 0.45);
            --crit-ink: #b91c1c;
            --muted-bg: rgba(100, 116, 139, 0.10);
            --muted-border: rgba(100, 116, 139, 0.30);
            --cyan-bg: rgba(8, 145, 178, 0.07);
            --cyan-bg-strong: rgba(8, 145, 178, 0.12);
            --accent-border: rgba(8, 145, 178, 0.45);
            --btn-bg: rgba(28, 50, 90, 0.04);
            --seg-bg: rgba(28, 50, 90, 0.05);
            --card-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), 0 1px 3px rgba(15, 23, 42, 0.04);
            --border-faint: rgba(28, 50, 90, 0.06);
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
        .container { max-width: 1080px; margin: 0 auto; padding: clamp(1rem, 4vw, 2.5rem); }

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
            margin-bottom: 1.25rem;
            overflow: hidden;
        }
        .card-failing { border-color: var(--crit-border); }
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
            display: flex;
            align-items: flex-end;
            gap: clamp(1px, 0.4%, 3px);
            height: 44px;
            border-bottom: 1px solid var(--border);
            flex: 1;
            min-width: 120px;
            overflow: hidden;
        }
        .spark-bar {
            flex: 1 1 0%;
            min-width: 1px;
            max-width: 10%;
            border-radius: 2px 2px 0 0;
            background: var(--cyan);
            opacity: 0.85;
        }
        .spark-bar:hover { opacity: 1; }
        .spark-fail { background: var(--crit); }
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
        table { width: 100%; min-width: 540px; border-collapse: collapse; font-size: 0.8125rem; }
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
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--ink-3);
            margin: 2rem 0 0.75rem;
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
        .dashboard-view { display: block; }
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
                        · {{ $iterationCount }} {{ $iterationCount === 1 ? 'iteration' : 'iterations' }}
                    </div>
                </div>
            </div>
            <div class="toolbar">
                <span class="badge badge-{{ $overallStatus }}"><span class="dot"></span><span>{{ $overallStatus }}</span></span>
                <div class="seg" role="group" aria-label="View">
                    <button class="btn active" id="btn-dashboard" aria-pressed="true" onclick="showView('dashboard')">Dashboard</button>
                    <button class="btn" id="btn-json" aria-pressed="false" onclick="showView('json')">JSON</button>
                </div>
                <button class="btn" onclick="exportImage()">Export image</button>
                <button class="btn" onclick="location.reload()">Run again</button>
                <button class="btn btn-icon" id="theme-toggle" onclick="cycleTheme()"
                        aria-label="Switch appearance (current: system)" title="Appearance: system">
                    <svg class="icon-light" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    <svg class="icon-dark" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                </button>
            </div>
        </div>

        <div class="dashboard-view" id="view-dashboard">
            @if ($probeCount === 0 && count($disabledProbes) === 0)
                <div class="card">
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

            @foreach ($results as $name => $result)
                @php $failing = $result['failures'] > 0; @endphp
                <div class="card{{ $failing ? ' card-failing' : '' }}">
                    <div class="card-header">
                        <div>
                            <div class="probe-title">
                                <span class="probe-dot {{ $failing ? 'probe-dot-crit' : 'probe-dot-ok' }}"></span>
                                <h2>{{ $name }}</h2>
                                <span class="badge badge-{{ $failing ? 'failures' : 'healthy' }}">
                                    <span>{{ $failing ? $result['failures'] . ' failure' . ($result['failures'] > 1 ? 's' : '') : 'healthy' }}</span>
                                </span>
                            </div>
                            <div class="probe-name">{{ $result['name'] }}</div>
                        </div>
                        <span class="chip" title="{{ $result['iterations'] }} iterations">&times;{{ $result['iterations'] }}</span>
                    </div>
                    <div class="card-body">
                        <div class="stat-row">
                            <div>
                                <div class="stat-label">Total p95</div>
                                <div class="stat-value">{{ $fmt($result['stats']['total_ms']['p95']) }}<span class="unit">ms</span></div>
                                <div class="stat-context">p50 {{ $fmt($result['stats']['total_ms']['p50']) }} · avg {{ $fmt($result['stats']['total_ms']['avg']) }}</div>
                            </div>
                            @if (isset($result['results']) && count($result['results']) > 0)
                                @php $maxTotal = max(array_map(fn ($r) => $r['total_ms'], $result['results'])) ?: 1; @endphp
                                <div class="spark" aria-hidden="true">
                                    @foreach ($result['results'] as $i => $iteration)
                                        <span class="spark-bar{{ $iteration['success'] ? '' : ' spark-fail' }}"
                                              style="height: {{ max(8, round($iteration['total_ms'] / $maxTotal * 100)) }}%"
                                              data-tip="#{{ $i + 1 }} · {{ number_format($iteration['total_ms'], 2) }} ms{{ $iteration['success'] ? '' : ' · failed' }}"></span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        <th scope="col">Metric</th>
                                        <th scope="col">Min</th>
                                        <th scope="col">Max</th>
                                        <th scope="col">Avg</th>
                                        <th scope="col">P50</th>
                                        <th scope="col" class="col-p95">P95</th>
                                        <th scope="col">P99</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (['connect_ms' => 'Connect', 'request_ms' => 'Request', 'total_ms' => 'Total'] as $key => $label)
                                        <tr class="{{ $key === 'total_ms' ? 'row-total' : '' }}">
                                            <td>{{ $label }}</td>
                                            <td>{{ number_format($result['stats'][$key]['min'], 2) }}</td>
                                            <td>{{ number_format($result['stats'][$key]['max'], 2) }}</td>
                                            <td>{{ number_format($result['stats'][$key]['avg'], 2) }}</td>
                                            <td>{{ number_format($result['stats'][$key]['p50'], 2) }}</td>
                                            <td class="col-p95">{{ number_format($result['stats'][$key]['p95'], 2) }}</td>
                                            <td>{{ number_format($result['stats'][$key]['p99'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($result['failures'] > 0 && isset($result['results']))
                            <details class="errors">
                                <summary>{{ $result['failures'] }} error{{ $result['failures'] > 1 ? 's' : '' }}</summary>
                                <ul class="error-list">
                                    @foreach ($result['results'] as $i => $iteration)
                                        @if (!$iteration['success'] && !empty($iteration['error']))
                                            <li><span class="iter">#{{ $i + 1 }}</span>{{ $iteration['error'] }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            </details>
                        @endif
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

        <div class="footer">Powered by Netwatch</div>
    </div>

    <script>
        const NETWATCH_META = @json($netwatchMeta);

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
                border: 'rgba(148, 170, 220, 0.18)',
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
            var statusRgb = { healthy: '52, 211, 153', degraded: '251, 191, 36', unhealthy: '248, 113, 113' };

            // background: navy gradient + faint cyan bloom, echoing the page body
            var bg = ctx.createLinearGradient(0, 0, 0, H);
            bg.addColorStop(0, '#131a2b');
            bg.addColorStop(1, '#0a0f1d');
            ctx.fillStyle = bg;
            ctx.fillRect(0, 0, W, H);
            var glow = ctx.createRadialGradient(W / 2, -100, 0, W / 2, -100, 700);
            glow.addColorStop(0, 'rgba(34, 211, 238, 0.08)');
            glow.addColorStop(1, 'rgba(34, 211, 238, 0)');
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
            ctx.strokeStyle = 'rgba(56, 225, 245, 0.35)';
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
                        ctx.fillStyle = r.success ? 'rgba(34, 211, 238, 0.75)' : C.crit;
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
                    ctx.fillStyle = 'rgba(13, 21, 38, 0.5)';
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
            var checkedAt = new Date(el.getAttribute('datetime')).getTime();
            if (isNaN(checkedAt)) return;

            function tick() {
                var seconds = Math.max(0, Math.round((Date.now() - checkedAt) / 1000));
                if (seconds < 60) {
                    el.textContent = seconds + 's ago';
                } else if (seconds < 3600) {
                    el.textContent = Math.floor(seconds / 60) + 'm ' + (seconds % 60) + 's ago';
                } else {
                    el.textContent = new Date(checkedAt).toLocaleString();
                }
            }

            tick();
            setInterval(tick, 1000);
        })();

        (function () {
            var MODES = ['system', 'light', 'dark'];
            var media = window.matchMedia('(prefers-color-scheme: dark)');
            var root = document.documentElement;
            var btn = document.getElementById('theme-toggle');

            function apply(mode) {
                var dark = mode === 'dark' || (mode === 'system' && media.matches);
                root.setAttribute('data-theme', dark ? 'dark' : 'light');
                root.setAttribute('data-theme-mode', mode);
                btn.setAttribute('aria-label', 'Switch appearance (current: ' + mode + ')');
                btn.title = 'Appearance: ' + mode;
                try {
                    if (mode === 'system') {
                        localStorage.removeItem('netwatch-theme');
                    } else {
                        localStorage.setItem('netwatch-theme', mode);
                    }
                } catch (e) {}
            }

            window.cycleTheme = function () {
                var current = root.getAttribute('data-theme-mode') || 'system';
                apply(MODES[(MODES.indexOf(current) + 1) % MODES.length]);
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
