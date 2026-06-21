@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Pagination" class="flex items-center justify-between">
        <div class="flex flex-1 justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold leading-5 text-slate-400">
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold leading-5 text-slate-700 transition hover:border-brand-200 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative ml-3 inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold leading-5 text-slate-700 transition hover:border-brand-200 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-200">
                    Berikutnya
                </a>
            @else
                <span class="relative ml-3 inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold leading-5 text-slate-400">
                    Berikutnya
                </span>
            @endif
        </div>

        <div class="hidden flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm leading-5 text-slate-500">
                    Menampilkan
                    @if ($paginator->firstItem())
                        <span class="font-bold text-slate-800">{{ $paginator->firstItem() }}</span>
                        sampai
                        <span class="font-bold text-slate-800">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    dari
                    <span class="font-bold text-slate-800">{{ $paginator->total() }}</span>
                    hasil
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex rounded-xl shadow-sm">
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Halaman sebelumnya">
                            <span class="relative inline-flex items-center rounded-l-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium leading-5 text-slate-300" aria-hidden="true">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center rounded-l-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium leading-5 text-slate-500 transition hover:border-brand-200 hover:text-brand-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-brand-200" aria-label="Halaman sebelumnya">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative -ml-px inline-flex items-center border border-slate-200 bg-white px-4 py-2 text-sm font-medium leading-5 text-slate-500">{{ $element }}</span>
                            </span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative -ml-px inline-flex items-center border border-brand-200 bg-brand-50 px-4 py-2 text-sm font-bold leading-5 text-brand-700">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative -ml-px inline-flex items-center border border-slate-200 bg-white px-4 py-2 text-sm font-medium leading-5 text-slate-700 transition hover:border-brand-200 hover:text-brand-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-brand-200" aria-label="Ke halaman {{ $page }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative -ml-px inline-flex items-center rounded-r-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium leading-5 text-slate-500 transition hover:border-brand-200 hover:text-brand-700 focus:z-10 focus:outline-none focus:ring-2 focus:ring-brand-200" aria-label="Halaman berikutnya">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="Halaman berikutnya">
                            <span class="relative -ml-px inline-flex items-center rounded-r-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium leading-5 text-slate-300" aria-hidden="true">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
