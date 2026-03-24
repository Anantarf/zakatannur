<?php

namespace App\Support;

use setasign\Fpdi\Tcpdf\Fpdi;

final class RekapPdf
{
    /**
     * @param array{
     *   year:int,
     *   metode:?string,
     *   petugas_name:?string,
     *   generated_at_wib:string,
     *   items:array<int,array{
     *     category:string,
     *     jumlah_transaksi:int,
     *     total_uang:int,
     *     total_uang_display:string,
     *     total_beras_kg:float,
     *     total_beras_kg_display:string,
     *     total_display:string
     *   }>,
     *   totals:array{jumlah_transaksi:int,total_uang:int,total_uang_display:string,total_beras_kg:float,total_beras_kg_display:string,total_display:string}
     * } $payload
     * @return string Raw PDF bytes
     */
    public static function renderA4Rekap(array $payload): string
    {
        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('ZakatAnNur');
        $pdf->SetAuthor('ZakatAnNur');
        $pdf->SetTitle('Rekap Zakat ' . (string) $payload['year']);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'REKAP ZAKAT', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Ln(1);

        $labelW = 35;

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($labelW, 6, 'Tahun', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, ': ' . (string) $payload['year'], 0, 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($labelW, 6, 'Metode', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, ': ' . (string) ($payload['metode'] ?: 'SEMUA'), 0, 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($labelW, 6, 'Petugas', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, ': ' . (string) ($payload['petugas_name'] ?: 'SEMUA'), 0, 1);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($labelW, 6, 'Dibuat', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 6, ': ' . $payload['generated_at_wib'], 0, 1);

        $pdf->Ln(4);

        // Table header
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(240, 240, 240);

        $colCategory = 35;
        $colCount = 25;
        $colUang = 45;
        $colBeras = 35;
        $colTotal = 0;

        $pdf->Cell($colCategory, 8, 'Kategori', 1, 0, 'L', true);
        $pdf->Cell($colCount, 8, 'Transaksi', 1, 0, 'R', true);
        $pdf->Cell($colUang, 8, 'Total Uang', 1, 0, 'R', true);
        $pdf->Cell($colBeras, 8, 'Total Beras', 1, 0, 'R', true);
        $pdf->Cell($colTotal, 8, 'TOTAL', 1, 1, 'R', true);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetFillColor(255, 255, 255);

        foreach ($payload['items'] as $item) {
            $pdf->Cell($colCategory, 8, strtoupper((string) $item['category']), 1, 0, 'L');
            $pdf->Cell($colCount, 8, (string) $item['jumlah_transaksi'], 1, 0, 'R');
            $pdf->Cell($colUang, 8, (string) $item['total_uang_display'], 1, 0, 'R');
            $pdf->Cell($colBeras, 8, (string) $item['total_beras_kg_display'], 1, 0, 'R');
            $pdf->Cell($colTotal, 8, (string) $item['total_display'], 1, 1, 'R');
        }

        $totals = $payload['totals'];

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($colCategory, 8, 'TOTAL', 1, 0, 'L');
        $pdf->Cell($colCount, 8, (string) $totals['jumlah_transaksi'], 1, 0, 'R');
        $pdf->Cell($colUang, 8, (string) $totals['total_uang_display'], 1, 0, 'R');
        $pdf->Cell($colBeras, 8, (string) $totals['total_beras_kg_display'], 1, 0, 'R');
        $pdf->Cell($colTotal, 8, (string) $totals['total_display'], 1, 1, 'R');

        return $pdf->Output('', 'S');
    }
}
