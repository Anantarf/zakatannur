<div x-show="activeTab === 'beranda'"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="space-y-6 p-3 sm:p-5 lg:p-8 bg-white rounded-[2rem] sm:rounded-[3rem] shadow-2xl shadow-emerald-900/5 border border-slate-100">
    @include('public.partials.beranda-hero')
    @include('public.partials.beranda-intro')
    @include('public.partials.beranda-values')
    @include('public.partials.beranda-cta')
</div>
