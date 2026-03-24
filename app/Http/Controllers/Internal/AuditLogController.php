<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ZakatTransaction;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $query = AuditLog::with('actorUser');

        if ($sortBy === 'petugas') {
            $query->leftJoin('users', 'audit_logs.actor_user_id', '=', 'users.id')
                ->select('audit_logs.*') // Avoid column name collisions
                ->orderBy('users.name', $sortDir);
        } elseif (in_array($sortBy, ['action', 'created_at'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('internal.audit_logs.index', compact('logs', 'sortBy', 'sortDir'));
    }

    public function bulkDeleteTransactions(Request $request)
    {
        // Fitur ini dinonaktifkan sementara untuk mencegah penghapusan data secara tidak sengaja
        abort(403, 'Fitur Pembersihan Data sedang dinonaktifkan sementara.');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = $request->start_date . ' 00:00:00';
        $end = $request->end_date . ' 23:59:59';

        $count = DB::transaction(function() use ($start, $end, $request) {
            $query = ZakatTransaction::withTrashed()
                ->where(function($q) use ($start, $end) {
                    $q->whereBetween('waktu_terima', [$start, $end])
                      ->orWhereBetween('created_at', [$start, $end]);
                });

            $affectedCount = $query->count();
            
            if ($affectedCount > 0) {
                $query->forceDelete();

                Audit::log($request, 'system.bulk_transaction_cleanup', null, [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'count' => $affectedCount
                ]);
            }

            return $affectedCount;
        });

        if ($count === 0) {
            return back()->with('error', 'Tidak ditemukan transaksi pada rentang tanggal tersebut.');
        }

        return back()->with('status', "Pembersihan database berhasil. {$count} transaksi telah dihapus secara permanen.");
    }
}
