<?php
declare(strict_types=1);

/* =========================================================
   ERROR LOGGING (safe pe shared hosting)
   ========================================================= */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/_mail_log/php_errors.log');
error_reporting(E_ALL);

/* =========================================================
   STRIPE
   ========================================================= */
require_once __DIR__ . '/stripe-php/init.php';

/** 🔴 Înlocuiește cu SECRET KEY-ul tău */
\Stripe\Stripe::setApiKey('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');

/* =========================================================
   CONFIG (single source of truth)
   ========================================================= */
$config = require __DIR__ . '/_app_config.php';

$PRICE_EUR   = (float)($config['price_eur'] ?? 4.99);
$PRICE_CENTS = (int) round($PRICE_EUR * 100);
if ($PRICE_CENTS < 50) { $PRICE_CENTS = 50; $PRICE_EUR = 0.50; }

$CURRENCY     = strtolower((string)($config['currency'] ?? 'EUR')); // 'eur'
$PRODUCT_NAME = (string)($config['product_name'] ?? 'Fitnessstudio-Kündigung als PDF');
$DOMAIN       = (string)($config['domain'] ?? 'https://www.kuendigungexpress.de');
$CONTACT_EMAIL= (string)($config['contact_email'] ?? 'kontakt@kuendigungexpress.de');

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
  echo "<!doctype html><html lang='de'><head><meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'><meta name='theme-color' content='#16A34A'><meta name='apple-mobile-web-app-status-bar-style' content='default'>";
  echo "<title>Fehler – KündigungExpress</title>";
  echo "<link rel='icon' href='/favicon.ico' sizes='any'>";
  echo "<style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,sans-serif;background:#F7F9FC;color:#0F172A;display:flex;flex-direction:column;min-height:100vh}
    .wrap{max-width:560px;margin:auto;padding:32px 24px;display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1}
    .card{background:#fff;border:1px solid #E2E8F0;border-radius:20px;padding:36px 32px;text-align:center;width:100%;box-shadow:0 6px 24px rgba(15,23,42,0.06)}
    .err-icon{width:60px;height:60px;background:#FEF2F2;border:1px solid #FECACA;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 18px}
    h1{font-size:22px;font-weight:900;margin-bottom:10px;color:#0F172A}
    p{font-size:14px;color:#475569;line-height:1.65;margin-bottom:18px}
    a.btn{display:inline-block;background:#16A34A;color:#fff;text-decoration:none;padding:13px 24px;border-radius:13px;font-weight:900;font-size:15px;transition:filter .15s}
    a.btn:hover{filter:brightness(.95)}
    .contact{font-size:12px;color:#94A3B8;margin-top:16px}
    .contact a{color:#16A34A;font-weight:700;text-decoration:none}
    footer{text-align:center;font-size:12px;color:#94A3B8;padding:16px 24px}
    footer a{color:inherit;text-decoration:none}
  </style>
    
    <script src="/cookie-consent.js" defer></script>

<link rel="preload" href="/style.css?v=15" as="style"> <link rel="stylesheet" href="/style.css?v=15"></head>
<body>";
  echo "<div class='wrap'><div class='card'>";
  echo "<div class='err-icon'>⚠️</div>";
  echo "<h1>Etwas ist schiefgelaufen</h1>";
  echo "<p>".htmlspecialchars($msg)."</p>";
  echo "<a class='btn' href='/formular.php'>← Zurück zum Formular</a>";
  echo "<div class='contact'>Hilfe benötigt? <a href='mailto:".htmlspecialchars($contact)."'>".htmlspecialchars($contact)."</a></div>";
  echo "</div></div>";
  echo "<footer>© 2026 KündigungExpress · <a href='/impressum.html' style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Impressum</a> · <a href='/datenschutz.html' style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Datenschutz</a></footer>";
  echo "<style>
@media (max-width: 820px) {
  html body { padding-bottom: 68px !important; }
  html body div.wrap { padding-bottom: 0 !important; }
  html body div.wrap div.card { padding-bottom: 24px !important; }
  html body footer { margin-top: 24px !important; padding-top: 0 !important; padding-bottom: 16px !important; }
  html body footer .brand-disclaimer { margin-bottom: 4px !important; line-height: 1.3 !important; }
}
</style>
</body></html>";
  exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  fehler('Ungültiger Aufruf. Bitte starten Sie den Vorgang über das Formular neu.', $CONTACT_EMAIL);
}


/* =========================================================
   READ & VALIDATE INPUT
   ========================================================= */
$firstName = clean_text((string)($_POST['firstName'] ?? ''));
$lastName  = clean_text((string)($_POST['lastName'] ?? ''));
$street    = clean_multiline((string)($_POST['street'] ?? ''));
$zip       = trim((string)preg_replace('/\s+/', '', (string)($_POST['zip'] ?? '')));
$city      = clean_text((string)($_POST['city'] ?? ''));
$email     = clean_text((string)($_POST['email'] ?? ''));

$studio = clean_text((string)($_POST['anbieter'] ?? ''));


$studioStreet = clean_text((string)($_POST['studioStreet'] ?? ''));
$studioZip    = trim((string)preg_replace('/\s+/', '', (string)($_POST['studioZip'] ?? '')));
$studioCity   = clean_text((string)($_POST['studioCity'] ?? ''));

$contractNo = clean_text((string)($_POST['contractNo'] ?? ''));

$terminationMode = (string)($_POST['terminationMode'] ?? 'next_possible');
$terminationDate = clean_text((string)($_POST['terminationDate'] ?? ''));

$providerEmail = clean_text((string)($_POST['providerEmail'] ?? ''));
$sendEmail     = isset($_POST['sendEmail']) ? '1' : '0';

/* Pflichtfelder */
if ($firstName==='' || $lastName==='' || $street==='' || $zip==='' || $city==='' || $studio==='') {
  fehler('Bitte überprüfen Sie Ihre Angaben: Vorname, Nachname, vollständige Adresse und der Name des Vertragspartners sind erforderliche Pflichtfelder.', $CONTACT_EMAIL);
}
if (!preg_match('/^\d{5}$/', $zip)) {
  fehler('Bitte geben Sie eine gültige 5-stellige Postleitzahl ein (z. B. 10115).', $CONTACT_EMAIL);
}
if ($studioZip!=='' && !preg_match('/^\d{5}$/', $studioZip)) {
  fehler('Bitte geben Sie eine gültige 5-stellige Postleitzahl für das Fitnessstudio an oder lassen Sie das Feld leer.', $CONTACT_EMAIL);
}
if ($email!=='' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  fehler('Bitte geben Sie eine gültige E-Mail-Adresse an oder lassen Sie das Feld leer.', $CONTACT_EMAIL);
}
if ($terminationMode!=='next_possible' && $terminationMode!=='specific_date') {
  $terminationMode='next_possible';
}
if ($terminationMode==='specific_date') {
  if ($terminationDate==='') fehler('Sie haben ein spezifisches Datum gewählt, jedoch kein Datum hinterlegt.', $CONTACT_EMAIL);
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $terminationDate)) fehler('Bitte geben Sie ein korrektes Datum an.', $CONTACT_EMAIL);
}
if ($sendEmail==='1') {
  if ($providerEmail==='' || !filter_var($providerEmail, FILTER_VALIDATE_EMAIL)) {
    fehler('Der E-Mail-Versand wurde aktiviert, aber es wurde keine gültige Empfängeradresse des Anbieters hinterlegt.', $CONTACT_EMAIL);
  }
} else {
  $providerEmail='';
}
/* =========================================================
   SAVE PAYLOAD
   ========================================================= */
$dataDir = __DIR__ . '/_data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

$token = bin2hex(random_bytes(16));
$type = ($_POST['type'] ?? 'fitness') === 'handy' ? 'handy' : 'fitness';
$payload = [
'type' => $type,
'firstName'=>$firstName,'lastName'=>$lastName,'street'=>$street,'zip'=>$zip,'city'=>$city,'email'=>$email,
  'studio'=>$studio,'studioStreet'=>$studioStreet,'studioZip'=>$studioZip,'studioCity'=>$studioCity,
  'contractNo'=>$contractNo,'terminationMode'=>$terminationMode,'terminationDate'=>$terminationDate,
  'providerEmail'=>$providerEmail,'sendEmail'=>$sendEmail,
  'price_eur'=>$PRICE_EUR,'price_cents'=>$PRICE_CENTS,'currency'=>strtoupper($CURRENCY),
  'createdAt'=>time(),
];
file_put_contents($dataDir.'/'.$token.'.json', json_encode($payload, JSON_UNESCAPED_UNICODE));
/* =========================================================
   STRIPE CHECKOUT + ROBUST REDIRECT (HTML FALLBACK)
   ========================================================= */
try {
  $session = \Stripe\Checkout\Session::create([
    'mode'=>'payment',
    'payment_method_types'=>['card'],
    'line_items'=>[[
      'quantity'=>1,
      'price_data'=>[
        'currency'=>$CURRENCY,
        'unit_amount'=>$PRICE_CENTS,
        'product_data'=>['name'=>$PRODUCT_NAME],
      ],
    ]],
    'success_url'=>$DOMAIN.'/success_final.php?session_id={CHECKOUT_SESSION_ID}&t='.$token,
    'cancel_url' =>$DOMAIN.'/formular.php',
  ]);

  $stripeUrl = $session->url;

  // HTML-Weiterleitung + Fallback-Button (robust gegen Adblocker)
  echo "<!doctype html><html lang='de'><head><meta charset='utf-8'>";
  echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
  echo "<title>Weiterleitung… – KündigungExpress</title>";
  echo "<meta http-equiv='refresh' content='0;url=".htmlspecialchars($stripeUrl,ENT_QUOTES)."'>";
  echo "<link rel='icon' href='/favicon.ico' sizes='any'>";
  echo "<meta name='theme-color' content='#16A34A'>";
  echo "<style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,sans-serif;background:#F7F9FC;color:#0F172A;display:flex;flex-direction:column;min-height:100vh}
    .wrap{max-width:520px;margin:auto;padding:32px 24px;flex:1;display:flex;align-items:center;justify-content:center}
    .card{background:#fff;border:1px solid #E2E8F0;border-radius:20px;padding:36px 32px;text-align:center;width:100%;box-shadow:0 6px 24px rgba(15,23,42,0.06)}
    .spin{width:48px;height:48px;border:3px solid #E2E8F0;border-top-color:#16A34A;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 20px}
    @keyframes spin{to{transform:rotate(360deg)}}
    h1{font-size:20px;font-weight:900;margin-bottom:10px}
    p{font-size:14px;color:#475569;line-height:1.65;margin-bottom:20px}
    a.btn{display:inline-block;background:#16A34A;color:#fff;text-decoration:none;padding:13px 24px;border-radius:13px;font-weight:900;font-size:15px;transition:filter .15s}
    a.btn:hover{filter:brightness(.95)}
    .note{font-size:12px;color:#94A3B8;margin-top:14px}
    footer{text-align:center;font-size:12px;color:#94A3B8;padding:16px 24px}
    footer a{color:inherit;text-decoration:none}
  </style><link rel="preload" href="/style.css?v=15" as="style"> <link rel="stylesheet" href="/style.css?v=15"></head><body>";
  echo "<div class='wrap'><div class='card'>";
  echo "<div class='spin'></div>";
  echo "<h1>Weiterleitung zu Stripe</h1>";
  echo "<p>Sie werden sicher zu unserem Zahlungsanbieter weitergeleitet…</p>";
  echo "<a class='btn' href='".htmlspecialchars($stripeUrl,ENT_QUOTES)."'>Weiter zu Stripe →</a>";
  echo "<div class='note'>🔒 SSL-verschlüsselt · Sichere Zahlung über Stripe</div>";
  echo "</div></div>";
  echo "<footer>© 2026 KündigungExpress · <a href='/impressum.html' style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Impressum</a> · <a href='/datenschutz.html' style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Datenschutz</a></footer>";
  echo "<style>
@media (max-width: 820px) {
  html body { padding-bottom: 68px !important; }
  html body div.wrap { padding-bottom: 0 !important; }
  html body div.wrap div.card { padding-bottom: 24px !important; }
  html body footer { margin-top: 24px !important; padding-top: 0 !important; padding-bottom: 16px !important; }
  html body footer .brand-disclaimer { margin-bottom: 4px !important; line-height: 1.3 !important; }
}
</style>
</body></html>";
  exit;

} catch (Exception $e) {
  fehler('Stripe-Fehler: '.$e->getMessage(), $CONTACT_EMAIL);
}