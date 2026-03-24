<?php

require 'vendor/autoload.php';

use setasign\Fpdi\Tcpdf\Fpdi;

class KopGenerator extends Fpdi {
}

$pdf = new KopGenerator();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$refPath = 'C:/Users/Ananta Raihan/Downloads/kop_surat/kop-surat-1447h-exact.pdf';

if (!file_exists($refPath)) {
    die("File referensi tidak ditemukan.");
}

$pdf->setSourceFile($refPath);
$tplIdx = $pdf->importPage(1);

$pdf->AddPage();
$pdf->useTemplate($tplIdx, 0, 0, 210, 297, true);

$logoNew = 'public/images/logo_zakatannur.png';

if (file_exists($logoNew)) {
    // Tutup logo lama dengan kotak putih yang lebih presisi
    // Berdasarkan gambar bapak, logo berada agak ke bawah dibanding baris pertama teks
    $pdf->SetFillColor(255, 255, 255);
    // x=12, y=8, width=35, height=35 (Disesuaikan agar menutup sempurna)
    $pdf->Rect(10, 5, 38, 38, 'F'); 
    
    // Pasang logo baru dengan posisi Y yang selaras (inline) dengan teks
    // Baris pertama teks PANITIA... biasanya di y=12-14. 
    // Kita taruh logo di y=10 agar terlihat center vertikal terhadap 3 baris teks.
    $pdf->Image($logoNew, 14, 10, 28, '', 'PNG');
}

$outputFile = 'storage/app/templates/letterhead_zakat_annur_exact_v4.pdf';
$pdf->Output(realpath('.') . '/' . $outputFile, 'F');

echo "PDF Berhasil diperbaiki ke v4: " . $outputFile . "\n";
