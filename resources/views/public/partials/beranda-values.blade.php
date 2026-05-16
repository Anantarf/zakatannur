<div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    @include('public.partials.beranda-quote')
    @include('public.partials.beranda-feature-card', [
        'title' => 'Manajemen Muzakki',
        'description' => 'Pencatatan data jamaah muzakki yang rapi dan amanah.',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
    ])
    @include('public.partials.beranda-feature-card', [
        'title' => 'Laporan Real-Time',
        'description' => 'Transparansi penuh melalui rekapitulasi otomatis publik.',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>',
    ])
    @include('public.partials.beranda-feature-card', [
        'title' => 'Amanah & Profesional',
        'description' => 'Dikelola oleh Panitia Zakat dengan integritas tinggi.',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>',
    ])
</div>
