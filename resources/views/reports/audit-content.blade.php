<x-partials.header />

<div class="py-8 grid grid-cols-1 gap-4 lg:gap-8 xl:gap-12">
    <div class="px-4 lg:px-8 pb-4 lg:pb-8 xl:pb-12 border-b border-gray-100">
        <x-title
            :url="$audit->targetUrl"
        />
    </div>

    <div class="px-4 lg:px-8">
        <div class="bg-slate-50 rounded-2xl p-6 text-center border border-slate-200">
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
                <x-final-note
                    :color="$audit->score->getColor()"
                    :note="$audit->score->toPercentage()"
                />
            </div>
        </div>
    </div>

    <div class="px-4 lg:px-8">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">{{ __('audit.core_web_vitals') }}</h3>

        <div class="grid grid-cols-1 gap-2 lg:gap-4">
            <div class="grid grid-cols-2 gap-2 lg:gap-4">
                <x-core-web-vitals-card
                    type="lcp"
                    :value="$audit->lcp->format()"
                    :metric="$audit->lcp"
                />
                <x-core-web-vitals-message type="lcp" :metric="$audit->lcp" />
            </div>
            <div class="grid grid-cols-2 gap-2 lg:gap-4">
                <x-core-web-vitals-card
                    type="fcp"
                    :value="$audit->fcp->format()"
                    :metric="$audit->fcp"
                />
                <x-core-web-vitals-message type="fcp" :metric="$audit->fcp" />
            </div>
            <div class="grid grid-cols-2 gap-2 lg:gap-4">
                <x-core-web-vitals-card
                    type="cls"
                    :value="$audit->cls->format()"
                    :metric="$audit->cls"
                />
                <x-core-web-vitals-message type="cls" :metric="$audit->cls" />
            </div>
        </div>
    </div>

    <div class="px-4 lg:px-8">
        <div class="bg-blue-50 rounded-xl border border-blue-100 p-4">
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
</div>

<x-partials.footer :auditId="$audit->auditId" />
