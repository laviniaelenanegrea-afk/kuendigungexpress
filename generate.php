<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/_mail_log/php_errors.log');
error_reporting(E_ALL);

/* =========================================================
   DEPENDENCIES
   ========================================================= */
require_once __DIR__ . '/dompdf/autoload.inc.php';
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/* =========================================================
   CONFIG
   ========================================================= */
$config        = require __DIR__ . '/_app_config.php';
$CONTACT_EMAIL = (string)($config['contact_email'] ?? 'kontakt@kuendigungexpress.de');

/* =========================================================
   HELPERS
   ========================================================= */
function clean_text(string $v): string {
    $v = trim($v);
    $v = preg_replace('/\s+/u', ' ', $v) ?? $v;
    return $v;
}
function clean_multiline(string $v): string {
    $v = trim($v);
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    $v = preg_replace('/[ \t]+/u', ' ', $v) ?? $v;
    $v = preg_replace("/\n{3,}/u", "\n\n", $v) ?? $v;
    return $v;
}
function fehler(string $msg, string $contact): void {
    echo "<!doctype html><html lang='de'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>Fehler – KündigungExpress</title>";
    echo "<style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,sans-serif;background:#F7F9FC;color:#0F172A;display:flex;flex-direction:column;min-height:100vh}.wrap{max-width:560px;margin:auto;padding:32px 24px;display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1}.card{background:#fff;border:1px solid #E2E8F0;border-radius:20px;padding:36px 32px;text-align:center;width:100%;box-shadow:0 6px 24px rgba(15,23,42,0.06)}.err-icon{width:60px;height:60px;background:#FEF2F2;border:1px solid #FECACA;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 18px}h1{font-size:22px;font-weight:900;margin-bottom:10px}p{font-size:14px;color:#475569;line-height:1.65;margin-bottom:18px}a.btn{display:inline-block;background:#16A34A;color:#fff;text-decoration:none;padding:13px 24px;border-radius:13px;font-weight:900;font-size:15px}.contact{font-size:12px;color:#94A3B8;margin-top:16px}.contact a{color:#16A34A;font-weight:700;text-decoration:none}footer{text-align:center;font-size:12px;color:#94A3B8;padding:12px 24px 20px}</style>";
    echo "</head><body><div class='wrap'><div class='card'><div class='err-icon'>⚠️</div><h1>Etwas ist schiefgelaufen</h1>";
    echo "<p>" . htmlspecialchars($msg) . "</p>";
    echo "<a class='btn' href='/formular.php'>← Zurück zum Formular</a>";
    echo "<div class='contact'>Hilfe? <a href='mailto:" . htmlspecialchars($contact) . "'>" . htmlspecialchars($contact) . "</a></div>";
    echo "</div></div><footer>© 2026 KündigungExpress · <a href='/impressum.html'>Impressum</a> · <a href='/datenschutz.html'>Datenschutz</a></footer></body></html>";
    exit;
}

/* =========================================================
   ONLY ACCEPT POST
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fehler('Ungültiger Aufruf. Bitte starten Sie den Vorgang über das Formular neu.', $CONTACT_EMAIL);
}

/* =========================================================
   READ & VALIDATE INPUT
   ========================================================= */
$firstName       = clean_text((string)($_POST['firstName'] ?? ''));
$lastName        = clean_text((string)($_POST['lastName'] ?? ''));
$street          = clean_multiline((string)($_POST['street'] ?? ''));
$zip             = trim((string)preg_replace('/\s+/', '', (string)($_POST['zip'] ?? '')));
$city            = clean_text((string)($_POST['city'] ?? ''));
$email           = clean_text((string)($_POST['email'] ?? ''));
$studio          = clean_text((string)($_POST['anbieter'] ?? ''));
$studioStreet    = clean_text((string)($_POST['studioStreet'] ?? ''));
$studioZip       = trim((string)preg_replace('/\s+/', '', (string)($_POST['studioZip'] ?? '')));
$studioCity      = clean_text((string)($_POST['studioCity'] ?? ''));
$contractNo      = clean_text((string)($_POST['contractNo'] ?? ''));
$terminationMode = (string)($_POST['terminationMode'] ?? 'next_possible');
$terminationDate = clean_text((string)($_POST['terminationDate'] ?? ''));
$providerEmail   = clean_text((string)($_POST['providerEmail'] ?? ''));
$sendEmail       = isset($_POST['sendEmail']) ? '1' : '0';
$type            = ($_POST['type'] ?? 'fitness');
$type            = in_array($type, ['handy', 'fitness', 'kfz'], true) ? $type : 'fitness';

