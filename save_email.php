<?php
declare(strict_types=1); ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/_mail_log/php_errors.log'); header('Content-Type: application/json'); if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); echo json_encode(['ok' => false]); exit;
} $email = trim((string)($_POST['email'] ?? '')); if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'invalid email']); exit;
} $listFile = __DIR__ . '/_data/email_list.csv';
$listDir = dirname($listFile);
if (!is_dir($listDir)) mkdir($listDir, 0755, true); // Avoid duplicates
$existing = file_exists($listFile) ? file_get_contents($listFile) : '';
if (strpos($existing, $email) !== false) { echo json_encode(['ok' => true, 'duplicate' => true]); exit;
} $line = implode(',', [ date('Y-m-d H:i:s'), $email, 'kuendigungexpress'
]) . "\n"; file_put_contents($listFile, $line, FILE_APPEND | LOCK_EX); echo json_encode(['ok' => true]);
exit;