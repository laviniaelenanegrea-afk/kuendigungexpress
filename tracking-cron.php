<?php
declare(strict_types=1);

/**
 * tracking-cron.php — Sendungsnachweis-Versand für Einschreiben
 *
 * Läuft periodisch (IONOS Cronjob, z. B. alle 2–4 Stunden).
 * Prüft offene Einschreiben-Bestellungen bei LetterXpress und sendet dem
 * Kunden automatisch eine E-Mail mit Tracking-Link, sobald die
 * Sendungsnummer (Deutsche Post) verfügbar ist.
 *
 * Ablauf:
 *   1. Alle Bestellungen in /_orders/*.json durchgehen
 *   2. Nur Einschreiben mit lxJobId, live-Modus, noch kein Tracking gesendet
 *   3. getLxJobStatus() bei LX abfragen
 *   4. Wenn Sendungsnummer da → Kunden-E-Mail senden + Order markieren
 *
 * Aufruf:
 *   - CLI (empfohlen):  php /pfad/zu/tracking-cron.php
 *   - URL mit Token:    https://www.kuendigungexpress.de/tracking-cron.php?token=GEHEIM
 *                       (Token muss _app_config['cron_token'] entsprechen)
 *
 * Idempotent: jede Order bekommt höchstens EINE Tracking-Mail (Flag trackingSent).
 */

/* ---------------------------------------------------------------
   0. Konfiguration & Sicherheit
   --------------------------------------------------------------- */

$IS_CLI = (PHP_SAPI === 'cli');

$appConfig = [];
if (file_exists(__DIR__ . '/_app_config.php')) {
    $appConfig = require __DIR__ . '/_app_config.php';
    if (!is_array($appConfig)) $appConfig = [];
}
$contactEmail = (string)($appConfig['contact_email'] ?? 'kontakt@kuendigungexpress.de');

