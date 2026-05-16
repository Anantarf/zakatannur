<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\ZakatTransaction;
use App\Services\Transactions\TransactionGroupLifecycleService;
use App\Services\Transactions\TransactionHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionHistoryController extends Controller
{
    public function index(Request $request, TransactionHistoryService $historyService): View
    {
        $filters = $historyService->parseFilters($request);
        $transactions = $historyService->paginatedHistory($filters, $request->query());

        return view('internal.transactions.index', array_merge(
            $historyService->indexViewData($filters),
            ['transactions' => $transactions]
        ));
    }

    public function trash(Request $request, TransactionHistoryService $historyService): View
    {
        $q = trim((string) $request->query('q', ''));

        return view('internal.transactions.trash', [
            'transactions' => $historyService->paginatedTrash($q, $request->query()),
            'purgeDays' => (int) config('zakat.retention.purge_days', 30),
            'q' => $q,
        ]);
    }

    public function destroy(
        Request $request,
        ZakatTransaction $transaction,
        TransactionGroupLifecycleService $lifecycleService
    ): RedirectResponse {
        $lifecycleService->authorizeDeletion($request->user(), $transaction);

        $validated = $request->validate([
            'deleted_reason' => ['required', 'string', 'min:' . (int) config('zakat.validation.reason_min', 5), 'max:' . (int) config('zakat.validation.reason_max', 255)],
        ]);

        $result = $lifecycleService->trashGroup($request, $transaction, $validated['deleted_reason']);

        return redirect()->route('internal.transactions.index')
            ->with('status', "Transaksi {$result['no_transaksi']} ({$result['payer']}) berhasil dipindahkan ke sampah.")
            ->with('undo_id', $result['id'])
            ->with('undo_no', $result['no_transaksi']);
    }

    public function restore(
        Request $request,
        int $transactionId,
        TransactionGroupLifecycleService $lifecycleService
    ): RedirectResponse {
        $result = $lifecycleService->restoreGroup($request, $transactionId);

        if (!$result['restored']) {
            return redirect()->route('internal.transactions.trash')
                ->withErrors(['restore' => "Gagal memulihkan! Nomor {$result['no_transaksi']} sudah digunakan oleh transaksi aktif lain. Hapus atau ubah dulu nomor yang ada di Riwayat jika ingin memulihkan data ini."]);
        }

        return redirect()->back()->with('status', "Transaksi {$result['no_transaksi']} berhasil dikembalikan ke riwayat aktif.");
    }

    public function forceDelete(
        Request $request,
        int $transactionId,
        TransactionGroupLifecycleService $lifecycleService
    ): RedirectResponse {
        $noTransaksi = $lifecycleService->forceDeleteGroup($request, $transactionId);

        return redirect()->route('internal.transactions.trash')
            ->with('status', 'Transaksi ' . $noTransaksi . ' berhasil dihapus permanen.');
    }
}
