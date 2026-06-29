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

    public function show(Request $request, ZakatTransaction $transaction): View
    {
        $tx = $transaction;
        $groupNumber = $tx->no_transaksi;

        if ($tx->trashed() && !in_array($request->user()->role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $groupItems = $this->resolveGroupItems($groupNumber);

        $totalUang = $groupItems->where('metode', '!=', ZakatTransaction::METHOD_BERAS)->sum('nominal_uang');
        $totalTf = $groupItems->where('metode', ZakatTransaction::METHOD_UANG)->where('is_transfer', true)->sum('nominal_uang');
        $totalCash = $totalUang - $totalTf;
        $totalBeras = $groupItems->where('metode', ZakatTransaction::METHOD_BERAS)->sum('jumlah_beras_kg');

        return view('internal.transactions.show', [
            'mainTx' => $tx,
            'groupedArr' => $groupItems->groupBy(fn($t) => $t->muzakki ? $t->muzakki->name : '-'),
            'groupNumber' => $groupNumber,
            'noTransaksi' => $groupNumber,
            'totalUang' => (int) $totalUang,
            'totalTf' => (int) $totalTf,
            'totalCash' => (int) $totalCash,
            'totalBeras' => (float) $totalBeras,
            'shiftLabel' => $tx->shift_label,
        ]);
    }

    public function receipt(Request $request, ZakatTransaction $transaction, TransactionReceiptLifecycleService $receiptLifecycleService): Response
    {
        $user = $request->user();
        $tx = $transaction;
        $groupNumber = $tx->no_transaksi;

        $groupItems = $this->resolveGroupItemsForReceipt($groupNumber);

        if ($tx->trashed() && !in_array($user->role, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $this->authorize('printReceipt', $tx);

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
        $pdfBytes = ReceiptPdf::renderA4Receipt($groupItems, $petugas, $disk->path($template->storage_path));
        $receiptLifecycleService->markGroupAsPrinted($request, $tx);

        return response($pdfBytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="tanda-terima-' . $groupNumber . '.pdf"',
        ]);
    }

    public function edit(Request $request, ZakatTransaction $transaction): View
    {
        $tx = $transaction;
        $this->authorize('update', $tx);

        $groupItems = ZakatTransaction::transactionGroupItems($tx->no_transaksi, false, [
            'muzakki' => fn($q) => $q->withTrashed(),
            'zakatPeriod',
        ]);

        return view('internal.transactions.create', $this->transactionFormViewData(
            $tx->tahun_zakat,
            [
                'isEdit' => true,
                'mainTx' => $tx,
                'persons' => TransactionTransformer::toAlpinePersons($groupItems),
                'officers' => User::orderBy('name')->get(['id', 'name', 'role']),
            ]
        ));
    }

    public function update(StoreZakatTransactionRequest $request, ZakatTransaction $transaction, ZakatService $service): RedirectResponse
    {
        $data = $request->validated();
        $tx = $transaction;

        $this->authorize('update', $tx);

        $user = $request->user();
        if ($user->role === User::ROLE_STAFF && isset($data['tahun_zakat']) && (int) $data['tahun_zakat'] !== (int) $tx->tahun_zakat) {
            return back()->withErrors(['tahun_zakat' => 'Tahun zakat tidak dapat diubah oleh Staff setelah transaksi tersimpan.']);
        }

        $service->validateNominalDefaults($data);
        $results = $service->storeTransaction($data, $request->user()->id, $tx->no_transaksi);
        $targetId = count($results) > 0 ? $results[0]->id : $transaction->id;

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

    private function resolveGroupItems(string $groupNumber): \Illuminate\Support\Collection
    {
        $relations = ['muzakki' => fn($q) => $q->withTrashed(), 'zakatPeriod'];
        $items = ZakatTransaction::transactionGroupItems($groupNumber, false, $relations);

        return $items->isEmpty()
            ? ZakatTransaction::transactionGroupItems($groupNumber, true, $relations)
            : $items;
    }

    private function resolveGroupItemsForReceipt(string $groupNumber): \Illuminate\Support\Collection
    {
        $query = fn(bool $withTrashed) => ($withTrashed ? ZakatTransaction::withTrashed() : ZakatTransaction::query())
            ->with(['muzakki' => fn($q) => $q->withTrashed(), 'zakatPeriod'])
            ->forTransactionGroup($groupNumber)
            ->valid()
            ->orderBy('id', 'asc')
            ->get();

        $items = $query(false);

        return $items->isEmpty() ? $query(true) : $items;
    }
}
