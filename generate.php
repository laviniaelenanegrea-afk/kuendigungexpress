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
echo "<link rel='stylesheet' href='/style.css?v=13'></head><body><div class='wrap'><div class='card'><div class='err-icon'>⚠️</div><h1>Etwas ist schiefgelaufen</h1>";
    echo "<p>" . htmlspecialchars($msg) . "</p>";
    echo "<a class='btn' href='/formular.php'>← Zurück zum Formular</a>";
    echo "<div class='contact'>Hilfe? <a href='mailto:" . htmlspecialchars($contact) . "'>" . htmlspecialchars($contact) . "</a></div>";
    echo "</div></div><footer>© 2026 KündigungExpress · <a href='/impressum.html'>Impressum</a> · <a href='/datenschutz.html'>Datenschutz</a> · <a href='/agb.html'>AGB</a> · <a href='/hilfe.html'>Hilfe</a></footer>
</body></html>";
    exit;
}

/* =========================================================
   ONLY ACCEPT POST
   ========================================================= */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    fehler('Ungültiger Aufruf. Bitte starten Sie den Vorgang über das Formular neu.', $CONTACT_EMAIL);
}

/* =========================================================
   M4a — HONEYPOT
   Câmp ascuns 'website' în formular (CSS display:none).
   Boții îl completează, oamenii nu. Dacă e plin → respingem
   silențios cu 200 (botul crede că a reușit, nu retry-uiește).
   ========================================================= */
if (trim((string)($_POST['website'] ?? '')) !== '') {
    error_log('[generate] Honeypot triggered from IP ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    // Răspuns 200 "fals-pozitiv" — nu dăm botului semnal că a fost prins
    http_response_code(200);
    fehler('Ihre Anfrage wird verarbeitet. Bitte versuchen Sie es in Kürze erneut.', $CONTACT_EMAIL);
}

/* =========================================================
   M4b — RATE LIMIT — max 8 PDF-uri/10 min per IP
   Protejează CPU pe shared hosting (dompdf e costisitor).
   Stocare: hash SHA-256 al IP-ului (privacy), director protejat.
   ========================================================= */
$genClientIp = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$genIpHash   = substr(hash('sha256', $genClientIp), 0, 16);

$genRateDir = __DIR__ . '/_rate_limit';
if (!is_dir($genRateDir)) {
    @mkdir($genRateDir, 0700, true);
    @file_put_contents($genRateDir . '/.htaccess', "Require all denied\n");
}

$genRateFile   = $genRateDir . '/generate_' . $genIpHash . '.json';
$genNow        = time();
$genWindowSec  = 600;  // 10 minute
$genMaxReq     = 8;

$genRate = ['count' => 0, 'windowStart' => $genNow];
if (file_exists($genRateFile)) {
    $genTmp = json_decode((string)@file_get_contents($genRateFile), true);
    if (is_array($genTmp) && isset($genTmp['count'], $genTmp['windowStart'])) {
        $genRate = $genTmp;
    }
}
// Window expirat — reset
if ($genNow - (int)$genRate['windowStart'] > $genWindowSec) {
    $genRate = ['count' => 0, 'windowStart' => $genNow];
}
if ((int)$genRate['count'] >= $genMaxReq) {
    $waitMin = (int)ceil(($genWindowSec - ($genNow - (int)$genRate['windowStart'])) / 60);
    error_log('[generate] Rate limit hit for IP ' . $genIpHash . ' (' . $genRate['count'] . ' in window)');
    http_response_code(429);
    fehler('Zu viele Anfragen in kurzer Zeit. Bitte versuchen Sie es in ca. ' . $waitMin . ' Minuten erneut.', $CONTACT_EMAIL);
}
$genRate['count']++;
@file_put_contents($genRateFile, json_encode($genRate));

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
$terminationMode = (string)($_POST['terminationMode'] ?? 'next_possible');
$terminationDate = clean_text((string)($_POST['terminationDate'] ?? ''));
$providerEmail   = clean_text((string)($_POST['providerEmail'] ?? ''));
$sendEmail       = (isset($_POST['sendEmail']) && (string)$_POST['sendEmail'] === '1') ? '1' : '0';
$trustpilotConsent = (isset($_POST['trustpilotConsent']) && (string)$_POST['trustpilotConsent'] === '1') ? '1' : '0';
$type            = ($_POST['type'] ?? 'fitness');
$type            = in_array($type, ['handy', 'fitness', 'kfz', 'bank'], true) ? $type : 'fitness';

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
$addressCompact = trim($street . ', ' . $zip . ' ' . $city, ' ,');
$addressCompact = str_replace(['Straße', 'straße'], ['Str.', 'str.'], $addressCompact);
$studioAddress = trim(($studioStreet ?? '') . "\n" . ($studioZip ?? '') . ' ' . ($studioCity ?? ''));
$emailLine = !empty($email) ? 'E-Mail: ' . htmlspecialchars($email) : '';

/* --- START LOGICĂ INTELIGENTĂ CONTRACT & KENNZEICHEN --- */
$rawContract = trim((string)($_POST['contractNo'] ?? ''));
$isNachgereicht = (empty($rawContract) || mb_strtolower($rawContract, 'UTF-8') === 'wird nachgereicht');

// 1. Stabilim prefixul corect
if ($type === 'kfz') {
    $contractPrefix = 'Versicherungsschein-Nr.';
} elseif ($type === 'fitness') {
    $contractPrefix = 'Mitgliedsnummer/Vertragsnummer';
} elseif ($type === 'bank') {
    $contractPrefix = 'IBAN / Kontonummer';
} else {
    $contractPrefix = 'Vertragsnummer'; // Pentru Handy
}

// 2. Construim prima parte a propoziției
if ($isNachgereicht) {
    $contractLine = $contractPrefix . ' wird nachgereicht';
} else {
    $contractLine = $contractPrefix . ' ' . htmlspecialchars($rawContract);
}

// 3. Adăugăm Kennzeichen-ul (DOAR pentru KFZ, dacă a fost completat)
$plate = clean_text((string)($_POST['plate'] ?? ''));
if ($type === 'kfz' && !empty($plate)) {
    $contractLine .= ' | Kennzeichen: ' . htmlspecialchars($plate);
}
$zielIban = clean_text((string)($_POST['ziel_iban'] ?? ''));
if ($type === 'bank' && !empty($zielIban)) {
    $contractLine .= ' | Ziel-IBAN: ' . htmlspecialchars($zielIban);
}

$plateLine = '';
/* --- END LOGICĂ INTELIGENTĂ --- */

$terminationLine = ($terminationMode === 'specific_date' && !empty($terminationDate))
    ? 'zum ' . htmlspecialchars(date('d.m.Y', strtotime($terminationDate)))
    : 'zum nächstmöglichen Zeitpunkt';

$hilfsweise = ($terminationMode === 'specific_date' && !empty($terminationDate))
    ? ', hilfsweise zum nächstmöglichen Zeitpunkt,'
    : '';

if ($type === 'kfz') {
    $templateFile = __DIR__ . '/templates/kfz-kuendigung.html';
} elseif ($type === 'handy') {
    $templateFile = __DIR__ . '/templates/handy-kuendigung.html';
} elseif ($type === 'bank') {
    $templateFile = __DIR__ . '/templates/bank-kuendigung.html';
} else {
    $templateFile = __DIR__ . '/templates/fitness-kuendigung.html';
}

if (!file_exists($templateFile)) {
    fehler('PDF-Vorlage nicht gefunden. Bitte kontaktieren Sie uns.', $CONTACT_EMAIL);
}

$template = file_get_contents($templateFile);

/* =========================================================
   SIGNATURE HANDLING — embedded base64 PNG din signature pad (Versand path)
   ========================================================= */
// Gratis-PDF: User druckt und unterschreibt von Hand — kein digitales Bild
$signatureImageHtml = '';
$signatureInstructionHtml = '';

$html = str_replace(
    ['{{name}}', '{{address}}', '{{address_compact}}', '{{studio}}', '{{studio_address}}', '{{email_line}}',
     '{{contract_line}}', '{{termination_line}}', '{{date}}', '{{city}}', '{{hilfsweise}}', '{{plate_line}}',
     '{{signature_image}}', '{{signature_instruction}}'],
    [nl2br(htmlspecialchars($name)), nl2br(htmlspecialchars($address)), htmlspecialchars($addressCompact),
     htmlspecialchars($studio), nl2br(htmlspecialchars($studioAddress)),
     $emailLine, $contractLine, $terminationLine, date('d.m.Y'), htmlspecialchars($city), $hilfsweise, $plateLine,
     $signatureImageHtml, $signatureInstructionHtml],
    $template
);

/* =========================================================
   PREVIEW MODE
   ========================================================= */
$isConfirm = isset($_POST['confirm']) && (string)$_POST['confirm'] === '1';
if (!$isConfirm) {
    $allFields = [
        'type'              => $type,
        'firstName'         => $firstName,
        'lastName'          => $lastName,
        'street'            => $street,
        'zip'               => $zip,
        'city'              => $city,
        'email'             => $email,
        'anbieter'          => $studio,
        'studioStreet'      => $studioStreet,
        'studioZip'         => $studioZip,
        'studioCity'        => $studioCity,
        'terminationMode'   => $terminationMode,
        'terminationDate'   => $terminationDate,
        'contractNo'        => (string)($_POST['contractNo'] ?? ''),
        'plate'             => (string)($_POST['plate'] ?? ''),
        'providerEmail'     => $providerEmail,
        'sendEmail'         => $sendEmail,
        'trustpilotConsent' => $trustpilotConsent,
    ];
    $hiddenInputs = '';
    $hiddenInputsVersand = ''; // pentru versandForm: fără email (apare vizibil cu required)
    foreach ($allFields as $k => $v) {
        $input = '<input type="hidden" name="' . htmlspecialchars($k) . '" value="' . htmlspecialchars((string)$v) . '">' . "\n";
        $hiddenInputs .= $input;
        if ($k !== 'email') $hiddenInputsVersand .= $input;
    }
    $hiddenInputs .= '<input type="hidden" name="confirm" value="1">' . "\n";

    $backUrl = '/formular.php?type=' . urlencode($type) . '&anbieter=' . urlencode($studio);

    $iframeInject = '<style>
      body { padding: 38px 68px 30px 68px !important; box-sizing: border-box !important; }
      html, body { overflow: hidden !important; scrollbar-width: none !important; }
      body::-webkit-scrollbar, html::-webkit-scrollbar { display: none !important; width: 0 !important; height: 0 !important; }
      .footer { display: none !important; }
      .lxp-zone-spacer { display: none !important; }
      .lxp-below-addr { display: none !important; }
      .sig-area { height: 14px !important; min-height: 0 !important; }
    </style>';
    $htmlForIframe = preg_replace('/<\/head>/i', $iframeInject . '</head>', $html, 1);
    $iframeContent = htmlspecialchars($htmlForIframe, ENT_QUOTES, 'UTF-8');

    $pageTitle = ($type === 'kfz' ? 'KFZ-Versicherung' : ($type === 'handy' ? 'Handyvertrag' : ($type === 'bank' ? 'Girokonto' : 'Fitnessstudio'))) . ' Kündigung — Vorschau';

    echo '<!doctype html><html lang="de"><head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<meta name="robots" content="noindex, nofollow">';
    echo '<meta name="theme-color" content="#16A34A">';
    echo '<title>' . htmlspecialchars($pageTitle) . ' | KündigungExpress</title>';
    echo '<script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      "event": "preview_view",
      "contract_type": "' . htmlspecialchars($type, ENT_QUOTES) . '",
      "provider": "' . htmlspecialchars($studio, ENT_QUOTES) . '"
    });
    </script>';
    ?>
