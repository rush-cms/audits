@props([
    'type',
    'metric',
])

@php
    $thresholds = match($type) {
        'lcp' => ['good' => 2500, 'poor' => 4000, 'unit' => 'ms'],
        'fcp' => ['good' => 1800, 'poor' => 3000, 'unit' => 'ms'],
        'cls' => ['good' => 0.1, 'poor' => 0.25, 'unit' => 'value'],
        default => ['good' => 0, 'poor' => 0, 'unit' => 'value'],
    };

    $value = $thresholds['unit'] === 'ms' ? $metric->toMilliseconds() : $metric->getValue();

    $status = match(true) {
        $value <= $thresholds['good'] => 'excellent',
        $value <= $thresholds['poor'] => 'poor',
        default => 'very_poor',
    };

    $messageKey = "audit.messages.{$type}_{$status}";
    $message = __($messageKey);

    $colorClasses = match($status) {
        'excellent' => 'text-green-800',
        'poor' => 'text-amber-800',
        'very_poor' => 'text-red-800',
        default => 'text-slate-800',
    };
@endphp

<div class="flex items-center justify-start {{ $colorClasses }}">
    <p class="leading-relaxed">{{ $message }}</p>
</div>
