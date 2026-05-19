@php
    $actionClass = match(true) {
        $log->action === 'Created.Transaction' || str_contains($log->action, 'created') => 'bg-emerald-100 text-emerald-700',
        $log->action === 'Updated.Transaction' || str_contains($log->action, 'updated') => 'bg-amber-100 text-amber-700',
        $log->action === 'transaction.delete' => 'bg-pink-100 text-pink-700',
        $log->action === 'Deleted.Permanently.Transaction' => 'bg-red-900 text-white',
        $log->action === 'Restored.Transaction' || str_contains($log->action, 'restored') => 'bg-indigo-100 text-indigo-700',
        $log->action === 'login' => 'bg-blue-100 text-blue-700',
        $log->action === 'logout' => 'bg-slate-200 text-slate-700',
        str_contains($log->action, 'deleted') => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-700'
    };
@endphp
<span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $actionClass }}">
    {{ $log->action }}
</span>
