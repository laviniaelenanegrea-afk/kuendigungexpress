<?php
/**
 * cleanup_pdfs.php — WebCron endpoint
 * Called daily by Ionos WebCron.
 * Protected by secret token to prevent public access.
 *
 * Ionos WebCron setup:
 * HTTP GET: https://www.kuendigungexpress.de/cleanup_pdfs.php?token=DEIN_TOKEN
 * Intervall: täglich, nachts (0-6 Uhr)
 *
 * Cleanup categories:
 *   /pdf/Kuendigung-*.pdf   → 24h   (free download flow — user already saved local)
 *   /pdf/versand_*.pdf      → 90d   (paid orders — covers Stripe chargeback window of 60d + buffer)
 *   /pdf/sig_*.png          → 24h   (orphaned signature tmp files from crashes mid-script)
 *   /_data/*.json           → 48h   (abandoned Stripe sessions — token expired)
 *   /_orders/_lock_*        → 5min  (stale atomic locks from crashed processes)
 */

// Replace with random 64-hex token. Generate: openssl rand -hex 32
$SECRET = '8f1b3c9d2aed4a163b75021f7e6a58ce2e08c966019e1363e05bff32047bf4d4';

if (!hash_equals($SECRET, (string)($_GET['token'] ?? ''))) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$now    = time();
$report = []; // categorie → [scanned, deleted, errors]

// ---------- Helper: delete files matching glob if older than $maxAge seconds ----------
function cleanupFiles(string $pattern, int $maxAge, int $now, string $label, array &$report): void {
    $files = glob($pattern) ?: [];
    $scanned = $deleted = $errors = 0;

    foreach ($files as $file) {
        if (!is_file($file)) continue;
        $scanned++;
        $mtime = @filemtime($file);
        if ($mtime === false) continue;
        if (($now - $mtime) > $maxAge) {
            if (@unlink($file)) {
                $deleted++;
            } else {
                $errors++;
                error_log('[cleanup_pdfs] Failed unlink: ' . $file);
            }
        }
    }
    $report[$label] = compact('scanned', 'deleted', 'errors');
}

// ---------- 1. /pdf/ — Kuendigung-*.pdf (free flow), 24h ----------
$pdfDir = __DIR__ . '/pdf';
if (is_dir($pdfDir)) {
    cleanupFiles($pdfDir . '/Kuendigung-*.pdf', 24 * 3600, $now, 'kuendigung', $report);

    // ---------- 2. versand_*.pdf (paid Versand orders), 90 days ----------
    cleanupFiles($pdfDir . '/versand_*.pdf', 90 * 86400, $now, 'versand', $report);

    // ---------- 3. sig_*.png (orphaned signature tmp), 24h ----------
    cleanupFiles($pdfDir . '/sig_*.png', 24 * 3600, $now, 'sig_orphans', $report);
} else {
    $report['pdfdir'] = ['scanned' => 0, 'deleted' => 0, 'errors' => 1];
    error_log('[cleanup_pdfs] /pdf directory missing');
}

// ---------- 4. /_data/ — abandoned Stripe sessions, 48h ----------
$dataDir = __DIR__ . '/_data';
if (is_dir($dataDir)) {
    cleanupFiles($dataDir . '/*.json', 48 * 3600, $now, 'data_sessions', $report);
}

// ---------- 4b. /_rate_limit/ — IP rate counters expired, 24h ----------
// Window e 15 min — orice fișier mai vechi de 24h e clear stale
$rateLimitDir = __DIR__ . '/_rate_limit';
if (is_dir($rateLimitDir)) {
    cleanupFiles($rateLimitDir . '/*.json', 24 * 3600, $now, 'rate_limits', $report);
}

// ---------- 5. /_orders/_lock_* — stale atomic locks, 5min ----------
$ordersDir = __DIR__ . '/_orders';
$lockScanned = $lockDeleted = $lockErrors = 0;
if (is_dir($ordersDir)) {
    $locks = glob($ordersDir . '/_lock_*') ?: [];
    foreach ($locks as $lockPath) {
        if (!is_dir($lockPath)) continue;
        $lockScanned++;
        $mtime = @filemtime($lockPath);
        if ($mtime === false) continue;
        if (($now - $mtime) > 300) { // 5 minutes
            if (@rmdir($lockPath)) {
                $lockDeleted++;
            } else {
                $lockErrors++;
                error_log('[cleanup_pdfs] Failed rmdir stale lock: ' . $lockPath);
            }
        }
    }
}
$report['stale_locks'] = ['scanned' => $lockScanned, 'deleted' => $lockDeleted, 'errors' => $lockErrors];

// ---------- Final report ----------
echo date('Y-m-d H:i:s') . " — cleanup_pdfs done\n";
foreach ($report as $label => $stats) {
    echo sprintf("  %-15s scanned=%d deleted=%d errors=%d\n",
        $label, $stats['scanned'], $stats['deleted'], $stats['errors']);
}
