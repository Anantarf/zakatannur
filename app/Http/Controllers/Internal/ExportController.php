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

        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);
        $date = $request->query('date');
        $formattedDate = Carbon::parse($date)->translatedFormat('d F Y');

        $start = Carbon::parse($date, 'Asia/Jakarta')->startOfDay();
        $end = Carbon::parse($date, 'Asia/Jakarta')->endOfDay();

        $transactions = ZakatTransaction::query()
            ->select(
                'no_transaksi',
                DB::raw('MAX(pembayar_nama) as pembayar_nama'),
                DB::raw('MAX(shift) as shift'),
                DB::raw('MAX(keterangan) as keterangan'),
                DB::raw('MAX(waktu_terima) as waktu_terima'),
                DB::raw('MAX(created_at) as created_at'),
                DB::raw('SUM(CASE WHEN category = "fitrah" THEN nominal_uang ELSE 0 END) as fitrah_uang'),
                DB::raw('SUM(CASE WHEN category = "fitrah" AND metode = "beras" THEN jumlah_beras_kg ELSE 0 END) as fitrah_beras'),
                DB::raw('MAX(CASE WHEN category = "fitrah" THEN is_transfer ELSE 0 END) as fitrah_has_tf'),
                DB::raw('SUM(CASE WHEN category = "infaq" THEN nominal_uang ELSE 0 END) as infaq_uang'),
                DB::raw('SUM(CASE WHEN category = "infaq" AND metode = "beras" THEN jumlah_beras_kg ELSE 0 END) as infaq_beras'),
                DB::raw('MAX(CASE WHEN category = "infaq" THEN is_transfer ELSE 0 END) as infaq_has_tf'),
                DB::raw('SUM(CASE WHEN category = "fidyah" THEN nominal_uang ELSE 0 END) as fidyah_uang'),
                DB::raw('MAX(CASE WHEN category = "fidyah" THEN is_transfer ELSE 0 END) as fidyah_has_tf'),
                DB::raw('SUM(CASE WHEN category = "mal" THEN nominal_uang ELSE 0 END) as mal_uang'),
                DB::raw('MAX(CASE WHEN category = "mal" THEN is_transfer ELSE 0 END) as mal_has_tf'),
                DB::raw('SUM(jiwa) as total_jiwa'),
                DB::raw('SUM(CASE WHEN is_transfer = 1 THEN nominal_uang ELSE 0 END) as tx_tf_uang'),
                DB::raw('SUM(CASE WHEN is_transfer = 0 THEN nominal_uang ELSE 0 END) as tx_cash_uang'),
                DB::raw('MAX(CASE WHEN metode = "uang" THEN is_transfer ELSE 0 END) as has_transfer')
            )
            ->where('status', ZakatTransaction::STATUS_VALID)
            ->whereRaw('COALESCE(waktu_terima, created_at) >= ?', [$start])
            ->whereRaw('COALESCE(waktu_terima, created_at) <= ?', [$end])
            ->groupBy('no_transaksi')
            ->orderBy('waktu_terima', 'asc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Harian');

        // Set Default Font Arial
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        // Title Row
        $sheet->setCellValue('A1', "REKAP ZAKAT - " . strtoupper($formattedDate));
        $sheet->mergeCells('A1:N1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Row 2 (Group Headers)
        $headers = [
            'A2' => 'No Transaksi',
            'B2' => 'Nama',
            'C2' => 'Shift',
            'D2' => 'Fitrah',
            'F2' => 'Infaq',
            'H2' => 'Fidyah',
            'I2' => 'Mal',
            'J2' => 'Jiwa',
            'K2' => 'Jumlah Zakat Fitrah',
            'M2' => 'Transfer',
            'N2' => 'Keterangan'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Merging for Header Row 2
        $sheet->mergeCells('A2:A3');
        $sheet->mergeCells('B2:B3');
        $sheet->mergeCells('C2:C3');
        $sheet->mergeCells('D2:E2'); // Fitrah group
        $sheet->mergeCells('F2:G2'); // Infaq group
        $sheet->mergeCells('H2:H3');
        $sheet->mergeCells('I2:I3');
        $sheet->mergeCells('J2:J3');
        $sheet->mergeCells('K2:L2'); // Jumlah Zakat Fitrah group
        $sheet->mergeCells('M2:M3');
        $sheet->mergeCells('N2:N3');

        // Header Row 3 (Sub Headers)
        $sheet->setCellValue('D3', 'Uang (Rp)');
        $sheet->setCellValue('E3', 'Beras (Kg)');
        $sheet->setCellValue('F3', 'Uang (Rp)');
        $sheet->setCellValue('G3', 'Beras (Kg)');
        $sheet->setCellValue('K3', 'Uang (Rp)');
        $sheet->setCellValue('L3', 'Beras (Kg)');

        // Header Styling
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'DCE6F1'], // Light Blue Gray
            ],
        ];
        $sheet->getStyle('A2:N3')->applyFromArray($headerStyle);

        // Data Rows
        $rowIdx = 4;
        $totalFitrahUang = 0; $totalFitrahBeras = 0;
        $totalInfaqUang = 0; $totalInfaqBeras = 0;
        $totalFidyah = 0; $totalMal = 0; $totalJiwa = 0;
        $totalTf = 0; $totalCash = 0;

        if ($transactions->isNotEmpty()) {
            foreach ($transactions as $tx) {
                $fitrahUang = (int)data_get($tx, 'fitrah_uang');
                $fitrahBeras = (float)data_get($tx, 'fitrah_beras');
                $infaqUang = (int)data_get($tx, 'infaq_uang');
                $infaqBeras = (float)data_get($tx, 'infaq_beras');
                $fidyah = (int)data_get($tx, 'fidyah_uang');
                $mal = (int)data_get($tx, 'mal_uang');
                $jiwa = (int)data_get($tx, 'total_jiwa');

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
                $sheet->setCellValue('K' . $rowIdx, $fitrahUang); // As per image "Jumlah Zakat Fitrah (Uang)"
                $sheet->setCellValue('L' . $rowIdx, $fitrahBeras);
                
                $hasTf = (bool)data_get($tx, 'has_transfer');
                $txTfUang = (int)data_get($tx, 'tx_tf_uang');
                $txCashUang = (int)data_get($tx, 'tx_cash_uang');

                $sheet->setCellValue('M' . $rowIdx, $hasTf ? 'TF' : '');
                $sheet->setCellValue('N' . $rowIdx, ''); // Keterangan di kosongkan sesuai permintaan

                if ($hasTf) {
                    $sheet->getStyle('A'.$rowIdx.':N'.$rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('C6EFCE'); // Green tint like in image

                    if ((bool)data_get($tx, 'fitrah_has_tf')) {
                        $sheet->getStyle('D'.$rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('A9D08E');
                        $sheet->getStyle('K'.$rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('A9D08E');
                    }
                    if ((bool)data_get($tx, 'infaq_has_tf')) {
                        $sheet->getStyle('F'.$rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('A9D08E');
                    }
                    if ((bool)data_get($tx, 'fidyah_has_tf')) {
                        $sheet->getStyle('H'.$rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('A9D08E');
                    }
                    if ((bool)data_get($tx, 'mal_has_tf')) {
                        $sheet->getStyle('I'.$rowIdx)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('A9D08E');
                    }
                }

                $totalTf += $txTfUang;
                $totalCash += $txCashUang;

                $totalFitrahUang += $fitrahUang;
                $totalFitrahBeras += $fitrahBeras;
                $totalInfaqUang += $infaqUang;
                $totalInfaqBeras += $infaqBeras;
                $totalFidyah += $fidyah;
                $totalMal += $mal;
                $totalJiwa += $jiwa;

                $rowIdx++;
            }

            // Apply borders and number formatting
            $sheet->getStyle('A4:N' . ($rowIdx - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            
            // Alignment
            $sheet->getStyle('C4:C' . ($rowIdx - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D4:L' . ($rowIdx - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Number Formats
            $rupiahFormat = '#,##0';
            $berasFormat = '#,##0.00';
            
            $sheet->getStyle('D4:D' . ($rowIdx - 1))->getNumberFormat()->setFormatCode($rupiahFormat);
            $sheet->getStyle('E4:E' . ($rowIdx - 1))->getNumberFormat()->setFormatCode($berasFormat);
            $sheet->getStyle('F4:F' . ($rowIdx - 1))->getNumberFormat()->setFormatCode($rupiahFormat);
            $sheet->getStyle('G4:G' . ($rowIdx - 1))->getNumberFormat()->setFormatCode($berasFormat);
            $sheet->getStyle('H4:H' . ($rowIdx - 1))->getNumberFormat()->setFormatCode($rupiahFormat);
            $sheet->getStyle('I4:I' . ($rowIdx - 1))->getNumberFormat()->setFormatCode($rupiahFormat);
            $sheet->getStyle('K4:K' . ($rowIdx - 1))->getNumberFormat()->setFormatCode($rupiahFormat);
            $sheet->getStyle('L4:L' . ($rowIdx - 1))->getNumberFormat()->setFormatCode($berasFormat);
        } else {
            // If empty, just set rowIdx after headers
            $rowIdx = 4;
            $rupiahFormat = '#,##0';
            $berasFormat = '#,##0.00';
        }

        // Total Row
        $sheet->setCellValue('A' . $rowIdx, 'TOTAL');
        $sheet->mergeCells('A' . $rowIdx . ':C' . $rowIdx);
        $sheet->setCellValue('D' . $rowIdx, $totalFitrahUang);
        $sheet->setCellValue('E' . $rowIdx, $totalFitrahBeras);
        $sheet->setCellValue('F' . $rowIdx, $totalInfaqUang);
        $sheet->setCellValue('G' . $rowIdx, $totalInfaqBeras);
        $sheet->setCellValue('H' . $rowIdx, $totalFidyah);
        $sheet->setCellValue('I' . $rowIdx, $totalMal);
        $sheet->setCellValue('J' . $rowIdx, $totalJiwa);
        $sheet->setCellValue('K' . $rowIdx, $totalFitrahUang);
        $sheet->setCellValue('L' . $rowIdx, $totalFitrahBeras);

        $totalStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E1F2'], // Soft blue
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
            ]
        ];
        $sheet->getStyle('A' . $rowIdx . ':N' . $rowIdx)->applyFromArray($totalStyle);
        $sheet->getStyle('A' . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Apply formats to Total Row
        $sheet->getStyle('D' . $rowIdx . ':K' . $rowIdx)->getNumberFormat()->setFormatCode($rupiahFormat);
        $sheet->getStyle('E' . $rowIdx)->getNumberFormat()->setFormatCode($berasFormat);
        $sheet->getStyle('G' . $rowIdx)->getNumberFormat()->setFormatCode($berasFormat);
        $sheet->getStyle('L' . $rowIdx)->getNumberFormat()->setFormatCode($berasFormat);

        // Summary Block at Bottom Right
        $sumStart = $rowIdx + 2;
        $sheet->setCellValue('L' . $sumStart, 'TOTAL SEMUA');
        $sheet->setCellValue('M' . $sumStart, $totalTf + $totalCash);
        $sheet->setCellValue('L' . ($sumStart + 1), 'TF');
        $sheet->setCellValue('M' . ($sumStart + 1), $totalTf);
        $sheet->setCellValue('L' . ($sumStart + 2), 'CASH');
        $sheet->setCellValue('M' . ($sumStart + 2), $totalCash);
        
        $sheet->getStyle('M' . $sumStart . ':M' . ($sumStart + 2))->getNumberFormat()->setFormatCode($rupiahFormat);
        
        $sumStyle = [
            'font' => ['bold' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF2CC'], // Light Yellow
            ],
        ];
        $sheet->getStyle('L' . $sumStart . ':M' . ($sumStart + 2))->applyFromArray($sumStyle);

        // Auto size columns
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'Rekap_Zakat_' . $date . '.xlsx';
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
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DCE6F1');

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

        $fileName = 'Rekap_Tahunan_' . $year . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'php_xlsx');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