<style>
/* ============== Base ============== */
:root {
  --bg: #F7F9FC;
  --card: #FFFFFF;
  --text: #0F172A;
  --muted: #475569;
  --border: #E2E8F0;
  --primary: #16A34A;
  --primary-dark: #15803D;
  --primary-soft: #F0FDF4;
  --soft: #F1F5F9;
  --focus: rgba(22,163,74,0.3);
}
* { box-sizing: border-box; margin: 0; padding: 0; }

html, body { overflow-x: hidden; max-width: 100vw; }
body {
  font-family: Arial, sans-serif;
  background: var(--bg);
  color: var(--text);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.wrap {
  max-width: 880px;
  margin: 0 auto;
  padding: 28px 24px 8px;
  width: 100%;
}

.wrap > header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}
.wrap > header a { text-decoration: none; color: inherit; }
.brand { font-weight: 900; font-size: 20px; }
nav a { margin-left: 16px; color: var(--muted); font-size: 14px; text-decoration: none; }
nav a:hover { color: var(--text); }

/* Progress bar */
.progress-bar {
  display: flex;
  align-items: center;
  gap: 0;
  margin-bottom: 28px;
}
.prog-step {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--muted);
}
.prog-step.active { color: var(--primary); font-weight: 700; }
.prog-dot {
  width: 28px; height: 28px;
  border-radius: 50%;
  background: var(--soft);
  border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 800; color: var(--muted); flex-shrink: 0;
}
.prog-step.active .prog-dot { background: var(--primary); border-color: var(--primary); color: #fff; }
.prog-step.done .prog-dot { background: #DCFCE7; border-color: var(--primary); color: var(--primary); }
.prog-line { flex: 1; height: 2px; background: var(--border); margin: 0 8px; }

/* Page title */
.page-title { margin-bottom: 24px; text-align: center; }
.page-title h1 {
  font-size: clamp(24px, 4vw, 32px);
  font-weight: 900;
  line-height: 1.2;
  margin-bottom: 10px;
  letter-spacing: -0.02em;
}
.page-title .sub {
  font-size: 15px; color: var(--muted); line-height: 1.5;
  max-width: 580px; margin: 0 auto;
}

.site-header, .mobile-cta { display: none; }
.site-header {
  position: fixed; top: 0; left: 0; right: 0;
  height: 56px; z-index: 1000;
  background: #fff; border-bottom: 1px solid var(--border);
  align-items: center; justify-content: center;
}
.site-header .brand a {
  font-size: 18px; font-weight: 900;
  color: var(--text); text-decoration: none;
}

footer {
  text-align: center; padding: 16px 24px 20px; margin-top: 16px;
}
footer p { font-size: 12px; color: #94A3B8; line-height: 1.6; margin-bottom: 4px; }
footer a { color: inherit; text-decoration: none; }
footer a:hover { color: var(--text); }

.pv-toolbar {
  display: flex; align-items: center; justify-content: space-between;
  gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
}
.pv-back {
  display: inline-flex; align-items: center; gap: 8px;
  background: #fff; color: var(--text);
  border: 1.5px solid var(--border);
  font-weight: 700; font-size: 14px;
  padding: 11px 18px; border-radius: 12px;
  text-decoration: none; cursor: pointer;
  transition: all 0.15s ease;
  font-family: inherit; line-height: 1.2;
}
.pv-back:hover {
  background: var(--primary-soft);
  border-color: var(--primary);
  color: var(--primary-dark);
  transform: translateX(-2px);
}
.pv-toolbar-hint { font-size: 13px; color: var(--muted); }

.pv-paper-frame {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 14px;
  box-shadow: 0 12px 32px rgba(15,23,42,0.08), 0 4px 12px rgba(15,23,42,0.04);
  overflow: hidden;
  margin: 0 auto 28px;
  position: relative;
  width: 794px;
  max-width: 100%;
}
.pv-paper-frame iframe {
  display: block;
  border: 0;
  background: #fff;
  width: 794px;
  height: 600px;
  transform-origin: top left;
}

.pv-actions { display: grid; gap: 20px; }

.pv-featured {
  background: linear-gradient(180deg, #F0FDF4 0%, #FFFFFF 100%);
  border: 2px solid var(--primary);
  border-radius: 18px;
  padding: 28px;
  box-shadow: 0 10px 28px rgba(22,163,74,0.12);
  position: relative;
  overflow: hidden;
}
.pv-featured::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
  background: linear-gradient(90deg, var(--primary) 0%, #22C55E 50%, var(--primary) 100%);
}

.pv-featured h2 {
  font-size: 22px; font-weight: 900; line-height: 1.25;
  margin-bottom: 8px; letter-spacing: -0.01em;
}
.pv-featured .pv-sub {
  font-size: 15px; color: #334155; line-height: 1.55; margin-bottom: 16px;
}
.pv-benefits {
  list-style: none; margin: 0 0 18px; padding: 0;
}
.pv-benefits li {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 7px 0; font-size: 14px; color: var(--text); line-height: 1.5;
}
.pv-benefits li::before {
  content: '✓'; color: var(--primary); font-weight: 900; font-size: 16px;
  flex-shrink: 0; line-height: 1.3;
}
.pv-benefits li strong { color: var(--text); }

.pv-pricing {
  display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 18px;
}
.pv-price-card {
  background: #fff;
  border: 1px solid #BBF7D0;
  border-radius: 12px;
  padding: 12px 14px;
  text-align: center;
}
.pv-price-card .pv-price-label {
  display: block; font-size: 11px; font-weight: 700; color: var(--primary-dark);
  text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;
}
.pv-price-card .pv-price-val {
  display: block; font-size: 20px; font-weight: 900; color: var(--text); line-height: 1;
}
.pv-price-card .pv-price-meta {
  display: block; font-size: 11px; color: var(--muted); margin-top: 4px;
}

.pv-tier-radio {
  cursor: pointer;
  border: 2px solid #BBF7D0;
  transition: all 0.15s ease;
  position: relative;
}
.pv-tier-radio input[type="radio"] {
  position: absolute; opacity: 0; pointer-events: none;
}
.pv-tier-radio.is-selected {
  border-color: var(--primary);
  background: linear-gradient(180deg, #ECFDF5 0%, #fff 100%);
  box-shadow: 0 4px 12px rgba(22,163,74,0.15);
}
.pv-tier-radio.is-selected::after {
  content: '✓';
  position: absolute;
  top: 6px; right: 8px;
  width: 18px; height: 18px;
  background: var(--primary); color: #fff;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 900;
}
.pv-tier-radio:hover:not(.is-selected) {
  border-color: var(--primary);
  background: #F0FDF4;
}

.sig-modal-overlay {
  position: fixed; inset: 0;
  display: flex; align-items: flex-start; justify-content: center;
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(4px);
  z-index: 10000;
  padding: 0;
  overflow-y: auto;
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity 0.22s ease, visibility 0s linear 0.22s;
}
.sig-modal-overlay.active {
  opacity: 1; visibility: visible; pointer-events: auto;
  transition: opacity 0.22s ease, visibility 0s linear 0s;
}
@media (min-width: 500px) {
  .sig-modal-overlay { align-items: center; padding: 16px; }
}
.sig-modal-box {
  background: #fff;
  border-radius: 0;
  padding: 20px 16px 16px;
  width: 100%; max-width: 100%;
  min-height: 100vh;
  box-sizing: border-box;
  box-shadow: none;
}
.sig-modal-box, .sig-modal-box * { box-sizing: border-box; max-width: 100%; }
@media (min-width: 500px) {
  .sig-modal-box {
    border-radius: 20px;
    padding: 24px 22px 20px;
    max-width: 500px;
    min-height: 0;
    max-height: calc(100vh - 32px);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: keModalPop 0.28s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  }
}
.sig-modal-title {
  font-size: 20px; font-weight: 900;
  color: var(--text); margin-bottom: 6px;
  text-align: center; letter-spacing: -0.01em;
}
.sig-modal-sub {
  font-size: 14px; color: var(--muted);
  line-height: 1.5; margin-bottom: 18px;
  text-align: center;
}
.sig-modal-tier {
  text-align: center; font-size: 14px;
  margin-bottom: 16px; padding: 8px 14px;
  background: var(--primary-soft); color: var(--primary-dark);
  border-radius: 10px; font-weight: 700;
  display: inline-block; width: 100%;
}
.sig-canvas-wrap {
  position: relative;
  background: #FAFBFC;
  border: 2px dashed var(--border);
  border-radius: 12px;
  margin-bottom: 12px;
  overflow: hidden;
}
.sig-canvas-wrap.has-signature {
  border-style: solid;
  border-color: var(--primary);
  background: #fff;
}
.sig-canvas-wrap canvas {
  display: block;
  width: 100%;
  height: 180px;
  touch-action: none;
  cursor: crosshair;
}
@media (max-width: 640px) { .sig-canvas-wrap canvas { height: 200px; } }

.sig-rotate-hint { display: none; }
@media (max-width: 640px) and (orientation: portrait) {
  .sig-rotate-hint {
    display: block; text-align: center;
    font-size: 11.5px; line-height: 1.35;
    color: #92400E; background: #FEF3C7;
    border: 1px solid #FDE68A; border-radius: 8px;
    padding: 6px 8px; margin-bottom: 10px;
    font-weight: 600;
  }
}

.sig-summary {
  background: var(--primary-soft);
  border: 1.5px solid var(--primary);
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 14px;
  display: flex; align-items: center; gap: 10px;
  flex-wrap: wrap;
}
.sig-summary-icon { font-size: 22px; line-height: 1; flex-shrink: 0; }
.sig-summary-text {
  flex: 1 1 0; min-width: 0;
  font-size: 13px; color: var(--text); line-height: 1.45;
  word-wrap: break-word; overflow-wrap: anywhere;
}
.sig-summary-text strong { color: var(--primary-dark); font-weight: 800; }
.sig-summary-price {
  background: var(--primary-dark); color: #fff;
  padding: 6px 10px; border-radius: 8px;
  font-weight: 800; font-size: 14px;
  white-space: nowrap; flex-shrink: 0;
}

.sig-email-field {
  display: none;
  margin-bottom: 14px;
  padding: 12px 14px;
  background: #FEFCE8;
  border: 1px solid #FDE68A;
  border-radius: 12px;
}
.sig-email-field.is-visible { display: block; }
.sig-email-field label {
  display: block; font-size: 13px; font-weight: 700;
  color: #78350F; margin-bottom: 6px;
}
.sig-email-field input {
  width: 100%; box-sizing: border-box;
  padding: 10px 12px;
  border: 1px solid #FDE68A; border-radius: 8px;
  font-size: 14px; background: #fff; color: var(--text);
}
.sig-email-field small { font-weight: 500; color: #B45309; }
.sig-actions-secondary {
  display: flex; gap: 8px;
  margin-bottom: 16px;
  justify-content: center;
  flex-wrap: wrap;
}
.sig-action-link {
  background: none; border: 0;
  color: var(--muted); font-size: 13px;
  cursor: pointer; padding: 6px 10px;
  text-decoration: underline;
  transition: color 0.15s;
}
.sig-action-link:hover { color: var(--text); }
.sig-cta-confirm {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  width: 100%; padding: 15px 20px;
  background: var(--primary); color: #fff;
  border: 0; border-radius: 12px;
  font-size: 15px; font-weight: 900; cursor: pointer;
  box-shadow: 0 4px 14px rgba(22,163,74,0.25);
  transition: all 0.15s ease;
  margin-bottom: 8px;
}
.sig-cta-confirm:hover:not(:disabled) {
  background: var(--primary-dark);
  transform: translateY(-1px);
}
.sig-cta-confirm:disabled {
  background: #CBD5E1; color: #fff; cursor: not-allowed;
  box-shadow: none; opacity: 0.85;
}
.sig-cancel {
  display: block; width: 100%;
  background: none; border: 0;
  color: var(--muted); font-size: 14px;
  cursor: pointer; padding: 10px;
  text-decoration: underline;
}
.sig-cancel:hover { color: var(--text); }
.sig-legal {
  font-size: 11px; color: var(--muted);
  line-height: 1.5; text-align: center;
  margin-top: 12px; padding-top: 10px;
  border-top: 1px solid var(--border);
}

.sig-checkboxes {
  margin: 14px 0 18px;
  padding-top: 14px;
  border-top: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.sig-check-row {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  font-size: 12.5px;
  line-height: 1.55;
  color: var(--text);
  cursor: pointer;
  user-select: none;
}
.sig-check-row input[type="checkbox"] {
  flex-shrink: 0;
  margin-top: 2px;
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: var(--primary);
}
.sig-check-row span {
  flex: 1;
}
.sig-check-row a {
  color: var(--primary);
  text-decoration: underline;
  font-weight: 600;
}
.sig-check-row a:hover {
  color: var(--primary-dark);
}

.pv-email-block {
  margin: 0 0 16px 0;
  padding: 14px 16px;
  background: #FFFBEB;
  border: 1px solid #FDE68A;
  border-radius: 12px;
}
.pv-email-block label {
  display: block; font-size: 13px; font-weight: 700;
  color: #78350F; margin-bottom: 6px;
}
.pv-email-block .pv-req { color: #DC2626; }
.pv-email-block input {
  width: 100%; box-sizing: border-box;
  padding: 11px 13px; font-size: 15px;
  border: 1.5px solid #E2E8F0; border-radius: 9px;
  background: #fff; outline: none;
  transition: border-color .15s;
}
.pv-email-block input:focus { border-color: var(--primary); }
.pv-email-block input:invalid:not(:placeholder-shown) {
  border-color: #DC2626;
}
.pv-email-block small {
  display: block; margin-top: 6px;
  font-size: 12px; color: #92400E;
}

.pv-cta-primary {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  width: 100%; padding: 16px 24px;
  background: var(--primary); color: #fff;
  border: 0; border-radius: 14px;
  font-size: 16px; font-weight: 900; cursor: pointer;
  text-decoration: none;
  box-shadow: 0 4px 14px rgba(22,163,74,0.3);
  transition: all 0.15s ease;
}
.pv-cta-primary:hover:not(:disabled) {
  background: var(--primary-dark);
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(22,163,74,0.4);
}
.pv-cta-primary:disabled {
  background: #CBD5E1; color: #fff; cursor: not-allowed;
  box-shadow: none; opacity: 0.85;
}
.pv-cta-primary .pv-cta-icon { font-size: 18px; }

.pv-secondary {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 20px 24px;
  display: flex; align-items: center; gap: 18px;
  flex-wrap: wrap;
}
.pv-secondary .pv-sec-icon {
  width: 44px; height: 44px;
  background: var(--soft);
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; flex-shrink: 0;
}
.pv-secondary .pv-sec-text { flex: 1; min-width: 200px; }
.pv-secondary h3 {
  font-size: 16px; font-weight: 800; color: var(--text);
  margin-bottom: 4px; letter-spacing: -0.01em;
}
.pv-secondary p {
  font-size: 13px; color: var(--muted); line-height: 1.5;
}
.pv-cta-secondary {
  display: inline-flex; align-items: center; gap: 6px;
  background: #fff; color: var(--primary);
  border: 1.5px solid var(--primary);
  font-weight: 800; font-size: 14px;
  padding: 11px 18px; border-radius: 11px;
  text-decoration: none; cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
}
.pv-cta-secondary:hover {
  background: var(--primary-soft);
  transform: translateY(-1px);
}

.ke-trust-strip {
  max-width: 880px;
  margin: 0 auto;
  padding: 16px 24px 8px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px 20px;
  font-size: 12px;
  color: #64748B;
  text-align: center;
}
.ke-trust-strip span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
}
.ke-footer {
  max-width: 880px;
  margin: 0 auto;
  padding: 8px 24px 24px;
  text-align: center;
}
.ke-footer p {
  font-size: 12px;
  color: #94A3B8;
  line-height: 1.6;
  margin: 0 0 4px;
  text-align: center;
}
.ke-footer a {
  color: #64748B;
  text-decoration: none;
}
.ke-footer a:hover {
  color: #0F172A;
  text-decoration: underline;
}

.ke-modal-overlay {
  display: none; position: fixed; inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  z-index: 9999;
  align-items: center; justify-content: center;
  padding: 20px;
}
.ke-modal-overlay.active { display: flex; }
.ke-modal-box {
  background: #fff;
  border-radius: 18px;
  padding: 32px 28px;
  max-width: 460px; width: 100%;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0,0,0,0.3);
  animation: keModalPop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes keModalPop {
  from { opacity: 0; transform: scale(0.9) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
.ke-modal-icon { font-size: 40px; margin-bottom: 14px; line-height: 1; }
.ke-modal-title {
  font-size: 20px; font-weight: 900; color: var(--text);
  margin-bottom: 12px; letter-spacing: -0.01em;
}
.ke-modal-text {
  font-size: 15px; color: var(--muted);
  line-height: 1.6; margin-bottom: 22px;
}
.ke-modal-cta {
  display: block; width: 100%;
  padding: 14px 20px;
  background: var(--primary); color: #fff;
  border: 0; border-radius: 12px;
  font-size: 15px; font-weight: 800;
  text-decoration: none; cursor: pointer;
  margin-bottom: 10px;
  transition: filter 0.15s, transform 0.15s;
}
.ke-modal-cta:hover { filter: brightness(0.95); transform: translateY(-1px); }
.ke-modal-skip {
  background: none; border: 0;
  color: var(--muted); font-size: 14px;
  cursor: pointer; padding: 8px;
  text-decoration: underline;
  transition: color 0.15s;
}
.ke-modal-skip:hover { color: var(--text); }

@media (max-width: 720px) {
  body { padding-top: 56px; }
  .site-header { display: flex !important; }
  .wrap > header { display: none; }
  .wrap { padding: 16px 14px 8px; }
  .progress-bar { margin: 16px 0 20px; }
  .prog-step span { display: none; }
  .prog-step { gap: 0; }
  .prog-dot { width: 24px; height: 24px; font-size: 12px; }
  .prog-line { margin: 0 8px; }
  .page-title { margin-bottom: 16px; }
  .page-title h1 { font-size: 22px; margin-bottom: 6px; }
  .page-title .sub { font-size: 13.5px; }
  .pv-paper-frame { margin-bottom: 18px; }
  .pv-featured { padding: 20px 16px; }
  .pv-featured h2 { font-size: 19px; }
  .pv-featured .pv-sub { font-size: 14px; }
  .pv-benefits li { font-size: 13.5px; padding: 6px 0; }
  .pv-pricing { grid-template-columns: 1fr; gap: 8px; margin-bottom: 14px; }
  .pv-price-card { padding: 10px 12px; }
  .pv-price-card .pv-price-val { font-size: 18px; }
  .pv-secondary { padding: 18px; flex-direction: column; align-items: flex-start; }
  .pv-cta-secondary { width: 100%; justify-content: center; }
  .pv-cta-primary {
    padding: 14px 14px;
    font-size: 14.5px;
    font-weight: 800;
    line-height: 1.3;
    white-space: normal;
  }
  .pv-cta-primary .pv-cta-icon { font-size: 16px; }
  .pv-toolbar { gap: 8px; }
  .pv-toolbar-hint { display: none; }
  footer { padding: 14px 16px 18px; margin-top: 12px; }
  footer p { font-size: 11.5px; }
  .ke-trust-strip { padding: 14px 16px 4px; gap: 8px 14px; font-size: 11px; }
  .ke-footer { padding: 4px 16px 20px; }
  .ke-footer p { font-size: 11.5px; }
}
</style>
</head>
<body>

<header class="site-header">
  <div class="brand"><a href="/">KündigungExpress</a></div>
</header>

<div class="wrap">
  <header>
    <div class="brand"><a href="/">KündigungExpress</a></div>
    <nav>
      <a href="/hilfe.html">Hilfe / FAQ</a>
      <a href="/impressum.html">Impressum</a>
      <a href="/datenschutz.html">Datenschutz</a>
    </nav>
  </header>

  <div class="progress-bar">
    <div class="prog-step done">
      <div class="prog-dot">✓</div>
      <span>Vertragsart</span>
    </div>
    <div class="prog-line"></div>
    <div class="prog-step done">
      <div class="prog-dot">✓</div>
      <span>Ihre Daten</span>
    </div>
    <div class="prog-line"></div>
    <div class="prog-step active">
      <div class="prog-dot">3</div>
      <span>Fertiges Dokument</span>
    </div>
  </div>

  <div class="page-title">
    <h1>Ihre Kündigung ist fertig</h1>
    <div class="sub">Wählen Sie, wie Sie möchten Sie weitermachen: Wir versenden für Sie — oder Sie laden das PDF kostenlos herunter.</div>
  </div>

  <div class="pv-toolbar">
    <form id="backToForm" method="POST" action="/formular.php" style="margin:0;">
      <?php
        $backFields = ['type','anbieter','firstName','lastName','street','zip','city','email',
                       'studioStreet','studioZip','studioCity',
                       'terminationMode','terminationDate','contractNo','plate',
                       'sendEmail','providerEmail','trustpilotConsent'];
        foreach ($backFields as $bf):
          $bv = (string)($_POST[$bf] ?? '');
        ?>
        <input type="hidden" name="<?= htmlspecialchars($bf) ?>" value="<?= htmlspecialchars($bv) ?>">
      <?php endforeach; ?>
      <input type="hidden" name="back_from_preview" value="1">
      <button type="submit" class="pv-back"><span>←</span> <span>Daten korrigieren</span></button>
    </form>
    <div class="pv-toolbar-hint">Bitte prüfen — alles korrekt?</div>
  </div>

  <div class="pv-paper-frame" id="paperFrame">
    <iframe
      title="Brief-Vorschau"
      sandbox="allow-same-origin allow-scripts"
      srcdoc="<?= $iframeContent ?>"
      id="briefFrame"></iframe>
  </div>

  <div class="pv-actions">

    <div class="pv-featured">
      <h2>Wir versenden Ihre Kündigung für Sie</h2>
      <p class="pv-sub">
        Sie tippen — wir erledigen den Rest: ausdrucken, kuvertieren, frankieren und in den Briefkasten werfen.
        Kein Drucker nötig, kein Postgang.
      </p>

      <ul class="pv-benefits">
        <li><span><strong>Druck, Kuvertierung &amp; Versand</strong> — alles inklusive</span></li>
        <li><span>Zustellung in <strong>1–3 Werktagen</strong> via Deutsche Post</span></li>
        <li><span>Wahlweise <strong>Standard</strong> oder <strong>Einwurfeinschreiben</strong> mit Zustellnachweis</span></li>
        <li><span>PDF-Kopie automatisch zum Download</span></li>
      </ul>

      <form method="post" action="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'kuendigungexpress.de', ENT_QUOTES) ?>/stripe-checkout.php" id="versandForm" style="margin:0;">
        <?= $hiddenInputsVersand ?>
        <input type="hidden" name="signature" id="versandSignatureField" value="">
        <input type="hidden" name="versandTier" id="versandTierField" value="standard">
        <input type="hidden" name="agbAccepted" id="agbAcceptedField" value="0">
        <input type="hidden" name="widerrufWaiver" id="widerrufWaiverField" value="0">

        <div class="pv-pricing">
          <label class="pv-price-card pv-tier-radio is-selected" data-tier="standard">
            <input type="radio" name="versandTierUi" value="standard" checked>
            <span class="pv-price-label">Standard</span>
            <span class="pv-price-val">3,99 €</span>
            <span class="pv-price-meta">Normaler Versand</span>
          </label>
          <label class="pv-price-card pv-tier-radio" data-tier="einschreiben">
            <input type="radio" name="versandTierUi" value="einschreiben">
            <span class="pv-price-label">Einwurfeinschreiben</span>
            <span class="pv-price-val">8,99 €</span>
            <span class="pv-price-meta">Mit Zustellnachweis</span>
          </label>
        </div>

        <div class="pv-email-block">
          <label for="versandEmailInput">Ihre E-Mail-Adresse <span class="pv-req">*</span></label>
          <input type="email" name="email" id="versandEmailInput" required
                 value="<?= htmlspecialchars($email) ?>"
                 placeholder="ihre@email.de" autocomplete="email" inputmode="email">
          <small>Erforderlich für Bestellbestätigung und ggf. Zustellnachweis.</small>
        </div>

        <button type="button" class="pv-cta-primary" id="versandBtn">
          <span class="pv-cta-icon">📨</span>
          Versand bestellen
        </button>
      </form>

    </div>

    <div class="pv-secondary">
      <div class="pv-sec-icon">📄</div>
      <div class="pv-sec-text">
        <h3>Nur das PDF</h3>
        <p>Direkt im Browser laden — <strong>ohne Anmeldung, ohne E-Mail, ohne Wartezeit</strong>.</p>
      </div>
      <form method="post" action="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'kuendigungexpress.de', ENT_QUOTES) ?>/generate.php" style="margin:0;">
        <?= $hiddenInputs ?>
        <button type="submit" class="pv-cta-secondary">PDF kostenlos laden →</button>
      </form>
    </div>

  </div>
</div>

<div class="ke-trust-strip">
  <span>🔒 SSL-verschlüsselt</span>
  <span>🛡️ DSGVO-konform</span>
  <span>⚖️ Muster-Vorlage, keine Rechtsberatung</span>
</div>

<footer class="ke-footer">
  <p>© 2026 KündigungExpress · <a href="/impressum.html">Impressum</a> · <a href="/datenschutz.html">Datenschutz</a> · <a href="/agb.html">AGB</a> · <a href="/hilfe.html">Hilfe</a> · <a href="#" onclick="return keResetConsent(event)">Cookie-Einstellungen</a></p>
</footer>
<script>
function keResetConsent(e) {
  if (e && e.preventDefault) e.preventDefault();
  try {
    localStorage.removeItem('cookieConsent');
    document.cookie = 'cookieConsent=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
  } catch(err) {}
  location.reload();
  return false;
}
</script>

<script>
(function() {
  var iframe = document.getElementById('briefFrame');
  var paperFrame = document.getElementById('paperFrame');
  if (!iframe || !paperFrame) return;

  var A4_WIDTH = 794; 
  var contentHeight = 1123; 

  function measureContent() {
    try {
      var doc = iframe.contentDocument || iframe.contentWindow.document;
      if (!doc || !doc.body) return contentHeight;
      var sig = doc.querySelector('table.sig-table');
      if (sig) {
        return sig.offsetTop + sig.offsetHeight + 40;
      }
      return Math.max(doc.body.scrollHeight, doc.documentElement.scrollHeight);
    } catch (e) {
      return contentHeight;
    }
  }

  function applyScale() {
    var containerWidth = paperFrame.clientWidth;
    if (containerWidth < 10) return; 

    var scale = Math.min(1, containerWidth / A4_WIDTH);
    iframe.style.transform = 'scale(' + scale + ')';
    iframe.style.height = contentHeight + 'px';
    paperFrame.style.height = Math.round(contentHeight * scale) + 'px';
  }

  function refresh() {
    contentHeight = measureContent();
    applyScale();
  }

  iframe.addEventListener('load', refresh);
  setTimeout(refresh, 200);
  setTimeout(refresh, 600);
  setTimeout(refresh, 1200);

  var resizeTimer;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(applyScale, 100);
  });
})();
</script>

<div class="ke-modal-overlay" id="upsellModal">
  <div class="ke-modal-box">
    <div class="ke-modal-icon">⏳</div>
    <div class="ke-modal-title">Einen Moment noch...</div>
    <div class="ke-modal-text" id="upsellText"></div>
    <a href="#" target="_blank" rel="nofollow sponsored" class="ke-modal-cta" id="upsellCta">🔥 Tarife vergleichen</a>
    <button type="button" class="ke-modal-skip" id="upsellSkip">Nein danke, PDF jetzt herunterladen</button>
  </div>
</div>

<script>
(function() {
  'use strict';

  var pdfForm = document.querySelector('.pv-secondary form');
  if (!pdfForm) return;

  var modal = document.getElementById('upsellModal');
  var modalText = document.getElementById('upsellText');
  var modalCta = document.getElementById('upsellCta');
  var modalSkip = document.getElementById('upsellSkip');

  function getVal(name) {
    var el = pdfForm.querySelector('input[name="' + name + '"]');
    return el ? el.value : '';
  }

  function buildAffiliate(type, anbieterRaw) {
    var anbieter = anbieterRaw.toLowerCase();
    if (type === 'handy') {
      if (anbieter.indexOf('telekom') !== -1 || anbieter.indexOf('congstar') !== -1 || anbieter.indexOf('fraenk') !== -1) {
        return {
          link: 'https://www.tariffuxx.de/handytarife?r=1126248&subid=modal_pv',
          text: 'Sie kündigen bei <strong>' + escapeHtml(anbieterRaw) + '</strong>. Wussten Sie, dass Sie Ihre Rufnummer mitnehmen und im gleichen Netz bleiben können, aber bis zu 50% sparen?',
          btn: '🔥 Im gleichen Netz bleiben & sparen',
          color: '#3B82F6'
        };
      }
      if (anbieter.indexOf('o2') !== -1 || anbieter.indexOf('vodafone') !== -1 || anbieter.indexOf('drillisch') !== -1 || anbieter.indexOf('1&1') !== -1 || anbieter.indexOf('freenet') !== -1 || anbieter.indexOf('telefonica') !== -1) {
        return {
          link: 'https://www.awin1.com/awclick.php?gid=361937&mid=11430&awinaffid=2838186&linkid=4581533&clickref=modal_pv',
          text: 'Schlechtes Netz bei <strong>' + escapeHtml(anbieterRaw) + '</strong>? Sichern Sie sich jetzt das beste D1-Netz Deutschlands (Telekom) und nehmen Sie Ihre Rufnummer einfach mit.',
          btn: '🔥 Zum besten Netz Deutschlands wechseln',
          color: '#E20074'
        };
      }
      return {
        link: 'https://a.check24.net/misc/click.php?pid=1169420&aid=18&deep=handytarife&cat=7',
        text: 'Sie kündigen bei <strong>' + escapeHtml(anbieterRaw) + '</strong>. Zahlen Sie künftig nicht mehr als nötig! Vergleichen Sie jetzt Tarife und sichern Sie sich exklusive Wechselboni.',
        btn: '🔥 Handytarife vergleichen & sparen',
        color: '#1E40AF'
      };
    }
    if (type === 'kfz') {
      return {
        link: 'https://a.partner-versicherung.de/click.php?partner_id=201450&ad_id=15&deep=kfz-versicherung',
        text: 'Die Kündigung bei <strong>' + escapeHtml(anbieterRaw) + '</strong> wird vorbereitet. Wussten Sie, dass Sie bei einem Wechsel der KFZ-Versicherung oft bis zu 850 € im Jahr sparen können?',
        btn: '🚗 KFZ-Tarife vergleichen & sparen',
        color: '#1E40AF'
      };
    }
    return null;
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function(c) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
    });
  }

  var modalAlreadyShown = false;
  function submitForm() {
    HTMLFormElement.prototype.submit.call(pdfForm);
  }

  pdfForm.addEventListener('submit', function(e) {
    if (modalAlreadyShown) return; 
    e.preventDefault();

    var type = getVal('type');
    var anbieter = getVal('anbieter');
    var aff = buildAffiliate(type, anbieter);

    if (!aff) {
      modalAlreadyShown = true;
      submitForm();
      return;
    }

    modalText.innerHTML = aff.text;
    modalCta.innerText = aff.btn;
    modalCta.style.background = aff.color;
    modalCta.href = aff.link;
    modal.classList.add('active');

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      'event': 'affiliate_modal_view',
      'contract_type': type,
      'provider': anbieter
    });
  });

  if (modalCta) {
    modalCta.addEventListener('click', function() {
      var type = getVal('type');
      var anbieter = getVal('anbieter');
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        'event': 'affiliate_modal_click',
        'contract_type': type,
        'provider': anbieter
      });

      setTimeout(function() {
        modal.classList.remove('active');
        modalAlreadyShown = true;
        submitForm();
      }, 150);
    });
  }
  if (modalSkip) {
    modalSkip.addEventListener('click', function() {
      modal.classList.remove('active');
      modalAlreadyShown = true;
      submitForm();
    });
  }
})();
</script>

<div class="sig-modal-overlay" id="sigModal">
  <div class="sig-modal-box">
    <div class="sig-modal-title">Bitte unterschreiben Sie</div>
    <div class="sig-modal-sub">
      Ihre Unterschrift wird auf das Schreiben gedruckt — rechtsverbindlich für die Kündigung.
    </div>
    <div class="sig-summary">
      <span class="sig-summary-icon">📨</span>
      <div class="sig-summary-text">
        <strong id="sigSummaryTierName">Standardbrief</strong> an <strong><?= htmlspecialchars($studio !== '' ? $studio : 'Ihren Anbieter') ?></strong>
      </div>
      <span class="sig-summary-price" id="sigSummaryPrice">3,99 €</span>
    </div>

    <div class="sig-modal-tier" id="sigTierLabel" style="display:none;">Versand Standard · 3,99 €</div>

    <div class="sig-rotate-hint">💡 Tipp: Drehen Sie das Handy für eine breitere Unterschriftsfläche</div>

    <div class="sig-canvas-wrap" id="sigCanvasWrap">
      <canvas id="sigCanvas"></canvas>
    </div>

    <div class="sig-actions-secondary">
      <button type="button" class="sig-action-link" id="sigClearBtn">Löschen</button>
      <button type="button" class="sig-action-link" id="sigUndoBtn">Letzten Strich rückgängig</button>
    </div>

    <div class="sig-checkboxes">
      <label class="sig-check-row">
        <input type="checkbox" id="sigAgbCheck">
        <span>Ich habe die <a href="/agb.html" target="_blank" rel="noopener">AGB</a> und die <a href="/datenschutz.html" target="_blank" rel="noopener">Datenschutzerklärung</a> gelesen und akzeptiere sie.</span>
      </label>
      <label class="sig-check-row">
        <input type="checkbox" id="sigWiderrufCheck">
        <span>Ich stimme ausdrücklich zu, dass mit dem Versand-Service vor Ablauf der Widerrufsfrist begonnen wird. Mir ist bekannt, dass mein <a href="/agb.html" target="_blank" rel="noopener">Widerrufsrecht</a> mit vollständiger Vertragserfüllung erlischt.</span>
      </label>
    </div>

    <div class="sig-email-field" id="sigEmailField">
      <label>E-Mail für Bestätigung <small>(empfohlen, optional)</small></label>
      <input type="email" id="sigEmailInput" placeholder="ihre@email.de" autocomplete="email">
    </div>

    <button type="button" class="sig-cta-confirm" id="sigConfirmBtn" disabled>
      Weiter zur Zahlung →
    </button>
    <button type="button" class="sig-cancel" id="sigCancelBtn">Abbrechen</button>

    <div class="sig-legal">
      Mit dem Klick auf "Weiter zur Zahlung" bestätigen Sie, dass die Unterschrift Ihre eigene ist
      und Sie zur Kündigung berechtigt sind.
    </div>
  </div>
</div>

<script src="/signature_pad.min.js"></script>
<script>
(function() {
  'use strict';

  var tierRadios = document.querySelectorAll('.pv-tier-radio');
  var tierField = document.getElementById('versandTierField');
  tierRadios.forEach(function(card) {
    card.addEventListener('click', function() {
      tierRadios.forEach(function(c) { c.classList.remove('is-selected'); });
      card.classList.add('is-selected');
      var radio = card.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
      var tier = card.getAttribute('data-tier');
      if (tier && tierField) tierField.value = tier;
    });
  });

  var modal = document.getElementById('sigModal');
  var canvas = document.getElementById('sigCanvas');
  var canvasWrap = document.getElementById('sigCanvasWrap');
  var confirmBtn = document.getElementById('sigConfirmBtn');
  var clearBtn = document.getElementById('sigClearBtn');
  var undoBtn = document.getElementById('sigUndoBtn');
  var cancelBtn = document.getElementById('sigCancelBtn');
  var tierLabel = document.getElementById('sigTierLabel');
  var versandBtn = document.getElementById('versandBtn');
  var versandForm = document.getElementById('versandForm');
  var sigField = document.getElementById('versandSignatureField');
  var agbCheck = document.getElementById('sigAgbCheck');
  var widerrufCheck = document.getElementById('sigWiderrufCheck');
  var agbField = document.getElementById('agbAcceptedField');
  var widerrufField = document.getElementById('widerrufWaiverField');

  var signaturePad = null;

  function updateConfirmBtn() {
    var hasSignature = signaturePad && !signaturePad.isEmpty();
    var agbOk = agbCheck && agbCheck.checked;
    var widerrufOk = widerrufCheck && widerrufCheck.checked;
    confirmBtn.disabled = !(hasSignature && agbOk && widerrufOk);
    canvasWrap.classList.toggle('has-signature', !!hasSignature);
  }

  if (agbCheck) agbCheck.addEventListener('change', updateConfirmBtn);
  if (widerrufCheck) widerrufCheck.addEventListener('change', updateConfirmBtn);

  function initSignaturePad() {
    if (signaturePad || typeof SignaturePad === 'undefined') return;
    var ratio = Math.max(window.devicePixelRatio || 1, 1);
    var rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    var ctx = canvas.getContext('2d');
    ctx.scale(ratio, ratio);

    signaturePad = new SignaturePad(canvas, {
      backgroundColor: 'rgba(255,255,255,0)',
      penColor: '#0F172A',
      minWidth: 0.8,
      maxWidth: 2.4,
      throttle: 16,
      velocityFilterWeight: 0.7
    });

    signaturePad.addEventListener('endStroke', updateConfirmBtn);
  }

  var summaryTierName = document.getElementById('sigSummaryTierName');
  var summaryPrice = document.getElementById('sigSummaryPrice');

  function updateSummary(tier) {
    if (summaryTierName && summaryPrice) {
      summaryTierName.textContent = tier === 'einschreiben' ? 'Einwurfeinschreiben' : 'Standardbrief';
      summaryPrice.textContent = tier === 'einschreiben' ? '8,99 €' : '3,99 €';
    }
    if (tierLabel) {
      tierLabel.textContent = tier === 'einschreiben'
        ? 'Versand mit Einwurfeinschreiben · 8,99 €'
        : 'Versand Standard · 3,99 €';
    }
  }
  tierRadios.forEach(function(card) {
    card.addEventListener('click', function() {
      var t = card.getAttribute('data-tier');
      if (t) updateSummary(t);
    });
  });

  var emailFieldWrap = document.getElementById('sigEmailField');
  var emailInput = document.getElementById('sigEmailInput');
  var hiddenEmailField = versandForm ? versandForm.querySelector('input[name="email"]') : null;

  function openModal() {
    // Email-ul e acum required + vizibil în form, deci sig-email-field nu mai apare
    if (emailFieldWrap) emailFieldWrap.classList.remove('is-visible');

    var selectedTier = document.querySelector('.pv-tier-radio.is-selected');
    var tier = selectedTier ? selectedTier.getAttribute('data-tier') : 'standard';
    updateSummary(tier);

    if (agbCheck) agbCheck.checked = false;
    if (widerrufCheck) widerrufCheck.checked = false;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    setTimeout(function() {
      initSignaturePad();
      if (signaturePad) {
        signaturePad.clear();
        canvasWrap.classList.remove('has-signature');
      }
      updateConfirmBtn();
    }, 50);
  }

  function closeModal() {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (versandBtn) versandBtn.addEventListener('click', function() {
    // Validează email înainte de a deschide modal-ul de semnătură
    if (hiddenEmailField) {
      var emailVal = (hiddenEmailField.value || '').trim();
      var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (emailVal === '' || !emailRegex.test(emailVal)) {
        hiddenEmailField.focus();
        // Browser folosește validare nativă cu :invalid CSS; afișăm și mesaj clar
        if (typeof hiddenEmailField.reportValidity === 'function') {
          hiddenEmailField.reportValidity();
        } else {
          alert('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
        }
        return;
      }
    }
    openModal();
  });
  if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
  if (clearBtn) {
    clearBtn.addEventListener('click', function() {
      if (signaturePad) {
        signaturePad.clear();
        canvasWrap.classList.remove('has-signature');
        updateConfirmBtn();
      }
    });
  }
  if (undoBtn) {
    undoBtn.addEventListener('click', function() {
      if (!signaturePad) return;
      var data = signaturePad.toData();
      if (data.length > 0) {
        data.pop();
        signaturePad.fromData(data);
        updateConfirmBtn();
      }
    });
  }
  if (confirmBtn) {
    confirmBtn.addEventListener('click', function() {
      if (!signaturePad || signaturePad.isEmpty()) return;
      if (!agbCheck || !agbCheck.checked || !widerrufCheck || !widerrufCheck.checked) return;

      var dataURL = getCroppedSignatureDataURL(canvas);
      if (sigField) sigField.value = dataURL;

      if (agbField) agbField.value = '1';
      if (widerrufField) widerrufField.value = '1';

      var confirmInput = versandForm.querySelector('input[name="confirm"]');
      if (confirmInput) {
        confirmInput.parentNode.removeChild(confirmInput);
      }

      var selectedTier = document.querySelector('.pv-tier-radio.is-selected');
      if (selectedTier && tierField) tierField.value = selectedTier.getAttribute('data-tier') || 'standard';

      // Fallback: dacă cumva hiddenEmailField e gol (nu ar trebui — required în form),
      // încercăm să folosim sig-email-input ca rezervă
      if (hiddenEmailField && (hiddenEmailField.value || '').trim() === '' && emailInput) {
        var typedEmail = (emailInput.value || '').trim();
        if (typedEmail !== '') hiddenEmailField.value = typedEmail;
      }

      confirmBtn.disabled = true;
      confirmBtn.textContent = 'Weiter zur Zahlung...';
      versandForm.submit();
    });
  }

  function getCroppedSignatureDataURL(srcCanvas) {
    var ctx = srcCanvas.getContext('2d');
    var w = srcCanvas.width;
    var h = srcCanvas.height;
    var imageData;
    try {
      imageData = ctx.getImageData(0, 0, w, h);
    } catch (e) {
      ctx.save();
      ctx.globalCompositeOperation = 'destination-over';
      ctx.fillStyle = '#FFFFFF';
      ctx.fillRect(0, 0, w, h);
      ctx.restore();
      return srcCanvas.toDataURL('image/png');
    }
    var data = imageData.data;
    var minX = w, minY = h, maxX = -1, maxY = -1;

    for (var y = 0; y < h; y++) {
      for (var x = 0; x < w; x++) {
        var alpha = data[(y * w + x) * 4 + 3];
        if (alpha > 0) {
          if (x < minX) minX = x;
          if (x > maxX) maxX = x;
          if (y < minY) minY = y;
          if (y > maxY) maxY = y;
        }
      }
    }

    if (maxX < 0) {
      return srcCanvas.toDataURL('image/png');
    }

    var pad = 8;
    minX = Math.max(0, minX - pad);
    minY = Math.max(0, minY - pad);
    maxX = Math.min(w - 1, maxX + pad);
    maxY = Math.min(h - 1, maxY + pad);
    var cropW = maxX - minX + 1;
    var cropH = maxY - minY + 1;

    var croppedCanvas = document.createElement('canvas');
    croppedCanvas.width = cropW;
    croppedCanvas.height = cropH;
    var croppedCtx = croppedCanvas.getContext('2d');
    croppedCtx.fillStyle = '#FFFFFF';
    croppedCtx.fillRect(0, 0, cropW, cropH);
    croppedCtx.drawImage(srcCanvas, minX, minY, cropW, cropH, 0, 0, cropW, cropH);
    return croppedCanvas.toDataURL('image/png');
  }

  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) closeModal();
    });
  }

  window.addEventListener('resize', function() {
    if (modal.classList.contains('active') && signaturePad) {
      var data = signaturePad.toData();
      var ratio = Math.max(window.devicePixelRatio || 1, 1);
      var rect = canvas.getBoundingClientRect();
      canvas.width = rect.width * ratio;
      canvas.height = rect.height * ratio;
      canvas.getContext('2d').scale(ratio, ratio);
      signaturePad.clear();
      signaturePad.fromData(data);
    }
  });
})();
</script>

