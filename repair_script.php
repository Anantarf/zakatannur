<?php
$filePath = $argv[1];
$c = file_get_contents($filePath);

// 1. Identifikasi blok otak
$startMarker = "window.zakatApp = () => ({";
$endMarker = "};";

$posStart = strpos($c, $startMarker);
$posEnd = strpos($c, $endMarker, $posStart);

if ($posStart !== false && $posEnd !== false) {
    $brainBody = substr($c, $posStart, $posEnd + strlen($endMarker) - $posStart);
    
    // Hapus yang telanjang/salah tadi
    $c = str_replace($brainBody, "", $c);
    
    // 2. Bungkus dengan <script> dan taruh di Head
    $wrappedBrain = "<script>\n" . $brainBody . "\n</script>";
    
    $headMarker = "</head>";
    $c = str_replace($headMarker, $wrappedBrain . "\n" . $headMarker, $c);
}

file_put_contents($filePath, $c);
echo "Brain Wrapped and Re-Transferred Successfully!\n";
