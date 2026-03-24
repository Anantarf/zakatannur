<?php
$filePath = $argv[1];
$c = file_get_contents($filePath);

// Langkah Pamungkas: Sikat sisa )) dan }); gaya lama sampe bersih
$c = preg_replace('/\s*\}\)\);\s*\}\);\s*<\/script>/s', "\n    };\n</script>", $c);

file_put_contents($filePath, $c);
echo "Frontend KILAT 2.0 SUCCESS!\n";