</body></html>
<?php
    exit;
}

/* =========================================================
   PDF GENERATION (only reached when confirm=1)
   ========================================================= */
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

$providerSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower((string)@iconv('UTF-8', 'ASCII//TRANSLIT', $studio)));
$providerSlug = trim($providerSlug, '-');
$providerSlug = $providerSlug !== '' ? $providerSlug : 'anbieter';

$filename = 'Kuendigung-' . $providerSlug . '_' . bin2hex(random_bytes(4)) . '.pdf';
file_put_contents($pdfDir . '/' . $filename, $dompdf->output());

/* Contor all-time (create-if-missing, NU se șterge de cron) */
$counterFile = __DIR__ . '/_counter.txt';
$count = is_file($counterFile) ? (int)trim((string)@file_get_contents($counterFile)) : 0;
@file_put_contents($counterFile, (string)($count + 1), LOCK_EX);

$dataDir = __DIR__ . '/_data';
if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);

/* Log append-only de PDF-uri (supraviețuiește cron-ului, ca affiliate_clicks.log).
   Format: ts \t type \t provider \t emailSent \t hasEmail */
$pdfLogLine = implode("\t", [
    time(),
    $type,
    str_replace(["\t", "\n", "\r"], ' ', (string)$studio),
    (isset($sendEmail) && $sendEmail === '1' && !empty($providerEmail)) ? '1' : '0',
    !empty($email) ? '1' : '0',
]) . "\n";
@file_put_contents($dataDir . '/pdf_generated.log', $pdfLogLine, FILE_APPEND | LOCK_EX);

