<x-partials.header />

<div class="py-8 grid grid-cols-1 gap-4 lg:gap-8 xl:gap-12">
    <div class="print:px-0 px-4 lg:px-8 pb-4 lg:pb-8 xl:pb-12 border-b border-gray-100">
        <x-title
            :url="$audit->targetUrl"
        />
    </div>

    <div class="print:px-0 px-4 lg:px-8">
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
                    <span class="text-5xl font-bold text-slate-900">
                        {{ $audit->score->toPercentage() }}
                    </span>
                    <span class="text-slate-500 text-sm">
                        {{ __('audit.out_of') }}
                    </span>
                </div>
            </div>
            <div class="mt-4">
                <x-final-note
                    :color="$audit->score->getColor()"
                    :note="$audit->score->toPercentage()"
                />
            </div>
        </div>

        <x-device-mockup
            :desktopScreenshot="$audit->desktopScreenshot"
            :mobileScreenshot="$audit->mobileScreenshot"
            :screenshotFailed="$audit->screenshotFailed"
        />
    </div>

    <x-page-break />

    <div class="print:px-0 px-4 lg:px-8">
        <x-section-title>
            {{ __('audit.core_web_vitals') }}
        </x-section-title>

        <div class="grid grid-cols-1 gap-2 lg:gap-4">
            <div class="grid grid-cols-2 gap-2 lg:gap-4">
                <x-core-web-vitals-card
                    type="lcp"
                    :value="$audit->lcp->format()"
                    :metric="$audit->lcp"
                />
                <x-core-web-vitals-message
                    type="lcp"
                    :metric="$audit->lcp"
                />
            </div>
            <div class="grid grid-cols-2 gap-2 lg:gap-4">
                <x-core-web-vitals-card
                    type="fcp"
                    :value="$audit->fcp->format()"
                    :metric="$audit->fcp"
                />
                <x-core-web-vitals-message
                    type="fcp"
                    :metric="$audit->fcp"
                />
            </div>
            <div class="grid grid-cols-2 gap-2 lg:gap-4">
                <x-core-web-vitals-card
                    type="cls"
                    :value="$audit->cls->format()"
                    :metric="$audit->cls"
                />
                <x-core-web-vitals-message
                    type="cls"
                    :metric="$audit->cls"
                />
            </div>
        </div>
    </div>

    @if(config('audits.report.show_seo') && $audit->seo && count($audit->seo->failedAudits) > 0)
        <x-page-break />

        <div class="print:px-0 px-4 lg:px-8">
            <x-section-title>
                {{ __('audit.seo') }}
            </x-section-title>
            <x-audit-section
                :score="$audit->seo->score"
                :failedAudits="$audit->seo->failedAudits"
            />
        </div>
    @endif

    @if(config('audits.report.show_accessibility') && $audit->accessibility && count($audit->accessibility->failedAudits) > 0)
        <x-page-break />

        <div class="print:px-0 px-4 lg:px-8">
            <x-section-title>
                {{ __('audit.accessibility') }}
            </x-section-title>
            <x-audit-section
                :score="$audit->accessibility->score"
                :failedAudits="$audit->accessibility->failedAudits"
            />
        </div>
    @endif

    <div class="print:px-0 px-4 lg:px-8">
        <x-closing
            :score="$audit->score->toPercentage()"
        />
    </div>

    <div class="print:px-0 px-4 lg:px-8">
        <x-partials.footer :auditId="$audit->auditId" />
    </div>
</div>
