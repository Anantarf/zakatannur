<?php

namespace App\Support;

use App\Models\Muzakki;
use App\Models\Template;
use App\Models\User;
use App\Models\ZakatTransaction;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Support\Str;
use setasign\Fpdi\Tcpdf\Fpdi;

final class ReceiptPdf
{
    public static function getActiveLetterheadTemplate(): ?Template
    {
        return Template::query()
            ->where('template_type', Template::TYPE_LETTERHEAD)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }

    /**
     * @return string Raw PDF bytes
     */
    public static function renderA4Receipt(
        iterable $transactions,
        User $petugas,
        string $letterheadPdfPath
    ): string {
        $firstTx = collect($transactions)->first();
        if (!$firstTx) {
            throw new \RuntimeException('Data transaksi tidak ditemukan.');
        }

        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('ZakatAnNur');
        $pdf->SetAuthor('ZakatAnNur');
        $pdf->SetTitle('Tanda Terima ' . $firstTx->no_transaksi);
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 20);

        $pdf->AddPage();

        // Background letterhead
        $pageCount = $pdf->setSourceFile($letterheadPdfPath);
        if ($pageCount < 1) {
            throw new \RuntimeException('Template kop PDF tidak valid.');
        }
        $tpl = $pdf->importPage(1);
        $pdf->useTemplate($tpl, 0, 0, 210, 297, true);

        $waktuTerima = $firstTx->waktu_terima
            ? Carbon::parse($firstTx->waktu_terima)->timezone('Asia/Jakarta')->locale('id')
            : now('Asia/Jakarta')->locale('id');



        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetY(55);
        $title = 'TANDA TERIMA PEMBAYARAN ZAKAT ' . $firstTx->tahun_zakat;
        if ($firstTx->tahun_zakat == 2026) $title .= ' (1447 H)';
        
        $pdf->Cell(0, 8, $title, 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Ln(4);

        $leftLabelW = 35; 
        $colonW = 5;
        $valueW = 0;

        $pdf->Cell($leftLabelW, 7, 'No. Transaksi', 0, 0);
        $pdf->Cell($colonW, 7, ':', 0, 0, 'C');
        $pdf->Cell($valueW, 7, $firstTx->no_transaksi, 0, 1);

        $pdf->Cell($leftLabelW, 7, 'Petugas', 0, 0);
        $pdf->Cell($colonW, 7, ':', 0, 0, 'C');
        $pdf->Cell($valueW, 7, $petugas->name, 0, 1);

        $pdf->Cell($leftLabelW, 7, 'Tanggal', 0, 0);
        $pdf->Cell($colonW, 7, ':', 0, 0, 'C');
        $pdf->Cell($valueW, 7, $waktuTerima->translatedFormat('d F Y'), 0, 1);

        $pdf->Cell($leftLabelW, 7, 'Waktu', 0, 0);
        $pdf->Cell($colonW, 7, ':', 0, 0, 'C');
        $pdf->Cell($valueW, 7, $waktuTerima->format('H:i') . ' WIB', 0, 1);

        $shiftLabel = ZakatTransaction::getShiftLabel($firstTx->shift);
        $pdf->Cell($leftLabelW, 7, 'Shift', 0, 0);
        $pdf->Cell($colonW, 7, ':', 0, 0, 'C');
        $pdf->Cell($valueW, 7, $shiftLabel, 0, 1);

        $pdf->Cell($leftLabelW, 7, 'Pembayar', 0, 0);
        $pdf->Cell($colonW, 7, ':', 0, 0, 'C');
        $pdf->Cell($valueW, 7, ($firstTx->pembayar_nama ?? '-'), 0, 1);

        if (!empty($firstTx->pembayar_alamat) && $firstTx->pembayar_alamat !== '-') {
            $pdf->Cell($leftLabelW, 7, 'Alamat', 0, 0);
            $pdf->Cell($colonW, 7, ':', 0, 0, 'C');
            $pdf->MultiCell($valueW, 7, $firstTx->pembayar_alamat, 0, 'L');
        }

        if (!empty($firstTx->pembayar_phone)) {
            $pdf->Cell($leftLabelW, 7, 'No HP/WA', 0, 0);
            $pdf->Cell($colonW, 7, ':', 0, 0, 'C');
            $pdf->Cell($valueW, 7, $firstTx->pembayar_phone, 0, 1);
        }

        $pdf->Ln(4);
        
        // Header list (Tanpa border tabel, lebih clean)
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetDrawColor(220, 220, 220); // Soft grey line
        $pdf->Cell(0, 0, '', 'T', 1); 
        $pdf->Ln(2);
        
        $pdf->Cell(8, 7, 'No', 0, 0, 'C');
        $pdf->Cell(50, 7, 'Nama Muzakki', 0, 0, 'L');
        $pdf->Cell(30, 7, 'Kategori', 0, 0, 'L');
        $pdf->Cell(22, 7, 'Bentuk', 0, 0, 'C');
        $pdf->Cell(25, 7, 'Keterangan', 0, 0, 'C');
        $pdf->Cell(0, 7, 'Nominal', 0, 1, 'R');
        
        $pdf->Ln(1);
        $pdf->Cell(0, 0, '', 'T', 1);
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 9);
        $lastPersonKey = null;
        $personCounter = 0;
        
