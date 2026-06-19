<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Muzakki;
use App\Models\ZakatTransaction;
use App\Services\Muzakki\MuzakkiCrmService;
use App\Services\Muzakki\MuzakkiMergeService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MuzakkiController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = Muzakki::query()
            ->withCount(['transactions as valid_transactions_count' => fn ($query) => $query->valid()])
            ->withSum(['transactions as valid_total_uang' => fn ($query) => $query->valid()], 'nominal_uang')
            ->withSum(['transactions as valid_total_beras' => fn ($query) => $query->valid()], 'jumlah_beras_kg')
            ->withMax(['transactions as last_transaction_at' => fn ($query) => $query->valid()], 'waktu_terima')
            ->search($q)
            ->orderBy('name');

        $muzakki = $query->paginate(20)->appends($request->query());

        return view('internal.muzakki.index', [
            'muzakki' => $muzakki,
            'q' => $q,
            'totalMuzakki' => Muzakki::query()->count(),
            'activeMuzakki' => Muzakki::query()
                ->whereHas('transactions', fn ($query) => $query->valid())
                ->count(),
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        return \App\Support\Format::phone($phone);
    }

    public function autocomplete(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = Muzakki::search($q)
            ->limit(10)
            ->get(['id', 'name', 'address', 'phone']);

        return response()->json($results);
    }

    // Muzakki is created only via transactions, removing store/create to be lean

    public function edit(Muzakki $muzakki)
    {
        return view('internal.muzakki.form', [
            'mode' => 'edit',
            'muzakki' => $muzakki,
        ]);
    }

    public function show(Muzakki $muzakki, MuzakkiCrmService $crmService)
    {
        $muzakki->loadCount(['transactions as valid_transactions_count' => fn ($query) => $query->valid()]);

        return view('internal.muzakki.show', array_merge([
            'muzakki' => $muzakki,
        ], $crmService->profile($muzakki)));
    }

    public function update(Request $request, Muzakki $muzakki)
    {
        $data = $this->validateData($request);

        if (!empty($data['phone'])) {
            $data['phone'] = \App\Support\Format::phone($data['phone']);
        }

        $muzakki->update($data);

        return redirect()->route('internal.muzakki.index')->with('status', 'Muzakki diperbarui.');
    }

    public function merge(Request $request, Muzakki $muzakki, MuzakkiMergeService $mergeService)
    {
        $data = $request->validate([
            'duplicate_id' => ['required', 'integer', 'exists:muzakki,id'],
            'confirm_name' => ['required', 'string'],
        ]);

        if ((int) $data['duplicate_id'] === (int) $muzakki->id) {
            throw ValidationException::withMessages([
                'duplicate_id' => 'Muzakki duplikat tidak boleh sama dengan target utama.',
            ]);
        }

        if ((string) $data['confirm_name'] !== (string) $muzakki->name) {
            throw ValidationException::withMessages([
                'confirm_name' => 'Ketik nama target utama dengan tepat untuk mengonfirmasi merge.',
            ]);
        }

        $duplicate = Muzakki::query()->findOrFail((int) $data['duplicate_id']);
        $result = $mergeService->mergeInto($muzakki, $duplicate);

        Audit::log($request, 'muzakki.merged', $muzakki, $result);

        return redirect()
            ->route('internal.muzakki.show', ['muzakki' => $muzakki->id])
            ->with('status', 'Data duplikat digabung. ' . $result['moved_transactions'] . ' transaksi dipindahkan ke profil utama.');
    }

    public function destroy(Request $request, Muzakki $muzakki)
    {
        $muzakki->delete();

        return redirect()->route('internal.muzakki.index')->with('status', 'Muzakki dipindahkan ke Tempat Sampah.');
    }

    public function trash(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = Muzakki::onlyTrashed()
            ->search($q)
            ->orderBy('deleted_at', 'desc');

        $muzakki = $query->paginate(20)->appends($request->query());

        return view('internal.muzakki.trash', [
            'muzakki' => $muzakki,
            'q' => $q,
        ]);
    }

    public function restore(Muzakki $muzakki)
    {
        $muzakki->restore();

        return back()->with('status', 'Muzakki berhasil dipulihkan.');
    }

    public function forceDelete(Muzakki $muzakki)
    {
        $hasTransactions = ZakatTransaction::withTrashed()->where('muzakki_id', $muzakki->id)->exists();
        if ($hasTransactions) {
            return back()->with('error', 'Muzakki tidak bisa dihapus permanen karena masih memiliki riwayat transaksi.');
        }

        $muzakki->forceDelete();

        return back()->with('status', 'Muzakki dihapus secara permanen.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:' . (int) config('zakat.validation.muzakki_name_edit_max', 150)],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:' . (int) config('zakat.validation.muzakki_phone_max', 30)],
        ], [
            'name.required' => 'Nama wajib diisi.',
        ]);
    }
}
