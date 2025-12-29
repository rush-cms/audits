@props([
    'type',
    'value',
    'metric',
])

@php
    $config = match($type) {
        'lcp' => [
            'title' => __('audit.lcp'),
            'full' => __('audit.lcp_full'),
            'iconBg' => 'bg-blue-50',
            'iconColor' => 'text-blue-600',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
            'goodThreshold' => 2500,
            'poorThreshold' => 4000,
            'maxScale' => 4000,
            'goodLabel' => '≤2.5s',
            'maxLabel' => '4s+',
            'minLabel' => '0s',
            'unit' => 'ms',
        ],
        'fcp' => [
            'title' => __('audit.fcp'),
            'full' => __('audit.fcp_full'),
            'iconBg' => 'bg-purple-50',
            'iconColor' => 'text-purple-600',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
            'goodThreshold' => 1800,
            'poorThreshold' => 3000,
            'maxScale' => 3000,
            'goodLabel' => '≤1.8s',
            'maxLabel' => '3s+',
            'minLabel' => '0s',
            'unit' => 'ms',
        ],
        'cls' => [
            'title' => __('audit.cls'),
            'full' => __('audit.cls_full'),
            'iconBg' => 'bg-emerald-50',
            'iconColor' => 'text-emerald-600',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>',
            'goodThreshold' => 0.1,
            'poorThreshold' => 0.25,
            'maxScale' => 0.25,
            'goodLabel' => '≤0.1',
            'maxLabel' => '0.25+',
            'minLabel' => '0',
            'unit' => 'value',
        ],
        default => [],
    };

    $numericValue = $config['unit'] === 'ms' ? $metric->toMilliseconds() : $metric->getValue();
    $percent = min(100, ($numericValue / $config['maxScale']) * 100);
    $barColor = $numericValue <= $config['goodThreshold']
        ? 'bg-green-500'
        : ($numericValue <= $config['poorThreshold'] ? 'bg-amber-500' : 'bg-red-500');
@endphp

<div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ $config['title'] }}</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $value }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $config['full'] }}</div>
        </div>
        <div class="w-12 h-12 rounded-xl {{ $config['iconBg'] }} flex items-center justify-center">
            <svg class="w-6 h-6 {{ $config['iconColor'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                {!! $config['icon'] !!}
            </svg>
        </div>
    </div>
    <div class="mt-3">
        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full {{ $barColor }} rounded-full" style="width: {{ $percent }}%"></div>
        </div>
        <div class="flex justify-between text-xs text-slate-400 mt-1">
            <span>{{ $config['minLabel'] }}</span>
            <span class="text-green-600">{{ __('audit.good') }} {{ $config['goodLabel'] }}</span>
            <span>{{ $config['maxLabel'] }}</span>
        </div>
    </div>
</div>