@php
    $isArchived = ($scope ?? 'active') === 'archived';
@endphp
<div class="ui-empty-state-box">
    <div class="flex flex-col items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="mb-2 h-10 w-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <span class="text-sm font-medium text-gray-400">
            {{ $isArchived
                ? 'Belum ada kasus aman di riwayat review untuk filter ini.'
                : 'Belum ada kasus anomali aktif untuk filter ini.' }}
        </span>
        @if (!empty($showBackToFirstPage) && $groups->total() > 0 && request()->has('page') && request('page') > 1)
            <div class="mt-4">
                <a href="{{ request()->fullUrlWithQuery(['page' => 1]) }}" class="ui-btn ui-btn-secondary">
                    Kembali ke Halaman 1
                </a>
            </div>
        @endif
    </div>
</div>