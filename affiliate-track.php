<?php
/**
 * affiliate-track.php
 * Beacon endpoint pentru tracking click-uri affiliate.
 * Folosire: navigator.sendBeacon('/affiliate-track.php', formData)
 * 
 * formData keys:
 *   partner: 'check24' | 'tariffuxx' | 'telekom_awin' | 'plankpad_awin' | 'tarifcheck24' | 'crash'
 *   page:    pagina sursă (optional)
 */
declare(strict_types=1);

// Validare partner
$allowed = ['check24', 'tariffuxx', 'telekom_awin', 'plankpad_awin', 'tarifcheck24', 'crash'];
$partner = $_POST['partner'] ?? '';

if (!in_array($partner, $allowed, true)) {
    http_response_code(400);
    exit('invalid partner');
}

// Page (optional, sanitized)
$page = '';
if (!empty($_POST['page'])) {
    $page = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', (string)$_POST['page']);
    $page = substr($page, 0, 100);
}

// Log entry: timestamp \t partner \t page
$line = time() . "\t" . $partner . "\t" . $page . "\n";
$logFile = __DIR__ . '/_data/affiliate_clicks.log';

// Asigură directorul există
if (!is_dir(__DIR__ . '/_data')) {
    @mkdir(__DIR__ . '/_data', 0755, true);
}

// Append cu lock pentru safe concurrent writes
file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

http_response_code(204);
exit;
