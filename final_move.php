<?php
$filePath = "resources/views/public/home.blade.php";
$c = file_get_contents($filePath);

// 1. Cabut otak dari bawah
$startMarker = "<script>\nwindow.zakatApp";
$posBrain = strpos($c, $startMarker);
$endMarker = "</html>";

if ($posBrain !== false) {
    $brainSnippet = substr($c, $posBrain, strpos($c, "</script>", $posBrain) + 9 - $posBrain);
    
    // Hapus dari bawan
    $c = str_replace($brainSnippet, "", $c);
    
    // 2. Suntikkan ke Head (di bawah style)
    $headMarker = "</head>";
    $c = str_replace($headMarker, $brainSnippet . "\n" . $headMarker, $c);
}

file_put_contents($filePath, $c);
echo "Final Brain Relocation to HEAD: SUCCESS.\n";
