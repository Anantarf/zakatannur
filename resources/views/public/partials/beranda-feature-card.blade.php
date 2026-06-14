<article class="public-shell hover-lift flex items-start gap-4 p-4 group">
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700 shadow-sm transition-all group-hover:bg-brand-700 group-hover:text-white">
        @switch($icon)
            @case('people')
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                @break

            @case('report')
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                @break

            @case('committee')
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                @break
        @endswitch
    </div>
    <div class="min-w-0">
        <h4 class="text-[0.98rem] font-bold tracking-[-0.01em] text-slate-950 sm:text-[1.02rem]">{{ $title }}</h4>
        <p class="mt-1.5 text-[13px] font-medium leading-relaxed text-slate-600 sm:text-[13.5px]">{{ $description }}</p>
    </div>
</article>
