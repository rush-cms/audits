@php
    $colorMap = [
        'green' => [
            'label' => __('audit.good'),
            'bg' => 'bg-green-100',
            'text' => 'text-green-700',
            'dot' => 'bg-green-500',
        ],
        'orange' => [
            'label' => __('audit.needs_improvement'),
            'bg' => 'bg-amber-100',
            'text' => 'text-amber-700',
            'dot' => 'bg-amber-500',
        ],
        'red' => [
            'label' => __('audit.poor'),
            'bg' => 'bg-red-100',
            'text' => 'text-red-700',
            'dot' => 'bg-red-500',
        ],
    ];

    $config = $colorMap[$color] ?? $colorMap['red'];
    
    $result = match(true) {
        $note < 50 => __('audit.messages.very_poor'),
        $note < 90 => __('audit.messages.poor'),
        default => __('audit.messages.excellent'),
    };
@endphp

<div class="flex items-center justify-center flex-col gap-2 lg:gap-4">
    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium {{ $config['bg'] }} {{ $config['text'] }}">
        <span class="w-2 h-2 rounded-full {{ $config['dot'] }}"></span>
        {{ $config['label'] }}
    </span>

    <span class="text-slate-500 max-w-lg text-center">
        {{ $result }}
    </span>
</div>