if ($firstName==='' || $lastName==='' || $street==='' || $zip==='' || $city==='' || $studio==='') {
    fehler('Bitte füllen Sie alle Pflichtfelder aus: Vorname, Nachname, Adresse und Vertragspartner.', $CONTACT_EMAIL);
}
if (!preg_match('/^\d{5}$/', $zip)) {
    fehler('Bitte geben Sie eine gültige 5-stellige Postleitzahl ein.', $CONTACT_EMAIL);
}
if ($studioZip !== '' && !preg_match('/^\d{5}$/', $studioZip)) {
    fehler('Ungültige Postleitzahl des Anbieters.', $CONTACT_EMAIL);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fehler('Bitte geben Sie eine gültige E-Mail-Adresse an.', $CONTACT_EMAIL);
}
if ($terminationMode !== 'next_possible' && $terminationMode !== 'specific_date') {
    $terminationMode = 'next_possible';
}
if ($terminationMode === 'specific_date') {
    if ($terminationDate === '') fehler('Bitte wählen Sie ein Kündigungsdatum.', $CONTACT_EMAIL);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $terminationDate)) fehler('Ungültiges Datum.', $CONTACT_EMAIL);
}
if ($sendEmail === '1') {
    if ($providerEmail === '' || !filter_var($providerEmail, FILTER_VALIDATE_EMAIL)) {
        fehler('E-Mail-Versand aktiviert, aber keine gültige Empfängeradresse angegeben.', $CONTACT_EMAIL);
    }
} else {
    $providerEmail = '';
}

/* =========================================================
   BUILD PDF
   ========================================================= */
$name    = $firstName . ' ' . $lastName;
$address = $street . "\n" . $zip . ' ' . $city;
$studioAddress = trim(($studioStreet ?? '') . "\n" . ($studioZip ?? '') . ' ' . ($studioCity ?? ''));
$emailLine = !empty($email) ? '<div class="meta-email">E-Mail: ' . htmlspecialchars($email) . '</div>' : '';
$contractLine = !empty($contractNo) ? 'mit der Vertragsnummer ' . htmlspecialchars($contractNo) : 'ohne Vertragsnummer';
$plate         = clean_text((string)($_POST['plate'] ?? ''));
$plateLine     = !empty($plate) ? htmlspecialchars($plate) : 'nicht angegeben';
$terminationLine = ($terminationMode === 'specific_date' && !empty($terminationDate))
    ? 'zum ' . htmlspecialchars(date('d.m.Y', strtotime($terminationDate)))
    : 'zum nächstmöglichen Zeitpunkt';

// Only add safety clause "hilfsweise zum nächstmöglichen Zeitpunkt" when user picked a specific date
$hilfsweise = ($terminationMode === 'specific_date' && !empty($terminationDate))
    ? ', hilfsweise zum nächstmöglichen Zeitpunkt,'
    : '';

if ($type === 'kfz') {
    $templateFile = __DIR__ . '/templates/kfz-kuendigung.html';
} elseif ($type === 'handy') {
    $templateFile = __DIR__ . '/templates/handy-kuendigung.html';
} else {
    $templateFile = __DIR__ . '/templates/fitness-kuendigung.html';
}

if (!file_exists($templateFile)) {
    fehler('PDF-Vorlage nicht gefunden. Bitte kontaktieren Sie uns.', $CONTACT_EMAIL);
}

