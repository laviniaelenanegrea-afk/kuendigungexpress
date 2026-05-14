<?php
/**
 * consent-log.php
 * Audit log pentru deciziile de cookie consent.
 * Nu stochează IP-ul, doar timestamp + decizie + pagină.
 * Folosit ca dovadă în caz de audit DSGVO.
 */
declare(strict_types=1);

$decision = $_POST['decision'] ?? '';
if (!in_array($decision, ['accepted', 'rejected'], true)) {
    http_response_code(400);
    exit;
}

$page = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', (string)($_POST['page'] ?? ''));
$page = substr($page, 0, 100);

$logDir = __DIR__ . '/_data';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

// Format: timestamp \t decision \t page
$line = time() . "\t" . $decision . "\t" . $page . "\n";
file_put_contents($logDir . '/consent_log.txt', $line, FILE_APPEND | LOCK_EX);

http_response_code(204);
exit;
