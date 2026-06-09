@php
    $marqueeItems = [
        'Masjid An-Nur Komplek BPK V Gandul - Melayani dan menyalurkan Zakat Fitrah, Fidyah, Zakat Mal, Infaq Shodaqoh.',
        'Tunaikan zakat tepat waktu agar manfaatnya dapat segera dirasakan oleh saudara kita yang membutuhkan.',
        'Amanah dan transparan dalam pengelolaan zakat adalah komitmen utama Panitia Zakat Masjid An-Nur.',
    ];
@endphp

<footer class="public-footer-shell">
    <div class="public-footer-credit">
        <span class="public-footer-kicker">Powered by</span>
        <div class="footer-item-group group flex flex-col items-center gap-1 sm:flex-row sm:gap-1.5">
            <span class="public-footer-brand">Ikatan Remaja Komplek BPK V Gandul</span>
            <img src="/images/logo_irk.webp" class="footer-logo h-3.5 w-auto opacity-60" alt="Logo IRK">
        </div>
    </div>
</footer>

<div class="sticky-marquee" aria-label="Informasi zakat berjalan">
    <div class="sticky-marquee-track">
        @foreach ([1, 2] as $loop)
            <div class="sticky-marquee-copy" @if ($loop === 2) aria-hidden="true" @endif>
                @foreach ($marqueeItems as $item)
                    <span>{{ $item }}</span>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
