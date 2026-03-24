<?php
$filePath = $argv[1];
$c = file_get_contents($filePath);

// 1. Ambil Otak Sehat (Yang dimulai dengan window.zakatApp)
$goodBrainMarker = "window.zakatApp = () => ({";
$posGoodStart = strpos($c, $goodBrainMarker);

if ($posGoodStart !== false) {
    // Ambil seluruh isi otak sehat sampai penutup script terakhir
    $goodBrainWithTail = substr($c, $posGoodStart);
    
    // 2. Bersihkan seluruh area Head dari baris 81 sampai sebelum otak sehat dimulai
    $headStart = "</style>";
    $posHeadEnd = strpos($c, $headStart) + strlen($headStart);
    
    $prefix = substr($c, 0, $posHeadEnd);
    $suffix = strstr($goodBrainWithTail, "</body>"); // Ambil sisa body sampai bawah
    
    // 3. Susun Ulang: Prefix (Head) + <script> + Otak Sehat + </script> + Body
    $newContent = $prefix . "\n<script>\n" . $goodBrainWithTail;
    
    file_put_contents($filePath, $newContent);
    echo "ULTIMATE RESTORE SUCCESS! BRAIN RE-SYNCHRONIZED.\n";
}