// URL-Zugriff nur mit gültigem Token (CLI ist immer erlaubt)
if (!$IS_CLI) {
    $expected = trim((string)($appConfig['cron_token'] ?? ''));
    $given    = trim((string)($_GET['token'] ?? ''));
    if ($expected === '' || !hash_equals($expected, $given)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo "403 Forbidden\n";
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/lx-api.php';
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$lxConfig = file_exists(__DIR__ . '/_lx_config.php')   ? require __DIR__ . '/_lx_config.php'   : null;
$smtp     = file_exists(__DIR__ . '/_smtp_config.php') ? require __DIR__ . '/_smtp_config.php' : null;

$ordersDir = __DIR__ . '/_orders';

/* ---------------------------------------------------------------
   Hilfsfunktion: Log-Zeile
   --------------------------------------------------------------- */
$logLines = [];
$logf = function (string $msg) use (&$logLines) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    $logLines[] = $line;
    echo $line . "\n";
};

if (!is_array($lxConfig)) { $logf('ABBRUCH: _lx_config.php fehlt oder ungültig.'); exit(1); }
if (!is_array($smtp))     { $logf('ABBRUCH: _smtp_config.php fehlt oder ungültig.'); exit(1); }
if (!is_dir($ordersDir))  { $logf('ABBRUCH: _orders-Verzeichnis fehlt.'); exit(1); }

/* ---------------------------------------------------------------
   Lock: keine parallelen Läufe (exklusiver flock)
   --------------------------------------------------------------- */
// flock ist atomar und wird vom Betriebssystem auch bei Crash/Timeout
// automatisch freigegeben → keine "stale lock"-Probleme, keine Doppelläufe.
$lockFile = $ordersDir . '/_cron.lock';
$lockFp   = @fopen($lockFile, 'c');
if ($lockFp === false || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    $logf('Ein anderer Lauf ist aktiv — beende.');
    if ($lockFp !== false) { @fclose($lockFp); }
    exit(0);
}
register_shutdown_function(static function () use ($lockFp) {
    @flock($lockFp, LOCK_UN);
    @fclose($lockFp);
});

/* ---------------------------------------------------------------
   1. Bestellungen durchgehen
   --------------------------------------------------------------- */

// Sehr alte Bestellungen nicht mehr abfragen (LX hält Jobs nicht ewig vor).
// Großzügiger Default + konfigurierbar; 0 = kein Limit.
$MAX_AGE_DAYS = (int)($appConfig['tracking_max_age_days'] ?? 90);
$now = time();

// Atomares Schreiben: tmp-Datei + rename (verhindert halb geschriebene JSON).
$saveOrder = static function (string $path, array $data): void {
    $tmp = $path . '.tmp';
    if (@file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false) {
        @chmod($tmp, 0600);
        @rename($tmp, $path);
    }
};

$stats = ['checked' => 0, 'sent' => 0, 'pending' => 0, 'skipped' => 0, 'errors' => 0];

$files = glob($ordersDir . '/*.json') ?: [];
$logf('Starte Tracking-Lauf — ' . count($files) . ' Order-Dateien gefunden.');

foreach ($files as $orderFile) {
    $raw = @file_get_contents($orderFile);
    if ($raw === false) { $stats['errors']++; continue; }

    $order = json_decode($raw, true);
    if (!is_array($order)) { $stats['errors']++; continue; }

    $tier  = (string)($order['tier'] ?? '');
    $jobId = trim((string)($order['lxJobId'] ?? ''));
    $email = trim((string)($order['userEmail'] ?? ''));

    // --- Filter: nur relevante Einschreiben-Bestellungen ---
    if ($tier !== 'einschreiben')                                   { $stats['skipped']++; continue; }
    if ($jobId === '')                                             { $stats['skipped']++; continue; }
    if (($order['lxStatus'] ?? '') === 'failed')                   { $stats['skipped']++; continue; }
    if (($order['lxMode'] ?? 'test') === 'test')                   { $stats['skipped']++; continue; }
    if (!empty($order['trackingSent']))                            { $stats['skipped']++; continue; }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $stats['skipped']++; continue; }

    $createdAt = (int)($order['createdAt'] ?? 0);
    if ($createdAt > 0 && ($now - $createdAt) > $MAX_AGE_DAYS * 86400) {
        $stats['skipped']++;
        continue;
    }

    $stats['checked']++;
    $orderNumber = (string)($order['orderNumber'] ?? basename($orderFile, '.json'));

    // --- Status bei LX abfragen ---
    $status = getLxJobStatus($jobId, $lxConfig);
    if (empty($status['ok'])) {
        $stats['errors']++;
        $logf("  [{$orderNumber}] LX-Abfrage fehlgeschlagen: " . (string)($status['message'] ?? '?'));
        continue;
    }

    // Status im Order-File aktualisieren (Sichtbarkeit)
    if (!empty($status['status'])) {
        $order['lxStatus'] = (string)$status['status'];
    }

    $sendung = trim((string)($status['sendungsnummer'] ?? ''));
    if ($sendung === '') {
        // Noch kein Tracking verfügbar — nur Status speichern, später erneut versuchen
        $order['trackingCheckedAt'] = $now;
        $saveOrder($orderFile, $order);
        $stats['pending']++;
        $logf("  [{$orderNumber}] noch kein Tracking (LX-Status: " . (string)($status['status'] ?? '?') . ")");
        continue;
    }

    $trackingUrl = (string)($status['tracking_url'] ?? '');
    if ($trackingUrl === '') {
        $trackingUrl = 'https://www.deutschepost.de/sendung/simpleQueryResult.html?form.sendungsNummer='
                     . urlencode($sendung) . '&lang=de';
    }

    // --- Tracking-E-Mail an Kunden senden ---
    $form      = is_array($order['formData'] ?? null) ? $order['formData'] : [];
    $firstName = trim((string)($form['firstName'] ?? ''));
    $studio    = (string)($order['anbieter'] ?? ($form['anbieter'] ?? ''));

    $emailOk = sendTrackingMail($smtp, $email, $firstName, $studio, $orderNumber, $sendung, $trackingUrl);

    if ($emailOk) {
        $order['trackingSent']    = true;
        $order['trackingSentAt']  = $now;
        $order['sendungsnummer']  = $sendung;
        $order['trackingUrl']     = $trackingUrl;
        $saveOrder($orderFile, $order);
        $stats['sent']++;
        $logf("  [{$orderNumber}] ✓ Tracking-Mail gesendet an {$email} (Sendung: {$sendung})");
    } else {
        $stats['errors']++;
        $logf("  [{$orderNumber}] ✗ Tracking-Mail FEHLGESCHLAGEN an {$email} — wird beim nächsten Lauf erneut versucht.");
    }
}

/* ---------------------------------------------------------------
   2. Zusammenfassung
   --------------------------------------------------------------- */
$logf(sprintf(
    'Fertig. geprüft=%d, gesendet=%d, ausstehend=%d, übersprungen=%d, fehler=%d',
    $stats['checked'], $stats['sent'], $stats['pending'], $stats['skipped'], $stats['errors']
));

// Persistente Log-Datei (rotiert simpel pro Monat)
$logFile = $ordersDir . '/_tracking-cron_' . date('Ym') . '.log';
@file_put_contents($logFile, implode("\n", $logLines) . "\n", FILE_APPEND);

