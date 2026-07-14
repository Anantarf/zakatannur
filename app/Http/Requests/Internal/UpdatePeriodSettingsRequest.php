<?php

namespace App\Http\Requests\Internal;

use App\Models\AppSetting;
use App\Services\Charts\ChartRangeResolver;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePeriodSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $publicRefreshFormMaxSeconds = (int) config('zakat.public_refresh.form_max_seconds', 600);

        return [
            'active_year' => [
                'required', 'integer',
                'min:' . (int) config('zakat.year_bounds.min', 2000),
                'max:' . (int) config('zakat.year_bounds.max', 2100),
            ],
            'period_label' => ['nullable', 'string', 'max:80'],
            'hijri_year' => ['nullable', 'integer', 'min:1300', 'max:1600'],
            'hijri_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'period_starts_at' => ['nullable', 'date'],
            'period_ends_at' => ['nullable', 'date'],
            'default_fitrah_cash_per_jiwa' => ['required', 'integer', 'min:0', 'max:100000000'],
            'default_fitrah_beras_per_jiwa' => ['required', 'numeric', 'min:0', 'max:100'],
            'default_fidyah_per_hari' => ['required', 'integer', 'min:0', 'max:100000000'],
            'default_fidyah_beras_per_hari' => ['required', 'numeric', 'min:0', 'max:100'],
            'nishab_gold_gram' => ['required', 'integer', 'min:1', 'max:1000'],
            'gold_price_per_gram' => ['required', 'integer', 'min:1', 'max:100000000'],
            'chart_starts_at' => ['nullable', 'date'],
            'chart_ends_at' => ['nullable', 'date'],
            'chart_fallback_buffer_days' => ['required', 'integer', 'min:0', 'max:14'],
            'public_refresh_interval_seconds' => ['required', 'integer', 'min:0', 'max:' . $publicRefreshFormMaxSeconds],
            'dashboard_chart_mode' => ['nullable', 'string'],
            'dashboard_chart_period_id' => ['nullable', 'integer', 'exists:zakat_periods,id'],
            'dashboard_chart_starts_at' => ['nullable', 'date'],
            'dashboard_chart_ends_at' => ['nullable', 'date'],
            'dashboard_chart_show_offseason_archive' => ['nullable', 'boolean'],
            'dashboard_chart_auto_switch_on_new_active_period' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $input = $v->getData();
            $currentActiveYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
            $publicRefreshMinSeconds = (int) config('zakat.public_refresh.min_seconds', 10);
            $publicRefreshMaxSeconds = (int) config('zakat.public_refresh.max_seconds', 60);

            $interval = (int) ($input['public_refresh_interval_seconds'] ?? 15);
            $activeYearInput = (int) ($input['active_year'] ?? 0);
            $dashboardChartMode = (string) ($input['dashboard_chart_mode'] ?? ChartRangeResolver::DASHBOARD_MODE_ACTIVE_PERIOD);

            if (!array_key_exists($dashboardChartMode, ChartRangeResolver::dashboardModes())) {
                $v->errors()->add('dashboard_chart_mode', 'Mode grafik dashboard tidak valid.');
            }

            if ($interval !== 0 && ($interval < $publicRefreshMinSeconds || $interval > $publicRefreshMaxSeconds)) {
                $v->errors()->add('public_refresh_interval_seconds', 'Interval refresh publik harus 0 (mati) atau ' . $publicRefreshMinSeconds . '-' . $publicRefreshMaxSeconds . ' detik.');
            }

            if ($activeYearInput !== $currentActiveYear) {
                $v->errors()->add('active_year', 'Perubahan Tahun Aktif hanya bisa lewat "Mulai Periode Baru".');
            }

            $this->assertDateRange($v, 'chart_starts_at', 'chart_ends_at', 'Selesai Grafik tidak boleh lebih awal dari Mulai Grafik.');
            $this->assertDateRange($v, 'dashboard_chart_starts_at', 'dashboard_chart_ends_at', 'Selesai Grafik Dashboard tidak boleh lebih awal dari Mulai Grafik Dashboard.');
            $this->assertDateRange($v, 'period_starts_at', 'period_ends_at', 'Selesai Periode tidak boleh lebih awal dari Mulai Periode.');

            if ($dashboardChartMode === ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD && empty($input['dashboard_chart_period_id'])) {
                $v->errors()->add('dashboard_chart_period_id', 'Pilih periode grafik saat mode manual digunakan.');
            }
        });
    }

    private function assertDateRange($validator, string $startKey, string $endKey, string $message): void
    {
        $input = $validator->getData();
        $start = $input[$startKey] ?? null;
        $end = $input[$endKey] ?? null;
        if ($start && $end && $end < $start) {
            $validator->errors()->add($endKey, $message);
        }
    }
}