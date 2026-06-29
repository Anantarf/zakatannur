@props(['name', 'options' => [], 'value' => '', 'placeholder' => 'Pilih...'])

<div x-data="{
        open: false,
        value: '{{ $value }}',
        options: {{ json_encode($options) }},
        get selectedLabel() {
            return this.options[this.value] || '{{ $placeholder }}';
        },
        select(val) {
            this.value = val;
            this.open = false;
            this.$nextTick(() => {
                this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    }"
    x-modelable="value"
    {{ $attributes->whereStartsWith('x-model') }}
    @click.outside="open = false"
    class="relative w-full {{ $attributes->get('class') }}"
>
    <!-- Hidden input to store the actual value and trigger form submission -->
    <input type="hidden" name="{{ $name }}" x-model="value" x-ref="hiddenInput" {{ $attributes->except(['class', 'x-model']) }} />

    <!-- Trigger Button -->
    <button type="button" 
            @click="open = !open"
            class="ui-select flex items-center justify-between w-full shadow-sm"
            :class="{ 'border-brand-500 ring-2 ring-brand-500 ring-offset-2': open }"
    >
        <span x-text="selectedLabel" class="truncate pointer-events-none" :class="{ 'text-slate-400': !value, 'text-slate-800 font-bold': value }"></span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 transition-transform duration-200 pointer-events-none" :class="open ? 'rotate-180 text-brand-600' : 'text-slate-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
         style="display: none;"
         class="absolute z-50 mt-2 w-full min-w-[200px] rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl max-h-60 overflow-y-auto"
    >
        <!-- Placeholder Option (Clear selection) -->
        <div class="mb-1 border-b border-slate-100 pb-1">
            <button type="button"
                    @click="select('')"
                    class="w-full rounded-lg px-3 py-2.5 text-left text-[13px] transition-colors"
                    :class="value === '' ? 'bg-brand-50 font-bold text-brand-700' : 'font-semibold text-slate-500 hover:bg-slate-50 hover:text-slate-800'"
            >
                {{ $placeholder }}
            </button>
        </div>
        
        <!-- Options List -->
        <template x-for="(label, key) in options" :key="key">
            <button type="button"
                    @click="select(key)"
                    class="w-full flex items-center justify-between rounded-lg px-3 py-2.5 text-left text-[13px] transition-colors mb-0.5 last:mb-0"
                    :class="String(value) === String(key) ? 'bg-brand-50 font-bold text-brand-700' : 'font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-600'"
            >
                <span x-text="label" class="truncate"></span>
                <svg x-show="String(value) === String(key)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </button>
        </template>
    </div>
</div>
