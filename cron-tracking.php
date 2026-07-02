<?php
declare(strict_types=1);

/**
 * CRON — Tracking Einwurfeinschreiben
 *
 * Verifică periodic comenzile Einschreiben la LetterXpress și trimite
 * utilizatorului un email cu tracking-ul Deutsche Post când acesta devine disponibil.
 *
 * Rulare recomandată: la fiecare 2 ore (după ce LX procesează și trimite scrisoarea)
 *   (exemplu crontab vezi mai jos, dupa acest bloc de comentariu)
 *
 * Logică:
 *   1. Citește toate _orders/*.json
 *   2. Filtrează: tier=einschreiben + lxJobId setat + trackingEmailSent != true + max 30 zile vechi
 *   3. Apelează getLxJobStatus() pentru fiecare job
 *   4. Dacă sendungsnummer disponibil → trimite email cu tracking → marchează trackingEmailSent=true
 *   5. Dacă job în eroare după 7 zile → trimite email de alertă admin
 */

// Exemplu crontab (la fiecare 2 ore) - aici "*/" e inofensiv (comentariu de o linie):
//   0 */2 * * * php /var/www/html/cron-tracking.php >> /var/www/html/_mail_log/cron-tracking.log 2>&1

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/_mail_log/php_errors.log');
error_reporting(E_ALL);

$logPrefix = '[cron-tracking ' . date('Y-m-d H:i:s') . ']';

// ===== Protecție token — necesar pentru WebCron (IONOS face HTTP GET) =====
// Token-ul trebuie schimbat cu un șir random generat de tine (min. 32 caractere)
// URL WebCron: https://kuendigungexpress.de/cron-tracking.php?token=TOKENTAU_DE_SETAT_AICI
define('CRON_SECRET_TOKEN', 'a3908af2f3e18148557084103ee896324c7755d8427a0f7a');

$isCli      = (PHP_SAPI === 'cli');
$tokenValid = isset($_GET['token']) && hash_equals(CRON_SECRET_TOKEN, (string)$_GET['token']);

if (!$isCli && !$tokenValid) {
    http_response_code(403);
    header('Content-Type: text/plain');
    exit('Forbidden');
}

// Output plain text când e apelat via HTTP (WebCron vede răspunsul în log)
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/lx-api.php';
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$lxConfig   = require __DIR__ . '/_lx_config.php';
$smtpConfig = file_exists(__DIR__ . '/_smtp_config.php') ? require __DIR__ . '/_smtp_config.php' : null;

$ordersDir = __DIR__ . '/_orders';
if (!is_dir($ordersDir)) {
    echo $logPrefix . ' No _orders directory found. Exiting.' . PHP_EOL;
    exit(0);
}

$files = glob($ordersDir . '/*.json');
if ($files === false || count($files) === 0) {
    echo $logPrefix . ' No order files found.' . PHP_EOL;
    exit(0);
}

$processed = 0;
$sent      = 0;
$skipped   = 0;
$errors    = 0;

