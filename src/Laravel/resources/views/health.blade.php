<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Netwatch Health Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
            padding: 2rem;
        }
        .container { max-width: 960px; margin: 0 auto; }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .header h1 { font-size: 1.5rem; font-weight: 600; }
        .header .meta { font-size: 0.85rem; color: #666; }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-healthy { background: #d1fae5; color: #065f46; }
        .badge-degraded { background: #fef3c7; color: #92400e; }
        .badge-unhealthy { background: #fee2e2; color: #991b1b; }
        .badge-failures { background: #fee2e2; color: #991b1b; }
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .card-header h2 { font-size: 1.1rem; font-weight: 600; }
        .card-header .iterations { font-size: 0.8rem; color: #666; }
        .card-body { padding: 1rem 1.25rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        th, td { padding: 0.5rem 0.75rem; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        th { color: #666; font-weight: 500; border-bottom: 1px solid #e5e7eb; }
        td { border-bottom: 1px solid #f3f4f6; font-variant-numeric: tabular-nums; }
        tr:last-child td { border-bottom: none; }
        details { margin-top: 1rem; }
        summary {
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            color: #991b1b;
            padding: 0.5rem 0;
        }
        .error-list { list-style: none; padding: 0.5rem 0; }
        .error-list li {
            font-size: 0.8rem;
            color: #991b1b;
            padding: 0.25rem 0;
            border-bottom: 1px solid #fee2e2;
            font-family: monospace;
        }
        .error-list li:last-child { border-bottom: none; }
        .footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.8rem;
            color: #999;
        }
        .toolbar {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #fff;
            color: #374151;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
        }
        .btn:hover { background: #f9fafb; border-color: #9ca3af; }
        .btn.active { background: #111827; color: #fff; border-color: #111827; }
        .btn.active:hover { background: #1f2937; }
        .btn-success {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        .json-panel {
            display: none;
            background: #1e1e1e;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .json-panel.visible { display: block; }
        .json-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid #333;
        }
        .json-panel-header span {
            font-size: 0.8rem;
            font-weight: 500;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .json-panel-header .json-actions { display: flex; gap: 0.5rem; }
        .json-panel-header .btn {
            border-color: #444;
            background: #2d2d2d;
            color: #d1d5db;
        }
        .json-panel-header .btn:hover { background: #3d3d3d; border-color: #555; }
        .json-panel-header .btn-success { background: #065f46; color: #d1fae5; border-color: #065f46; }
        .json-content {
            padding: 1.25rem;
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }
        .json-content pre {
            margin: 0;
            font-family: 'SF Mono', 'Fira Code', 'Fira Mono', Menlo, Consolas, monospace;
            font-size: 0.8rem;
            line-height: 1.5;
            color: #d4d4d4;
            white-space: pre;
        }
        .card-disabled {
            opacity: 0.55;
        }
        .card-disabled .card-body {
            padding: 0.75rem 1.25rem;
            font-size: 0.85rem;
            color: #6b7280;
        }
        .badge-disabled { background: #e5e7eb; color: #6b7280; }
        .dashboard-view { display: block; }
        .dashboard-view.hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Netwatch Health Dashboard</h1>
                <div class="meta">Checked at {{ $checkedAt }}</div>
            </div>
            <div class="toolbar">
                <span class="badge badge-{{ $overallStatus }}">{{ $overallStatus }}</span>
                <button class="btn active" id="btn-dashboard" onclick="showView('dashboard')">Dashboard</button>
                <button class="btn" id="btn-json" onclick="showView('json')">JSON</button>
            </div>
        </div>

        <div class="dashboard-view" id="view-dashboard">
            @foreach ($results as $name => $result)
                @php
                    $probeStatus = $result['failures'] > 0 ? 'failures' : 'healthy';
                @endphp
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2>{{ $name }}</h2>
                            <span class="iterations">{{ $result['iterations'] }} iterations</span>
                        </div>
                        <span class="badge badge-{{ $probeStatus }}">
                            {{ $probeStatus === 'healthy' ? 'healthy' : $result['failures'] . ' failure' . ($result['failures'] > 1 ? 's' : '') }}
                        </span>
                    </div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Min</th>
                                    <th>Max</th>
                                    <th>Avg</th>
                                    <th>P50</th>
                                    <th>P95</th>
                                    <th>P99</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (['connect_ms' => 'Connect', 'request_ms' => 'Request', 'total_ms' => 'Total'] as $key => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td>{{ number_format($result['stats'][$key]['min'], 2) }}</td>
                                        <td>{{ number_format($result['stats'][$key]['max'], 2) }}</td>
                                        <td>{{ number_format($result['stats'][$key]['avg'], 2) }}</td>
                                        <td>{{ number_format($result['stats'][$key]['p50'], 2) }}</td>
                                        <td>{{ number_format($result['stats'][$key]['p95'], 2) }}</td>
                                        <td>{{ number_format($result['stats'][$key]['p99'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        @if ($result['failures'] > 0 && isset($result['results']))
                            <details>
                                <summary>Show errors ({{ $result['failures'] }})</summary>
                                <ul class="error-list">
                                    @foreach ($result['results'] as $iteration)
                                        @if (!$iteration['success'] && !empty($iteration['error']))
                                            <li>{{ $iteration['error'] }}</li>
                                        @endif
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                </div>
            @endforeach

            @foreach ($disabledProbes as $name)
                <div class="card card-disabled">
                    <div class="card-header">
                        <div>
                            <h2>{{ $name }}</h2>
                        </div>
                        <span class="badge badge-disabled">disabled</span>
                    </div>
                    <div class="card-body">
                        This probe is disabled. Set its <code>enabled</code> flag to <code>true</code> in your config to activate it.
                    </div>
                </div>
            @endforeach
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

            if (view === 'json') {
                dashboard.classList.add('hidden');
                json.classList.add('visible');
                btnDashboard.classList.remove('active');
                btnJson.classList.add('active');
            } else {
                dashboard.classList.remove('hidden');
                json.classList.remove('visible');
                btnDashboard.classList.add('active');
                btnJson.classList.remove('active');
            }
        }

        function copyJson() {
            var text = document.getElementById('json-output').textContent;
            var btn = document.getElementById('btn-copy');

            navigator.clipboard.writeText(text).then(function() {
                btn.textContent = 'Copied!';
                btn.classList.add('btn-success');
                setTimeout(function() {
                    btn.textContent = 'Copy';
                    btn.classList.remove('btn-success');
                }, 2000);
            });
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
    </script>
</body>
</html>
