<div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-xl shadow-emerald-900/5 flex flex-col justify-between group hover:border-emerald-200 transition-all hover:-translate-y-1">
    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 mb-6 group-hover:bg-emerald-600 group-hover:text-white transition-all shadow-sm">
        {!! $icon !!}
    </div>
    <div>
        <h4 class="text-lg font-black text-slate-900">{{ $title }}</h4>
        <p class="text-sm text-slate-500 font-bold mt-2 leading-relaxed">{{ $description }}</p>
    </div>
</div>
