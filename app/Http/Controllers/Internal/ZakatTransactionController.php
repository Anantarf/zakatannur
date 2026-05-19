<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\StoreZakatTransactionRequest;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\ZakatTransaction;
use App\Services\Transactions\AnnualZakatDefaultsResolver;
use App\Services\Transactions\TransactionReceiptLifecycleService;
use App\Services\ZakatService;
use App\Support\ReceiptPdf;
use App\Transformers\TransactionTransformer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ZakatTransactionController extends Controller
{
    private AnnualZakatDefaultsResolver $defaultsResolver;

    public function __construct(AnnualZakatDefaultsResolver $defaultsResolver)
    {
        $this->defaultsResolver = $defaultsResolver;
    }

    public function create(Request $request): View
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        return view('internal.transactions.create', $this->transactionFormViewData($activeYear));
    }

    public function store(StoreZakatTransactionRequest $request, ZakatService $service): RedirectResponse
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

    public function show(Request $request, int $transaction): View
    {
        $tx = ZakatTransaction::withTrashed()->findOrFail($transaction);

        if ($tx->trashed() && !in_array($request->user()->role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $transactions = ZakatTransaction::query()
            ->with(['muzakki' => fn($q) => $q->withTrashed()])
            ->where('no_transaksi', $tx->no_transaksi)
            ->orderBy('id', 'asc')
            ->get();

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

        return view('internal.transactions.show', [
            'mainTx' => $tx,
            'groupedArr' => $transactions->groupBy(fn($t) => $t->muzakki ? $t->muzakki->name : '-'),
            'noTransaksi' => $tx->no_transaksi,
            'totalUang' => (int) $totalUang,
            'totalTf' => (int) $totalTf,
            'totalCash' => (int) $totalCash,
            'totalBeras' => (float) $totalBeras,
            'shiftLabel' => $tx->shift_label,
        ]);
    }

    public function receipt(Request $request, int $transaction, TransactionReceiptLifecycleService $receiptLifecycleService): Response
    {
        $user = $request->user();
        $tx = ZakatTransaction::withTrashed()->findOrFail($transaction);

        $transactions = ZakatTransaction::query()
            ->with(['muzakki' => fn($q) => $q->withTrashed()])
            ->where('no_transaksi', $tx->no_transaksi)
            ->valid()
            ->orderBy('id', 'asc')
            ->get();

        if ($transactions->isEmpty()) {
            $transactions = ZakatTransaction::withTrashed()
                ->with(['muzakki' => fn($q) => $q->withTrashed()])
                ->where('no_transaksi', $tx->no_transaksi)
                ->valid()
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
        $pdfBytes = ReceiptPdf::renderA4Receipt($transactions, $petugas, $disk->path($template->storage_path));
        $receiptLifecycleService->markGroupAsPrinted($request, $tx);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="tanda-terima-' . $tx->no_transaksi . '.pdf"',
        ]);
    }

    public function edit(Request $request, int $transaction): View
    {
        $tx = ZakatTransaction::findOrFail($transaction);
        $this->authorize('update', $tx);

        $all = ZakatTransaction::with(['muzakki' => fn($q) => $q->withTrashed()])
            ->where('no_transaksi', $tx->no_transaksi)
            ->orderBy('id', 'asc')
            ->get();

        return view('internal.transactions.create', $this->transactionFormViewData(
            $tx->tahun_zakat,
            [
                'isEdit' => true,
                'mainTx' => $tx,
                'persons' => TransactionTransformer::toAlpinePersons($all),
                'officers' => User::orderBy('name')->get(['id', 'name', 'role']),
            ]
        ));
    }

    public function update(StoreZakatTransactionRequest $request, int $transaction, ZakatService $service): RedirectResponse
    {
        $data = $request->validated();
        $tx = ZakatTransaction::findOrFail($transaction);

        $this->authorize('update', $tx);

        $user = $request->user();
        if ($user->role === User::ROLE_STAFF && isset($data['tahun_zakat']) && (int) $data['tahun_zakat'] !== (int) $tx->tahun_zakat) {
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
     * Single source of truth, used by both create() and edit().
     */
    private function getAnnualDefaults(int $year): array
    {
        $defaults = $this->defaultsResolver->resolve($year);

        return [
            'berasPerJiwa' => $defaults->fitrahBerasPerJiwa,
            'fitrahUang' => $defaults->fitrahCashPerJiwa,
            'fidyahUang' => $defaults->fidyahPerHari,
            'fidyahBeras' => $defaults->fidyahBerasPerHari,
        ];
    }

    private function transactionFormViewData(int $year, array $overrides = []): array
    {
        return array_merge(
            $this->getAnnualDefaults($year),
            [
                'activeYear' => $year,
                'shifts' => ZakatTransaction::SHIFTS,
                'shiftLabels' => ZakatTransaction::SHIFT_LABELS,
            ],
            $overrides
        );
    }
}
