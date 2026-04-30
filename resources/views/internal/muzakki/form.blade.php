<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-bold text-xl sm:text-2xl text-emerald-800 leading-tight flex items-center justify-center sm:justify-start gap-2 text-center sm:text-left">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ $mode === 'create' ? 'Tambah Muzakki' : 'Ubah Muzakki' }}
            </h2>
            <a href="{{ route('internal.muzakki.index') }}" class="inline-flex justify-center items-center gap-2 rounded-xl bg-white border border-gray-100 px-4 py-3 sm:py-2 text-sm font-bold text-gray-500 hover:text-emerald-700 hover:bg-emerald-50 transition-all w-full sm:w-auto shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-form-errors />

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-emerald-50 px-6 py-4 border-b border-emerald-100 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <h3 class="font-bold text-emerald-900">Data Muzakki</h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ $mode === 'create' ? route('internal.muzakki.store') : route('internal.muzakki.update', ['muzakki' => $muzakki->id]) }}" class="space-y-5">
                        @csrf
                        @if ($mode === 'edit')
                            @method('PATCH')
                        @endif

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1" for="name">Nama</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $muzakki->name) }}" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all" required maxlength="150" />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1" for="phone">No HP</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone', $muzakki->phone) }}" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all" maxlength="30" />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1" for="address">Alamat</label>
                            <textarea id="address" name="address" rows="3" class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all">{{ old('address', $muzakki->address) }}</textarea>
                        </div>

                        <div class="pt-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <button type="submit" class="inline-flex justify-center items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-md hover:bg-emerald-700 transition-all w-full sm:w-auto">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                Simpan
                            </button>
                            <a href="{{ route('internal.muzakki.index') }}" class="px-4 py-3 sm:py-2 text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors w-full sm:w-auto text-center border sm:border-0 border-gray-200 rounded-xl sm:rounded-none">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
