<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ZakatTransaction;
use App\Support\SqlDialect;

class GuestLatestController extends Controller
{
    public function index()
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        // Fetch 5 latest validated transactions within the current active year
        $effectiveTimestamp = SqlDialect::effectiveTimestamp();

        $latest = ZakatTransaction::valid()
            ->where('tahun_zakat', $activeYear)
            ->orderByRaw("{$effectiveTimestamp} DESC")
            ->limit(5)
            ->get()
            ->map(function ($tx) {
                return [
                    'id' => $tx->id,
                    'category' => $tx->category,
                    'uang' => (int) $tx->nominal_uang,
                    'beras' => (float) $tx->jumlah_beras_kg,
                    'timestamp' => $tx->waktu_terima ? $tx->waktu_terima->timestamp : $tx->created_at->timestamp,
                ];
            });

        return response()->json([
            'data' => $latest
        ]);
    }
}
