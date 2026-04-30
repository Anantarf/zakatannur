<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex justify-center items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-sm sm:text-base font-black text-white shadow-lg shadow-emerald-100 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all transform hover:-translate-y-0.5 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>
