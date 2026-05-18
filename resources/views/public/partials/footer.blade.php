@php
    $marqueeItems = [
        ['before' => '', 'highlight' => 'Masjid An-Nur Komplek BPK V Gandul', 'after' => ' - Melayani dan menyalurkan Zakat Fitrah, Fidyah, Zakat Mal, Infaq Shodaqoh.'],
        ['before' => 'Tunaikan ', 'highlight' => 'zakat tepat waktu', 'after' => ' agar manfaatnya dapat segera dirasakan oleh saudara kita yang membutuhkan.'],
        ['before' => 'Zakat Anda sangat berarti untuk ', 'highlight' => 'membantu sesama', 'after' => ' dan meringankan beban umat.'],
        ['before' => 'Semoga zakat yang Bapak dan Ibu keluarkan menjadi ', 'highlight' => 'pembersih harta', 'after' => ' dan pembuka pintu rezeki.'],
        ['before' => '', 'highlight' => 'Amanah dan transparan', 'after' => ' dalam pengelolaan zakat adalah komitmen utama Panitia Zakat Masjid An-Nur.'],
        ['before' => 'Mari raih ', 'highlight' => 'keberkahan', 'after' => ' dengan menyisihkan sebagian harta untuk kemaslahatan umat.'],
        ['before' => 'Harta yang dizakatkan tidak akan berkurang, melainkan ', 'highlight' => 'bertambah berkahnya', 'after' => '.'],
    ];
@endphp

<div class="fixed bottom-0 left-0 right-0 z-50 flex flex-col pointer-events-none">
    <div class="pointer-events-auto">
        <div class="bg-emerald-950 border-t border-emerald-900 shadow-2xl relative w-full shrink-0 overflow-hidden backdrop-blur-md bg-opacity-95">
            <div class="marquee-container h-8 sm:h-10">
                <div class="marquee-track h-full items-center">
                    @foreach ([1, 2] as $marqueeLoop)
                        <div class="flex shrink-0 items-center whitespace-nowrap px-4 text-[13px] sm:text-[15px] font-bold text-emerald-50/60 tracking-wide" @if ($marqueeLoop === 2) aria-hidden="true" @endif>
                            @foreach ($marqueeItems as $item)
                                <span class="mx-8">{{ $item['before'] }}<span class="text-emerald-200">{{ $item['highlight'] }}</span>{{ $item['after'] }}</span>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <footer class="bg-emerald-950 border-t border-emerald-900 pt-2 pb-4 sm:pt-3 sm:pb-8 text-center w-full shrink-0 backdrop-blur-md bg-opacity-95">
            <div class="mx-auto max-w-5xl px-6">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-2 text-[11px] sm:text-[12px] font-bold tracking-widest sm:tracking-[0.2em] uppercase">
                    <span class="text-emerald-50/40">Powered by</span>
                    <div class="footer-item-group group flex items-center gap-2">
                        <span class="font-black text-emerald-300 transition-colors duration-300 group-hover:text-emerald-200">Ikatan Remaja Komplek BPK V Gandul</span>
                        <img src="/images/logo_irk.webp" class="footer-logo h-4 w-auto opacity-70" alt="Logo IRK">
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>