$trackingFile = $dataDir . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.json';
$trackingRecord = [
    'createdAt'   => time(),
    'type'        => $type,                                        
    'provider'    => $studio,                                      
    'emailSent'   => isset($sendEmail) && $sendEmail === '1' && !empty($providerEmail),
    'hasContract' => !empty($rawContract),
    'hasEmail'    => !empty($email),
    'tpConsent'   => $trustpilotConsent === '1',
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
} elseif ($type === 'bank') {
    $mailBody = "Sehr geehrte Damen und Herren,\n\nanbei erhalten Sie meine Kündigung als PDF.\n\nHiermit kündige ich mein Girokonto fristgerecht " . strip_tags($terminationLine) . " gemäß § 675h BGB. Ein etwaiges Restguthaben überweisen Sie bitte auf mein angegebenes Konto.\n\nMit freundlichen Grüßen\n" . $name;
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
            $mail->addReplyTo($email, $name);
        }
        $mail->addAttachment($pdfDir . '/' . $filename, 'Kuendigung-' . $providerSlug . '.pdf');
        $mail->Subject = ($type === 'kfz' ? 'Kündigung meiner KFZ-Versicherung – ' : ($type === 'handy' ? 'Kündigung meines Mobilfunkvertrags – ' : ($type === 'bank' ? 'Kündigung meines Girokontos – ' : 'Kündigung meiner Fitnessstudio-Mitgliedschaft – '))) . $name;
        $mail->Body = $mailBody;
        $mail->send();
        $emailSent = true;
    } catch (Exception $e) {
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
        if ($trustpilotConsent === '1') {
            $confirm->addBCC('kuendigungexpress.de+5dff45cab6@invite.trustpilot.com');
        }
        $confirm->addAttachment($pdfDir . '/' . $filename, 'Kuendigung-' . $providerSlug . '.pdf');
        $confirm->isHTML(true);
        $confirm->Subject = 'Ihre Kündigung an ' . $studio . ' — PDF im Anhang';
        $confirm->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b; line-height: 1.6; background-color: #ffffff; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px;'>
            <div style='margin-bottom: 24px;'>
                <h2 style='margin: 0 0 12px 0; font-size: 22px; color: #0f172a; font-weight: 800; letter-spacing: -0.02em;'>Ihr Kündigungsschreiben</h2>
                <p style='margin: 0; font-size: 15px; color: #475569;'>Guten Tag " . htmlspecialchars($firstName) . ",<br><br>im Anhang finden Sie Ihr fertiges Kündigungsschreiben für <strong>" . htmlspecialchars($studio) . "</strong> als PDF.</p>
            </div>
            <div style='margin-bottom: 24px; padding-top: 8px;'>
                <h3 style='margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #0f172a;'>Nächste Schritte</h3>
                <ol style='margin: 0; padding-left: 20px; font-size: 14px; color: #475569;'>
                    <li style='margin-bottom: 8px;'>Ausdrucken und eigenhändig unterschreiben.</li>
                    <li style='margin-bottom: 8px;'>Per Einschreiben mit Rückschein versenden.</li>
                    <li style='margin-bottom: 8px;'>Eingangsbestätigung mit Vertragsende aufbewahren.</li>
                </ol>
            </div>
            <div style='background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; margin-bottom: 24px; font-size: 14px; color: #166534; font-weight: 500;'>
                💡 <strong>Tipp:</strong> Wir bieten auch einen bequemen Versand-Service an — wir drucken, kuvertieren und versenden Ihre Kündigung für Sie. Besuchen Sie <a href='https://kuendigungexpress.de' style='color: #166534; font-weight: 700;'>kuendigungexpress.de</a> für weitere Informationen.
            </div>
            <div style='border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 14px; color: #475569;'>
                <p style='margin: 0 0 12px 0;'>Bei Fragen antworten Sie einfach auf diese E-Mail.</p>
                <p style='margin: 0; font-weight: 700; color: #0f172a;'>Mit freundlichen Grüßen,<br><span style='color: #16a34a;'>KündigungExpress</span></p>
            </div>
            <div style='margin-top: 24px; padding-top: 12px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #94a3b8;'>
                <a href='https://kuendigungexpress.de' style='color: #94a3b8; text-decoration: none;'>kuendigungexpress.de</a>
            </div>
        </div>";
        $confirm->send();
    } catch (Exception $e) {
    }
}