foreach ($files as $orderFile) {
    $raw = @file_get_contents($orderFile);
    if ($raw === false) continue;

    $order = json_decode($raw, true);
    if (!is_array($order)) continue;

    // ===== Filtre =====

    // Doar Einschreiben
    if (($order['tier'] ?? '') !== 'einschreiben') {
        $skipped++;
        continue;
    }

    // Deja trimis
    if (!empty($order['trackingEmailSent'])) {
        $skipped++;
        continue;
    }

    // Trebuie să aibă LX job ID
    $jobId = (string)($order['lxJobId'] ?? '');
    if ($jobId === '') {
        echo $logPrefix . ' SKIP no lxJobId: ' . basename($orderFile) . PHP_EOL;
        $skipped++;
        continue;
    }

    // Nu mai vechi de 30 de zile (probabil livrat deja, tracking expirat)
    $createdAt = (int)($order['createdAt'] ?? 0);
    if ($createdAt > 0 && (time() - $createdAt) > (30 * 86400)) {
        echo $logPrefix . ' SKIP too old (>30 days): ' . basename($orderFile) . PHP_EOL;
        $skipped++;
        continue;
    }

    // User trebuie să aibă email
    $userEmail = trim((string)($order['userEmail'] ?? ''));
    if ($userEmail === '' || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        echo $logPrefix . ' SKIP no valid email: ' . basename($orderFile) . PHP_EOL;
        $skipped++;
        continue;
    }

    $processed++;

    // ===== Interogare LX =====
    $lxStatus = getLxJobStatus($jobId, $lxConfig);

    echo $logPrefix . ' Job ' . $jobId . ' status=' . ($lxStatus['status'] ?? 'unknown')
        . ' sendungsnummer=' . ($lxStatus['sendungsnummer'] ?? '-')
        . ' order=' . ($order['orderNumber'] ?? basename($orderFile))
        . PHP_EOL;

    if (!$lxStatus['ok']) {
        echo $logPrefix . ' LX API error: ' . $lxStatus['message'] . PHP_EOL;
        $errors++;
        continue;
    }

    $sendungsnummer = $lxStatus['sendungsnummer'] ?? '';
    $trackingUrl    = $lxStatus['tracking_url'] ?? '';

    if ($sendungsnummer === '') {
        // Tracking nu e disponibil încă — normal dacă scrisoarea e în producție
        echo $logPrefix . ' Tracking not yet available for job ' . $jobId . PHP_EOL;
        continue;
    }

    // ===== Trimite email cu tracking =====
    if ($smtpConfig === null) {
        echo $logPrefix . ' ERROR: _smtp_config.php missing, cannot send tracking email.' . PHP_EOL;
        $errors++;
        continue;
    }

    $orderNumber = (string)($order['orderNumber'] ?? '');
    $anbieter    = (string)($order['anbieter'] ?? $order['studio'] ?? 'Ihren Anbieter');
    $firstName   = '';

    // Extragem prenumele dacă e salvat în formData
    if (!empty($order['formData']['firstName'])) {
        $firstName = trim((string)$order['formData']['firstName']);
    }

    $emailSentOk = sendTrackingEmail(
        $userEmail, $firstName, $orderNumber, $anbieter,
        $sendungsnummer, $trackingUrl, $smtpConfig
    );

    if ($emailSentOk) {
        // Actualizăm order-ul JSON
        $order['trackingEmailSent']  = true;
        $order['trackingEmailSentAt'] = time();
        $order['sendungsnummer']     = $sendungsnummer;
        $order['trackingUrl']        = $trackingUrl;
        file_put_contents($orderFile, json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo $logPrefix . ' Tracking email sent to ' . $userEmail . ' for order ' . $orderNumber . PHP_EOL;
        $sent++;
    } else {
        echo $logPrefix . ' ERROR sending tracking email for order ' . $orderNumber . PHP_EOL;
        $errors++;
    }
}

echo $logPrefix . ' Done. processed=' . $processed . ' sent=' . $sent
    . ' skipped=' . $skipped . ' errors=' . $errors . PHP_EOL;
exit(0);

// ===== Helper: trimite emailul cu tracking =====
function sendTrackingEmail(
    string $to,
    string $firstName,
    string $orderNumber,
    string $anbieter,
    string $sendungsnummer,
    string $trackingUrl,
    array  $smtp
): bool {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->Port       = (int)$smtp['port'];
        $mail->SMTPSecure = $smtp['encryption'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($smtp['username'], 'KündigungExpress');
        $mail->addAddress($to);

        $greeting = $firstName !== '' ? 'Guten Tag ' . htmlspecialchars($firstName) . ',' : 'Guten Tag,';
        $anbieterHtml = htmlspecialchars($anbieter);
        $orderHtml    = htmlspecialchars($orderNumber);
        $snrHtml      = htmlspecialchars($sendungsnummer);
        $urlHtml      = htmlspecialchars($trackingUrl);

        $mail->isHTML(true);
        $mail->Subject = 'Ihr Einwurfeinschreiben wurde zugestellt – Sendungsnachweis [' . $orderNumber . ']';

        $mail->Body = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b;
            line-height: 1.6; background-color: #ffffff; padding: 24px;
            border: 1px solid #e2e8f0; border-radius: 16px;'>

  <div style='margin-bottom: 24px;'>
    <h2 style='margin: 0 0 12px 0; font-size: 22px; color: #0f172a; font-weight: 800;
               letter-spacing: -0.02em;'>📬 Sendungsnachweis verfügbar</h2>
    <p style='margin: 0; font-size: 15px; color: #475569;'>
      {$greeting}<br><br>
      Ihr Einwurfeinschreiben an <strong>{$anbieterHtml}</strong> wurde von der
      Deutschen Post bearbeitet. Nachfolgend finden Sie Ihren offiziellen Sendungsnachweis.
    </p>
  </div>

  <div style='background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px;
              padding: 22px; margin-bottom: 24px; text-align: center;'>
    <div style='font-size: 13px; color: #166534; font-weight: 700; margin-bottom: 8px;
                text-transform: uppercase; letter-spacing: 0.05em;'>
      Sendungsnummer (Einwurfeinschreiben)
    </div>
    <div style='font-family: monospace; font-size: 22px; font-weight: 900; color: #0f172a;
                letter-spacing: 2px; margin-bottom: 16px;'>
      {$snrHtml}
    </div>
    <a href='{$urlHtml}'
       style='display: inline-block; background: #16A34A; color: #ffffff;
              padding: 13px 28px; border-radius: 10px; font-weight: 800;
              font-size: 15px; text-decoration: none; letter-spacing: -0.01em;'>
      📦 Sendung bei Deutsche Post verfolgen →
    </a>
  </div>

  <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
              padding: 18px; margin-bottom: 24px;'>
    <h3 style='margin: 0 0 12px 0; font-size: 13px; font-weight: 800;
               text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;'>Bestelldetails</h3>
    <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
      <tr>
        <td style='padding: 5px 0; color: #64748b; width: 45%;'>Bestellnummer:</td>
        <td style='padding: 5px 0; font-weight: 700; color: #0f172a;
                   font-family: monospace; font-size: 14px;'>{$orderHtml}</td>
      </tr>
      <tr>
        <td style='padding: 5px 0; color: #64748b;'>Empfänger:</td>
        <td style='padding: 5px 0; font-weight: 600; color: #0f172a;'>{$anbieterHtml}</td>
      </tr>
      <tr>
        <td style='padding: 5px 0; color: #64748b;'>Versandart:</td>
        <td style='padding: 5px 0; color: #0f172a;'>Einwurfeinschreiben (8,99 €)</td>
      </tr>
    </table>
    <div style='margin-top: 12px; padding-top: 10px; border-top: 1px solid #e2e8f0;
                font-size: 11px; color: #94a3b8; font-style: italic;'>
      (Kleinunternehmer §19 UStG · keine MwSt. ausgewiesen)
    </div>
  </div>

  <div style='background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px;
              padding: 14px; margin-bottom: 24px; font-size: 13.5px; color: #92400e;'>
    💡 <strong>Hinweis:</strong> Bei Einwurfeinschreiben wird der Briefumschlag in den
    Briefkasten des Empfängers eingeworfen und der Einwurf dokumentiert. Dies dient als
    rechtsgültiger Nachweis der Zustellung.
  </div>

  <div style='border-top: 1px solid #e2e8f0; padding-top: 18px; font-size: 14px; color: #475569;'>
    <p style='margin: 0 0 10px 0;'>
      Bei Fragen antworten Sie einfach auf diese E-Mail.
    </p>
    <p style='margin: 0; font-weight: 700; color: #0f172a;'>
      Mit freundlichen Grüßen,<br>
      <span style='color: #16a34a;'>KündigungExpress</span>
    </p>
  </div>

  <div style='margin-top: 20px; padding-top: 12px; border-top: 1px solid #f1f5f9;
              text-align: center; font-size: 12px; color: #94a3b8;'>
    <a href='https://kuendigungexpress.de' style='color: #94a3b8; text-decoration: none;'>
      kuendigungexpress.de
    </a>
  </div>
</div>";

        $mail->AltBody = $greeting . "\n\n"
            . "Ihr Einwurfeinschreiben an " . $anbieter . " wurde bearbeitet.\n\n"
            . "Sendungsnummer: " . $sendungsnummer . "\n"
            . "Tracking: " . $trackingUrl . "\n\n"
            . "Bestellnummer: " . $orderNumber . "\n\n"
            . "Mit freundlichen Grüßen,\nKündigungExpress\nhttps://kuendigungexpress.de";

        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('[cron-tracking] Email failed for ' . $to . ': ' . $e->getMessage());
        return false;
    }
}