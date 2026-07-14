<x-guest-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-emerald-800">
            Konsultasi Zakat AI
        </h2>
    </x-slot>

    <div class="py-12 flex-1 flex flex-col items-center">
        <div class="w-full max-w-4xl px-4 sm:px-6 lg:px-8 flex-1 flex flex-col min-h-[600px] max-h-[80vh]">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-emerald-100 flex-1 flex flex-col relative">
                <x-chatbot-widget :embedded="true" />
            </div>

            <div class="mt-4 text-center">
                <p class="text-xs text-slate-500">
                    AI dapat melakukan kesalahan. Harap verifikasi perhitungan akhir dengan amil Zakat An-Nur.
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
