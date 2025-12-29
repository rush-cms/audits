@props([
    'desktopScreenshot',
    'mobileScreenshot',
    'screenshotFailed',
])

@if($desktopScreenshot || $mobileScreenshot)
    <div class="flex items-end justify-center gap-4 lg:gap-8 py-6">
        @if($desktopScreenshot)
            <div class="relative">
                <div class="bg-slate-800 rounded-t-lg p-2 pb-0">
                    <div class="flex gap-1 mb-2">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    </div>
                    <img
                        src="{{ $desktopScreenshot }}"
                        alt="Desktop Preview"
                        class="w-64 lg:w-80 rounded-t shadow-lg"
                    />
                </div>
                <div class="bg-slate-700 h-3 rounded-b-lg"></div>
                <div class="bg-slate-600 h-1 w-24 mx-auto rounded-b"></div>
            </div>
        @endif

        @if($mobileScreenshot)
            <div class="relative">
                <div class="bg-slate-800 rounded-2xl p-1.5 pb-1">
                    <div class="w-8 h-1 bg-slate-600 rounded-full mx-auto mb-1"></div>
                    <img
                        src="{{ $mobileScreenshot }}"
                        alt="Mobile Preview"
                        class="w-20 lg:w-24 rounded-lg shadow-lg"
                    />
                    <div class="w-6 h-6 border-2 border-slate-600 rounded-full mx-auto mt-1"></div>
                </div>
            </div>
        @endif
    </div>
@elseif($screenshotFailed)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center text-sm text-amber-700">
        {{ __('audit.screenshot_unavailable') }}
    </div>
@endif
