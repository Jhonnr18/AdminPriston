<?php

/**
 * Serve BMP icons without bootstrapping Laravel (critical on Windows artisan serve).
 * Usage: /icon.php?c=WA101
 */

declare(strict_types=1);

$code = strtoupper((string) ($_GET['c'] ?? ''));
if (! preg_match('/^[A-Z]{2,3}\d{2,4}$/', $code)) {
    http_response_code(404);
    exit;
}

$cacheDir = __DIR__.DIRECTORY_SEPARATOR.'icons';
$cached = $cacheDir.DIRECTORY_SEPARATOR.$code.'.bmp';
if (is_file($cached)) {
    header('Content-Type: image/bmp');
    header('Cache-Control: public, max-age=604800, immutable');
    header('Content-Length: '.(string) filesize($cached));
    readfile($cached);
    exit;
}

$root = 'D:\\valhalla\\Game\\image\\Sinimage\\Items';
$envFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (str_starts_with(ltrim($line), '#') || ! str_contains($line, 'VALHALLA_CLIENT_ITEMS_ROOT=')) {
            continue;
        }
        $value = trim(explode('=', $line, 2)[1] ?? '', " \t\"'");
        $value = str_replace('\\\\', '\\', $value);
        if ($value !== '') {
            $root = $value;
        }
        break;
    }
}

$prefix = substr($code, 0, 2);
$folders = match ($prefix) {
    'WA', 'WC', 'WH', 'WP', 'WS', 'WT', 'WD', 'WN', 'WV', 'WM' => ['Weapon'],
    'DA', 'DS', 'DB', 'DG' => ['Defense'],
    'OA', 'OR', 'OB', 'OS', 'OE' => ['Accessory'],
    'CA' => ['Defense', 'Event'],
    'BI', 'BC', 'BD' => ['Premium'],
    'PL', 'PS', 'PM' => ['Potion'],
    'QT', 'QE' => ['Quest'],
    'GP', 'FO' => ['Accessory', 'Defense'],
    'RR' => ['Accessory', 'Defense', 'Premium'],
    'AS' => ['Wing'],
    default => [],
};

$candidates = $folders !== []
    ? array_values(array_unique([...$folders, '']))
    : ['Weapon', 'Defense', 'Accessory', 'DropItem', 'Premium', 'Potion', 'Quest', 'Event', 'Wing', ''];

$filename = 'it'.$code.'.bmp';
$path = null;
foreach ($candidates as $folder) {
    $try = $folder === ''
        ? $root.DIRECTORY_SEPARATOR.$filename
        : $root.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$filename;
    if (@is_file($try)) {
        $path = $try;
        break;
    }
}

if ($path === null) {
    http_response_code(404);
    exit;
}

if (! is_dir($cacheDir)) {
    @mkdir($cacheDir, 0775, true);
}
@copy($path, $cached);
$serve = is_file($cached) ? $cached : $path;

header('Content-Type: image/bmp');
header('Cache-Control: public, max-age=604800, immutable');
header('Content-Length: '.(string) filesize($serve));
readfile($serve);
