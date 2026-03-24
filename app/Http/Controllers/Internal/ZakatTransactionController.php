<?php

namespace App\Http\Controllers\Internal;

use App\Http\Requests\Internal\StoreZakatTransactionRequest;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatTransaction;
use App\Support\ReceiptPdf;
use App\Support\ViewOptions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ZakatTransactionController extends Controller
{
    public function create(Request $request)
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
        $years = ViewOptions::years($activeYear);

        $annualSetting = \App\Models\AnnualSetting::where('year', $activeYear)->first();
        $berasPerJiwa = $annualSetting ? $annualSetting->default_fitrah_beras_per_jiwa : 2.5;

        $fitrahUang = $annualSetting ? $annualSetting->default_fitrah_cash_per_jiwa : 50000;
        $fidyahUang = $annualSetting ? $annualSetting->default_fidyah_per_hari : 30000;
        $fidyahBeras = $annualSetting ? $annualSetting->default_fidyah_beras_per_hari : 0.75;

        return view('internal.transactions.create', [
            'years' => $years,
            'activeYear' => $activeYear,
            'categories' => ZakatTransaction::CATEGORIES,
            'methods' => ZakatTransaction::METHODS,
            'shifts' => ZakatTransaction::SHIFTS,
            'shiftLabels' => ZakatTransaction::SHIFT_LABELS,
            'berasPerJiwa' => (float) $berasPerJiwa,
            'fitrahUang' => (int) $fitrahUang,
            'fidyahUang' => (int) $fidyahUang,
            'fidyahBeras' => (float) $fidyahBeras,
        ]);
    }

    public function store(StoreZakatTransactionRequest $request, \App\Services\ZakatService $service)
    {
        $data = $request->validated();
        $service->validateNominalDefaults($data);
        
        $results = $service->storeTransaction($data, $request->user()->id);

        if (empty($results)) {
            return back()->withErrors(['no_transaksi' => 'Gagal menyimpan transaksi. Coba lagi.'])->withInput();
        }

        return redirect()->route('internal.transactions.show', ['transaction' => $results[0]->id])
            ->with('status', 'Transaksi berhasil disimpan!');
    }

    public function show(Request $request, int $transaction)
    {
        $tx = ZakatTransaction::withTrashed()->findOrFail($transaction);

        $transactions = ZakatTransaction::query()
            ->with(['muzakki' => fn($q) => $q->withTrashed()])
            ->where('no_transaksi', $tx->no_transaksi)
            ->where('pembayar_nama', $tx->pembayar_nama)
            ->orderBy('id', 'asc')
            ->get();

        // Fallback: if $tx is trashed and no active siblings exist,
        // show archived records so admin can still view historical receipts.
        if ($transactions->isEmpty()) {
            $transactions = ZakatTransaction::withTrashed()
                ->with(['muzakki' => fn($q) => $q->withTrashed()])
                ->where('no_transaksi', $tx->no_transaksi)
                ->get();
        }

        $totalUang = $transactions->where('metode', '!=', ZakatTransaction::METHOD_BERAS)->sum('nominal_uang');
        $totalTf = $transactions->where('metode', ZakatTransaction::METHOD_UANG)->where('is_transfer', true)->sum('nominal_uang');
        $totalCash = $totalUang - $totalTf;
        $totalBeras = $transactions->where('metode', ZakatTransaction::METHOD_BERAS)->sum('jumlah_beras_kg');

        $groupedArr = $transactions->groupBy(fn($t) => $t->muzakki ? $t->muzakki->name : '-');

        return view('internal.transactions.show', [
            'mainTx' => $tx,
            'groupedArr' => $groupedArr,
            'noTransaksi' => $tx->no_transaksi,
            'totalUang' => (int) $totalUang,
            'totalTf' => (int) $totalTf,
            'totalCash' => (int) $totalCash,
            'totalBeras' => (float) $totalBeras,
            'shiftLabel' => $tx->shift_label
        ]);
    }

    public function receipt(Request $request, int $transaction)
    {
        $user = $request->user();

        $tx = ZakatTransaction::withTrashed()->findOrFail($transaction);
        
        $transactions = ZakatTransaction::query()
            ->with(['muzakki' => fn($q) => $q->withTrashed()])
            ->where('no_transaksi', $tx->no_transaksi)
            ->where('pembayar_nama', $tx->pembayar_nama)
            ->where('status', ZakatTransaction::STATUS_VALID)
            ->orderBy('id', 'asc')
            ->get();

        // Fallback: if $tx is trashed and no active siblings exist,
        // show archived records so admin can still view/print historical receipts.
        if ($transactions->isEmpty()) {
            $transactions = ZakatTransaction::withTrashed()
                ->with(['muzakki' => fn($q) => $q->withTrashed()])
                ->where('no_transaksi', $tx->no_transaksi)
                ->get();
        }


        if ($tx->trashed() && !in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $template = ReceiptPdf::getActiveLetterheadTemplate();
        if (!$template) {
            return redirect()->route('internal.transactions.create')
                ->withErrors(['letterhead' => 'Template kop belum aktif. Minta super_admin mengaktifkan template kop terlebih dahulu.']);
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($template->storage_path)) {
            return redirect()->route('internal.transactions.create')
                ->withErrors(['letterhead' => 'File template kop tidak ditemukan di storage. Upload ulang atau aktifkan template lain.']);
        }

        $petugas = User::find($tx->petugas_id) ?? $user;

        $pdfBytes = ReceiptPdf::renderA4Receipt(
            $transactions,
            $petugas,
            $disk->path($template->storage_path)
        );

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="tanda-terima-' . $tx->no_transaksi . '.pdf"',
        ]);
    }

    public function edit(Request $request, int $transaction)
    {
        $tx = ZakatTransaction::findOrFail($transaction);

        // Strict Edit Regulation
        $this->authorizeEdit($request->user(), $tx);

        $all = ZakatTransaction::with(['muzakki' => fn($q) => $q->withTrashed()])
            ->where('no_transaksi', $tx->no_transaksi)
            ->orderBy('id', 'asc')
            ->get();

        $activeYear = $tx->tahun_zakat;
        $years = ViewOptions::years($activeYear);

        $annualSetting = \App\Models\AnnualSetting::where('year', $activeYear)->first();
        $berasPerJiwa = $annualSetting ? $annualSetting->default_fitrah_beras_per_jiwa : 2.5;
        $fitrahUang = $annualSetting ? $annualSetting->default_fitrah_cash_per_jiwa : 50000;
        $fidyahUang = $annualSetting ? $annualSetting->default_fidyah_per_hari : 30000;
        $fidyahBeras = $annualSetting ? $annualSetting->default_fidyah_beras_per_hari : 0.75;

        $persons = \App\Transformers\TransactionTransformer::toAlpinePersons($all);

        return view('internal.transactions.create', [
            'isEdit' => true,
            'mainTx' => $tx,
            'persons' => $persons,
            'years' => $years,
            'activeYear' => $activeYear,
            'categories' => ZakatTransaction::CATEGORIES,
            'methods' => ZakatTransaction::METHODS,
            'shifts' => ZakatTransaction::SHIFTS,
            'shiftLabels' => ZakatTransaction::SHIFT_LABELS,
            'officers' => User::orderBy('name')->get(['id', 'name', 'role']),
            'berasPerJiwa' => (float) $berasPerJiwa,
            'fitrahUang' => (int) $fitrahUang,
            'fidyahUang' => (int) $fidyahUang,
            'fidyahBeras' => (float) $fidyahBeras,
        ]);
    }

    public function update(StoreZakatTransactionRequest $request, int $transaction, \App\Services\ZakatService $service)
    {
        $data = $request->validated();

        $tx = ZakatTransaction::findOrFail($transaction);

        // Strict Edit Regulation
        $this->authorizeEdit($request->user(), $tx);

        $service->validateNominalDefaults($data);
        $results = $service->storeTransaction($data, $request->user()->id, $tx->no_transaksi);

        $targetId = count($results) > 0 ? $results[0]->id : $transaction;

        return redirect()->route('internal.transactions.show', ['transaction' => $targetId])
            ->with('status', 'Transaksi berhasil diupdate!');
    }

    private function authorizeEdit(User $user, ZakatTransaction $tx): void
    {
        if ($user->role === User::ROLE_SUPER_ADMIN || $user->role === User::ROLE_ADMIN) {
            return;
        }

        // Staff Restrictions:
        // 1. Can only edit their own transactions
        if ((int)$tx->petugas_id !== (int)$user->id) {
            abort(Response::HTTP_FORBIDDEN, 'Anda hanya dapat mengedit transaksi yang Anda layani sendiri.');
        }

        // 2. Can only edit today's transactions (within same calendar date)
        $txDate = ($tx->waktu_terima ?? $tx->created_at)->timezone('Asia/Jakarta');
        if (!$txDate->isToday()) {
            abort(Response::HTTP_FORBIDDEN, 'Batas waktu pengeditan harian telah berakhir. Silakan hubungi Admin untuk perubahan data hari sebelumnya.');
        }

        // 3. Prevent changing year (tahun_zakat) during edit by Staff via request comparison
        if (request()->has('tahun_zakat') && (int)request('tahun_zakat') !== (int)$tx->tahun_zakat) {
            abort(Response::HTTP_FORBIDDEN, 'Tahun zakat tidak dapat diubah oleh Staff setelah transaksi tersimpan.');
        }
    }
}