$template = file_get_contents($templateFile);
$html = str_replace(
    ['{{name}}', '{{address}}', '{{studio}}', '{{studio_address}}', '{{email_line}}',
     '{{contract_line}}', '{{termination_line}}', '{{date}}', '{{city}}', '{{hilfsweise}}', '{{plate_line}}'],
    [nl2br(htmlspecialchars($name)), nl2br(htmlspecialchars($address)),
     htmlspecialchars($studio), nl2br(htmlspecialchars($studioAddress)),
     $emailLine, $contractLine, $terminationLine, date('d.m.Y'), htmlspecialchars($city), $hilfsweise, $plateLine],
    $template
);

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4');
$dompdf->render();

$pdfDir = __DIR__ . '/pdf';
if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);
$providerSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $studio)));
$providerSlug = trim($providerSlug, '-');
$providerSlug = $providerSlug !== '' ? $providerSlug : 'vertrag';
$filename = 'Kuendigung-' . $providerSlug . '_' . bin2hex(random_bytes(4)) . '.pdf';
file_put_contents($pdfDir . '/' . $filename, $dompdf->output());

// Increment social proof counter
$counterFile = __DIR__ . '/_counter.txt';
if (file_exists($counterFile)) {
    $count = (int)file_get_contents($counterFile);
    file_put_contents($counterFile, (string)($count + 1));
}

// DASHBOARD TRACKING
$dataDir = __DIR__ . '/_data';
if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
$trackingFile = $dataDir . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.json';
$trackingRecord = [
    'createdAt'   => time(),
    'type'        => $type,                                
    'provider'    => $studio,                              
    'emailSent'   => isset($sendEmail) && $sendEmail === '1' && !empty($providerEmail),
    'hasContract' => !empty($contractNo),
    'hasEmail'    => !empty($email),
];
@file_put_contents($trackingFile, json_encode($trackingRecord, JSON_UNESCAPED_UNICODE));
$downloadUrl = '/pdf/' . $filename;

/* =========================================================
   EMAIL (OPTIONAL)
   ========================================================= */
$emailSent = false;
if ($type === 'kfz') {
    $mailBody = "Sehr geehrte Damen und Herren,\n\nanbei erhalten Sie meine Kündigung als PDF.\n\nHiermit kündige ich meine KFZ-Versicherung fristgerecht " . strip_tags($terminationLine) . " zum Ablauf des laufenden Versicherungsjahres.\n\nMit freundlichen Grüßen\n" . $name;
} elseif ($type === 'handy') {
    $mailBody = "Sehr geehrte Damen und Herren,\n\nanbei erhalten Sie meine Kündigung als PDF.\n\nHiermit kündige ich meinen Mobilfunkvertrag fristgerecht " . strip_tags($terminationLine) . " gemäß § 56 TKG.\n\nMit freundlichen Grüßen\n" . $name;
} else {
    $mailBody = "Sehr geehrte Damen und Herren,\n\nanbei erhalten Sie meine Kündigung als PDF.\n\nHiermit kündige ich meine Mitgliedschaft fristgerecht " . strip_tags($terminationLine) . " gemäß § 621 BGB.\n\nMit freundlichen Grüßen\n" . $name;
}

if ($sendEmail === '1' && !empty($providerEmail)) {
    try {
        $smtp = require __DIR__ . '/_smtp_config.php';
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
        $mail->addAddress($providerEmail);
        if (!empty($email)) {
            $mail->addCC($email);
            $mail->addReplyTo($email, $name);
        }
        $mail->addAttachment($pdfDir . '/' . $filename, 'Kuendigung-' . $providerSlug . '.pdf');
        $mail->Subject = ($type === 'kfz' ? 'Kündigung meiner KFZ-Versicherung – ' : ($type === 'handy' ? 'Kündigung meines Mobilfunkvertrags – ' : 'Kündigung meiner Fitnessstudio-Mitgliedschaft – ')) . $name;
        $mail->Body = $mailBody;
        $mail->send();
        $emailSent = true;
    } catch (Exception $e) {
        // fail silent
    }
}

