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
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Storage;

class ZakatTransactionController extends Controller
{
    public function create(Request $request)
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        return view('internal.transactions.create', array_merge(
            $this->getAnnualDefaults($activeYear),
            [
                'years'      => ViewOptions::years($activeYear),
                'activeYear' => $activeYear,
                'categories' => ZakatTransaction::CATEGORIES,
                'methods'    => ZakatTransaction::METHODS,
                'shifts'     => ZakatTransaction::SHIFTS,
                'shiftLabels'=> ZakatTransaction::SHIFT_LABELS,
            ]
        ));
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

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
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

        // Strict Edit Regulation (Moved to Policy)
        $this->authorize('update', $tx);

        $all = ZakatTransaction::with(['muzakki' => fn($q) => $q->withTrashed()])
            ->where('no_transaksi', $tx->no_transaksi)
            ->orderBy('id', 'asc')
            ->get();

        $persons = \App\Transformers\TransactionTransformer::toAlpinePersons($all);

        return view('internal.transactions.create', array_merge(
            $this->getAnnualDefaults($tx->tahun_zakat),
            [
                'isEdit'     => true,
                'mainTx'     => $tx,
                'persons'    => $persons,
                'years'      => ViewOptions::years($tx->tahun_zakat),
                'activeYear' => $tx->tahun_zakat,
                'categories' => ZakatTransaction::CATEGORIES,
                'methods'    => ZakatTransaction::METHODS,
                'shifts'     => ZakatTransaction::SHIFTS,
                'shiftLabels'=> ZakatTransaction::SHIFT_LABELS,
                'officers'   => User::orderBy('name')->get(['id', 'name', 'role']),
            ]
        ));
    }

    public function update(StoreZakatTransactionRequest $request, int $transaction, \App\Services\ZakatService $service)
    {
        $data = $request->validated();

        $tx = ZakatTransaction::findOrFail($transaction);

        // Strict Edit Regulation (Moved to Policy)
        $this->authorize('update', $tx);

        $user = $request->user();
        if ($user->role === User::ROLE_STAFF && isset($data['tahun_zakat']) && (int)$data['tahun_zakat'] !== (int)$tx->tahun_zakat) {
            return back()->withErrors(['tahun_zakat' => 'Tahun zakat tidak dapat diubah oleh Staff setelah transaksi tersimpan.']);
        }

        $service->validateNominalDefaults($data);
        $results = $service->storeTransaction($data, $request->user()->id, $tx->no_transaksi);

        $targetId = count($results) > 0 ? $results[0]->id : $transaction;

        return redirect()->route('internal.transactions.show', ['transaction' => $targetId])
            ->with('status', 'Transaksi berhasil diupdate!');
    }


    /**
     * Returns AnnualSetting-based defaults for the given year, with safe fallbacks.
     * Single source of truth — used by both create() and edit().
     */
    private function getAnnualDefaults(int $year): array
    {
        $s = \App\Models\AnnualSetting::where('year', $year)->first();

        return [
            'berasPerJiwa' => (float) ($s->default_fitrah_beras_per_jiwa ?? 2.5),
            'fitrahUang'   => (int)   ($s->default_fitrah_cash_per_jiwa  ?? 50000),
            'fidyahUang'   => (int)   ($s->default_fidyah_per_hari       ?? 30000),
            'fidyahBeras'  => (float) ($s->default_fidyah_beras_per_hari ?? 0.75),
        ];
    }
}
