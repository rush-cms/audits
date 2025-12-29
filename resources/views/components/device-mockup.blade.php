@props([
    'desktopScreenshot',
    'mobileScreenshot',
    'screenshotFailed',
])

@if($desktopScreenshot || $mobileScreenshot)
    <div class="flex items-end justify-center gap-6 lg:gap-10 py-6">
        @if($desktopScreenshot)
            <div class="relative">
                <div class="bg-[#1d1d1f] rounded-t-xl p-1.5 pb-0">
                    <div class="flex gap-1.5 mb-1.5 px-2">
                        <span class="w-2 h-2 rounded-full bg-[#ff5f57]"></span>
                        <span class="w-2 h-2 rounded-full bg-[#febc2e]"></span>
                        <span class="w-2 h-2 rounded-full bg-[#28c840]"></span>
                    </div>
                    <img
                        src="{{ $desktopScreenshot }}"
                        alt="Desktop Preview"
                        class="w-64 lg:w-80 rounded-sm"
                    />
                </div>
                <div class="bg-[#3d3d3f] h-3 rounded-b-lg"></div>
                <div class="bg-[#2d2d2f] h-1 w-24 mx-auto rounded-b-sm"></div>
            </div>
        @endif

        @if($mobileScreenshot)
            <div class="relative">
                <div class="bg-[#1d1d1f] rounded-[1.75rem] p-[3px] shadow-2xl border border-[#3d3d3f]">
                    <div class="bg-black rounded-[1.5rem] overflow-hidden relative">
                        <div class="absolute top-2 left-1/2 -translate-x-1/2 z-10">
                            <div class="bg-black w-12 h-3 rounded-full"></div>
                        </div>
                        <img
                            src="{{ $mobileScreenshot }}"
                            alt="Mobile Preview"
                            class="w-24 lg:w-28 rounded-[1.5rem]"
                        />
                        <div class="absolute bottom-1.5 left-1/2 -translate-x-1/2">
                            <div class="h-1 w-8 bg-white/90 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@elseif($screenshotFailed)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center text-sm text-amber-700">
        {{ __('audit.screenshot_unavailable') }}
    </div>
@endif
