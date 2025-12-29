@props([
    'score',
    'failedAudits',
])

@if(count($failedAudits) > 0)
    <div class="grid print:grid-cols-4 grid-cols-1 lg:grid-cols-4 gap-4">
        <div class="relative bg-slate-50 rounded-xl p-4 border border-slate-200 flex flex-col items-center justify-center">
            @php
                $color = $score->getColor();
                $percentage = $score->toPercentage();
            @endphp
            <div class="text-4xl font-bold {{ $color === 'green' ? 'text-green-600' : ($color === 'orange' ? 'text-amber-600' : 'text-red-600') }}">
                {{ $percentage }}
            </div>
            <div class="text-slate-500 text-sm">
                {{ __('audit.out_of') }}
            </div>
            <div class="absolute left-0 bottom-0 py-1 px-6 w-full h-3 rounded-full">
                <div class="w-full h-full bg-gray-300 rounded-full">
                    <div class="h-full rounded-full {{ $color === 'green' ? 'bg-green-600' : ($color === 'orange' ? 'bg-amber-600' : 'bg-red-600') }}" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-3 print:col-span-3">
            <ul class="space-y-2">
                @foreach($failedAudits as $audit)
                    <li class="text-sm text-slate-700 flex items-center justify-start gap-2">
                        <x-audit-section-icon />
                        <span>{{ $audit['title'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif