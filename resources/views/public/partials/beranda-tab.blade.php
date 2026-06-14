<div x-show="activeTab === 'beranda'"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="space-y-3 sm:space-y-4">
    @include('public.partials.beranda-hero')
    @include('public.partials.beranda-values')
    @include('public.partials.beranda-quote')
</div>
