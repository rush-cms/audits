<div class="pt-4 grid grid-cols-2 gap-2 print:text-xs text-sm text-slate-500 border-t border-slate-200">
    <div class="flex flex-row items-center justify-start gap-2">
        <span>{{ __('audit.powered_by') }}</span>
        <span class="font-semibold text-slate-700">
            {{ config('audits.brand_name') }}
        </span>
    </div>
    <div class="flex flex-row items-center justify-end gap-2">
        {{ __('audit.audit_id') }} <span class="font-mono text-xs bg-slate-100 ml-1 p-1 rounded-sm">{{ $auditId }}</span>
    </div>
    <div class="text-slate-400">
        {{ __('audit.data_from') }}
    </div>
</div>