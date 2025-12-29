@php
    $pass = $pass ?? false;
@endphp

<div class="w-6 h-6 p-1 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-center shrink-0">
    @if($pass)
        <svg
            class="w-full h-full object-contain text-green-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
            />
        </svg>
    @else
        <svg
            class="w-full h-full object-contain text-red-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
            />
        </svg>
    @endif
</div>