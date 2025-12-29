    <div class="bg-slate-50 border-t border-slate-200 px-8 py-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-slate-500">
            <div class="flex items-center gap-2">
                <span>{{ __('audit.powered_by') }}</span>
                <span class="font-semibold text-slate-700">
                    {{ config('audits.brand_name') }}
                </span>
            </div>
            <div class="text-center">
                {{ __('audit.audit_id') }}: <span class="font-mono text-xs bg-slate-200 px-2 py-1 rounded">{{ $auditId }}</span>
            </div>
            <div class="text-slate-400">
                {{ __('audit.data_from') }}
            </div>
        </div>
    </div>