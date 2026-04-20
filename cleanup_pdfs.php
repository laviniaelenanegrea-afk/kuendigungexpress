<?php
/**
 * cleanup_pdfs.php — WebCron endpoint
 * Called daily by Ionos WebCron.
 * Protected by secret token to prevent public access.
 *
 * Ionos WebCron setup:
 * HTTP GET: https://www.kuendigungexpress.de/cleanup_pdfs.php?token=ke2026cleanup
 * Intervall: täglich, nachts (0-6 Uhr)
 */

$SECRET = 'ke2026cleanup';

if (($_GET['token'] ?? '') !== $SECRET) {
    http_response_code(403);
    exit('Forbidden');
}

$pdfDir = __DIR__ . '/pdf';
$maxAge = 24 * 60 * 60; // 24 hours
$now    = time();
$deleted = 0;
$errors  = 0;

if (!is_dir($pdfDir)) {
    echo "PDF directory not found.\n";
    exit(1);
}

$files = glob($pdfDir . '/kuendigung_*.pdf') ?: [];

foreach ($files as $file) {
    if (!is_file($file)) continue;
    if (($now - filemtime($file)) > $maxAge) {
        unlink($file) ? $deleted++ : $errors++;
    }
}

$remaining = count(glob($pdfDir . '/kuendigung_*.pdf') ?: []);
echo date('Y-m-d H:i:s') . " — Deleted: $deleted | Errors: $errors | Remaining: $remaining\n";
