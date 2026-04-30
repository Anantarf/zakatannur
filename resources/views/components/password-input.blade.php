@props([
    'name' => 'password',
    'label' => 'Kata Sandi',
    'hint' => null,
    'required' => false,
])

<div x-data="{ showPass: false }">
    <label class="block text-sm font-bold text-gray-700 mb-1" for="{{ $name }}">{{ $label }}</label>
    <div class="relative">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            :type="showPass ? 'text' : 'password'"
            {{ $required ? 'required' : '' }}
            class="w-full rounded-xl border-gray-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-all pr-10"
        />
        <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 transition-colors">
            <svg x-show="!showPass" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <svg x-show="showPass" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18" />
            </svg>
        </button>
    </div>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
