<section class="grid grid-cols-1 gap-6 md:grid-cols-3">
    @include('public.partials.beranda-feature-card', [
        'title' => 'Manajemen Muzakki',
        'description' => 'Pencatatan data jamaah muzakki yang rapi dan amanah.',
        'icon' => 'people',
    ])
    @include('public.partials.beranda-feature-card', [
        'title' => 'Laporan Real-Time',
        'description' => 'Transparansi penuh melalui rekapitulasi otomatis publik.',
        'icon' => 'report',
    ])
    @include('public.partials.beranda-feature-card', [
        'title' => 'Amanah & Profesional',
        'description' => 'Disusun Panitia Zakat An-Nur per periode.',
        'icon' => 'committee',
    ])
</section>
