<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/_mail_log/php_errors.log');

$token       = (string)($_GET['t'] ?? '');
$sessionId   = (string)($_GET['session_id'] ?? '');
$orderNumber = '';
$plate       = '';

// Weiche Verifikation der Stripe-Session (Quelle der Wahrheit = Webhook)
if ($sessionId !== '' && is_file(__DIR__ . '/stripe-php/init.php') && is_file(__DIR__ . '/_stripe_config.php')) {
    try {
        require_once __DIR__ . '/stripe-php/init.php';
        $config = require __DIR__ . '/_stripe_config.php';
        $mode = $config['mode'] ?? 'test';
        $sk = $config[$mode]['secret_key'] ?? '';
        if ($sk !== '' && strpos($sk, 'sk_') === 0) {
            \Stripe\Stripe::setApiKey($sk);
            \Stripe\Checkout\Session::retrieve($sessionId);
        }
    } catch (\Throwable $e) {
        error_log('[abmelden-success] session verify failed: ' . $e->getMessage());
    }
}

// Auftragsdaten aus _orders (vom Webhook angelegt)
if (preg_match('/^[a-f0-9]{32}$/', $token)) {
    $of = __DIR__ . '/_orders/' . $token . '.json';
    if (is_file($of)) {
        $o = json_decode((string)@file_get_contents($of), true);
        if (is_array($o)) {
            $orderNumber = (string)($o['orderNumber'] ?? '');
            $plate       = (string)($o['plate_display'] ?? $o['plate'] ?? '');
        }
    }
}
$today = (new DateTime('now', new DateTimeZone('Europe/Berlin')))->format('d.m.');
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<link rel="preconnect" href="https://www.clarity.ms">
<link rel="dns-prefetch" href="//www.clarity.ms">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#16A34A">
<title>Vielen Dank — Abmeldung beauftragt | KündigungExpress</title>
<style>
:root {
  --bg: #F7F9FC; --card: #FFFFFF; --text: #0F172A; --muted: #475569;
  --border: #E2E8F0; --primary: #16A34A; --primary-dark: #15803D; --primary-soft: #F0FDF4;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: Arial, sans-serif !important;
  background: var(--bg) !important;
  color: var(--text) !important;
  display: block !important;
}
.wrap { max-width: 600px; margin: 0 auto; padding: 32px 20px 8px; }
.success-card {
  background: var(--card); border: 1px solid var(--border);
  border-radius: 24px; padding: 36px 28px; text-align: center;
  box-shadow: 0 8px 32px rgba(22,163,74,0.08);
  margin-bottom: 16px;
}
.check-circle {
  width: 72px; height: 72px;
  background: linear-gradient(135deg, #16A34A, #22C55E);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 18px;
  font-size: 36px; color: #fff;
  box-shadow: 0 8px 20px rgba(22,163,74,0.3);
}
h1 {
  font-size: clamp(22px, 4vw, 28px);
  font-weight: 900; margin-bottom: 10px;
  letter-spacing: -0.01em;
}
.success-sub {
  font-size: 15px; color: var(--muted);
  line-height: 1.65; margin-bottom: 26px;
  max-width: 460px; margin-left: auto; margin-right: auto;
}

.timeline-wrap {
  background: var(--bg); border: 1px solid var(--border);
  border-radius: 16px; margin: 0 auto 26px; text-align: left;
  max-width: 460px;
}
.tl-header {
  background: #F8FAFC; padding: 12px 18px;
  border-bottom: 1px solid var(--border); border-radius: 16px 16px 0 0;
  display: flex; justify-content: space-between; align-items: center;
}
.tl-order { font-family: monospace; font-size: 13.5px; font-weight: 800; color: #475569; letter-spacing: 0.5px;}
.tl-tier { font-size: 11.5px; font-weight: 800; color: #166534; background: #DCFCE7; padding: 4px 10px; border-radius: 6px; border: 1px solid #BBF7D0; }
.tl-body { padding: 22px 18px 4px; }
.tl-step { display: flex; gap: 16px; margin-bottom: 24px; position: relative; }
.tl-step:not(:last-child)::after {
  content: ''; position: absolute; left: 15px; top: 34px; bottom: -20px;
  width: 2px; background: #E2E8F0;
}
.tl-icon {
  width: 32px; height: 32px; border-radius: 50%;
  background: #F1F5F9; border: 2px solid #CBD5E1;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; flex-shrink: 0; position: relative; z-index: 2;
}
.tl-step.done .tl-icon { background: var(--primary); border-color: var(--primary); color: #fff; }
.tl-step:not(.done) .tl-icon { border-style: dashed; }
.tl-step.done:not(:last-child)::after { background: var(--primary); }
.tl-content { padding-top: 5px; }
.tl-content h3 { font-size: 14.5px; font-weight: 800; color: var(--text); margin-bottom: 4px; display: flex; flex-wrap: wrap; align-items: center; gap: 8px;}
.tl-date { font-weight: 700; font-size: 11.5px; color: var(--muted); background: #F1F5F9; padding: 2px 6px; border-radius: 6px; white-space: nowrap; }
.tl-step.done .tl-date { color: #166534; background: #DCFCE7; }
.tl-content p { font-size: 13px; color: var(--muted); line-height: 1.5; margin: 0; }
.tl-content p strong { color: var(--text); }

.btn-download {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  background: var(--primary); color: #fff;
  border: 2px solid var(--primary);
  box-shadow: 0 4px 14px rgba(22,163,74,.28);
  padding: 14px 22px; border-radius: 14px;
  font-weight: 800; font-size: 14.5px;
  text-decoration: none; cursor: pointer;
  transition: all 0.15s ease;
  margin: 0 auto;
  max-width: 360px;
}
.btn-download:hover { background: var(--primary-dark); transform: translateY(-1px); }
.back-link { margin-top: 16px; text-align: center; font-size: 13px; color: var(--muted); }
.back-link a { color: var(--primary); text-decoration: none; font-weight: 700; }

.ke-trust-strip { max-width: 880px; margin: 0 auto; padding: 16px 24px 8px; display: flex; flex-wrap: wrap; justify-content: center; gap: 12px 20px; font-size: 12px; color: #64748B; }
.ke-trust-strip span { display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }
.ke-footer { max-width: 880px; margin: 0 auto; padding: 8px 24px 24px; text-align: center; }
.ke-footer p { font-size: 12px; color: #94A3B8; line-height: 1.6; margin: 0 0 4px; text-align: center; }
.ke-footer a { color: #64748B; text-decoration: none; }
.ke-footer a:hover { color: #0F172A; text-decoration: underline; }

@media (max-width: 720px) {
  .wrap { padding: 24px 14px 8px; }
  .success-card { padding: 28px 20px; border-radius: 18px; }
  .check-circle { width: 60px; height: 60px; font-size: 30px; }
  .tl-header { padding: 12px 14px; }
  .tl-body { padding: 20px 14px 4px; }
  .ke-trust-strip { padding: 14px 16px 4px; gap: 8px 14px; font-size: 11px; }
  .ke-footer { padding: 4px 16px 20px; }
  .ke-footer p { font-size: 11.5px; }
  .btn-download { max-width: 100%; }
  .back-link a { display: inline-block; padding: 4px 2px; }
}
@media (max-width: 400px) {
  .tl-step { gap: 12px; }
  .tl-icon { width: 28px; height: 28px; font-size: 12px; }
  .tl-step:not(:last-child)::after { left: 13px; }
  .tl-content h3 { font-size: 13px; }
  .tl-header { flex-wrap: wrap; gap: 6px; }
  .success-sub { font-size: 14px; }
}
@media (prefers-reduced-motion: reduce){
  *,*::before,*::after{animation-duration:.001ms !important;animation-iteration-count:1 !important;transition-duration:.001ms !important;scroll-behavior:auto !important;}
  .btn-download:hover{transform:none;}
}
</style>
</head>
<body>
<div class="wrap">
  <div class="success-card">
    <div class="check-circle">✓</div>
    <h1>Zahlung erhalten — wir kümmern uns!</h1>
    <p class="success-sub">
      Ihr Abmelde-Auftrag<?= $plate !== '' ? ' für <strong>' . htmlspecialchars($plate) . '</strong>' : '' ?> ist bei uns. Wir übermitteln die Außerbetriebsetzung an die zuständige Behörde und schicken Ihnen die amtliche Bestätigung per E-Mail — werktags in der Regel innerhalb von 24 Stunden.
    </p>

    <div class="timeline-wrap">
      <div class="tl-header">
        <?php if ($orderNumber !== ''): ?>
          <span class="tl-order"><?= htmlspecialchars($orderNumber) ?></span>
        <?php else: ?>
          <span class="tl-order">Abmeldung</span>
        <?php endif; ?>
        <span class="tl-tier">Kfz-Abmeldung</span>
      </div>
      <div class="tl-body">

        <div class="tl-step done">
          <div class="tl-icon">✓</div>
          <div class="tl-content">
            <h3>Bestellung bestätigt <span class="tl-date">Heute, <?= $today ?></span></h3>
            <p>Zahlung erfolgreich. Ihr Auftrag ist eingegangen.</p>
          </div>
        </div>

        <div class="tl-step">
          <div class="tl-icon">🏛️</div>
          <div class="tl-content">
            <h3>Übermittlung an die Behörde <span class="tl-date">werktags ≤ 2 Std.</span></h3>
            <p>Wir übermitteln die Außerbetriebsetzung an das KBA bzw. die zuständige Zulassungsstelle. Am Wochenende bearbeiten wir Ihren Auftrag am nächsten Werktag.</p>
          </div>
        </div>

        <div class="tl-step">
          <div class="tl-icon">📧</div>
          <div class="tl-content">
            <h3>Amtliche Bestätigung <span class="tl-date">werktags ≤ 24 Std.</span></h3>
            <p>Sie erhalten die Abmeldebestätigung als PDF per E-Mail. Kfz-Steuer und Versicherung werden von der Behörde automatisch informiert.</p>
          </div>
        </div>

      </div>
    </div>

    <div style="margin-top: 8px; padding-top: 20px; border-top: 1px solid #E2E8F0;">
      <p style="font-size: 13px; color: #64748B;">Keine Bestätigungs-E-Mail erhalten? Bitte auch im Spam-Ordner nachsehen. Bei Fragen antworten Sie einfach auf Ihre Bestätigung oder schreiben Sie an <a href="mailto:kontakt@kuendigungexpress.de" style="color:#16A34A;font-weight:700;text-decoration:none;">kontakt@kuendigungexpress.de</a>.</p>
    </div>
  </div>

  <div class="back-link">
    Einen Vertrag kündigen?
    <a href="/formular.php?type=handy">📱 Handyvertrag</a> ·
    <a href="/formular.php?type=kfz">🚗 KFZ-Versicherung</a> ·
    <a href="/formular.php?type=fitness">🏋️ Fitness</a> ·
    <a href="/formular.php?type=bank">🏦 Girokonto</a>
  </div>
</div>

<div class="ke-trust-strip">
  <span>🔒 SSL-verschlüsselt</span>
  <span>🛡️ DSGVO-konform</span>
  <span>⚖️ Behördenservice, keine Rechtsberatung</span>
</div>

<footer class="cv-auto" style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; margin-top: 20px; padding: 16px 0;">
  <p class="brand-disclaimer" style="width: 100%; text-align: center; margin: 0 0 5px; color: #64748B; font-size: 12px;">© 2026 KündigungExpress · <a href="/unsere-mission.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Über uns</a> · <a href="/wie-wir-kuendigungexpress-gebaut-haben.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Unsere Geschichte</a> · <a href="/impressum.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Impressum</a> · <a href="/datenschutz.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Datenschutz</a> · <a href="/agb.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">AGB</a> · <a href="/hilfe.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Hilfe</a> · <a href="#" onclick="return keResetConsent(event)" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;cursor:pointer;">Cookie-Einstellungen</a></p>
  <p class="brand-disclaimer" style="width: 100%; text-align: center; margin: 0; color: #64748B; font-size: 12px;">Erstellt mit <a href="https://digital-firmen.de" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;" target="_blank">digital-firmen.de</a></p>
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
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
    'event': 'purchase',
    'transaction_id': '<?= htmlspecialchars($orderNumber) ?>',
    'value': 24.90,
    'currency': 'EUR',
    'items': [{ 'item_id': 'abmeldung', 'item_name': 'Kfz-Abmeldung', 'price': 24.90, 'quantity': 1 }]
});
</script>
<script>try { sessionStorage.removeItem('ke_abmelden_draft_v1'); } catch(e){}</script>
<script src="/clarity-loader.js" async></script>
</body>
</html>
