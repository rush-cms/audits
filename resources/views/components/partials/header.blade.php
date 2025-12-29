<div class="bg-slate-800 px-8 py-6 print:rounded-xl">
    <div class="flex justify-between items-center">
        <div class="flex items-center gap-4">
            @if(config('audits.logo_path'))
                <img
                    src="{{ asset(config('audits.logo_path')) }}"
                    alt="{{ config('audits.brand_name') }}"
                    class="h-10"
                >
            @else
                <span class="text-2xl font-bold text-white">
                    {{ config('audits.brand_name') }}
                </span>
            @endif
        </div>
        <div class="text-slate-400 text-sm">
            {{ __('audit.generated_at') }} {{ now()->format(config('audits.report.date_format')) }}
        </div>
    </div>
</div>