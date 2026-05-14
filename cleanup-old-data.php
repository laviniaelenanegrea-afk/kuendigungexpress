<?php
/**
 * cleanup-old-data.php
 * 
 * Șterge JSON-urile mai vechi de 90 zile din /_data și /_orders.
 * Conform DSGVO Art. 5 Abs. 1 lit. e (Speicherbegrenzung).
 * 
 * Configurare pe Ionos:
 *   1. Upload acest fișier în rădăcina site-ului
 *   2. Pune-l în .htaccess sau redenumește-l cu un nume necunoscut (security through obscurity)
 *   3. Configurează un Cron Job în Ionos Control Panel:
 *      - Frequency: zilnic, ora 03:00
 *      - Command: /usr/bin/php /path/to/cleanup-old-data.php
 *   ALT: rulează manual o dată/săptămână via wget cu token (vezi mai jos).
 *
 * SECURITATE: protejat cu token; rulare web doar cu URL + ?token=...
 */
declare(strict_types=1);

// === CONFIGURARE ===
$RETENTION_DAYS = 90;
$TOKEN = 'change-this-to-a-long-random-string-abc123xyz789'; // schimbă!
$DIRS = [
    __DIR__ . '/_data',
    __DIR__ . '/_orders',
];

// === SECURITY ===
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    // Web execution requires token
    $providedToken = $_GET['token'] ?? '';
    if (!hash_equals($TOKEN, $providedToken)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

// === EXECUTION ===
$cutoff = time() - ($RETENTION_DAYS * 86400);
$deleted = 0;
$kept = 0;
$errors = [];

foreach ($DIRS as $dir) {
    if (!is_dir($dir)) continue;
    foreach (glob($dir . '/*.json') as $file) {
        $data = @json_decode(file_get_contents($file), true);
        // Folosim createdAt din JSON dacă există, altfel mtime
        $ts = is_array($data) && isset($data['createdAt'])
            ? (int)$data['createdAt']
            : filemtime($file);
        if ($ts < $cutoff) {
            if (@unlink($file)) {
                $deleted++;
            } else {
                $errors[] = basename($file);
            }
        } else {
            $kept++;
        }
    }
}

// Log execution
$logLine = date('Y-m-d H:i:s') . "\tdeleted=$deleted\tkept=$kept\terrors=" . count($errors) . "\n";
@file_put_contents(__DIR__ . '/_data/cleanup_log.txt', $logLine, FILE_APPEND | LOCK_EX);

if ($isCli) {
    echo $logLine;
} else {
    header('Content-Type: text/plain');
    echo "Cleanup completed.\nDeleted: $deleted\nKept: $kept\nErrors: " . count($errors) . "\n";
}
exit;
