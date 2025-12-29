@php
    $closingType = match(true) {
        $score < 50 => 'critical',
        $score < 90 => 'medium',
        default => 'good',
    };
    
    $closing = __('audit.closing.' . $closingType);
@endphp


<x-section-title>
    {{ $closing['headline'] }}
</x-section-title>

<div class="grid grid-cols-3 gap-2">
    <div class="text-sm lg:text-base print:text-xs text-slate-700 col-span-2 leading-relaxed">
        {{ $closing['body'] }}
        <br/>
        @if($closing['stats'])
            <span class="font-medium text-orange-600">{{ $closing['stats'] }}</span>
        @endif
        <br/>
        {{ $closing['solution'] }}
    </div>

    <div class="col-span-1 flex flex-col items-center justify-center gap-2 bg-slate-50 rounded-lg border border-slate-200 p-4">
        <p class="print:text-sm font-semibold text-slate-700 text-center">
            {{ $closing['cta'] }}
        </p>
        <a
            href="{{ config('audits.report.cta_url') }}"
            class="print:text-sm text-white bg-amber-600 rounded-md py-2 px-4 text-center flex items-center justify-center font-bold uppercase"
        >
            {{  __('audit.cta_text') }}
        </a>
    </div>
</div>