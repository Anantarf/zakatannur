@props([
    'mobile' => false,
    'user' => null,
    'canInputTransaksi' => false,
    'isAdmin' => false,
    'segmented' => false,
    'pendingAnomalyCount' => 0,
])

@php
    $linkComponent = $mobile ? 'responsive-nav-link' : 'nav-link';
@endphp

<x-dynamic-component :component="$linkComponent" :href="route('dashboard')" :active="request()->routeIs('dashboard')">
    {{ __('Dashboard') }}
</x-dynamic-component>

@if ($canInputTransaksi)
    <x-dynamic-component :component="$linkComponent" :href="route('internal.muzakki.index')" :active="request()->routeIs('internal.muzakki.*')">
        {{ __('Muzakki') }}
    </x-dynamic-component>
    <x-dynamic-component :component="$linkComponent" :href="route('internal.transactions.index')" :active="request()->routeIs('internal.transactions.index') || request()->routeIs('internal.transactions.trash')">
        {{ __('Riwayat Transaksi') }}
    </x-dynamic-component>
    <x-dynamic-component :component="$linkComponent" :href="route('internal.transactions.create')" :active="request()->routeIs('internal.transactions.create')">
        {{ __('Input Transaksi') }}
    </x-dynamic-component>
@endif

@if ($isAdmin && $mobile && ! $segmented)
    <div class="border-t border-slate-100 bg-slate-50/50 pb-1 pt-4">
        <div class="ui-label px-4 py-2 text-slate-400">Area Admin</div>
        <x-dynamic-component :component="$linkComponent" :href="route('internal.users.index')" :active="request()->routeIs('internal.users.*')">
            {{ __('Manajemen Pengguna') }}
        </x-dynamic-component>
        <x-dynamic-component :component="$linkComponent" :href="route('internal.audit_logs.index')" :active="request()->routeIs('internal.audit_logs.index')">
            {{ __('Audit Logs') }}
        </x-dynamic-component>
        @if ($user?->isSuperAdmin())
            <x-dynamic-component :component="$linkComponent" :href="route('internal.settings.period.edit')" :active="request()->routeIs('internal.settings.period.*')">
                {{ __('Konfigurasi Periode') }}
            </x-dynamic-component>
            <x-dynamic-component :component="$linkComponent" :href="route('internal.templates.letterhead')" :active="request()->routeIs('internal.templates.*')">
                {{ __('Template Kop Surat') }}
            </x-dynamic-component>
        @endif
    </div>
@endif

@if ($isAdmin && $mobile && $segmented)
    <x-dynamic-component :component="$linkComponent" :href="route('internal.users.index')" :active="request()->routeIs('internal.users.*')">
        {{ __('Pengguna') }}
    </x-dynamic-component>
    <x-dynamic-component :component="$linkComponent" :href="route('internal.audit_logs.index')" :active="request()->routeIs('internal.audit_logs.index')">
        {{ __('Audit') }}
    </x-dynamic-component>
    @if ($user?->isSuperAdmin())
        <x-dynamic-component :component="$linkComponent" :href="route('internal.settings.period.edit')" :active="request()->routeIs('internal.settings.period.*')">
            {{ __('Periode') }}
        </x-dynamic-component>
        <x-dynamic-component :component="$linkComponent" :href="route('internal.templates.letterhead')" :active="request()->routeIs('internal.templates.*')">
            {{ __('Template') }}
        </x-dynamic-component>
    @endif
@endif