exit(0);


/* =========================================================
   E-MAIL-VERSAND
   ========================================================= */

/**
 * Sendet dem Kunden die Tracking-E-Mail mit Sendungsnummer + Deutsche-Post-Link.
 * Stil identisch zur Bestellbestätigung (Marke KündigungExpress).
 */
function sendTrackingMail(
    array  $smtp,
    string $toEmail,
    string $firstName,
    string $studio,
    string $orderNumber,
    string $sendungsnummer,
    string $trackingUrl
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
        $mail->addAddress($toEmail, trim($firstName));

        $mail->isHTML(true);
        $mail->Subject = 'Ihr Einschreiben ist unterwegs — Sendungsnachweis [' . $orderNumber . ']';

        $greetName = $firstName !== '' ? (' ' . htmlspecialchars($firstName)) : '';
        $studioSafe = htmlspecialchars($studio);

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b; line-height: 1.6; background-color: #ffffff; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px;'>

            <div style='margin-bottom: 24px;'>
                <h2 style='margin: 0 0 12px 0; font-size: 22px; color: #0f172a; font-weight: 800; letter-spacing: -0.02em;'>Ihr Einschreiben ist unterwegs" . $greetName . "!</h2>
                <p style='margin: 0; font-size: 15px; color: #475569;'>Ihre Kündigung" . ($studioSafe !== '' ? " an <strong>" . $studioSafe . "</strong>" : "") . " wurde als Einwurfeinschreiben bei der Deutschen Post eingeliefert. Mit dem folgenden Sendungsnachweis können Sie die Zustellung jederzeit nachverfolgen.</p>
            </div>

            <div style='background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 20px; margin-bottom: 24px;'>
                <h3 style='margin: 0 0 14px 0; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #2563eb;'>Sendungsnachweis</h3>
                <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
                    <tr>
                        <td style='padding: 6px 0; color: #64748b; width: 40%;'>Bestellnummer:</td>
                        <td style='padding: 6px 0; font-weight: 700; color: #0f172a; font-family: monospace;'>" . htmlspecialchars($orderNumber) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #64748b;'>Sendungsnummer:</td>
                        <td style='padding: 6px 0; font-weight: 700; color: #0f172a; font-family: monospace;'>" . htmlspecialchars($sendungsnummer) . "</td>
                    </tr>
                </table>
                <div style='margin-top: 16px;'>
                    <a href='" . htmlspecialchars($trackingUrl) . "' style='display: inline-block; background-color: #16a34a; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; padding: 12px 22px; border-radius: 10px;'>Sendung verfolgen →</a>
                </div>
                <p style='margin: 12px 0 0 0; font-size: 12px; color: #94a3b8;'>Falls der Button nicht funktioniert, kopieren Sie diesen Link:<br><span style='font-family: monospace; word-break: break-all;'>" . htmlspecialchars($trackingUrl) . "</span></p>
            </div>

            <div style='background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; margin-bottom: 24px; font-size: 14px; color: #166534; font-weight: 500;'>
                ℹ️ Bewahren Sie diesen Sendungsnachweis gut auf — er gilt als rechtssicherer Nachweis, dass Ihre Kündigung fristgerecht versendet wurde.
            </div>

            <div style='border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 14px; color: #475569;'>
                <p style='margin: 0 0 12px 0;'>Bei Fragen antworten Sie einfach direkt auf diese E-Mail.</p>
                <p style='margin: 0; font-weight: 700; color: #0f172a;'>Mit freundlichen Grüßen,<br><span style='color: #16a34a;'>KündigungExpress</span></p>
            </div>

            <div style='margin-top: 24px; padding-top: 12px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #94a3b8;'>
                <a href='https://kuendigungexpress.de' style='color: #94a3b8; text-decoration: none;'>kuendigungexpress.de</a>
            </div>
        </div>";

        $mail->AltBody = "Ihr Einschreiben ist unterwegs!\n\n"
            . "Bestellnummer: {$orderNumber}\n"
            . "Sendungsnummer: {$sendungsnummer}\n"
            . "Tracking: {$trackingUrl}\n\n"
            . "Bewahren Sie diesen Sendungsnachweis als Nachweis Ihrer fristgerechten Kündigung auf.\n\n"
            . "KündigungExpress — kuendigungexpress.de";

        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('[tracking-cron] Mail-Fehler an ' . $toEmail . ': ' . $e->getMessage());
        return false;
    } catch (\Throwable $e) {
        error_log('[tracking-cron] Allg. Fehler: ' . $e->getMessage());
        return false;
    }
}