if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    try {
        $smtp2 = require __DIR__ . '/_smtp_config.php';
        $confirm = new PHPMailer(true);
        $confirm->isSMTP();
        $confirm->Host       = $smtp2['host'];
        $confirm->SMTPAuth   = true;
        $confirm->Username   = $smtp2['username'];
        $confirm->Password   = $smtp2['password'];
        $confirm->Port       = (int)$smtp2['port'];
        $confirm->SMTPSecure = $smtp2['encryption'];
        $confirm->CharSet    = 'UTF-8';
        $confirm->setFrom($smtp2['username'], 'KündigungExpress');
        $confirm->addAddress($email, $name);
        $confirm->addAttachment($pdfDir . '/' . $filename, 'Kuendigung-' . $providerSlug . '.pdf');
        $confirm->Subject = 'Ihr Kündigungsschreiben von KündigungExpress';
        $confirm->Body    = "Guten Tag " . $firstName . ",\n\nim Anhang finden Sie Ihr Kündigungsschreiben für " . $studio . ".\n\nNächste Schritte:\n1. Ausdrucken und eigenhändig unterschreiben\n2. Per Einschreiben mit Rückschein versenden\n3. Eingangsbestätigung mit Vertragsende aufbewahren\n\nBei Fragen: kontakt@kuendigungexpress.de\n\nMit freundlichen Grüßen\nKündigungExpress · kuendigungexpress.de";
        $confirm->send();
    } catch (Exception $e) {
        // fail silent
    }
}

/* =========================================================
   SUCCESS PAGE — Download + Affiliate CTA
   ========================================================= */
