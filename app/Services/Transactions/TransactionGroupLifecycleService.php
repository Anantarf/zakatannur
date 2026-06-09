<?php

namespace App\Services\Transactions;

use App\Models\User;
use App\Models\TransactionRiskReview;
use App\Models\ZakatTransaction;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TransactionGroupLifecycleService
{
    public function __construct(
        private TransactionReviewAssistantService $reviewAssistantService,
    ) {
    }

    public function authorizeDeletion(User $user, ZakatTransaction $transaction): void
    {
        if ($user->role === User::ROLE_SUPER_ADMIN || $user->role === User::ROLE_ADMIN) {
            return;
        }

        if ((int) $transaction->petugas_id !== (int) $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'Anda hanya dapat menghapus transaksi yang Anda layani sendiri.');
        }

        if ($transaction->receipt_printed_at !== null) {
            abort(Response::HTTP_FORBIDDEN, 'Transaksi yang kwitansinya sudah dicetak hanya dapat dihapus oleh Admin.');
        }

        $transactionDate = ($transaction->waktu_terima ?? $transaction->created_at)->timezone(config('zakat.timezone'));
        if (!$transactionDate->isToday()) {
            abort(Response::HTTP_FORBIDDEN, 'Batas waktu penghapusan harian telah berakhir. Silakan hubungi Admin untuk menghapus data hari sebelumnya.');
        }
    }

    public function trashGroup(Request $request, ZakatTransaction $transaction, string $reason): array
    {
        $noTransaksi = $transaction->no_transaksi;
        $payer = $transaction->pembayar_nama;

        DB::transaction(function () use ($request, $noTransaksi, $payer, $reason) {
            ZakatTransaction::where('no_transaksi', $noTransaksi)->valid()->update([
                'deleted_by' => $request->user()->id,
                'deleted_reason' => $reason,
            ]);

            $affected = ZakatTransaction::where('no_transaksi', $noTransaksi)->valid()->delete();

            Audit::log($request, 'transaction.delete', null, [
                'no_transaksi' => $noTransaksi,
                'pembayar' => $payer,
                'items_count' => $affected,
            ]);
        });

        return [
            'id' => $transaction->id,
            'no_transaksi' => $noTransaksi,
            'payer' => $payer,
        ];
    }

    public function restoreGroup(Request $request, int $transactionId): array
    {
        $user = $request->user();
        $transaction = ZakatTransaction::withTrashed()->findOrFail($transactionId);
        $noTransaksi = $transaction->no_transaksi;

        $hasActiveCollision = ZakatTransaction::where('no_transaksi', $noTransaksi)
            ->whereNull('deleted_at')
            ->exists();

        if ($hasActiveCollision) {
            return [
                'restored' => false,
                'no_transaksi' => $noTransaksi,
            ];
        }

        DB::transaction(function () use ($noTransaksi, $transaction, $user, $request) {
            ZakatTransaction::onlyTrashed()
                ->where('no_transaksi', $noTransaksi)
                ->restore();

            ZakatTransaction::where('no_transaksi', $noTransaksi)
                ->update([
                    'restored_at' => now(config('zakat.timezone')),
                    'restored_by' => $user->id,
                    'deleted_by' => null,
                    'deleted_reason' => null,
                ]);

            Audit::log($request, 'Restored.Transaction', null, [
                'no_transaksi' => $noTransaksi,
                'deleted_by_user' => $transaction->deleted_by,
                'deleted_reason' => $transaction->deleted_reason,
                'restored_by' => $user->id,
            ]);
        });

        $restoredTransactions = ZakatTransaction::query()
            ->where('no_transaksi', $noTransaksi)
            ->orderBy('id')
            ->get();

        $this->reviewAssistantService->syncForTransactions($restoredTransactions);

        return [
            'restored' => true,
            'no_transaksi' => $noTransaksi,
        ];
    }

    public function forceDeleteGroup(Request $request, int $transactionId): string
    {
        $transaction = ZakatTransaction::withTrashed()->findOrFail($transactionId);
        $noTransaksi = $transaction->no_transaksi;

        DB::transaction(function () use ($noTransaksi, $request) {
            TransactionRiskReview::query()
                ->whereIn('zakat_transaction_id', ZakatTransaction::onlyTrashed()->where('no_transaksi', $noTransaksi)->select('id'))
                ->delete();

            $affected = ZakatTransaction::onlyTrashed()
                ->where('no_transaksi', $noTransaksi)
                ->forceDelete();

            Audit::log($request, 'Deleted.Permanently.Transaction', null, [
                'no_transaksi' => $noTransaksi,
                'items_count' => $affected,
            ]);
        });

        return $noTransaksi;
    }
}
