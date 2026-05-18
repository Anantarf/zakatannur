@php
    $overview = $historyOverview ?? [
        'totalGroups' => 0,
        'riskyGroups' => 0,
        'suspiciousGroups' => 0,
        'pendingReviewGroups' => 0,
        'safeReviewGroups' => 0,
    ];
@endphp

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
    <x-ui-stat-card title="Total Grup" :value="$overview['totalGroups']" description="Transaksi grup sesuai filter aktif." />
    <x-ui-stat-card title="Perlu Dicek" :value="$overview['riskyGroups']" description="Warning atau suspicious." tone="warning" />
    <x-ui-stat-card title="Kandidat Kuat" :value="$overview['suspiciousGroups']" description="Butuh verifikasi lebih dulu." tone="danger" />
    <x-ui-stat-card title="Belum Ditinjau" :value="$overview['pendingReviewGroups']" description="Masih menunggu keputusan operator." tone="muted" />
    <x-ui-stat-card title="Sudah Aman" :value="$overview['safeReviewGroups']" description="Review manual sudah ditutup." tone="info" />
</div>
