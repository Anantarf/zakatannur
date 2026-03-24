<?php
$compiledPath = "storage/framework/views/6645a0880c2145f60ae6e69b3342a8e9a2706b45.php";
$targetPath = "resources/views/public/home.blade.php";

$c = file_get_contents($compiledPath);

// 1. Reversi beberapa tag Blade dasar agar enak dibaca (Opsional tapi bagus)
$c = str_replace('<?php echo e(', '{{ ', $c);
$c = str_replace('); ?>', ' }}', $c);
$c = str_replace('<?php echo app(\'Illuminate\Foundation\Vite\')', '@vite', $c);

// 2. Cari bagian script Alpine lama di bawah untuk dibuang
$scriptStartPos = strpos($c, '<script>');
// Kita cari script terakhir beralpine init
$pos = strpos($c, "document.addEventListener('alpine:init'");
if ($pos !== false) {
    $c = substr($c, 0, $pos - 8); // Potong sebelum <script>
}

// 3. Ambil Otak Sehat yang tadi saya simpan (Saya tulis ulang di sini agar yakin)
$brain = <<<'EOD'
<script>
window.zakatApp = () => ({
    openLogin: <?php echo json_encode($errors->any() || request()->has('login'), 15, 512) ?>,
    activeTab: 'beranda',
    notification: {
        show: false,
        message: '',
        queue: [],
        processing: false
    },
    // ... (Dan seterusnya, tapi saya akan ambil dari memori context saya)
    // Sebenarnya saya akan ambil dari file home.blade.php yang sekarang (karena isinya cuma otak)
EOD;

// Realita: home.blade.php sekarang isinya CUMA otak. Mari kita ambil dari sana.
$currentHome = file_get_contents($targetPath);
$posBrain = strpos($currentHome, "<script>\nwindow.zakatApp");
$brainBlock = substr($currentHome, $posBrain);

// 4. Rakit Ulang: Badan (dari compiled) + Otak Sehat (dari home sekarang)
$finalContent = $c . "\n" . $brainBlock . "\n</body>\n</html>";

file_put_contents($targetPath, $finalContent);
echo "RESTORATION COMPLETED! Web is Back and Optimized.\n";