$isHandy = $type === 'handy';
$isKfz   = $type === 'kfz';
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="color-scheme" content="light">
<link rel="preconnect" href="https://www.clarity.ms">
<link rel="preconnect" href="https://a.check24.net">
<link rel="preconnect" href="https://www.awin1.com">
<link rel="dns-prefetch" href="//www.clarity.ms">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#16A34A">
<meta name="robots" content="noindex, nofollow">
<title>Ihr Kündigungsschreiben – KündigungExpress</title>
<link rel="icon" href="/favicon.ico" sizes="any">
<style>
:root{--bg:#F7F9FC;--card:#FFFFFF;--text:#0F172A;--muted:#475569;--border:#E2E8F0;--green:#16A34A}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;background:var(--bg);color:var(--text);display:flex;flex-direction:column;min-height:100vh}
.wrap{max-width:640px;margin:0 auto;padding:32px 20px 8px;flex:1;display:flex;flex-direction:column;gap:16px}
/* Modificat padding de la 36px la 24px vertical */
.success-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:20px 32px;text-align:center;box-shadow:0 8px 32px rgba(22,163,74,0.08)}
.check-circle{width:68px;height:68px;background:linear-gradient(135deg,#16A34A,#22C55E);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:34px;color:#fff;font-weight:900;line-height:1;box-shadow:0 6px 18px rgba(22,163,74,0.3)}
.saved-banner{background:#F0FDF4;border:1px solid rgba(22,163,74,0.25);border-radius:12px;padding:12px 16px;font-size:14px;color:#15803D;font-weight:600;margin-bottom:18px;line-height:1.5;}
.success-card h1{font-size:clamp(20px,4vw,26px);font-weight:900;margin-bottom:8px}
.success-card p{font-size:14px;color:var(--muted);line-height:1.7;margin-bottom:22px;max-width:420px;margin-left:auto;margin-right:auto}
.btn-download{display:block;background:var(--green);color:#fff;padding:16px 28px;border-radius:16px;font-weight:900;font-size:17px;text-decoration:none;transition:filter .15s;box-shadow:0 6px 18px rgba(22,163,74,0.28);margin-bottom:12px}
.btn-download:hover{filter:brightness(.95)}
.preview-link{display:block;text-align:center;font-size:13px;color:var(--muted);text-decoration:none;margin-bottom:8px;padding:4px;transition:color .15s}
.preview-link:hover{color:var(--green);text-decoration:underline}
.email-note{background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;padding:12px 16px;font-size:13px;color:#166534;margin-bottom:12px}
.next-steps{background:var(--bg);border:1px solid var(--border);border-radius:14px;padding:16px 18px;text-align:left;margin-top:8px}
.next-steps h2{font-size:13px;font-weight:800;margin-bottom:10px}
.next-steps ol{padding-left:18px;display:flex;flex-direction:column;gap:7px}
.next-steps li{font-size:13px;color:var(--muted);line-height:1.5}
.intent-survey{background:#FFF7ED;border:1px solid #FED7AA;border-radius:18px;padding:22px 24px;margin:18px 0;text-align:center}
.intent-survey h3{font-size:15px;font-weight:800;color:var(--text);margin-bottom:4px}
.intent-survey p{font-size:14px;color:var(--muted);margin-bottom:14px}
.survey-btns{display:flex;flex-wrap:wrap;gap:8px;justify-content:center}
.survey-btn{background:#fff;border:1.5px solid #FB923C;color:#9A3412;font-weight:700;font-size:13px;padding:10px 14px;border-radius:10px;cursor:pointer;transition:all .15s}
.survey-btn:hover{background:#FB923C;color:#fff}
.survey-thanks{display:none;color:#15803D;font-weight:700;font-size:14px;margin-top:10px}
.intent-survey.answered{background:#F0FDF4;border-color:#16A34A;border-width:2px;transition:all .3s}
.intent-survey.answered h3,
.intent-survey.answered p,
.intent-survey.answered .survey-btns{display:none}
.intent-survey.answered .survey-thanks{
  display:flex; align-items:center; justify-content:center; gap:10px; font-size:16px; font-weight:800; color:#15803D; padding:8px 0;
}
.intent-survey.answered .survey-thanks::before{
  content:"✓"; display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; background:#16A34A; color:#fff; border-radius:50%; font-weight:900; font-size:16px; flex-shrink:0;
}
.affiliate-card{background:var(--card);border:2px solid rgba(22,163,74,0.3);border-radius:24px;padding:28px 28px 24px;box-shadow:0 8px 32px rgba(22,163,74,0.10)}
.affiliate-card h2{font-size:18px;font-weight:900;margin-bottom:8px;color:var(--text);text-align:center}
.affiliate-card p{font-size:14px;color:var(--muted);line-height:1.65;margin-bottom:18px;text-align:center}
.aff-btn{display:block;text-align:center;font-weight:800;font-size:15px;padding:15px 20px;border-radius:14px;text-decoration:none;transition:filter .15s;margin-bottom:10px}
.aff-btn:hover{filter:brightness(.92)}
.aff-transparency{font-size:12px;color:#94A3B8;text-align:center;margin-top:14px;line-height:1.6;padding-top:12px;border-top:1px solid var(--border)}
.saved-line{background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:10px 14px;font-size:13px;color:#166534;text-align:center;margin-top:12px;line-height:1.5}
.warum-kostenlos{font-size:12px;color:#94A3B8;text-align:center;margin-top:10px;line-height:1.6}
.aff-btn-check24{background:#1E40AF;color:#fff}
.aff-btn-telekom{background:#E20074;color:#fff}
.aff-note{font-size:11px;color:#94A3B8;text-align:center;margin-top:6px}
.email-capture{background:#F0FDF4;border:1px solid #BBF7D0;border-radius:14px;padding:20px 18px}
.email-capture h2{font-size:14px;font-weight:800;color:var(--text);margin-bottom:6px}
.email-capture p{font-size:13px;color:var(--muted);margin-bottom:12px;line-height:1.5}
.capture-form{display:flex;gap:8px;flex-wrap:wrap}
.capture-form input[type="email"]{flex:1;min-width:0;padding:10px 14px;border:1px solid #BBF7D0;border-radius:10px;font-size:14px;background:#fff;color:var(--text);outline:none}
.capture-form input[type="email"]:focus{border-color:var(--green)}
.capture-form button{background:var(--green);color:#fff;border:none;padding:10px 18px;border-radius:10px;font-weight:800;font-size:14px;cursor:pointer}
.capture-consent{display:flex;align-items:flex-start;gap:8px;margin-top:10px;font-size:12px;color:var(--muted);line-height:1.5}
.capture-consent input{margin-top:2px;flex-shrink:0}
.capture-success{font-size:13px;color:var(--green);font-weight:700;margin-top:10px;display:none}
.review-block{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:20px;text-align:center}
.review-block h3{font-size:15px;font-weight:900;margin-bottom:6px}
.review-block p{font-size:13px;color:var(--muted);margin-bottom:14px;line-height:1.5}
.review-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
.review-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:12px;font-size:13px;font-weight:800;text-decoration:none;border:1px solid var(--border);color:var(--text);background:var(--bg);transition:border-color .15s}
.review-btn:hover{border-color:#94A3B8}
.review-stars{color:#FBBF24;font-size:15px;margin-bottom:4px}
.share-block{background:linear-gradient(160deg,#F0FDF4 0%,#fff 100%);border:2px solid rgba(22,163,74,0.25);border-radius:20px;padding:24px 20px;text-align:center;box-shadow:0 4px 16px rgba(22,163,74,0.08)}
.share-block h3{font-size:16px;font-weight:900;color:var(--text);margin-bottom:6px}.share-block p{font-size:13px;color:var(--muted);margin-bottom:14px}
.share-btn{display:inline-flex;align-items:center;gap:8px;background:var(--green);border:none;border-radius:12px;padding:12px 24px;font-size:14px;font-weight:800;color:#fff;cursor:pointer;text-decoration:none;transition:filter .15s;box-shadow:0 4px 12px rgba(22,163,74,0.25)}
.share-btn:hover{filter:brightness(.93)}
.share-copied{font-size:12px;color:var(--green);font-weight:700;margin-top:8px;display:none}
.back-link{text-align:center;font-size:13px;color:var(--muted);margin-top:4px}
.back-link a{color:var(--green);text-decoration:none;font-weight:700}
footer{text-align:center;font-size:12px;color:#94A3B8;padding:12px 24px 20px}
footer a{color:inherit;text-decoration:none}
footer p{margin-top:0 !important;margin-bottom:3px !important;font-size:12px;color:#64748B;line-height:1.5}
footer p:last-child{margin-bottom:0 !important}
.site-header{display:none;position:fixed;top:0;left:0;right:0;height:56px;background:#fff;border-bottom:1px solid var(--border);z-index:1000;align-items:center;justify-content:center}
.site-header .brand{font-weight:900;font-size:18px;color:var(--text);text-decoration:none}
.engagement-strip { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 18px; }
.eng-col { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 18px 16px; text-align: center; display: flex; flex-direction: column; }
.eng-icon { font-size: 22px; margin-bottom: 6px; line-height: 1; }
.eng-review .eng-icon { color: #f59e0b; font-size: 14px; letter-spacing: 1px; }
.eng-col h3 { font-size: 13px; font-weight: 800; color: var(--text); margin-bottom: 4px; }
.eng-col p { font-size: 12px; color: var(--muted); margin-bottom: 10px; line-height: 1.45; }
.eng-col .capture-form { display: flex; flex-direction: column; gap: 6px; margin-bottom: 8px; }
.eng-col .capture-form input { font-size: 12px; padding: 8px 10px; border: 1px solid var(--border); border-radius: 8px; width: 100%; box-sizing: border-box; }
.eng-col .capture-form button { font-size: 12px; padding: 8px 10px; background: var(--green); color: #fff; border: 0; border-radius: 8px; font-weight: 700; cursor: pointer; }
.eng-col .capture-consent { font-size: 10.5px; color: var(--muted); display: flex; align-items: flex-start; gap: 5px; text-align: left; line-height: 1.35; }
.eng-col .capture-consent input { margin-top: 2px; flex-shrink: 0; }
.eng-col .capture-success, .eng-col .share-copied { display: none; color: #15803D; font-weight: 700; font-size: 12px; margin-top: 6px; }
.eng-col .review-btns { display: flex; flex-direction: column; gap: 6px; }
.eng-col .review-btn { display: block; font-size: 12px; font-weight: 700; padding: 8px 10px; border-radius: 8px; text-decoration: none; text-align: center; border: 1px solid var(--border); color: var(--text); background: #fff; }
.eng-col .review-btn:hover { background: #f8fafc; }
.eng-col .share-btn { font-size: 12px; padding: 8px 14px; background: var(--green); color: #fff; border: 0; border-radius: 8px; font-weight: 700; cursor: pointer; width: 100%; justify-content: center; text-align: center; }
.back-link { text-align: center; margin-top: 18px; font-size: 13px; color: var(--muted); }
.back-link a { color: var(--green); font-weight: 700; text-decoration: none; }
@media(max-width:820px){ .site-header{display:flex !important} body{padding-top:56px} .wrap{padding-top:16px} }
@media(max-width:480px){ .success-card,.affiliate-card{padding:24px 18px;border-radius:18px} .capture-form{flex-direction:column} .capture-form button{width:100%} .engagement-strip{grid-template-columns:1fr;gap:10px} }
</style>
<meta name="ke-provider" content="<?= htmlspecialchars($studio, ENT_QUOTES) ?>">
<meta name="ke-type" content="<?= htmlspecialchars($type, ENT_QUOTES) ?>">
<script src="/clarity-loader.js" async></script>
</head>
<body>
<header class="site-header"><a href="/" class="brand">KündigungExpress</a></header>
<div class="wrap">

  <div class="success-card">
    <div class="check-circle">✓</div>
    <div class="saved-banner">
      🎉 Kostenlos — andere Dienste verlangen <strong>4,99 €</strong> für dieses Dokument.
    </div>
    <h1>Ihr Kündigungsschreiben ist fertig</h1>
    <p>Dein PDF wird automatisch heruntergeladen (Bitte kurz warten...). Drucke es anschließend aus und verschicke es.</p>
    <a class="btn-download" id="autoDownloadBtn" href="<?= htmlspecialchars($downloadUrl) ?>" download="Kuendigung-<?= htmlspecialchars($providerSlug) ?>.pdf">
      Download startet nicht? Hier klicken.
    </a>
    <a class="preview-link" href="<?= htmlspecialchars($downloadUrl) ?>" target="_blank" rel="noopener">
      Vorher ansehen (in neuem Tab)
    </a>
    <?php if ($emailSent): ?>
    <div class="saved-line">✉️ Per E-Mail an den Anbieter gesendet</div>
    <?php endif; ?>
  </div>

  <div class="affiliate-card">
    <?php if ($isKfz): ?>
    <h2>Nächster Schritt: Günstigere KFZ-Versicherung finden</h2>
    <p>Die Kündigung bei <strong><?= htmlspecialchars($studio) ?></strong> ist vorbereitet. Vergleichen Sie jetzt Tarife und sparen Sie bis zu 50% beim Wechsel.</p>
    <a href="https://a.check24.net/misc/click.php?pid=1169420&aid=18&deep=kfz-versicherung&cat=1"
       class="aff-btn aff-btn-check24" target="_blank" rel="nofollow sponsored" data-aff="check24-kfz">
      KFZ-Versicherung vergleichen · CHECK24
    </a>
    <?php elseif ($isHandy): ?>
    <h2>Nächster Schritt: Günstigeren Tarif sichern</h2>
    <p>Die Kündigung bei <strong><?= htmlspecialchars($studio) ?></strong> ist vorbereitet. Wechseln Sie jetzt – im gleichen Netz oder zu einem anderen Anbieter.</p>
    <a href="https://www.tariffuxx.de/handytarife?r=1126248&subid=generate-<?= htmlspecialchars($providerSlug) ?>"
       class="aff-btn" style="background:#3B82F6;color:#fff;margin-bottom:10px;" target="_blank" rel="nofollow sponsored" data-aff="tariffuxx-generate">
      Im gleichen Netz bleiben &amp; sparen → Tariffuxx
    </a>
    <a href="https://a.check24.net/misc/click.php?pid=1169420&aid=18&deep=handytarife&cat=7"
       class="aff-btn" style="background:#1E40AF;color:#fff;font-weight:700;" target="_blank" rel="nofollow sponsored" data-aff="check24-handy">
      Alle Anbieter vergleichen · CHECK24
    </a>
    <?php else: ?>
    <h2>Zuhause weitertrainieren – ohne Mitgliedschaft</h2>
    <p>Sie verlassen <strong><?= htmlspecialchars($studio) ?></strong>. Bleiben Sie fit – mit dem interaktiven Plankpad Balance Board für zuhause.</p>
    <a href="https://www.awin1.com/awclick.php?gid=593291&mid=118181&awinaffid=2838186&linkid=4693133&clickref=fitness-generate"
       class="aff-btn" style="background:#EA580C;color:#fff;" target="_blank" rel="nofollow sponsored" data-aff="plankpad-generate">
      🏋️ Zuhause trainieren mit Plankpad → jetzt entdecken
    </a>
    <?php endif; ?>
  </div>

  <div class="intent-survey" id="intentSurvey">
    <h3>Eine kurze Frage hilft uns sehr:</h3>
    <p>Wechseln Sie zu einem neuen Anbieter?</p>
    <div class="survey-btns">
      <button type="button" class="survey-btn" data-answer="switching">Ja, ich suche noch</button>
      <button type="button" class="survey-btn" data-answer="have-replacement">Habe schon einen</button>
      <button type="button" class="survey-btn" data-answer="no-replacement">Nein, kein neuer Vertrag</button>
    </div>
    <div class="survey-thanks" id="surveyThanks"><span>Vielen Dank! Ihre Antwort wurde gespeichert.</span></div>
  </div>

  <div class="engagement-strip">
    <div class="eng-col eng-reminder">
      <div class="eng-icon">📬</div>
      <h3>Frist-Erinnerung</h3>
      <p>Nie wieder eine Frist verpassen.</p>
      <form class="capture-form" id="captureForm">
        <input type="email" id="captureEmail" placeholder="ihre@email.de" required>
        <button type="submit">Aktivieren</button>
      </form>
      <label class="capture-consent">
        <input type="checkbox" id="captureConsent" required>
        Einverstanden mit Fristerinnerungen per E-Mail. Abmeldung jederzeit möglich.
      </label>
      <div class="capture-success" id="captureSuccess">✓ Eingetragen!</div>
    </div>

    <div class="eng-col eng-review">
      <div class="eng-icon">★★★★★</div>
      <h3>Bewertung hinterlassen</h3>
      <p>Sie haben kostenlos bekommen, wofür andere zahlen. Eine kurze Bewertung hilft uns, das für alle gratis zu halten.</p>
      <div class="review-btns">
        <a href="https://g.page/r/CQUi4-fYtkH4EAE/review" class="review-btn" target="_blank" rel="nofollow">⭐ Google</a>
        <a href="https://www.trustpilot.com/review/kuendigungexpress.de" class="review-btn" target="_blank" rel="nofollow">⭐ Trustpilot</a>
      </div>
    </div>

    <div class="eng-col eng-share">
      <div class="eng-icon">🔗</div>
      <h3>Link teilen</h3>
      <p>Schick es weiter — es kostet nichts und hilft sofort.</p>
      <button class="share-btn" data-action="share">Kostenlos teilen</button>
      <div class="share-copied" id="shareCopied">✓ Link kopiert!</div>
    </div>
  </div>

  <div class="back-link">
    Noch eine Kündigung? <a href="/">Zur Startseite</a>
  </div>

</div>

<footer>
  <p class="brand-disclaimer">© 2026 KündigungExpress · <a href="/impressum.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Impressum</a> · <a href="/datenschutz.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Datenschutz</a></p>
  <p class="brand-disclaimer">Erstellt mit <a href="https://digital-firmen.de" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;" target="_blank">digital-firmen.de</a></p>
</footer>

<script src="/generate-success.js" defer></script>
</body>
</html>