<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/_mail_log/php_errors.log');

$token = (string)($_GET['t'] ?? '');
$key   = (string)($_GET['k'] ?? '');

function ab_page(string $title, string $msg, string $color): void {
    echo "<!doctype html><html lang='de'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><meta name='robots' content='noindex,nofollow'><title>" . htmlspecialchars($title) . "</title>"
       . "<style>body{font-family:Arial,sans-serif;background:#F7F9FC;color:#0F172A;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}"
       . ".c{background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:32px;max-width:440px;text-align:center}"
       . "h1{font-size:20px;margin:0 0 10px}p{color:#475569;font-size:14px;line-height:1.6;margin:0}</style></head>"
       . "<body><div class='c'><h1 style='color:" . $color . "'>" . htmlspecialchars($title) . "</h1><p>" . $msg . "</p></div></body></html>";
}

if (!preg_match('/^[a-f0-9]{32}$/', $token) || $key === '') {
    http_response_code(400);
    ab_page('Ungültiger Link', 'Der Link ist unvollständig oder fehlerhaft.', '#B91C1C');
    exit;
}

$orderFile = __DIR__ . '/_orders/' . $token . '.json';
if (!is_file($orderFile)) {
    http_response_code(404);
    ab_page('Auftrag nicht gefunden', 'Zu diesem Link existiert kein Auftrag (evtl. bereits gelöscht).', '#B91C1C');
    exit;
}

$order = json_decode((string)@file_get_contents($orderFile), true);
if (!is_array($order)) {
    http_response_code(500);
    ab_page('Fehler', 'Der Auftrag konnte nicht gelesen werden.', '#B91C1C');
    exit;
}

if (!hash_equals((string)($order['erledigtKey'] ?? ''), $key)) {
    http_response_code(403);
    ab_page('Zugriff verweigert', 'Der Sicherheitsschlüssel stimmt nicht.', '#B91C1C');
    exit;
}

if ((string)($order['status'] ?? '') === 'erledigt') {
    ab_page('Bereits erledigt', 'Dieser Auftrag wurde bereits als erledigt markiert. Die Sicherheitscodes sind gelöscht.', '#15803D');
    exit;
}

// Sensible Einmal-Daten löschen (DSGVO), Auftrag zu Doku-Zwecken behalten
foreach (['fin', 'zb_code', 'code_back', 'code_front'] as $f) {
    if (array_key_exists($f, $order)) { $order[$f] = ''; }
}
$order['status']        = 'erledigt';
$order['processedAt']   = time();
$order['codesPurgedAt'] = time();

if (file_put_contents($orderFile, json_encode($order, JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    ab_page('Fehler beim Speichern', 'Bitte erneut versuchen.', '#B91C1C');
    exit;
}

ab_page('Als erledigt markiert', 'Der Auftrag <b>' . htmlspecialchars((string)($order['orderNumber'] ?? '')) . '</b> ist als erledigt markiert. Die Sicherheitscodes wurden gelöscht.', '#15803D');
