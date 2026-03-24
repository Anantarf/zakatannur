<?php
$targetPath = "resources/views/public/home.blade.php";
$c = file_get_contents($targetPath);

// 1. Cari di mana script lama dimulai di bagian tubuh bawah
$pos = strpos($c, "document.addEventListener('alpine:init'");
if ($pos !== false) {
    // Cari <script> pembukanya sebelum itu
    $startScript = strrpos(substr($c, 0, $pos), "<script>");
    // Cari </script> penutupnya setelah itu
    $endScript = strpos($c, "</script>", $pos) + 9;
    
    // Hapus total script lama tersebut
    $scriptLama = substr($c, $startScript, $endScript - $startScript);
    $c = str_replace($scriptLama, "", $c);
}

file_put_contents($targetPath, $c);
echo "Final Cleanup: OLD SCRIPTS PURGED.\n";
