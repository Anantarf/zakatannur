<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Muzakki;
use Illuminate\Http\Request;

class MuzakkiController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = Muzakki::query()
            ->search($q)
            ->orderBy('name');

        $muzakki = $query->paginate(20)->appends($request->query());

        return view('internal.muzakki.index', [
            'muzakki' => $muzakki,
            'q' => $q,
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

    public function update(Request $request, Muzakki $muzakki)
    {
        $data = $this->validateData($request);

        if (!empty($data['phone'])) {
            $data['phone'] = \App\Support\Format::phone($data['phone']);
        }

        $muzakki->update($data);

        return redirect()->route('internal.muzakki.index')->with('status', 'Muzakki diperbarui.');
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

    public function restore(Request $request, $muzakkiId)
    {
        $muzakki = Muzakki::onlyTrashed()->findOrFail($muzakkiId);
        $muzakki->restore();

        return back()->with('status', 'Muzakki berhasil dipulihkan.');
    }

    public function forceDelete(Request $request, $muzakkiId)
    {
        $muzakki = Muzakki::onlyTrashed()->findOrFail($muzakkiId);
        
        // Guard: prevent hard-deletion if any transaction (including trashed) still references this muzakki
        $hasTransactions = \App\Models\ZakatTransaction::withTrashed()->where('muzakki_id', $muzakki->id)->exists();
        if ($hasTransactions) {
            return back()->with('error', 'Muzakki tidak bisa dihapus permanen karena masih memiliki riwayat transaksi.');
        }

        $muzakki->forceDelete();

        return back()->with('status', 'Muzakki dihapus secara permanen.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'name.required' => 'Nama wajib diisi.',
        ]);
    }
}
