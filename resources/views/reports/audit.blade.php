<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Audit Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 40px; background: #fff; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid #e2e8f0; }
        .logo { height: 40px; }
        .brand { font-size: 24px; font-weight: 700; color: #0f172a; }
        .date { color: #64748b; font-size: 14px; }
        .title { font-size: 28px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .url { color: #3b82f6; font-size: 16px; word-break: break-all; }
        .score-section { display: flex; align-items: center; gap: 40px; margin: 40px 0; padding: 30px; background: #f8fafc; border-radius: 16px; }
        .score-circle { position: relative; width: 140px; height: 140px; }
        .score-circle svg { transform: rotate(-90deg); }
        .score-bg { fill: none; stroke: #e2e8f0; stroke-width: 8; }
        .score-fg { fill: none; stroke-width: 8; stroke-linecap: round; transition: stroke-dashoffset 0.5s; }
        .score-fg.green { stroke: #22c55e; }
        .score-fg.orange { stroke: #f59e0b; }
        .score-fg.red { stroke: #ef4444; }
        .score-value { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 36px; font-weight: 700; }
        .score-label { font-size: 18px; font-weight: 600; color: #475569; }
        .score-status { font-size: 14px; color: #64748b; margin-top: 4px; }
        .metrics { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 40px 0; }
        .metric { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; text-align: center; }
        .metric-value { font-size: 28px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
        .metric-label { font-size: 14px; color: #64748b; font-weight: 500; }
        .metric-desc { font-size: 12px; color: #94a3b8; margin-top: 8px; }
        .footer { margin-top: 60px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center; color: #64748b; font-size: 12px; }
        .footer a { color: #3b82f6; text-decoration: none; }
        @media print { body { background: #fff; } .container { padding: 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div>
                @if(config('audits.logo_url'))
                    <img src="{{ config('audits.logo_url') }}" alt="{{ config('audits.brand_name') }}" class="logo">
                @else
                    <span class="brand">{{ config('audits.brand_name') }}</span>
                @endif
            </div>
            <div class="date">
                Generated: {{ now()->format('M d, Y H:i') }} UTC
            </div>
        </header>

        <section>
            <h1 class="title">Performance Audit Report</h1>
            <p class="url">{{ $audit->targetUrl }}</p>
        </section>

        <section class="score-section">
            <div class="score-circle">
                <svg viewBox="0 0 36 36" width="140" height="140">
                    <path class="score-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                    <path class="score-fg {{ $audit->score->getColor() }}"
                          stroke-dasharray="{{ $audit->score->toPercentage() }}, 100"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                </svg>
                <div class="score-value">{{ $audit->score->toPercentage() }}</div>
            </div>
            <div>
                <div class="score-label">Performance Score</div>
                <div class="score-status">{{ $audit->score->getLabel() }}</div>
            </div>
        </section>

        <section class="metrics">
            <div class="metric">
                <div class="metric-value">{{ $audit->lcp->format() }}</div>
                <div class="metric-label">LCP</div>
                <div class="metric-desc">Largest Contentful Paint</div>
            </div>
            <div class="metric">
                <div class="metric-value">{{ $audit->fcp->format() }}</div>
                <div class="metric-label">FCP</div>
                <div class="metric-desc">First Contentful Paint</div>
            </div>
            <div class="metric">
                <div class="metric-value">{{ $audit->cls->format() }}</div>
                <div class="metric-label">CLS</div>
                <div class="metric-desc">Cumulative Layout Shift</div>
            </div>
        </section>

        <footer class="footer">
            <p>Report generated by <strong>{{ config('audits.brand_name') }}</strong></p>
            <p>Audit ID: {{ $audit->auditId }}</p>
        </footer>
    </div>
</body>
</html>
