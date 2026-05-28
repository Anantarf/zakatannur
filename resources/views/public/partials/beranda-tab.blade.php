<div x-show="activeTab === 'beranda'"
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="public-panel space-y-4 sm:space-y-5">
    @include('public.partials.beranda-hero')
    @include('public.partials.beranda-intro')
    @include('public.partials.beranda-values')
    @include('public.partials.beranda-cta')
    @include('public.partials.beranda-quote')
</div>
