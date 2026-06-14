<section class="grid grid-cols-1 gap-3 md:grid-cols-3">
    @include('public.partials.beranda-feature-card', [
        'title' => 'Transparan',
        'description' => 'Jamaah dapat membuka ringkasan penerimaan tanpa menunggu rekap manual.',
        'icon' => 'people',
    ])
    @include('public.partials.beranda-feature-card', [
        'title' => 'Mudah Dipantau',
        'description' => 'Kategori zakat, total uang, beras, dan jiwa disusun dalam tampilan ringkas.',
        'icon' => 'report',
    ])
    @include('public.partials.beranda-feature-card', [
        'title' => 'Amanah',
        'description' => 'Dikelola panitia zakat Masjid An-Nur untuk kebutuhan periode berjalan.',
        'icon' => 'committee',
    ])
</section>
