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
    @endphp
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
            --mono: ui-monospace, 'SF Mono', 'Cascadia Code', Menlo, Consolas, monospace;
            --sans: system-ui, -apple-system, 'Segoe UI', Roboto, Ubuntu, Cantarell, sans-serif;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--sans);
            color: var(--ink-2);
            background:
                radial-gradient(1100px 480px at 50% -120px, rgba(34, 211, 238, 0.07), transparent 60%),
                linear-gradient(160deg, #131a2b 0%, #0d1424 55%, #0a0f1d 100%);
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
        .badge-healthy { color: var(--ok); background: rgba(52, 211, 153, 0.10); border-color: rgba(52, 211, 153, 0.30); }
        .badge-healthy .dot { box-shadow: 0 0 8px rgba(52, 211, 153, 0.6); }
        @media (prefers-reduced-motion: no-preference) {
            .badge-healthy .dot { animation: nw-pulse 2.5s ease-in-out infinite; }
            @keyframes nw-pulse {
                0%, 100% { box-shadow: 0 0 4px rgba(52, 211, 153, 0.35); }
                50% { box-shadow: 0 0 10px rgba(52, 211, 153, 0.75); }
            }
        }
        .badge-degraded { color: var(--warn); background: rgba(251, 191, 36, 0.10); border-color: rgba(251, 191, 36, 0.30); }
        .badge-unhealthy, .badge-failures { color: var(--crit); background: rgba(248, 113, 113, 0.10); border-color: rgba(248, 113, 113, 0.30); }
        .badge-disabled { color: var(--ink-3); background: rgba(124, 141, 176, 0.10); border-color: rgba(124, 141, 176, 0.25); }

        /* ---------- buttons / toggle ---------- */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(148, 170, 220, 0.06);
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
        .btn:hover { color: var(--ink); border-color: rgba(34, 211, 238, 0.4); }
        .btn-success { color: var(--ok); border-color: rgba(52, 211, 153, 0.4); background: rgba(52, 211, 153, 0.10); }
        .seg {
            display: inline-flex;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: rgba(13, 21, 38, 0.6);
            padding: 2px;
        }
        .seg .btn { border: none; background: transparent; color: var(--ink-3); border-radius: 6px; }
        .seg .btn:hover { color: var(--ink-2); }
        .seg .btn.active { background: rgba(34, 211, 238, 0.12); color: var(--cyan); }
        :is(button, summary, a):focus-visible { outline: 2px solid var(--cyan); outline-offset: 2px; }

        /* ---------- cards ---------- */
        .card {
            background: var(--surface-1);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
            margin-bottom: 1.25rem;
            overflow: hidden;
        }
        .card-failing { border-color: rgba(248, 113, 113, 0.35); }
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
            gap: 2px;
            height: 44px;
            border-bottom: 1px solid var(--border);
            flex: 1;
            min-width: 120px;
        }
        .spark-bar {
            flex: 0 1 8px;
            min-width: 2px;
            border-radius: 2px 2px 0 0;
            background: var(--cyan);
            opacity: 0.85;
        }
        .spark-bar:hover { opacity: 1; }
        .spark-fail { background: var(--crit); }

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
            border-bottom: 1px solid rgba(148, 170, 220, 0.06);
            font-variant-numeric: tabular-nums;
        }
        tr:last-child td { border-bottom: none; }
        tr.row-total td { color: var(--ink); font-weight: 600; }
        th.col-p95, td.col-p95 { background: rgba(34, 211, 238, 0.06); }

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
            color: #fca5a5;
            background: rgba(248, 113, 113, 0.06);
            border-left: 2px solid rgba(248, 113, 113, 0.4);
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
            background: rgba(17, 26, 46, 0.45);
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
            color: #cbd5e1;
            white-space: pre;
        }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-thumb { background: rgba(124, 141, 176, 0.25); border-radius: 5px; }
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
                <button class="btn" onclick="location.reload()">Run again</button>
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
                                              title="#{{ $i + 1 }} — {{ number_format($iteration['total_ms'], 2) }} ms{{ $iteration['success'] ? '' : ' — failed' }}"></span>
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

        function downloadJson() {
            var text = document.getElementById('json-output').textContent;
            var blob = new Blob([text], { type: 'application/json' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'netwatch-health-' + new Date().toISOString().slice(0, 19).replace(/:/g, '-') + '.json';
            a.click();
            URL.revokeObjectURL(url);
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
    </script>
</body>
</html>
