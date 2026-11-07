<?php
// Quick repair & diagnostic tool
header('Content-Type: text/plain; charset=utf-8');
echo "=== ClassProgress Diagnostic & Auto-Fix Tool ===\n\n";

// 1. Patch Str.php
$paths = [
    __DIR__ . '/vendor/laravel/framework/src/Illuminate/Support/Str.php',
    __DIR__ . '/../vendor/laravel/framework/src/Illuminate/Support/Str.php',
];

$patched = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (strpos($content, '" \n\r\t\v\0"') !== false) {
            file_put_contents($path, str_replace('" \n\r\t\v\0"', '" \n\r\t\v"', $content));
            echo "[OK] Str.php berhasil di-patch pada: $path\n";
            $patched = true;
        } else {
            echo "[INFO] Str.php sudah dalam keadaan ter-patch di: $path\n";
            $patched = true;
        }
    }
}

if (!$patched) {
    echo "[WARN] File Str.php tidak ditemukan di path vendor.\n";
}

// 2. Clear view caches
$viewsPath = __DIR__ . '/storage/framework/views';
if (!is_dir($viewsPath)) {
    $viewsPath = __DIR__ . '/../storage/framework/views';
}

if (is_dir($viewsPath)) {
    $files = glob($viewsPath . '/*');
    $count = 0;
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== '.gitignore') {
            unlink($file);
            $count++;
        }
    }
    echo "[OK] Berhasil membersihkan $count file cache Blade di: $viewsPath\n";
}

echo "\nPHP Version: " . PHP_VERSION . "\n";
echo "Status: SUKSES! Silakan refresh kembali halaman https://classprogress.web.id\n";
