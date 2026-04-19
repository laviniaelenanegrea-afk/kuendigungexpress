<?php
declare(strict_types=1);

$type = $_GET['type'] ?? ($_POST['type'] ?? 'fitness');
$type = in_array($type, ['fitness', 'handy'], true) ? $type : 'fitness';
$anbieter = $_GET['anbieter'] ?? ($_POST['anbieter'] ?? '');

$isHandy = $type === 'handy';
$ctaText  = $isHandy ? 'Handyvertrag kündigen – kostenlos' : 'Fitness kündigen – kostenlos';
$title    = $isHandy ? 'Handyvertrag kündigen' : 'Fitnessstudio kündigen';
?>
<!doctype html>
<html lang="de" data-type="<?= htmlspecialchars($type) ?>">
<head>
<meta charset="utf-8">
<meta name="color-scheme" content="light">
<link rel="preconnect" href="https://www.clarity.ms">
<link rel="preconnect" href="https://a.check24.net">
<link rel="preconnect" href="https://www.awin1.com">
<link rel="dns-prefetch" href="//www.clarity.ms">
<link rel="dns-prefetch" href="//a.check24.net">
<link rel="dns-prefetch" href="//www.awin1.com">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#16A34A">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="robots" content="noindex, nofollow">
<title><?= $isHandy ? 'Handyvertrag kündigen – Kostenlos als PDF' : 'Fitnessstudio kündigen – Kostenlos als PDF' ?> | KündigungExpress</title>
<meta name="description" content="<?= $isHandy ? 'Handyvertrag kündigen: Daten eingeben, PDF sofort kostenlos herunterladen. Kein Abo, keine Registrierung.' : 'Fitnessstudio kündigen: Daten eingeben, PDF sofort kostenlos herunterladen. Kein Abo, keine Registrierung.' ?>">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<style>
:root {
  --bg: #F7F9FC;
  --card: #FFFFFF;
  --text: #0F172A;
  --muted: #475569;
  --border: #E2E8F0;
  --primary: #16A34A;
  --soft: #F1F5F9;
  --focus: rgba(22,163,74,0.3);
}
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: Arial, sans-serif;
  background: var(--bg);
  color: var(--text);
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.wrap {
  max-width: 680px;
  margin: 0 auto;
  padding: 28px 24px;
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* Header */
.wrap > header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}
header a { text-decoration: none; color: inherit; }
.brand { font-weight: 900; font-size: 20px; }
nav a { margin-left: 16px; color: var(--muted); font-size: 14px; text-decoration: none; }

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
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: var(--soft);
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  color: var(--muted);
  flex-shrink: 0;
}
.prog-step.active .prog-dot {
  background: var(--primary);
  border-color: var(--primary);
  color: #fff;
}
.prog-step.done .prog-dot {
  background: #DCFCE7;
  border-color: var(--primary);
  color: var(--primary);
}
.prog-line {
  flex: 1;
  height: 2px;
  background: var(--border);
  margin: 0 8px;
}

/* Kein Haken bar */
.kein-haken{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:16px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:12px;padding:10px 16px;margin-bottom:16px;font-size:13px;font-weight:700;color:#15803D}
.kein-haken span{display:flex;align-items:center;gap:5px}

/* Social proof */
.social-proof-form {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  margin-top: 10px;
  font-size: 14px;
  color: #475569;
}
.social-proof-form strong {
  color: #16A34A;
  font-weight: 900;
}

/* Page title */
.page-title {
  margin-bottom: 20px;
  text-align: center;
}
.page-title h1 {
  font-size: clamp(22px, 4vw, 30px);
  font-weight: 900;
  line-height: 1.2;
  margin-bottom: 12px;
}
.page-title .sub {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.page-title .sub span {
  background: #DCFCE7;
  border: 1px solid #BBF7D0;
  border-radius: 999px;
  padding: 4px 12px;
  font-size: 13px;
  font-weight: 700;
  color: #15803D;
}

/* Form */
form {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 24px;
  flex-grow: 1;
}

.section {
  margin-bottom: 24px;
}
.section:last-of-type { margin-bottom: 0; }

.section-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--border);
}
.section-icon {
  width: 32px;
  height: 32px;
  background: #F0FDF4;
  border: 1px solid #BBF7D0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
  pointer-events: none;
  user-select: none;
  cursor: default;
}
.section-header h2 {
  font-size: 16px;
  font-weight: 800;
  color: var(--text);
}
.section-header .opt-badge {
  margin-left: auto;
  font-size: 11px;
  color: var(--muted);
  background: var(--soft);
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 2px 8px;
}

