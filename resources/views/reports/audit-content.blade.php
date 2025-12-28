<div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 px-8 py-6">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                @if(config('audits.logo_url'))
                    <img src="{{ config('audits.logo_url') }}" alt="{{ config('audits.brand_name') }}" class="h-10">
                @else
                    <span class="text-2xl font-bold text-white">{{ config('audits.brand_name') }}</span>
                @endif
            </div>
            <div class="text-slate-400 text-sm">
                {{ __('audit.generated_at') }} {{ now()->format('M d, Y H:i') }} UTC
            </div>
        </div>
    </div>

    <div class="px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ __('audit.title') }}</h1>
            <a href="{{ $audit->targetUrl }}" class="text-blue-600 hover:text-blue-700 text-lg break-all">
                {{ $audit->targetUrl }}
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">
            <div class="lg:col-span-1">
                <div class="bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl p-6 text-center border border-slate-200">
                    <div class="relative inline-flex items-center justify-center">
                        <svg class="w-40 h-40 -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="2.5"/>
                            <circle cx="18" cy="18" r="15.9" fill="none"
                                stroke="{{ $audit->score->getColor() === 'green' ? '#22c55e' : ($audit->score->getColor() === 'orange' ? '#f59e0b' : '#ef4444') }}"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-dasharray="{{ $audit->score->toPercentage() }}, 100"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-5xl font-bold text-slate-900">{{ $audit->score->toPercentage() }}</span>
                            <span class="text-slate-500 text-sm">{{ __('audit.out_of') }}</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        @php
                            $color = $audit->score->getColor();
                            $label = match($color) {
                                'green' => __('audit.good'),
                                'orange' => __('audit.needs_improvement'),
                                default => __('audit.poor'),
                            };
                        @endphp
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium
                            {{ $color === 'green' ? 'bg-green-100 text-green-700' : ($color === 'orange' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                            <span class="w-2 h-2 rounded-full
                                {{ $color === 'green' ? 'bg-green-500' : ($color === 'orange' ? 'bg-amber-500' : 'bg-red-500') }}"></span>
                            {{ $label }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-4">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">{{ __('audit.core_web_vitals') }}</h3>

                <div class="bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ __('audit.lcp') }}</div>
                            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $audit->lcp->format() }}</div>
                            <div class="text-xs text-slate-400 mt-1">{{ __('audit.lcp_full') }}</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            @php
                                $lcpMs = $audit->lcp->toMilliseconds();
                                $lcpPercent = min(100, ($lcpMs / 4000) * 100);
                            @endphp
                            <div class="h-full {{ $lcpMs <= 2500 ? 'bg-green-500' : ($lcpMs <= 4000 ? 'bg-amber-500' : 'bg-red-500') }} rounded-full" style="width: {{ $lcpPercent }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-slate-400 mt-1">
                            <span>0s</span>
                            <span class="text-green-600">{{ __('audit.good') }} ≤2.5s</span>
                            <span>4s+</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ __('audit.fcp') }}</div>
                            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $audit->fcp->format() }}</div>
                            <div class="text-xs text-slate-400 mt-1">{{ __('audit.fcp_full') }}</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            @php
                                $fcpMs = $audit->fcp->toMilliseconds();
                                $fcpPercent = min(100, ($fcpMs / 3000) * 100);
                            @endphp
                            <div class="h-full {{ $fcpMs <= 1800 ? 'bg-green-500' : ($fcpMs <= 3000 ? 'bg-amber-500' : 'bg-red-500') }} rounded-full" style="width: {{ $fcpPercent }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-slate-400 mt-1">
                            <span>0s</span>
                            <span class="text-green-600">{{ __('audit.good') }} ≤1.8s</span>
                            <span>3s+</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-medium text-slate-500 uppercase tracking-wide">{{ __('audit.cls') }}</div>
                            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $audit->cls->format() }}</div>
                            <div class="text-xs text-slate-400 mt-1">{{ __('audit.cls_full') }}</div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            @php
                                $clsValue = $audit->cls->getValue();
                                $clsPercent = min(100, ($clsValue / 0.25) * 100);
                            @endphp
                            <div class="h-full {{ $clsValue <= 0.1 ? 'bg-green-500' : ($clsValue <= 0.25 ? 'bg-amber-500' : 'bg-red-500') }} rounded-full" style="width: {{ $clsPercent }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-slate-400 mt-1">
                            <span>0</span>
                            <span class="text-green-600">{{ __('audit.good') }} ≤0.1</span>
                            <span>0.25+</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 mb-8 border border-blue-100">
            <h3 class="text-lg font-semibold text-slate-900 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ __('audit.what_means') }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <div class="font-medium text-slate-700">{{ __('audit.lcp') }} ({{ __('audit.lcp_full') }})</div>
                    <p class="text-slate-500 mt-1">{{ __('audit.lcp_desc') }}</p>
                </div>
                <div>
                    <div class="font-medium text-slate-700">{{ __('audit.fcp') }} ({{ __('audit.fcp_full') }})</div>
                    <p class="text-slate-500 mt-1">{{ __('audit.fcp_desc') }}</p>
                </div>
                <div>
                    <div class="font-medium text-slate-700">{{ __('audit.cls') }} ({{ __('audit.cls_full') }})</div>
                    <p class="text-slate-500 mt-1">{{ __('audit.cls_desc') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-slate-50 border-t border-slate-200 px-8 py-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-slate-500">
            <div class="flex items-center gap-2">
                <span>{{ __('audit.powered_by') }}</span>
                <span class="font-semibold text-slate-700">{{ config('audits.brand_name') }}</span>
            </div>
            <div class="text-center">
                {{ __('audit.audit_id') }}: <span class="font-mono text-xs bg-slate-200 px-2 py-1 rounded">{{ $audit->auditId }}</span>
            </div>
            <div class="text-slate-400">
                {{ __('audit.data_from') }}
            </div>
        </div>
    </div>
</div>