/* =========================================================
   SUCCESS PAGE
   ========================================================= */
$isHandy = $type === 'handy';
$isKfz   = $type === 'kfz';
$isBank  = $type === 'bank';
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
body{font-family:Arial,sans-serif !important;background:var(--bg) !important;color:var(--text) !important;min-height:auto !important;display:block !important}
.wrap{max-width:640px;margin:0 auto;padding:32px 20px 8px;display:flex;flex-direction:column;gap:16px}
.success-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:20px 32px;text-align:center;box-shadow:0 8px 32px rgba(22,163,74,0.08)}
.check-circle{width:68px;height:68px;background:linear-gradient(135deg,#16A34A,#22C55E);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:34px;color:#fff;font-weight:900;line-height:1;box-shadow:0 6px 18px rgba(22,163,74,0.3)}
.saved-banner{background:#F0FDF4;border:1px solid rgba(22,163,74,0.25);border-radius:12px;padding:12px 16px;font-size:14px;color:#15803D;font-weight:600;margin-bottom:18px;line-height:1.5;}
.success-card h1{font-size:clamp(20px,4vw,26px);font-weight:900;margin-bottom:8px;font-family:Arial,sans-serif !important;letter-spacing:-0.01em}
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
.affiliate-card{background:var(--card);border:2px solid rgba(22,163,74,0.3);border-radius:24px;padding:28px 28px 24px;box-shadow:0 8px 32px rgba(22,163,74,0.10)}
.affiliate-card h2{font-size:18px;font-weight:900;margin-bottom:8px;color:var(--text);text-align:center;font-family:Arial,sans-serif !important;letter-spacing:-0.01em}
.affiliate-card p{font-size:14px;color:var(--muted);line-height:1.65;margin-bottom:18px;text-align:center}
.aff-btn{display:block;text-align:center;font-weight:800;font-size:15px;padding:15px 20px;border-radius:14px;text-decoration:none;transition:filter .15s;margin-bottom:10px}
.aff-btn:hover{filter:brightness(.92)}
.saved-line{background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:10px 14px;font-size:13px;color:#166534;text-align:center;margin-top:12px;line-height:1.5}
.aff-btn-check24{background:#1E40AF;color:#fff}
.review-btns{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
.review-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:12px;font-size:13px;font-weight:800;text-decoration:none;border:1px solid var(--border);color:var(--text);background:var(--bg);transition:border-color .15s}
.review-btn:hover{border-color:#94A3B8}
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
.engagement-strip { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 24px; max-width: 560px; margin-left: auto; margin-right: auto; }
.eng-col { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 18px 16px; text-align: center; display: flex; flex-direction: column; }
.eng-icon { font-size: 22px; margin-bottom: 6px; line-height: 1; }
.eng-review .eng-icon { color: #f59e0b; font-size: 14px; letter-spacing: 1px; }
.eng-col h3 { font-size: 13px; font-weight: 800; color: var(--text); margin-bottom: 4px; }
.eng-col p { font-size: 12px; color: var(--muted); margin-bottom: 10px; line-height: 1.45; }
.eng-col .share-copied { display: none; color: #15803D; font-weight: 700; font-size: 12px; margin-top: 6px; }
.eng-col .review-btns { display: flex; flex-direction: column; gap: 6px; }
.eng-col .review-btn { display: block; font-size: 12px; font-weight: 700; padding: 8px 10px; border-radius: 8px; text-decoration: none; text-align: center; border: 1px solid var(--border); color: var(--text); background: #fff; }
.eng-col .review-btn:hover { background: #f8fafc; }
.eng-col .share-btn { font-size: 12px; padding: 8px 14px; background: var(--green); color: #fff; border: 0; border-radius: 8px; font-weight: 700; cursor: pointer; width: 100%; justify-content: center; text-align: center; }

.ke-trust-strip {
  max-width: 880px;
  margin: 0 auto;
  padding: 16px 24px 8px;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 12px 20px;
  font-size: 12px;
  color: #64748B;
  text-align: center;
}
.ke-trust-strip span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  white-space: nowrap;
}
.ke-footer {
  max-width: 880px;
  margin: 0 auto;
  padding: 8px 24px 24px;
  text-align: center;
}
.ke-footer p {
  font-size: 12px;
  color: #94A3B8;
  line-height: 1.6;
  margin: 0 0 4px;
  text-align: center;
}
.ke-footer a {
  color: #64748B;
  text-decoration: none;
}
.ke-footer a:hover {
  color: #0F172A;
  text-decoration: underline;
}

@media(max-width:820px){ .site-header{display:flex !important} body{padding-top:56px} .wrap{padding-top:16px} }
@media(max-width:480px){ .success-card,.affiliate-card{padding:24px 18px;border-radius:18px} .engagement-strip{grid-template-columns:1fr;gap:10px} }
@media (max-width: 720px) {
  .ke-trust-strip { padding: 14px 16px 4px; gap: 8px 14px; font-size: 11px; }
  .ke-footer { padding: 4px 16px 20px; }
  .ke-footer p { font-size: 11.5px; }
}

.product-card {
    display: flex; 
    align-items: center; 
    gap: 14px; 
    padding: 12px 14px; 
    border: 1px solid var(--border); 
    border-radius: 14px; 
    text-decoration: none; 
    background: #F8FAFC; 
    transition: all 0.2s; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.product-card:hover {
    border-color: #CBD5E1;
    background: #F1F5F9;
    transform: translateY(-1px);
}
</style>
<meta name="ke-provider" content="<?= htmlspecialchars($studio, ENT_QUOTES) ?>">
<meta name="ke-type" content="<?= htmlspecialchars($type, ENT_QUOTES) ?>">

<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
    'event': 'generate_success',
    'conversion_type': 'free_pdf',
    'contract_type': '<?= htmlspecialchars($type, ENT_QUOTES) ?>',
    'provider': '<?= htmlspecialchars($studio, ENT_QUOTES) ?>',
    'email_sent': <?= $emailSent ? 'true' : 'false' ?>
});
</script>

<script src="/clarity-loader.js" async></script>
<link rel="preload" href="/style.css?v=14" as="style"> <link rel="stylesheet" href="/style.css?v=14"></head>
<body>
<header class="site-header"><a href="/" class="brand">KündigungExpress</a></header>
<div class="wrap">

  <div class="success-card">
    <div class="check-circle">✓</div>
    <div class="saved-banner">
      🎉 Kostenlos — andere Dienste verlangen <strong>Geld</strong> für dieses Dokument.
    </div>
    <h1>Ihr Kündigungsschreiben ist fertig</h1>
    <p>Dein PDF wird automatisch heruntergeladen (Bitte kurz warten...). Drucke es anschließend aus und verschicke es.</p>
    <a class="btn-download" id="autoDownloadBtn" href="<?= htmlspecialchars($downloadUrl) ?>" download="Kuendigung-<?= htmlspecialchars($providerSlug) ?>.pdf">
      Download startet nicht? Hier klicken.
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
       class="aff-btn aff-btn-check24" target="_blank" rel="nofollow sponsored" data-aff="check24-kfz"
       onclick="window.dataLayer = window.dataLayer || []; window.dataLayer.push({'event':'affiliate_click', 'network':'check24', 'vertical':'kfz'});">
      KFZ-Versicherung vergleichen · CHECK24
    </a>
    
    <?php elseif ($isBank): ?>
    <h2>Fast geschafft: Kontowechsel abschließen</h2>
    <p>Ihre Kündigung bei <strong><?= htmlspecialchars($studio) ?></strong> ist vorbereitet. Denken Sie daran, Ihr Restguthaben zu übertragen sowie Daueraufträge und Lastschriften rechtzeitig umzuziehen, bevor das Konto geschlossen wird.</p>

    <?php elseif ($isHandy): ?>
    <h2>Nächster Schritt: Günstigeren Tarif sichern</h2>
    <p>Die Kündigung bei <strong><?= htmlspecialchars($studio) ?></strong> ist vorbereitet. Vergleichen Sie jetzt Tarife und sparen Sie Geld.</p>
    <a href="https://www.tariffuxx.de/handytarife?r=1126248&subid=generate-<?= htmlspecialchars($providerSlug) ?>"
       class="aff-btn" style="background:#3B82F6;color:#fff;margin-bottom:12px;" target="_blank" rel="nofollow sponsored" data-aff="tariffuxx-generate"
       onclick="window.dataLayer = window.dataLayer || []; window.dataLayer.push({'event':'affiliate_click', 'network':'tariffuxx', 'vertical':'handy'});">
      Tarife im gleichen Netz vergleichen →
    </a>
    <div style="text-align: center; font-size: 13px;">
      oder <a href="https://a.check24.net/misc/click.php?pid=1169420&aid=18&deep=handytarife&cat=7" target="_blank" rel="nofollow sponsored" style="color: var(--muted); text-decoration: underline; font-weight: 600;"
      onclick="window.dataLayer = window.dataLayer || []; window.dataLayer.push({'event':'affiliate_click', 'network':'check24', 'vertical':'handy'});">alle Anbieter bei CHECK24 vergleichen</a>
    </div>
    
    <?php else: ?>
    <h2>Zuhause fit bleiben – Die 3 Essentials</h2>
    <p style="margin-bottom: 16px;">Sie verlassen <strong><?= htmlspecialchars($studio) ?></strong>? Mit diesem Basic-Setup sparen Sie sich das Studio-Abo dauerhaft:</p>
    
    <div style="text-align: left; display: flex; flex-direction: column; gap: 10px; margin-bottom: 14px;">
        <a href="https://www.amazon.de/SONGMICS-Hantelst%C3%A4nder-Neopren-Beschichtung-Krafttraining-Fitnessstudio/dp/B07P494CC7?__mk_de_DE=%C3%85M%C3%85%C5%BD%C3%95%C3%91&crid=3HWZ0X8PKHD00&dib=eyJ2IjoiMSJ9._tTUUNA-1Se5o1aUo_-Ief-EVDs45vox22mxi-8RqXobQ9BiylYO6uD8yyu0nGpSh2PkyfFtivKM0qq1Bph2pmd9KKa4iNR7P_O_Br2TiE1sc7rc_76xQJJvE0VOfFJ77bc-f3lzm7bOZfAVl21iuPOC9RoQwn0KRD_0QvGvvO4W4BJPCj2YidlWHZeO_rbJ-dDyIscUdC3AipgEOqim8TXq6iUrTKjxvufNwHY_SmQiXu_4ZWQGzyAMuWvkpY51NNRxtLOL97GVcX4WI77lrgGyvVgfxjfyHrRvA3N-o50.0Z2PELtes1k9hVJFe4Z-PBpMQKZbWWgAA3jMeSVSydM&dib_tag=se&keywords=Neopren%2BKurzhanteln%2BSet&qid=1779011040&refinements=p_n_free_shipping_afn_mfn%3A27011422031&rnid=27011419031&s=sports&sprefix=neopren%2Bkurzhanteln%2Bset%2Csports%2C152&sr=1-2-spons&aref=mS3gk6KYHg&sp_csd=d2lkZ2V0TmFtZT1zcF9hdGY&th=1&linkCode=ll2&tag=kuendigungexp-21&linkId=b04bc29220123fc9d72f2a05d9025837&ref_=as_li_ss_tl" class="product-card" target="_blank" rel="nofollow sponsored"
           onclick="window.dataLayer = window.dataLayer || []; window.dataLayer.push({'event':'affiliate_click', 'network':'amazon', 'product':'hanteln'});">
            <div style="font-size: 24px; line-height: 1;">💪</div>
            <div>
                <strong style="display: block; font-size: 14px; color: var(--text); margin-bottom: 2px;">Neopren Kurzhanteln-Set</strong>
                <span style="font-size: 12px; color: var(--muted); line-height: 1.3; display: block;">Für progressiven Muskelaufbau. Ersetzen ein halbes Studio.</span>
            </div>
        </a>

        <a href="https://www.amazon.de/VEICK-Widerstandsb%C3%A4nder-%C3%9Cbungsb%C3%A4nder-Workout-B%C3%A4nder-10-150Pfund/dp/B086X4PN48?__mk_de_DE=%C3%85M%C3%85%C5%BD%C3%95%C3%91&crid=2VT44D3C2VTYX&dib=eyJ2IjoiMSJ9.4hg4QiKQVX2p0bjw1uD6s3NMpPwojp4N-BBbjRIIlMosEEpmTivoN7hUPhdddkH4yLOWi5cT4g5LxAXX9vmuzX7dYt2czX3Njy729gxwUOdaKMOoytcNpYH4lRxyJllg8g5fdO07THwnIiO8Kfugv8krkkRsCzOXNQWU8sDBf0ZsC_Pk9A5Kg03a-SjdhZ2v3RpC9uVRxzVsex16Qv9G_sY4E4yc1xVMFETFyUTsCSOVNXiw3wpmVbEPp7LIwJhtfF63I5FsQiJvw8yr1nKi-4I1_tKiLRyd8R3buTnMXGI.9vl1CFg5QzT63ilE82M1T3D8A-OtubuBPnRp-wn5CRo&dib_tag=se&keywords=Fitnessb%C3%A4nder%2Bmit%2BGriffen&qid=1779010927&refinements=p_72%3A184747031%2Cp_n_free_shipping_afn_mfn%3A27011422031&rnid=27011419031&s=sports&sprefix=fitnessb%C3%A4nder%2Bmit%2Bgriffen%2Csports%2C101&sr=1-18&th=1&linkCode=ll2&tag=kuendigungexp-21&linkId=252ae18a2964b4122ad9ded9544b1061&ref_=as_li_ss_tl" class="product-card" target="_blank" rel="nofollow sponsored"
           onclick="window.dataLayer = window.dataLayer || []; window.dataLayer.push({'event':'affiliate_click', 'network':'amazon', 'product':'baender'});">
            <div style="font-size: 24px; line-height: 1;">🎗️</div>
            <div>
                <strong style="display: block; font-size: 14px; color: var(--text); margin-bottom: 2px;">Widerstandsbänder-Set</strong>
                <span style="font-size: 12px; color: var(--muted); line-height: 1.3; display: block;">Das perfekte Ganzkörper-Workout für Zuhause.</span>
            </div>
        </a>

        <a href="https://www.amazon.de/Gymnastikmatte-Premium-inkl-%C3%9Cbungsposter-Hautfreundliche/dp/B01N5TH9Z9?__mk_de_DE=%C3%85M%C3%85%C5%BD%C3%95%C3%91&crid=307Y0MPJSAJIX&dib=eyJ2IjoiMSJ9.FZRFd7oGYifVID2JMWBgGMcd24jBXBavsXegAt4PT70hSbFVbSmLXfttoOTxdKegPIVhkZz-WNLgY7OOEJtpRJF8u8_zptoqCY5R3PlCe7_S-4Ew5i0XItqGkQvCfn26fM-UTHOrxGfH_bONbWPuXhhmFyIbMo4LTCNBli7zImnbzej216VJqeIDpmmFGjJMjUcbB28UI0bT2lMsFjJlw9z2nf8vIcI1EOmwrR7ifbJRne-9rBVHsJdWyZMQ2JQXSrzJw8R01cAtA86f7vFgN4c28EHnXYXbF3fg_Hu8A48.Iv6QVAYMx3utOCsemdjtSF8jijP3_mzTnAOsRV1P0uI&dib_tag=se&keywords=Premium%2BFitnessmatte%2Bextra%2Bdick&qid=1779011171&refinements=p_n_free_shipping_afn_mfn%3A27011422031&rnid=27011419031&s=sports&sprefix=premium%2Bfitnessmatte%2Bextra%2Bdick%2Csports%2C107&sr=1-4&th=1&linkCode=ll2&tag=kuendigungexp-21&linkId=7d89d1f4cdc4ae2653f13ee41934c4c4&ref_=as_li_ss_tl" class="product-card" target="_blank" rel="nofollow sponsored"
           onclick="window.dataLayer = window.dataLayer || []; window.dataLayer.push({'event':'affiliate_click', 'network':'amazon', 'product':'matte'});">
            <div style="font-size: 24px; line-height: 1;">🧘‍♀️</div>
            <div>
                <strong style="display: block; font-size: 14px; color: var(--text); margin-bottom: 2px;">Premium Fitnessmatte</strong>
                <span style="font-size: 12px; color: var(--muted); line-height: 1.3; display: block;">Rutschfest, extra dick und ideal für Bodyweight-Training.</span>
            </div>
        </a>
    </div>
    <?php endif; ?>
    
    <div style="font-size: 11px; color: #94A3B8; text-align: center; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border); line-height: 1.45;">
        * Werbelink: Bei einem Vertragsabschluss erhalten wir eine Provision. Für Sie entstehen keine Mehrkosten.
    </div>
  </div>

 <div class="engagement-strip">
    <div class="eng-col eng-review">
      <div class="eng-icon">★★★★★</div>
      <h3>Bewertung hinterlassen</h3>
      <p>War unser Service für Sie hilfreich? Über eine ehrliche Bewertung freuen wir uns sehr.</p>
      <div class="review-btns">
        <a href="https://g.page/r/CQUi4-fYtkH4EAE/review" class="review-btn" target="_blank" rel="nofollow noopener">⭐ Google</a>
        <a href="https://de.trustpilot.com/evaluate/kuendigungexpress.de" class="review-btn" target="_blank" rel="nofollow noopener">⭐ Trustpilot</a>
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

<div class="ke-trust-strip">
  <span>🔒 SSL-verschlüsselt</span>
  <span>🛡️ DSGVO-konform</span>
  <span>⚖️ Muster-Vorlage, keine Rechtsberatung</span>
</div>

<footer class="ke-footer">
  <p>© 2026 KündigungExpress · <a href="/impressum.html">Impressum</a> · <a href="/datenschutz.html">Datenschutz</a> · <a href="/hilfe.html">Hilfe</a> · <a href="#" onclick="return keResetConsent(event)">Cookie-Einstellungen</a></p>
</footer>

<script src="/generate-success.js" defer></script>
</body>
</html>