/* Field grid */
.grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.full { grid-column: 1 / -1; }

.field { display: flex; flex-direction: column; gap: 4px; }
label {
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  text-transform: uppercase;
  letter-spacing: .04em;
}
input, select {
  width: 100%;
  padding: 11px 13px;
  border: 1px solid var(--border);
  border-radius: 10px;
  font-size: 15px;
  background: #fff;
  color: var(--text);
  transition: border-color .15s ease, box-shadow .15s ease;
}
input:focus, select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--focus);
}
.hint {
  font-size: 12px;
  color: #94A3B8;
  line-height: 1.5;
}

/* Optional section */
.optional-section {
  background: var(--soft);
  border: 1px dashed var(--border);
  border-radius: 14px;
  padding: 16px;
}

/* Checkbox row */
.checkbox-row {
  display: flex;
  gap: 10px;
  align-items: flex-start;
}
.checkbox-row input[type="checkbox"] {
  width: 18px;
  height: 18px;
  margin-top: 2px;
  flex-shrink: 0;
  accent-color: var(--primary);
}
.checkbox-label b { font-size: 14px; display: block; margin-bottom: 3px; }
.checkbox-label .hint { margin-top: 0; }

/* Summary box */
.summary-box {
  background: linear-gradient(160deg, #F0FDF4 0%, #fff 100%);
  border: 1px solid rgba(22,163,74,0.25);
  border-radius: 16px;
  padding: 20px;
  margin: 20px 0 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 72px;
}
.summary-price {
  font-size: 22px;
  font-weight: 900;
  color: var(--text);
  margin: 0;
}
.summary-pills {
  display: flex;
  justify-content: center;
  gap: 8px;
  flex-wrap: wrap;
}
.summary-pills span {
  background: #fff;
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 4px 12px;
  font-size: 12px;
  color: #334155;
}

/* Invalid field highlight (on submit fail) */
input.ke-invalid,
select.ke-invalid,
textarea.ke-invalid {
  border-color: #DC2626 !important;
  background: #FEF2F2;
  box-shadow: 0 0 0 3px rgba(220,38,38,0.12);
}
/* Email-Versand active state — clearer visual when checkbox is ticked */
.optional-section.is-active {
  background: #F0FDF4;
  border: 2px solid #86EFAC;
  transition: background .2s, border-color .2s;
}
.optional-section.is-active .hint { color: #166534; }

/* Submit button */
.btn-submit {
  width: 100%;
  background: var(--primary);
  color: #fff;
  border: none;
  border-radius: 14px;
  padding: 16px;
  font-size: 17px;
  font-weight: 900;
  cursor: pointer;
  transition: filter .15s ease, transform .15s ease;
  box-shadow: 0 4px 14px rgba(22,163,74,0.25);
}
.btn-submit:hover {
  filter: brightness(.95);
  transform: translateY(-1px);
}
.secure-note {
  text-align: center;
  font-size: 12px;
  color: #94A3B8;
  margin-top: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

/* Footer */
footer {
  margin-top: auto;
  padding: 18px 0 12px;
  font-size: 12px;
  color: #94A3B8;
  text-align: center;
}
footer a { color: inherit; text-decoration: none; }
footer p { margin-top: 0 !important; margin-bottom: 3px !important; font-size: 12px; color: #94A3B8; line-height: 1.5; }
footer p:last-child { margin-bottom: 0 !important; }
footer a:hover { color: #334155; }

/* Mobile */
.site-header, .mobile-cta { display: none; }

@media (max-width: 700px) {
  .grid { grid-template-columns: 1fr; }
  nav { display: none; }
}

@media (max-width: 640px) {
  body { display: block; overflow-x: hidden; padding-top: 56px; padding-bottom: 16px; }
  .wrap { display: block; padding: 0 0 16px; }

  /* Kein-Haken trust bar — tight on mobile so pillules nu ies din viewport */
  .kein-haken {
    gap: 6px 10px;
    padding: 10px 14px;
    font-size: 12px;
    margin: 0 16px 16px;
  }
  .kein-haken span { white-space: nowrap; }

  /* Social proof above Kein-Haken — readable on mobile */
  .social-proof-form {
    padding: 0 16px;
    font-size: 14px;
    margin-top: 8px;
    margin-bottom: 10px;
    justify-content: center;
  }

  .wrap > header { display: none; }
  .site-header {
    display: flex;
    justify-content: center;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 56px;
    z-index: 1000;
    background: #fff;
    border-bottom: 1px solid var(--border);
    padding: 0;
  }
  .site-header .brand a {
    font-size: 18px;
    font-weight: 900;
    color: var(--text);
    text-decoration: none;
  }

  .progress-bar { margin: 16px 16px 20px; }
  .prog-step span { display: none; }

  .page-title { padding: 0 16px; }
  form { border-radius: 0; border-left: none; border-right: none; padding: 20px 16px; }
  .summary-box { margin: 16px 0 14px; }

  footer { margin-top: 16px; padding: 10px 16px; }
}
</style>
<script src="/formular.js" defer></script>
    
    <!-- Microsoft Clarity -->
    <script type="text/javascript">
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "vz58ddxva5");
    </script>

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

  <!-- Progress -->
  <div class="progress-bar">
    <div class="prog-step done">
      <div class="prog-dot">✓</div>
      <span>Vertragsart</span>
    </div>
    <div class="prog-line"></div>
    <div class="prog-step active">
      <div class="prog-dot">2</div>
      <span>Ihre Daten</span>
    </div>
    <div class="prog-line"></div>
    <div class="prog-step">
      <div class="prog-dot">3</div>
      <span>PDF erstellen</span>
    </div>
  </div>

  <!-- Title -->
  <div class="page-title">
    <h1><?= $isHandy ? '📱 Handyvertrag kündigen' : '🏋️ Fitnessstudio kündigen' ?></h1>
    <div class="social-proof-form">
      <span>👥</span>
      <span><strong><?php $c=file_exists(__DIR__.'/_counter.txt')?(int)file_get_contents(__DIR__.'/_counter.txt'):1247; echo number_format($c,0,',','.'); ?></strong> Kündigungen bereits erstellt</span>
    </div>
  </div>

  <div class="kein-haken">
    <span>🔓 Kostenlos</span>
    <span>👤 Kein Login</span>
    <span>🚫 Kein Abo</span>
    <span>✌️ Kein Haken</span>
  </div>

  <form id="keForm" method="post" action="/generate.php">
    <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

    <!-- Persönliche Daten -->
    <div class="section">
      <div class="section-header">
        <div class="section-icon">👤</div>
        <h2>Ihre persönlichen Daten</h2>
      </div>
      <div class="grid">
        <div class="field">
          <label>Vorname *</label>
          <input name="firstName" required autocomplete="given-name">
        </div>
        <div class="field">
          <label>Nachname *</label>
          <input name="lastName" required autocomplete="family-name">
        </div>
        <div class="field full">
          <label>Straße &amp; Hausnummer *</label>
          <input name="street" required autocomplete="street-address">
        </div>
        <div class="field">
          <label>PLZ *</label>
          <input name="zip" inputmode="numeric" maxlength="5" required autocomplete="postal-code" placeholder="z. B. 63073">
        </div>
        <div class="field">
          <label>Ort *</label>
          <input name="city" required autocomplete="address-level2">
        </div>
        <div class="field full">
          <label>E-Mail <span style="font-weight:400;text-transform:none;letter-spacing:0">(optional)</span></label>
          <input name="email" type="email" placeholder="name@email.de" autocomplete="email">
          <div class="hint">Optional – kann im PDF erscheinen.</div>
        </div>
      </div>
    </div>

    <!-- Anbieter -->
    <div class="section">
      <div class="section-header">
        <div class="section-icon"><?= $isHandy ? '📡' : '🏋️' ?></div>
        <h2><?= $isHandy ? 'Mobilfunkanbieter' : 'Fitnessstudio' ?></h2>
      </div>
      <div class="grid">
        <div class="field full">
          <label><?= $isHandy ? 'Anbieter *' : 'Fitnessstudio *' ?></label>
          <input
            type="text"
            name="anbieter"
            value="<?= htmlspecialchars($anbieter) ?>"
            placeholder="<?= $isHandy ? 'z. B. Vodafone, Telekom, O2' : 'z. B. McFit, FitX, Clever Fit' ?>"
            required
          >
        </div>
        <div class="field full optional-section" style="display:block;">
          <div style="font-size:12px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Adresse des <?= $isHandy ? 'Anbieters' : 'Studios' ?> <span style="font-weight:400;text-transform:none">(optional)</span></div><div class="hint" style="margin-bottom:10px;">Wird im PDF vorausgefüllt, wenn Sie die Adresse kennen. Keine Pflichtangabe — das PDF ist ohne Adresse genauso gültig.</div>
          <div class="grid" style="margin:0;">
            <div class="field full">
              <input name="studioStreet" placeholder="Straße &amp; Hausnummer">
            </div>
            <div class="field">
              <input name="studioZip" inputmode="numeric" maxlength="5" placeholder="PLZ">
            </div>
            <div class="field">
              <input name="studioCity" placeholder="Ort">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Kündigungsart -->
    <div class="section">
      <div class="section-header">
        <div class="section-icon">📅</div>
        <h2>Kündigungstermin</h2>
        <span class="opt-badge">Optional</span>
      </div>
      <div class="field" style="margin-bottom:10px;">
        <select id="terminationMode" name="terminationMode">
          <option value="next_possible" selected>Zum nächstmöglichen Zeitpunkt</option>
          <option value="specific_date">Zu einem bestimmten Datum</option>
        </select>
        <div class="hint">Unsicher? Wählen Sie „nächstmöglichen Zeitpunkt" – das ist am sichersten.</div>
      </div>
      <div class="field" id="terminationDateWrap" style="display:none;">
        <label>Datum *</label>
        <input id="terminationDate" name="terminationDate" type="date">
      </div>
    </div>

    <!-- Optionale Angaben -->
    <div class="section">
      <div class="section-header">
        <div class="section-icon">🔢</div>
        <h2>Weitere Angaben</h2>
        <span class="opt-badge">Optional</span>
      </div>
      <div class="field">
        <label>Vertragsnummer</label>
        <input name="contractNo" placeholder="z. B. 12345678">
        <div class="hint">Steht auf Ihrer Rechnung oben rechts oder in der Anbieter-App unter „Mein Vertrag". Optional — ohne Nummer ist die Kündigung trotzdem gültig.</div>
      </div>
    </div>

    <!-- E-Mail Versand -->
    <div class="section">
      <div class="section-header">
        <div class="section-icon">✉️</div>
        <h2>Direkt per E-Mail senden</h2>
        <span class="opt-badge">Optional</span>
      </div>
      <div class="optional-section" id="emailSection">
        <div class="checkbox-row" style="margin-bottom:12px;">
          <input id="sendEmail" name="sendEmail" type="checkbox" value="1">
          <div class="checkbox-label">
            <b>Kündigung direkt ans <?= $isHandy ? 'Unternehmen' : 'Fitnessstudio' ?> senden</b>
            <div class="hint">Das PDF bleibt immer zusätzlich als Download verfügbar.</div>
          </div>
        </div>
        <div class="field">
          <label>E-Mail des <?= $isHandy ? 'Anbieters' : 'Studios' ?> <span id="providerReq" style="color:var(--primary);display:none;">*</span></label>
          <input id="providerEmail" name="providerEmail" type="email" placeholder="<?= $isHandy ? 'service@anbieter.de' : 'service@studio.de' ?>">
          <div class="hint" id="providerHint">Nur notwendig wenn oben aktiviert.</div>
        </div>
      </div>
    </div>

    <!-- Summary + Submit -->
    <div class="summary-box">
      <div class="summary-price">Kostenlos · Sofort als PDF</div>

    </div>

    <button class="btn-submit" type="submit">Kündigungsschreiben jetzt kostenlos erstellen →</button>
    <div class="secure-note">
      🔒 SSL-gesichert · Kein Abo · Keine Registrierung
    </div>

  </form>

  <footer>
    <p>Hinweis: Keine Rechtsberatung. Es werden allgemein anerkannte Standardformulierungen verwendet.</p>
    <p><a href="/impressum.html">Impressum</a> · <a href="/datenschutz.html">Datenschutz</a></p>
    <p>Erstellt mit <a href="https://digital-firmen.de" target="_blank">digital-firmen.de</a></p>
  </footer>
</div>

</body>
</html>