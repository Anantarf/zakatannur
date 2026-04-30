<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\ZakatTransaction;
use App\Models\AppSetting; // Keep AppSetting if it's used elsewhere in the controller, though not in the provided change.
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExportController extends Controller
{
    // Removed getHeaderStyle as PhpSpreadsheet handles styling differently

    public function exportDaily(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $request->validate(['date' => 'required|date_format:Y-m-d']);
        $date = $request->query('date');
        $start = Carbon::parse($date, 'Asia/Jakarta')->startOfDay();
        $end = Carbon::parse($date, 'Asia/Jakarta')->endOfDay();

        $transactions = $this->fetchDailyTransactions($start, $end);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $sheet = $this->setupDailySheet($spreadsheet, Carbon::parse($date)->translatedFormat('d F Y'));

        [$rowIdx, $totals] = $this->writeDailyRows($sheet, $transactions);
        $this->writeDailyTotalRow($sheet, $rowIdx, $totals);
        $this->writeDailySummarySection($sheet, $rowIdx, $totals['tf'], $totals['cash']);

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->downloadSpreadsheet($spreadsheet, 'Rekap_Zakat_' . $date . '.xlsx');
    }

    private function fetchDailyTransactions(Carbon $start, Carbon $end)
    {
        return ZakatTransaction::query()
            ->selectRaw("
                no_transaksi,
                MAX(pembayar_nama) as pembayar_nama,
                MAX(shift) as shift,
                MAX(keterangan) as keterangan,
                MAX(waktu_terima) as waktu_terima,
                MAX(created_at) as created_at,
                SUM(CASE WHEN category = ? THEN nominal_uang ELSE 0 END) as fitrah_uang,
                SUM(CASE WHEN category = ? AND metode = ? THEN jumlah_beras_kg ELSE 0 END) as fitrah_beras,
                MAX(CASE WHEN category = ? THEN is_transfer ELSE 0 END) as fitrah_has_tf,
                SUM(CASE WHEN category = ? THEN nominal_uang ELSE 0 END) as infaq_uang,
                SUM(CASE WHEN category = ? AND metode = ? THEN jumlah_beras_kg ELSE 0 END) as infaq_beras,
                MAX(CASE WHEN category = ? THEN is_transfer ELSE 0 END) as infaq_has_tf,
                SUM(CASE WHEN category = ? THEN nominal_uang ELSE 0 END) as fidyah_uang,
                MAX(CASE WHEN category = ? THEN is_transfer ELSE 0 END) as fidyah_has_tf,
                SUM(CASE WHEN category = ? THEN nominal_uang ELSE 0 END) as mal_uang,
                MAX(CASE WHEN category = ? THEN is_transfer ELSE 0 END) as mal_has_tf,
                SUM(jiwa) as total_jiwa,
                SUM(CASE WHEN is_transfer = 1 THEN nominal_uang ELSE 0 END) as tx_tf_uang,
                SUM(CASE WHEN is_transfer = 0 THEN nominal_uang ELSE 0 END) as tx_cash_uang,
                MAX(CASE WHEN metode = ? THEN is_transfer ELSE 0 END) as has_transfer
            ", [
                ZakatTransaction::CATEGORY_FITRAH,
                ZakatTransaction::CATEGORY_FITRAH, ZakatTransaction::METHOD_BERAS,
                ZakatTransaction::CATEGORY_FITRAH,
                ZakatTransaction::CATEGORY_INFAK,
                ZakatTransaction::CATEGORY_INFAK, ZakatTransaction::METHOD_BERAS,
                ZakatTransaction::CATEGORY_INFAK,
                ZakatTransaction::CATEGORY_FIDYAH,
                ZakatTransaction::CATEGORY_FIDYAH,
                ZakatTransaction::CATEGORY_MAL,
                ZakatTransaction::CATEGORY_MAL,
                ZakatTransaction::METHOD_UANG,
            ])
            ->where('status', ZakatTransaction::STATUS_VALID)
            ->whereRaw('COALESCE(waktu_terima, created_at) >= ?', [$start])
            ->whereRaw('COALESCE(waktu_terima, created_at) <= ?', [$end])
            ->groupBy('no_transaksi')
            ->orderBy('waktu_terima', 'asc')
            ->get();
    }

    private function setupDailySheet(Spreadsheet $spreadsheet, string $formattedDate)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Harian');

        $sheet->setCellValue('A1', 'REKAP ZAKAT - ' . strtoupper($formattedDate));
        $sheet->mergeCells('A1:N1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['A2' => 'No Transaksi', 'B2' => 'Nama', 'C2' => 'Shift', 'D2' => 'Fitrah',
                  'F2' => 'Infaq', 'H2' => 'Fidyah', 'I2' => 'Mal', 'J2' => 'Jiwa',
                  'K2' => 'Jumlah Zakat Fitrah', 'M2' => 'Transfer', 'N2' => 'Keterangan'] as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        foreach (['A2:A3', 'B2:B3', 'C2:C3', 'D2:E2', 'F2:G2', 'H2:H3',
                  'I2:I3', 'J2:J3', 'K2:L2', 'M2:M3', 'N2:N3'] as $range) {
            $sheet->mergeCells($range);
        }

        $sheet->setCellValue('D3', 'Uang (Rp)');
        $sheet->setCellValue('E3', 'Beras (Kg)');
        $sheet->setCellValue('F3', 'Uang (Rp)');
        $sheet->setCellValue('G3', 'Beras (Kg)');
        $sheet->setCellValue('K3', 'Uang (Rp)');
        $sheet->setCellValue('L3', 'Beras (Kg)');

        $sheet->getStyle('A2:N3')->applyFromArray($this->getCommonHeaderStyle());

        return $sheet;
    }

    private function writeDailyRows($sheet, $transactions): array
    {
        $rowIdx = 4;
        $totals = ['fitrahUang' => 0, 'fitrahBeras' => 0.0, 'infaqUang' => 0, 'infaqBeras' => 0.0,
                   'fidyah' => 0, 'mal' => 0, 'jiwa' => 0, 'tf' => 0, 'cash' => 0];

        foreach ($transactions as $tx) {
            $fitrahUang  = (int)data_get($tx, 'fitrah_uang');
            $fitrahBeras = (float)data_get($tx, 'fitrah_beras');
            $infaqUang   = (int)data_get($tx, 'infaq_uang');
            $infaqBeras  = (float)data_get($tx, 'infaq_beras');
            $fidyah      = (int)data_get($tx, 'fidyah_uang');
            $mal         = (int)data_get($tx, 'mal_uang');
            $jiwa        = (int)data_get($tx, 'total_jiwa');
            $hasTf       = (bool)data_get($tx, 'has_transfer');

            $sheet->setCellValue('A' . $rowIdx, data_get($tx, 'no_transaksi'));
            $sheet->setCellValue('B' . $rowIdx, data_get($tx, 'pembayar_nama'));
            $sheet->setCellValue('C' . $rowIdx, ZakatTransaction::getShiftLabel(data_get($tx, 'shift')));
            $sheet->setCellValue('D' . $rowIdx, $fitrahUang);
            $sheet->setCellValue('E' . $rowIdx, $fitrahBeras);
            $sheet->setCellValue('F' . $rowIdx, $infaqUang);
            $sheet->setCellValue('G' . $rowIdx, $infaqBeras);
            $sheet->setCellValue('H' . $rowIdx, $fidyah);
            $sheet->setCellValue('I' . $rowIdx, $mal);
            $sheet->setCellValue('J' . $rowIdx, $jiwa);
            $sheet->setCellValue('K' . $rowIdx, $fitrahUang);
            $sheet->setCellValue('L' . $rowIdx, $fitrahBeras);
            $sheet->setCellValue('M' . $rowIdx, $hasTf ? 'TF' : '');
            $sheet->setCellValue('N' . $rowIdx, '');

            if ($hasTf) {
                $sheet->getStyle('A'.$rowIdx.':N'.$rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C6EFCE');
                foreach (['fitrah_has_tf' => ['D', 'K'], 'infaq_has_tf' => ['F'], 'fidyah_has_tf' => ['H'], 'mal_has_tf' => ['I']] as $field => $cols) {
                    if ((bool)data_get($tx, $field)) {
                        foreach ($cols as $col) {
                            $sheet->getStyle($col.$rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('A9D08E');
                        }
                    }
                }
            }

            $totals['tf']         += (int)data_get($tx, 'tx_tf_uang');
            $totals['cash']       += (int)data_get($tx, 'tx_cash_uang');
            $totals['fitrahUang'] += $fitrahUang;
            $totals['fitrahBeras']+= $fitrahBeras;
            $totals['infaqUang']  += $infaqUang;
            $totals['infaqBeras'] += $infaqBeras;
            $totals['fidyah']     += $fidyah;
            $totals['mal']        += $mal;
            $totals['jiwa']       += $jiwa;
            $rowIdx++;
        }

        if ($rowIdx > 4) {
            $lastData = $rowIdx - 1;
            $sheet->getStyle('A4:N' . $lastData)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('C4:C' . $lastData)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D4:L' . $lastData)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle('D4:D'.$lastData)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E4:E'.$lastData)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('F4:F'.$lastData)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G4:G'.$lastData)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H4:H'.$lastData)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I4:I'.$lastData)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('K4:K'.$lastData)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('L4:L'.$lastData)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        return [$rowIdx, $totals];
    }

    private function writeDailyTotalRow($sheet, int $rowIdx, array $totals): void
    {
        $sheet->setCellValue('A' . $rowIdx, 'TOTAL');
        $sheet->mergeCells('A' . $rowIdx . ':C' . $rowIdx);
        $sheet->setCellValue('D' . $rowIdx, $totals['fitrahUang']);
        $sheet->setCellValue('E' . $rowIdx, $totals['fitrahBeras']);
        $sheet->setCellValue('F' . $rowIdx, $totals['infaqUang']);
        $sheet->setCellValue('G' . $rowIdx, $totals['infaqBeras']);
        $sheet->setCellValue('H' . $rowIdx, $totals['fidyah']);
        $sheet->setCellValue('I' . $rowIdx, $totals['mal']);
        $sheet->setCellValue('J' . $rowIdx, $totals['jiwa']);
        $sheet->setCellValue('K' . $rowIdx, $totals['fitrahUang']);
        $sheet->setCellValue('L' . $rowIdx, $totals['fitrahBeras']);

        $sheet->getStyle('A'.$rowIdx.':N'.$rowIdx)->applyFromArray([
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $sheet->getStyle('A'.$rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('D'.$rowIdx.':K'.$rowIdx)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E'.$rowIdx)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('G'.$rowIdx)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('L'.$rowIdx)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private function writeDailySummarySection($sheet, int $totalRowIdx, int $totalTf, int $totalCash): void
    {
        $base = $totalRowIdx + 2;
        $sheet->setCellValue('L' . $base,       'TOTAL SEMUA');
        $sheet->setCellValue('M' . $base,       $totalTf + $totalCash);
        $sheet->setCellValue('L' . ($base + 1), 'TF');
        $sheet->setCellValue('M' . ($base + 1), $totalTf);
        $sheet->setCellValue('L' . ($base + 2), 'CASH');
        $sheet->setCellValue('M' . ($base + 2), $totalCash);

        $sheet->getStyle('M'.$base.':M'.($base + 2))->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('L'.$base.':M'.($base + 2))->applyFromArray([
            'font'    => ['bold' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']],
        ]);
    }

    private function downloadSpreadsheet(Spreadsheet $spreadsheet, string $fileName)
    {
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'php_xlsx');
        $writer->save($tempFile);
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function exportYearly(Request $request)
    {
        ini_set('memory_limit', '1024M'); // Yearly often needs more
        set_time_limit(600);

        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
        ]);
        $year = $request->query('year');

        $summaryData = ZakatTransaction::where('status', ZakatTransaction::STATUS_VALID)
            ->where('tahun_zakat', $year)
            ->selectRaw("
                DATE(CONVERT_TZ(COALESCE(waktu_terima, created_at), '+00:00', '+07:00')) as date,
                shift,
                SUM(CASE WHEN category = ? THEN nominal_uang ELSE 0 END) as fitrah_uang,
                SUM(CASE WHEN category = ? AND metode = ? THEN jumlah_beras_kg ELSE 0 END) as fitrah_beras,
                SUM(CASE WHEN category = ? THEN jiwa ELSE 0 END) as jiwa_fitrah,
                SUM(CASE WHEN category = ? THEN nominal_uang ELSE 0 END) as mal_uang,
                SUM(CASE WHEN category = ? THEN nominal_uang ELSE 0 END) as infaq_uang,
                SUM(CASE WHEN category = ? THEN nominal_uang ELSE 0 END) as fidyah_uang,
                SUM(CASE WHEN is_transfer = 1 THEN nominal_uang ELSE 0 END) as tf_total,
                SUM(CASE WHEN is_transfer = 0 THEN nominal_uang ELSE 0 END) as cash_total
            ", [
                ZakatTransaction::CATEGORY_FITRAH,
                ZakatTransaction::CATEGORY_FITRAH, ZakatTransaction::METHOD_BERAS,
                ZakatTransaction::CATEGORY_FITRAH,
                ZakatTransaction::CATEGORY_MAL,
                ZakatTransaction::CATEGORY_INFAK,
                ZakatTransaction::CATEGORY_FIDYAH
            ])
            ->groupBy('date', 'shift')
            ->orderBy('date', 'asc')
            ->orderBy('shift', 'asc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Tahunan');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $headers = ['TANGGAL', 'SHIFT', 'FITRAH (RP)', 'FITRAH (KG)', 'JIWA (FITRAH)', 'MAL (RP)', 'INFAQ (RP)', 'FIDYAH (RP)', 'TF (RP)', 'CASH (RP)', 'GRAND TOTAL (RP)'];
        $sheet->fromArray($headers, null, 'A1');
        
        $sheet->getStyle('A1:K1')->applyFromArray($this->getCommonHeaderStyle());

        $rowIdx = 2;
        foreach ($summaryData as $row) {
            $fitrahUang = (int)data_get($row, 'fitrah_uang');
            $malUang = (int)data_get($row, 'mal_uang');
            $infaqUang = (int)data_get($row, 'infaq_uang');
            $fidyahUang = (int)data_get($row, 'fidyah_uang');
            $tfTotal = (int)data_get($row, 'tf_total');
            $cashTotal = (int)data_get($row, 'cash_total');
            
            $gt = $fitrahUang + $malUang + $infaqUang + $fidyahUang;
            $sheet->setCellValue('A' . $rowIdx, Carbon::parse(data_get($row, 'date'))->format('d M Y'));
            $sheet->setCellValue('B' . $rowIdx, ZakatTransaction::getShiftLabel(data_get($row, 'shift')));
            $sheet->setCellValue('C' . $rowIdx, $fitrahUang);
            $sheet->setCellValue('D' . $rowIdx, (float)data_get($row, 'fitrah_beras'));
            $sheet->setCellValue('E' . $rowIdx, (int)data_get($row, 'jiwa_fitrah'));
            $sheet->setCellValue('F' . $rowIdx, $malUang);
            $sheet->setCellValue('G' . $rowIdx, $infaqUang);
            $sheet->setCellValue('H' . $rowIdx, $fidyahUang);
            $sheet->setCellValue('I' . $rowIdx, $tfTotal);
            $sheet->setCellValue('J' . $rowIdx, $cashTotal);
            $sheet->setCellValue('K' . $rowIdx, $gt);
            $rowIdx++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->downloadSpreadsheet($spreadsheet, 'Rekap_Tahunan_' . $year . '.xlsx');
    }

    private function getCommonHeaderStyle(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '4472C4']],
                'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '4472C4']],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
        ];
    }
}
