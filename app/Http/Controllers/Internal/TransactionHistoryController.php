<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ZakatTransaction;
use App\Support\Audit;
use App\Support\ViewOptions;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransactionHistoryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->parseFilters($request);
        
        $transactions = ZakatTransaction::query()
            ->select(
                'no_transaksi',
                DB::raw('MAX(id) as id'),
                DB::raw('MAX(waktu_terima) as waktu_terima'),
                DB::raw('MAX(created_at) as created_at'),
                DB::raw('SUM(nominal_uang) as total_uang'),
                DB::raw('SUM(jumlah_beras_kg) as total_beras'),
                DB::raw('MAX(pembayar_nama) as pembayar_nama'),
                DB::raw('MAX(petugas_id) as petugas_id'),
                DB::raw('MAX(shift) as shift'),
                DB::raw('group_concat(DISTINCT category) as categories_list'),
                DB::raw('group_concat(DISTINCT metode) as methods_list'),
                DB::raw('COUNT(DISTINCT muzakki_id) as muzakki_total'), DB::raw('MAX(CASE WHEN metode = "uang" THEN is_transfer ELSE 0 END) as has_transfer')
            )
            ->with(['petugas'])
            ->filter($filters)
            ->groupBy('no_transaksi')
            ->orderByRaw('COALESCE(MAX(waktu_terima), MAX(created_at)) DESC')
            ->orderByDesc('no_transaksi')
            ->paginate(20)
            ->appends($request->query());

        $years = ViewOptions::years($filters['activeYear']);
        $petugasOptions = ViewOptions::petugasOptions();

        // Fetch distinct available dates and years for Export Modal using DB-level aggregation (Lean & Efficient)
        $availableDatesRaw = ZakatTransaction::where('status', ZakatTransaction::STATUS_VALID)
            ->selectRaw('DISTINCT DATE(COALESCE(waktu_terima, created_at)) as date')
            ->orderByDesc('date')
            ->pluck('date');
        
        $availableDates = $availableDatesRaw->mapWithKeys(function($date) {
            return [$date => \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('d F Y')];
        });

        $availableYears = ZakatTransaction::where('status', ZakatTransaction::STATUS_VALID)
            ->distinct()
            ->orderByDesc('tahun_zakat')
            ->pluck('tahun_zakat');


        return view('internal.transactions.index', array_merge($filters, [
            'transactions' => $transactions,
            'years' => $years,
            'categories' => ZakatTransaction::CATEGORIES,
            'methods' => ZakatTransaction::METHODS,
            'statuses' => ZakatTransaction::STATUSES,
            'petugasOptions' => $petugasOptions,
            'availableDates' => $availableDates,
            'availableYears' => $availableYears,
        ]));
    }

    /**
     * @return array{q:string,year:int|null,category:?string,metode:?string,status:?string,petugasId:?int,activeYear:int}
     */
    private function parseFilters(Request $request): array
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        $validated = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'category' => ['nullable', 'string', Rule::in(ZakatTransaction::CATEGORIES)],
            'metode' => ['nullable', 'string', Rule::in(ZakatTransaction::METHODS)],
            'status' => ['nullable', 'string', Rule::in(ZakatTransaction::STATUSES)],
            'petugas_id' => ['nullable', 'integer', 'exists:users,id'],
        ])->validate();

        $q = isset($validated['q']) ? trim((string) $validated['q']) : '';

        // Intent: ?year= not present → default to activeYear (most common use case).
        // ?year= present but empty string → null (user explicitly chose "Semua Waktu").
        $year = array_key_exists('year', $validated)
            ? ($validated['year'] !== null ? (int) $validated['year'] : null)
            : $activeYear;

        return [
            'q' => $q,
            'year' => $year,
            'category' => $validated['category'] ?? null,
            'metode' => $validated['metode'] ?? null,
            'status' => $validated['status'] ?? null,
            'petugasId' => isset($validated['petugas_id']) ? (int) $validated['petugas_id'] : null,
            'activeYear' => $activeYear,
        ];
    }

    public function trash(Request $request)
    {
        $purgeDays = 30;
        $q = trim((string) $request->query('q', ''));

        $transactions = ZakatTransaction::onlyTrashed()
            ->select(
                'no_transaksi',
                DB::raw('MAX(id) as id'),
                DB::raw('MAX(waktu_terima) as waktu_terima'),
                DB::raw('MAX(created_at) as created_at'),
                DB::raw('MAX(deleted_at) as deleted_at'),
                DB::raw('SUM(nominal_uang) as total_uang'),
                DB::raw('SUM(jumlah_beras_kg) as total_beras'),
                DB::raw('MAX(pembayar_nama) as pembayar_nama'),
                DB::raw('MAX(petugas_id) as petugas_id'),
                DB::raw('MAX(shift) as shift'),
                DB::raw('group_concat(DISTINCT category) as categories_list'),
                DB::raw('group_concat(DISTINCT metode) as methods_list'),
                DB::raw('COUNT(DISTINCT muzakki_id) as muzakki_total'), DB::raw('MAX(CASE WHEN metode = "uang" THEN is_transfer ELSE 0 END) as has_transfer')
            )
            ->with(['petugas'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . str_replace('%', '\\%', $q) . '%';
                $query->where(fn($sub) =>
                    $sub->where('no_transaksi', 'like', $like)
                        ->orWhere('pembayar_nama', 'like', $like)
                );
            })
            ->groupBy('no_transaksi')
            ->orderByDesc('deleted_at')
            ->paginate(20)
            ->appends($request->query());

        $transactions->getCollection()->transform(function ($transaction) use ($purgeDays) {
            // Fix: deleted_at inside MAX() might be stringy, ensure it's parsed as localized carbon
            $deletedAt = $transaction->deleted_at ? \Carbon\Carbon::parse($transaction->deleted_at)->setTimezone('Asia/Jakarta') : null;
            $transaction->days_left           = $deletedAt ? max(0, $purgeDays - (int) $deletedAt->startOfDay()->diffInDays(now('Asia/Jakarta')->startOfDay())) : null;
            $transaction->deleted_at_formatted = $deletedAt ? $deletedAt->format('d/m/Y H:i') : '-';
            return $transaction;
        });

        return view('internal.transactions.trash', [
            'transactions' => $transactions,
            'purgeDays'    => $purgeDays,
            'q'            => $q,
        ]);
    }

    public function destroy(Request $request, ZakatTransaction $transaction)
    {
        // Strict Deletion Regulation
        $this->authorizeDeletion($request->user(), $transaction);

        $no = $transaction->no_transaksi;
        $id = $transaction->id;
        $payer = $transaction->pembayar_nama;

        DB::transaction(function () use ($no, $request, $transaction, $payer) {
            ZakatTransaction::where('no_transaksi', $no)->update([
                'deleted_by' => $request->user()->id,
                'deleted_reason' => $request->input('deleted_reason'),
            ]);

            // Delete the entire group
            $affected = ZakatTransaction::where('no_transaksi', $no)->delete();

            Audit::log($request, 'transaction.delete', null, [
                'no_transaksi' => $no,
                'pembayar' => $payer,
                'items_count' => $affected
            ]);
        });

        return redirect()->route('internal.transactions.index')
            ->with('status', "Transaksi {$no} ({$payer}) berhasil dipindahkan ke sampah.")
            ->with('undo_id', $id)
            ->with('undo_no', $no);
    }

    private function authorizeDeletion(\App\Models\User $user, ZakatTransaction $tx): void
    {
        if ($user->role === \App\Models\User::ROLE_SUPER_ADMIN || $user->role === \App\Models\User::ROLE_ADMIN) {
            return;
        }

        // Staff Restrictions:
        // 1. Can only delete their own transactions
        if ((int)$tx->petugas_id !== (int)$user->id) {
            abort(Response::HTTP_FORBIDDEN, 'Anda hanya dapat menghapus transaksi yang Anda layani sendiri.');
        }

        // 2. Can only delete today's transactions
        $txDate = ($tx->waktu_terima ?? $tx->created_at)->timezone('Asia/Jakarta');
        if (!$txDate->isToday()) {
            abort(Response::HTTP_FORBIDDEN, 'Batas waktu penghapusan harian telah berakhir. Silakan hubungi Admin untuk menghapus data hari sebelumnya.');
        }
    }

    public function restore(Request $request, int $transactionId)
    {
        $user = $request->user();
        $transaction = ZakatTransaction::withTrashed()->findOrFail($transactionId);
        $noTransaksi = $transaction->no_transaksi;

        // Safety Guard: Prevent collision if number is already active
        $isExisting = ZakatTransaction::where('no_transaksi', $noTransaksi)->exists();
        if ($isExisting) {
            return redirect()->route('internal.transactions.trash')
                ->withErrors(['restore' => "Gagal memulihkan! Nomor {$noTransaksi} sudah digunakan oleh transaksi aktif lain. Hapus atau ubah dulu nomor yang ada di Riwayat jika ingin memulihkan data ini."]);
        }

        DB::transaction(function () use ($noTransaksi, $user, $request) {
            ZakatTransaction::onlyTrashed()
                ->where('no_transaksi', $noTransaksi)
                ->restore();

            ZakatTransaction::where('no_transaksi', $noTransaksi)
                ->update([
                    'restored_at' => now('Asia/Jakarta'),
                    'restored_by' => $user->id,
                    'deleted_by'  => null,
                ]);

            Audit::log($request, 'Restored.Transaction', null, [
                'no_transaksi' => $noTransaksi,
            ]);
        });

        return redirect()->back()->with('status', "Transaksi {$transaction->no_transaksi} berhasil dikembalikan ke riwayat aktif.");
    }

    public function forceDelete(Request $request, int $transactionId)
    {
        $transaction = ZakatTransaction::withTrashed()->findOrFail($transactionId);
        $noTransaksi = $transaction->no_transaksi;

        DB::transaction(function () use ($noTransaksi, $request) {
            $affected = ZakatTransaction::onlyTrashed()
                ->where('no_transaksi', $noTransaksi)
                ->forceDelete();

            Audit::log($request, 'Deleted.Permanently.Transaction', null, [
                'no_transaksi' => $noTransaksi,
                'items_count'  => $affected,
            ]);
        });

        return redirect()->route('internal.transactions.trash')
            ->with('status', 'Transaksi ' . $noTransaksi . ' berhasil dihapus permanen.');
    }
}
