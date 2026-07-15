@php
    $fmt = fn (float $v) => number_format($v, $v < 10 ? 2 : ($v < 100 ? 1 : 0));
    $failing = $result['failures'] > 0;
@endphp
<div class="card{{ $failing ? ' card-failing' : '' }}" data-probe="{{ $name }}">
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
                              data-tip="#{{ $i + 1 }} — {{ number_format($iteration['total_ms'], 2) }} ms{{ $iteration['success'] ? '' : ' — failed' }}"></span>
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
                        <tr class="{{ $key === 'total_ms' ? 'row-total' : 'row-detail' }}">
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
        <button type="button" class="metrics-toggle" data-metrics-toggle aria-expanded="false">Connect / Request</button>

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