        $totalUang = 0;
        $totalBeras = 0.0;
        
        foreach ($transactions as $i => $tx) {
            $cat = $tx->category_name;
            $met = $tx->metode_name;
            $name = $tx->muzakki ? $tx->muzakki->name : ($tx->pembayar_nama ?: '-');
            
            // Grouping logic: Unique key for the person in this receipt
            $currentPersonKey = $tx->muzakki_id ?: $name;
            $isNewPerson = ($currentPersonKey !== $lastPersonKey);
            
            if ($isNewPerson) {
                $personCounter++;
                $lastPersonKey = $currentPersonKey;
            }

            $ket = '-';
            if ($tx->category === 'fitrah' && $tx->jiwa) {
                $ket = $tx->jiwa . ' Jiwa';
            } elseif ($tx->category === 'fidyah' && $tx->hari) {
                $ket = $tx->hari . ' Hari';
            }

            $val = '-';
            if ($tx->metode === ZakatTransaction::METHOD_BERAS) {
                $kg = $tx->jumlah_beras_kg !== null ? (float) $tx->jumlah_beras_kg : 0.0;
                $val = Format::kg($kg);
                $totalBeras += $kg;
            } else {
                $nom = $tx->nominal_uang !== null ? (int) $tx->nominal_uang : 0;
                $val = Format::rupiah($nom);
                $totalUang += $nom;
            }

            $colWidthName = 50;
            $lineH = 6.5; 
            
            $startY = $pdf->GetY();
            $startX = $pdf->GetX();

            // Nomor - Only show for new person
            $pdf->Cell(8, $lineH, $isNewPerson ? ($personCounter . '.') : '', 0, 0, 'C');

            // Nama Muzakki with Wrap - Only show for new person
            if ($isNewPerson) {
                $nameX = $pdf->GetX();
                $nameHeight = $pdf->getStringHeight($colWidthName, $name);
                $rowH = max($lineH, $nameHeight);

                if (strlen($name) > 25) {
                    $pdf->MultiCell($colWidthName, 4.2, $name, 0, 'L', false, 0);
                    $pdf->SetXY($nameX + $colWidthName, $startY);
                } else {
                    $pdf->Cell($colWidthName, $lineH, $name, 0, 0, 'L');
                }
            } else {
                $pdf->Cell($colWidthName, $lineH, '', 0, 0, 'L');
                $rowH = $lineH;
            }

            // Other columns
            $pdf->Cell(30, $lineH, $cat, 0, 0, 'L');
            $pdf->Cell(22, $lineH, $met, 0, 0, 'C');
            $pdf->Cell(25, $lineH, $ket, 0, 0, 'C');
            $pdf->Cell(0, $lineH, $val, 0, 1, 'R');

            $pdf->SetY($startY + $rowH + 0.8); 
        }

        // Consolidate these to ensure they stay on the same page
        
        $summaryH = 15;
        $keteranganH = !empty($firstTx->keterangan) ? 15 : 0;
        $sigH = 35;
        $neededSpace = $summaryH + $keteranganH + $sigH;

        // Pagination check (including the summary in the "keep together" logic)
        if (($pdf->GetY() + $neededSpace) > ($pdf->GetPageHeight() - 25)) {
            $pdf->AddPage();
            $tpl = $pdf->importPage(1);
            $pdf->useTemplate($tpl, 0, 0, 210, 297, true);
            $pdf->SetY(55);
        }

        // 1. Summary Section
        $pdf->Ln(2);
        $pdf->Cell(0, 0, '', 'T', 1);
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'B', 10);
        
        if ($totalUang > 0) {
            $pdf->Cell(135, 6, 'TOTAL KESELURUHAN (RUPIAH)', 0, 0, 'R');
            $pdf->Cell(0, 6, ': ' . Format::rupiah($totalUang), 0, 1, 'R');
        }
        
        if ($totalBeras > 0) {
            $pdf->Cell(135, 6, 'TOTAL KESELURUHAN (BERAS)', 0, 0, 'R');
            $pdf->Cell(0, 6, ': ' . Format::kg($totalBeras), 0, 1, 'R');
        }

        // 2. Keterangan Section
        if (!empty($firstTx->keterangan)) {
            $pdf->Ln(3);
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($leftLabelW, 6, 'Keterangan', 0, 0);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->MultiCell(0, 6, ': ' . $firstTx->keterangan, 0, 'L');
        }

        // 3. Signature Section
        $pdf->Ln(5); // Optimized spacing
        $pdf->SetFont('helvetica', '', 10);
        $sigW = 60;
        $marginX = 20;
        $pageW = $pdf->GetPageWidth();
        
        $pdf->SetX($pageW - $marginX - $sigW);
        $pdf->Cell($sigW, 6, 'Petugas,', 0, 1, 'C');
        
        $pdf->Ln(12); // Space for TTD - Compact but sufficient
        
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetX($pageW - $marginX - $sigW);
        $pdf->Cell($sigW, 6, $petugas->name, 0, 1, 'C');

        return $pdf->Output('', 'S');

    }
}
