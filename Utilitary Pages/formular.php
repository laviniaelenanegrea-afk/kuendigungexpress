<?php
declare(strict_types=1);

$type = $_GET['type'] ?? ($_POST['type'] ?? 'fitness');
$type = in_array($type, ['fitness', 'handy', 'kfz'], true) ? $type : 'fitness';
$anbieter = $_GET['anbieter'] ?? ($_POST['anbieter'] ?? '');
$isRSG = in_array(strtolower(trim($anbieter)), ['mcfit', 'john reed', 'john-reed', 'johnreed'], true);

/* Provider address pre-fill mapping */
$providerAddresses = [
    // KFZ-Versicherung
    'allianz versicherungs-ag' => ['Postanschrift','10900','Berlin'],
    'huk-coburg' => ['Postanschrift','96444','Coburg'],
    'huk24' => ['HUK-COBURG-Platz 1','96444','Coburg'],
    'ergo versicherung ag' => ['ERGO-Platz 1','40477','Düsseldorf'],
    'axa versicherung ag' => ['Colonia-Allee 10-20','51067','Köln'],
    'devk allgemeine versicherungs-ag' => ['Riehler Str. 190','50735','Köln'],
    'adac autoversicherung ag' => ['Hansastraße 19','80686','München'],
    'generali deutschland versicherung ag' => ['Adenauerring 7','81737','München'],
    'hdi versicherung ag' => ['HDI-Platz 1','30659','Hannover'],
    'r+v allgemeine versicherung ag' => ['Raiffeisenplatz 1','65189','Wiesbaden'],
    'cosmosdirekt' => ['Postanschrift','66101','Saarbrücken'],
    'lvm versicherung' => ['Kolde-Ring 21','48126','Münster'],
    'vhv allgemeine versicherung ag' => ['VHV-Platz 1','30177','Hannover'],
    'württembergische versicherung ag' => ['W&W-Platz 1','70806','Kornwestheim'],
    'da deutsche allgemeine versicherung ag' => ['Platz der Einheit 2','60327','Frankfurt am Main'],
    'verti versicherung ag' => ['Rheinstraße 7A ','14513','Teltow'],
    'nürnberger allgemeine versicherungs-ag' => ['Ostendstraße 100','90334','Nürnberg'],
    'gothaer allgemeine versicherung ag' => ['Arnoldiplatz 1','50969','Köln'],
    'zurich gruppe deutschland' => ['Deutzer Allee 1','50679','Köln'],
    'provinzial versicherung ag' => ['Provinzialplatz 1','40591','Düsseldorf'],
    'signal iduna allgemeine versicherung ag' => ['Joseph-Scherer-Straße 3','44139','Dortmund'],
    'sv sparkassenversicherung holding ag' => ['Löwentorstraße 65','70376','Stuttgart'],
    'alte leipziger versicherung ag' => ['Alte Leipziger-Platz 1','61440','Oberursel'],
    'wgv-versicherung ag' => ['Tübinger Str. 55','70164','Stuttgart'],
    'itzehoer versicherung/brandgilde von 1691 versicherungsverein ag' => ['Itzehoer Platz','25521','Itzehoe'],
    'öffentliche versicherung braunschweig' => ['Theodor-Heuss-Str. 10','38122','Braunschweig'],
    
    // Mobilfunk
    'vodafone' => ['Vodafone GmbH (Kundenbetreuung)', '40875', 'Ratingen'],
    'telekom' => ['Telekom Deutschland GmbH (Kundenservice)', '53171', 'Bonn'],
    'o2' => ['Telefónica Germany GmbH & Co. OHG, Kundenbetreuung','90345','Nürnberg'],
    '1&1 telecom gmbh' => ['Elgendorfer Straße 57','56410','Montabaur'],
    'congstar' => ['congstar GmbH, Postfach 1165', '53001', 'Bonn'],
    'freenet' => ['freenet DLS GmbH, Postfach 90 02 65', '99105', 'Erfurt'],
    'otelo' => ['Vodafone GmbH (otelo Kundenbetreuung), Postfach 10 02 54', '33502', 'Bielefeld'],
    'mobilcom-debitel' => ['freenet DLS GmbH, Postfach 90 02 65', '99105', 'Erfurt'],
    'drillisch online gmbh' => ['Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'winsim' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'smartmobil' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'simplytel' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'premiumsim' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'maxxim' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'sim.de' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'yourfone' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'deutschlandsim' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'discotel' => ['Drillisch Online GmbH, Wilhelm-Röntgen-Str. 1-5', '63477', 'Maintal'],
    'aldi talk' => ['Telefónica Germany GmbH & Co. OHG (ALDI TALK), Postfach 730301', '90280', 'Nürnberg'],
    'ortel mobile' => ['Telefónica Germany GmbH & Co. OHG (Ortel Mobile), Postfach 730301', '90280', 'Nürnberg'],
    'ay yildiz' => ['Telefónica Germany GmbH & Co. OHG (Ay Yildiz), Postfach 730301', '90280', 'Nürnberg'],
    'norma connect' => ['Telekom Deutschland Multibrand GmbH, Postfach 1511','48105','Münster'],
    'tchibo mobil' => ['Tchibo Mobilfunk GmbH & Co. KG, Kundenservice, Postfach 601402','22214','Hamburg'],
    'super select' => ['Telefónica Germany GmbH & Co. OHG (Super Select), Postfach 730301','90280','Nürnberg'],
    'blau' => ['Telefónica Germany GmbH & Co. OHG (Blau)','90345','Nürnberg'],
    'fonic' => ['Telefónica Germany GmbH & Co. OHG (FONIC)','90345','Nürnberg'],
    'high mobile' => ['mobilezone GmbH - Kundenbetreuung, Porschestraße 7','44809','Bochum'],
    'klarmobil' => ['klarmobil GmbH - Kundenservice','25436','Uetersen'],
    'kaufland mobil' => ['Telekom Deutschland Multibrand GmbH, Postfach 1511','48105','Münster'],
    'lidl connect' => ['Vodafone GmbH - Lidl Connect Kundenbetreuung, Postfach 100254','33502','Bielefeld'],
    'lebara' => ['Lebara Germany Limited, Zollhof 17','40213','Düsseldorf'],
    'lycamobile' => ['Mainzer Landstraße 349', '60326', 'Frankfurt am Main'],
    'edeka mobil' => ['Vodafone GmbH (otelo), Kundenbetreuung', '40875', 'Ratingen'],
    'edeka smart' => ['Telekom Deutschland GmbH, Landgrabenweg 149', '53227', 'Bonn'],
    'ja! mobil' => ['congstar Services GmbH (ja! mobil), Weinsbergstraße 70','50823','Köln'],
    'fraenk' => ['congstar Services GmbH (fraenk), Weinsbergstraße 70','50823','Köln'],
      
    // Fitnessstudio - Nur zentrale Ketten
    'mcfit' => ['RSG Group GmbH, Tannenberg 4', '96132', 'Schlüsselfeld'],
    'fitx' => ['FitX Deutschland GmbH, Essener Straße 4-5', '45141', 'Essen'],
    'fitness first' => ['Fitness First Germany GmbH, Hanauer Landstraße 148a', '60314', 'Frankfurt am Main'],
    'holmes place' => ['Holmes Place Health Clubs GmbH, Charlottenstraße 65', '10117', 'Berlin'],
    'john reed' => ['RSG Group GmbH, Tannenberg 4', '96132', 'Schlüsselfeld'],
    'pfitzenmeier' => ['Unternehmensgruppe Pfitzenmeier, Essener Straße 12', '68723', 'Schwetzingen'],
    'body&soul' => ['Body&Soul Group, Riesstraße 16', '80992', 'München'],
    'high5' => ['RSG Group GmbH, Tannenberg 4', '96132', 'Schlüsselfeld'],
    'fitness express' => ['Fitness Express, Hanns-Martin-Schleyer-Str. 9', '71063', 'Sindelfingen'],
    'urban sports club' => ['Urban Sports GmbH, Michaelkirchstraße 20', '10179', 'Berlin'],
];

/* Provider Email pre-fill mapping */
$providerEmails = [
    // KFZ-Versicherung
    'allianz versicherungs-ag' => 'sachversicherung@allianz.de',
    'huk-coburg' => 'info@huk-coburg.de',
    'huk24' => 'info@huk24.de',
    'ergo versicherung ag' => 'kundenservice@ergo.de',
    'axa versicherung ag' => 'service@axa.de',
    'devk allgemeine versicherungs-ag' => 'info@devk.de',
    'adac autoversicherung ag' => 'service@acv.de',
    'generali deutschland versicherung ag' => 'service@generali.de',
    'hdi versicherung ag' => 'info@hdi.de',
    'r+v allgemeine versicherung ag' => 'rufv@ruv.de',
    'cosmosdirekt' => 'info@cosmosdirekt.de',
    'lvm versicherung' => 'info@lvm.de',
    'vhv allgemeine versicherung ag' => 'info@vhv.de',
    'verti versicherung ag' => 'service@verti.de',
    'gothaer allgemeine versicherung ag' => 'info@gothaer.de',
    'zurich gruppe deutschland' => 'service@zurich.de',
    'signal iduna allgemeine versicherung ag' => 'info@signal-iduna.de',
    'württembergische versicherung ag' => 'info@wuerttembergische.de',
    'da deutsche allgemeine versicherung ag' => 'service@dadirekt.de',
    'nürnberger allgemeine versicherungs-ag' => 'info@nuernberger.de',
    'provinzial versicherung ag' => 'service@provinzial.com',
    'sv sparkassenversicherung holding ag' => 'service@sparkassenversicherung.de',
    'alte leipziger versicherung ag' => 'service@alte-leipziger.de',
    'wgv-versicherung ag' => 'kundenservice@wgv.de',
    'itzehoer versicherung/brandgilde von 1691 versicherungsverein ag' => 'info@itzehoer.de',
    'öffentliche versicherung braunschweig' => 'info@oeffentliche.de',
    
    // Mobilfunk
    'vodafone' => 'kundenservice@vodafone.com',
    'telekom' => 'kundenservice@telekom.de',
    'o2' => 'impressum@cc.o2online.de',
    '1&1 telecom gmbh' => 'kuendigung@1und1.de',
    'congstar' => 'kundenservice@congstar.de',
    'freenet' => 'info@freenet-mobilfunk.de',
    'otelo' => 'kontakt@otelo.de',
    'mobilcom-debitel' => 'info@freenet-mobilfunk.de',
    'drillisch online gmbh' => 'kontakt@drillisch-online.de',
    'winsim' => 'kontakt@winsim.de',
    'smartmobil' => 'kontakt@smartmobil.de',
    'simplytel' => 'kontakt@simplytel.de',
    'premiumsim' => 'kontakt@premiumsim.de',
    'maxxim' => 'kontakt@maxxim.de',
    'sim.de' => 'kontakt@sim.de',
    'yourfone' => 'kontakt@yourfone.de',
    'deutschlandsim' => 'kontakt@drillisch-online.de',
    'discotel' => 'kontakt@drillisch-online.de',
    'aldi talk' => 'alditalk@eplus.de',
    'ortel mobile' => 'info@ortelmobile.de',
    'ay yildiz' => 'service@ayyildiz.de',
    'tchibo mobil' => 'info@tchibo-mobil.de',
    'blau' => 'service@blau.de',
    'fonic' => 'service@fonic.de',
    'klarmobil' => 'info@klarmobil.de',
    'kaufland mobil' => 'kundenbetreuung@kaufland-mobil.de',
    'lidl connect' => 'info@lidl-connect.de',
    'lebara' => 'halolebara@lebara.com',
    'fraenk' => 'impressum@fraenk.de',
    'norma connect' => 'kundenbetreuung@norma-connect.de',
    'super select' => 'impressum@cc.o2online.de',
    'high mobile' => 'service@high-mobile.de',
    'lycamobile' => 'cs@lycamobile.de',
    'edeka mobil' => 'kontakt@otelo.de',
    'edeka smart' => 'kundenbetreuung@edeka-smart.de',
    'ja! mobil' => 'kundenservice@jamobil.de',
    
    // Fitnessstudio
    'fitx' => 'mitglied@fitx.de',
    'fitness first' => 'service@fitnessfirst.de',
    'holmes place' => 'info@holmesplace.de',
    'urban sports club' => 'hello@urbansportsclub.com',
    'pfitzenmeier' => 'info@pfitzenmeier.de',
    'body&soul' => 'info@bodyandsoul.de',
    'fitness express' => 'info@fitness-express.de',
];

// LISTA FRANCIZELOR (Aici nu facem precompletare, doar informam)
$franchises = ['clever fit', 'kieser training', 'injoy', 'mrs. sporty', 'bodystreet', 'fitnessking', '7/11 fitness'];

$pfKey = mb_strtolower(trim($anbieter), 'UTF-8');
$pf = $providerAddresses[$pfKey] ?? null;
$pfEmail = $providerEmails[$pfKey] ?? '';
$isFranchise = in_array($pfKey, $franchises, true);

$isHandy = $type === 'handy';
$isKfz   = $type === 'kfz';
$ctaText  = $isKfz ? 'KFZ-Versicherung kündigen – kostenlos' : ($isHandy ? 'Handyvertrag kündigen – kostenlos' : 'Fitness kündigen – kostenlos');
$title    = $isKfz ? 'KFZ-Versicherung kündigen' : ($isHandy ? 'Handyvertrag kündigen' : 'Fitnessstudio kündigen');
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
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="theme-color" content="#16A34A">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="robots" content="noindex, nofollow">
<title><?= $isKfz ? 'KFZ-Versicherung kündigen – Kostenlos als PDF' : ($isHandy ? 'Handyvertrag kündigen – Kostenlos als PDF' : 'Fitnessstudio kündigen – Kostenlos als PDF') ?> | KündigungExpress</title>
<meta name="description" content="<?= $isKfz ? 'KFZ-Versicherung kündigen: Daten eingeben, PDF sofort kostenlos herunterladen. Kein Abo, keine Registrierung.' : ($isHandy ? 'Handyvertrag kündigen: Daten eingeben, PDF sofort kostenlos herunterladen. Kein Abo, keine Registrierung.' : 'Fitnessstudio kündigen: Daten eingeben, PDF sofort kostenlos herunterladen. Kein Abo, keine Registrierung.') ?>">
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
  max-width: 800px;
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

/* Field grid & Validation Styles */
.grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.full { grid-column: 1 / -1; }

.field { display: flex; flex-direction: column; gap: 4px; position: relative; }
label {
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  text-transform: uppercase;
  letter-spacing: .04em;
}

input:not([type="checkbox"]):not([type="radio"]), select {
  width: 100%;
  padding: 12px 14px; 
  border: 1px solid var(--border);
  border-radius: 10px;
  font-size: 16px;
  background: #fff;
  color: var(--text);
  transition: border-color .15s ease, box-shadow .15s ease;
  font-family: inherit;
  -webkit-appearance: none;
  appearance: none;
}
input:not([type="checkbox"]):not([type="radio"]):focus, select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px var(--focus);
}
select {
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 14px center;
  background-size: 16px;
  padding-right: 40px;
}

/* Inline Validation UI */
.input-wrapper { position: relative; width: 100%; display: block; }
.check-icon {
  display: none;
  position: absolute;
  top: 50%;
  right: 14px;
  transform: translateY(-50%);
  color: #16A34A;
  font-weight: 900;
  font-size: 14px;
  pointer-events: none;
}
.is-valid + .check-icon { display: block; }
.is-valid { border-color: #16A34A !important; padding-right: 36px !important; }

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
  width: 20px;
  height: 20px;
  margin-top: 2px;
  flex-shrink: 0;
  accent-color: var(--primary);
  -webkit-appearance: auto;
  appearance: auto;
  cursor: pointer;
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
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  min-height: 72px;
}
.summary-price {
  font-size: 22px;
  font-weight: 900;
  color: var(--text);
  margin: 0;
  text-align: center;
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

/* Invalid field highlight */
input.ke-invalid, select.ke-invalid, textarea.ke-invalid {
  border-color: #DC2626 !important;
  background: #FEF2F2;
  box-shadow: 0 0 0 3px rgba(220,38,38,0.12);
}
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
  transition: filter .15s ease, transform .15s ease, background .2s;
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

/* Mobile Sticky Submit */
@media (max-width: 640px) {
  .mobile-sticky-submit {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #ffffff;
      padding: 12px 16px;
      padding-bottom: calc(12px + env(safe-area-inset-bottom));
      box-shadow: 0 -4px 16px rgba(15,23,42,0.1);
      z-index: 1000;
      border-top: 1px solid var(--border);
      transition: transform 0.2s ease-out;
  }
  .mobile-sticky-submit .btn-submit {
      border-radius: 12px;
      padding: 14px;
  }
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

/* Mobile Resets */
.site-header, .mobile-cta { display: none; }

@media (max-width: 700px) {
  .grid { grid-template-columns: 1fr; }
  nav { display: none; }
}

@media (max-width: 640px) {
  body { display: block; overflow-x: hidden; padding-top: 56px; padding-bottom: 76px; } 
  .wrap { display: block; padding: 0; } 

  .field label { display: flex; flex-direction: column; align-items: flex-start !important; gap: 6px; }
  
  /* Elementele Hero centrate sus */
  .kein-haken { justify-content: center; gap: 6px 10px; padding: 12px 16px; font-size: 12px; margin: 0 16px 16px; }
  .kein-haken span { white-space: nowrap; }

  .social-proof-form { padding: 0 16px; font-size: 14px; margin-top: 8px; margin-bottom: 10px; justify-content: center; }

  .page-title { padding: 0 16px; text-align: center; }
  .page-title h1 { text-align: center; font-size: 26px; margin-bottom: 8px;}
  /* ----------------------------- */

  .wrap > header { display: none; }
  .site-header {
    display: flex; justify-content: center; align-items: center; position: fixed; top: 0; left: 0; right: 0; height: 56px; z-index: 1000; background: #fff; border-bottom: 1px solid var(--border); padding: 0;
  }
  .site-header .brand a { font-size: 18px; font-weight: 900; color: var(--text); text-decoration: none; }

  .progress-bar { margin: 16px 16px 20px; }
  .prog-step span { display: none; }

  form { border-radius: 0; border-left: none; border-right: none; padding: 20px 16px; margin-bottom: 0;}
  
  .summary-box { margin: 16px 0 14px; align-items: flex-start; text-align: left; padding: 18px; }
  .summary-price { font-size: 20px; text-align: left; }
  .summary-pills { justify-content: flex-start; }

  .secure-note { align-items: flex-start; text-align: left; padding-top: 8px; }

  footer { margin-top: 0; padding: 16px 16px 16px; text-align: left; background: var(--bg);}
  footer p { text-align: left; margin-bottom: 4px !important; }
}

/* =========================================
   MODAL INTERSTITIAL & LOADING CSS
   ========================================= */
.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(5px);
    z-index: 99999;
    display: none;
    align-items: center; justify-content: center;
    padding: 20px;
}
.modal-box {
    background: #fff;
    border-radius: 24px;
    width: 100%; max-width: 440px;
    padding: 32px 24px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    animation: modalPop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes modalPop {
    0% { transform: scale(0.9); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.modal-icon { font-size: 40px; margin-bottom: 16px; line-height: 1; }
.modal-title { font-size: 20px; font-weight: 900; color: #0F172A; margin-bottom: 12px; }
.modal-text { font-size: 15px; color: #475569; line-height: 1.6; margin-bottom: 24px; }
.modal-cta {
    display: block; width: 100%; color: #fff; text-decoration: none;
    font-weight: 900; font-size: 16px; padding: 16px; border-radius: 14px;
    margin-bottom: 16px; transition: transform 0.15s, filter 0.15s;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.modal-cta:hover { filter: brightness(0.95); transform: translateY(-1px); }
.modal-skip {
    background: none; border: none; color: #64748B; font-size: 14px;
    font-weight: 600; cursor: pointer; text-decoration: underline; padding: 8px;
    transition: color 0.15s;
}
.modal-skip:hover { color: #334155; }
</style>
<script src="/formular.js" defer></script>
    
    <script src="/cookie-consent.js" defer></script>

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
    <div class="prog-step active">
      <div class="prog-dot">2</div>
      <span>Ihre Daten</span>
    </div>
    <div class="prog-line"></div>
    <div class="prog-step">
      <div class="prog-dot">3</div>
      <span>Fertiges Dokument</span>
    </div>
  </div>

  <div class="page-title">
    <h1><?= $isKfz ? '🚗 KFZ-Versicherung kündigen' : ($isHandy ? '📱 Handyvertrag kündigen' : '🏋️ Fitnessstudio kündigen') ?></h1>
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

    <div class="section">
      <div style="display:inline-flex; align-items:center; gap:6px; background:#F0FDF4; color:#15803D; font-size:11px; font-weight:700; padding:4px 10px; border-radius:99px; border:1px solid #BBF7D0; margin-bottom: 12px;">
         🔒 SSL-Verschlüsselt & 100% DSGVO-konform
      </div>
      
      <div class="section-header" style="margin-top: 0;">
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
          <label>Ihre E-Mail <span style="font-weight:400;text-transform:none;letter-spacing:0">(Optional, für Bestätigung/Kopie)</span></label>
          <input name="email" type="email" placeholder="Ihre private E-Mail (z.B. name@gmx.de)" autocomplete="email">
        </div>
        <div class="field full">
          <div class="checkbox-row">
            <input id="trustpilotConsent" name="trustpilotConsent" type="checkbox" value="1">
            <div class="checkbox-label">
              <b>Bewertungs-Einladung erhalten</b>
              <div class="hint">Ich möchte nach Erhalt eine einmalige Einladung zur Bewertung auf Trustpilot bekommen. Hierzu wird meine E-Mail-Adresse an Trustpilot übermittelt. Diese Einwilligung ist freiwillig und kann jederzeit widerrufen werden.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="section-header">
        <div class="section-icon"><?= $isKfz ? '🚗' : ($isHandy ? '📡' : '🏋️') ?></div>
        <h2><?= $isKfz ? 'Versicherer' : ($isHandy ? 'Mobilfunkanbieter' : 'Fitnessstudio') ?></h2>
      </div>
      <div class="grid">
        <div class="field full">
          <label><?= $isKfz ? 'Versicherer *' : ($isHandy ? 'Anbieter *' : 'Fitnessstudio *') ?></label>
          <input
            type="text"
            name="anbieter"
            value="<?= htmlspecialchars($anbieter) ?>"
            placeholder="<?= $isKfz ? 'z. B. Allianz, HUK-COBURG, ERGO' : ($isHandy ? 'z. B. Vodafone, Telekom, O2' : 'z. B. McFit, FitX, Clever Fit') ?>"
            required
          >
        </div>
        <div class="field full optional-section" style="display:block;">
          <div style="font-size:12px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">Adresse des <?= $isKfz ? 'Versicherers' : ($isHandy ? 'Anbieters' : 'Studios') ?> <?= ($pf || $isFranchise) ? '' : '<span style="font-weight:400;text-transform:none">(optional)</span>' ?></div>
          
          <?php if ($pf): ?>
          <div class="hint" style="margin-bottom:10px; color: #16A34A;"><strong>Automatisch ausgefüllt</strong> – die Adresse wurde anhand Ihrer Anbieterauswahl hinterlegt.</div>
          <?php elseif ($isFranchise): ?>
          <div class="hint" style="margin-bottom:10px; color: #D97706;"><strong>Wichtig:</strong> <?= htmlspecialchars($anbieter) ?> wird im Franchise-System betrieben. Bitte geben Sie die Adresse Ihres Studios ein (siehe Rechnung/Vertrag), falls zur Hand.</div>
          <?php else: ?>
          <div class="hint" style="margin-bottom:10px; color: #16A34A;"><strong>Nicht zwingend erforderlich:</strong> Lassen Sie die Felder leer, falls Sie die Adresse nicht kennen. Das Kündigungsschreiben ist auch ohne diese Angabe zu 100% rechtsgültig.</div>
          <?php endif; ?>
          
          <div class="grid" style="margin:0;">
            <div class="field full">
              <input name="studioStreet" placeholder="Straße &amp; Hausnummer" value="<?= $pf ? htmlspecialchars($pf[0]) : '' ?>">
            </div>
            <div class="field">
              <input name="studioZip" inputmode="numeric" maxlength="5" placeholder="PLZ" value="<?= $pf ? htmlspecialchars($pf[1]) : '' ?>">
            </div>
            <div class="field">
              <input name="studioCity" placeholder="Ort" value="<?= $pf ? htmlspecialchars($pf[2]) : '' ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

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

    <div class="section">
      <div class="section-header">
        <div class="section-icon">🔢</div>
        <h2>Weitere Angaben</h2>
        <span class="opt-badge">Optional</span>
      </div>
      <div class="field">
        <label style="display:flex; justify-content:space-between; align-items:flex-end; width: 100%;">
          <span><?= $isKfz ? 'Versicherungsschein-Nr.' : 'Vertragsnummer' ?></span>
          <span style="color:var(--primary); font-size:11px; cursor:pointer; text-transform:none; font-weight:600; padding: 4px 0;" onclick="var inp = document.querySelector('input[name=\'contractNo\']'); inp.value = (inp.value === 'Wird nachgereicht') ? '' : 'Wird nachgereicht'; inp.dispatchEvent(new Event('input'));">Gerade nicht zur Hand?</span>
        </label>
        <input name="contractNo" placeholder="<?= $isKfz ? 'z. B. VS-123456789' : 'z. B. 12345678' ?>" onfocus="if(this.value === 'Wird nachgereicht') { this.value = ''; this.dispatchEvent(new Event('input')); }">
        <div class="hint"><?= $isKfz ? 'Steht auf Ihrem Versicherungsschein. Optional — ohne Nummer ist die Kündigung trotzdem gültig.' : 'Steht auf Ihrer Rechnung oben rechts. Optional — ohne Nummer ist die Kündigung trotzdem gültig.' ?></div>
      </div>
      <?php if ($isKfz): ?>
      <div class="field" style="margin-top:12px;">
        <label>Kennzeichen <span style="font-weight:400;text-transform:none;letter-spacing:0">(optional)</span></label>
        <input name="plate" placeholder="z. B. OF-KE 123">
        <div class="hint">Das amtliche Kennzeichen Ihres Fahrzeugs. Hilft bei der eindeutigen Zuordnung.</div>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($isRSG): ?>
    <div class="section">
      <div class="section-header">
        <div class="section-icon">⚠️</div>
        <h2>Kündigung per E-Mail nicht mehr möglich</h2>
      </div>
      <div style="background:#FEF9C3;border:1px solid #FDE68A;border-left:4px solid #F59E0B;border-radius:12px;padding:16px 20px;">
        <p style="margin:0 0 10px;font-weight:700;font-size:14px;color:#92400E;">RSG Group akzeptiert keine E-Mail-Kündigungen mehr</p>
        <p style="margin:0 0 12px;font-size:13px;color:#78350F;line-height:1.65;">Die RSG Group (McFit &amp; John Reed) hat die E-Mail-Adresse für Kündigungen abgeschaltet. Kündigungen müssen ab sofort über das offizielle Kontaktformular eingereicht werden.</p>
        <?php if (stripos($anbieter, 'john') !== false): ?>
        <a href="https://help.johnreed.fitness/hc/de" target="_blank" rel="nofollow noopener" style="display:inline-block;background:#DC2626;color:#FFFFFF;font-size:13px;font-weight:700;padding:11px 20px;border-radius:10px;text-decoration:none;">John Reed Kontaktformular öffnen →</a>
        <?php else: ?>
        <a href="https://hilfe.mcfit.com/hc/de" target="_blank" rel="nofollow noopener" style="display:inline-block;background:#DC2626;color:#FFFFFF;font-size:13px;font-weight:700;padding:11px 20px;border-radius:10px;text-decoration:none;">McFit Kontaktformular öffnen →</a>
        <?php endif; ?>
        <p style="margin:10px 0 0;font-size:12px;color:#78350F;">Ihr PDF können Sie trotzdem herunterladen und dort hochladen oder per Post versenden.</p>
      </div>
    </div>
    <?php else: ?>
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
            <b>Kündigung direkt ans <?= $isKfz ? 'Versicherungsunternehmen' : ($isHandy ? 'Unternehmen' : 'Fitnessstudio') ?> senden</b>
            <div class="hint">Das PDF bleibt immer zusätzlich als Download verfügbar.</div>
          </div>
        </div>
        
        <div class="field">
          <label>E-Mail des <?php echo $isKfz ? 'Versicherers' : ($isHandy ? 'Anbieters' : 'Studios'); ?> <span id="providerReq" style="color:var(--primary);display:none;">*</span></label>
          <input id="providerEmail" name="providerEmail" type="email" placeholder="<?php echo $isKfz ? 'service@versicherung.de' : ($isHandy ? 'service@anbieter.de' : 'service@studio.de'); ?>" value="">
          
          <?php if (!empty($pfEmail)): ?>
          <div class="hint" id="providerHint" style="color: #16A34A; font-weight: 600;">✓ E-Mail für <?php echo htmlspecialchars($anbieter, ENT_QUOTES, 'UTF-8'); ?> automatisch erkannt.</div>
          
          <div style="margin-top: 8px; font-size: 11px; line-height: 1.5; color: #475569; background: #F8FAFC; border-left: 3px solid #94A3B8; padding: 8px 12px; border-radius: 4px;">
            <strong>Wichtiger rechtlicher Hinweis:</strong> Bei einer Kündigung per E-Mail tragen Sie die Beweislast. Verlangen Sie immer eine Eingangsbestätigung. Sollten Sie nach 7 Tagen keine Bestätigung erhalten, empfehlen wir dringend, das heruntergeladene PDF zusätzlich per Einwurf-Einschreiben zu versenden.
          </div>
          
          <script>
            document.addEventListener('DOMContentLoaded', function() {
                var emailInput = document.getElementById('providerEmail');
                var sendCheckbox = document.getElementById('sendEmail');
                var defaultEmail = "<?php echo addslashes($pfEmail); ?>";
                
                if (emailInput && defaultEmail !== '') {
                    setTimeout(function() {
                        if (emailInput.value === '') {
                            emailInput.value = defaultEmail;
                        }
                    }, 150);
                    
                    if (sendCheckbox) {
                        sendCheckbox.addEventListener('change', function() {
                            if (!this.checked) {
                                setTimeout(function() {
                                    if (emailInput.value === '') {
                                        emailInput.value = defaultEmail;
                                    }
                                }, 50);
                            }
                        });
                    }
                }
            });
          </script>
          
          <?php else: ?>
          <div class="hint" id="providerHint" style="color: #D97706; background: #FFFBEB; border: 1px solid #FDE68A; padding: 12px 16px; border-radius: 10px; margin-top: 8px; line-height: 1.5;">
            <strong>Profi-Tipp:</strong> Wir haben für <em><?php echo htmlspecialchars($anbieter !== '' ? $anbieter : 'diesen Anbieter', ENT_QUOTES, 'UTF-8'); ?></em> keine spezielle Kündigungs-E-Mail hinterlegt.<br><br>
            <strong>So geht's trotzdem:</strong> Suchen Sie einfach auf der Website des Anbieters im <strong>Impressum</strong> nach der allgemeinen E-Mail-Adresse (z. B. info@...) und tragen Sie diese hier ein. Kündigungen an die offizielle Impressum-Adresse sind rechtlich absolut gültig. 
          </div>
          <?php endif; ?>
        </div>
        
      </div>
    </div>
    <?php endif; ?>

    <div class="summary-box">
      <div class="summary-price">Kostenlos · Sofort als PDF</div>
      <div class="summary-pills">
        <span>✓ Ohne Anmeldung</span>
        <span>✓ Geprüfte Vorlage</span>
        <span>✓ Kein Abo</span>
      </div>
    </div>

    <div class="mobile-sticky-submit">
        <button class="btn-submit" type="submit" id="mainSubmitBtn">PDF jetzt erstellen →</button>
    </div>
    
    <div class="secure-note" style="flex-direction: column; gap: 6px;">
      <div style="color: #64748B; font-size: 11px; line-height: 1.5;">
        Falls Sie eine eigene E-Mail-Adresse angegeben haben, erhalten Sie Ihr PDF zusätzlich als Kopie. Eine Einladung zur Bewertung auf Trustpilot senden wir nur, wenn Sie dem oben ausdrücklich zugestimmt haben.
      </div>
    </div>

  </form>

  <footer>
    <p>Hinweis: Keine Rechtsberatung. Es werden allgemein anerkannte Standardformulierungen verwendet.</p>
    <p>
<a href="/impressum.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Impressum</a> · <a href="/datenschutz.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Datenschutz</a> · <a href="/hilfe.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Hilfe</a> · <a href="#" onclick="return keResetConsent(event)" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Cookie-Einstellungen</a></p>
    <p>Erstellt mit <a href="https://digital-firmen.de"style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;> target="_blank">digital-firmen.de</a></p>
  </footer>
</div>

<div class="modal-overlay" id="upsellModal">
  <div class="modal-box">
    <div class="modal-icon">⏳</div>
    <div class="modal-title">Einen Moment noch...</div>
    <div class="modal-text" id="modalText">
      </div>
    <a href="#" target="_blank" rel="nofollow sponsored" class="modal-cta" id="modalMainCta">
      🔥 Tarife vergleichen
    </a>
    <button type="button" class="modal-skip" id="modalSkip">
      Nein danke, Kündigung jetzt herunterladen
    </button>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // === 1. Inline Validation System ===
    const inputs = document.querySelectorAll('input[required], input[name="zip"], input[name="email"], input[name="contractNo"]');
    
    inputs.forEach(input => {
        if (!input.parentNode.classList.contains('input-wrapper')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'input-wrapper';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            
            const check = document.createElement('span');
            check.className = 'check-icon';
            check.innerHTML = '✓';
            wrapper.appendChild(check);
        }

        input.addEventListener('input', function() {
            let isValid = false;
            let val = this.value.trim();
            
            if (this.name === 'zip') {
                isValid = /^\d{5}$/.test(val);
            } else if (this.type === 'email') {
                isValid = val === '' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
                if (val === '' && this.required) isValid = false;
            } else {
                isValid = val.length > 1;
            }
            
            if (isValid && val !== '') {
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });
    });

    // === 2. Mobile Sticky Button Behavior ===
    const stickySubmit = document.querySelector('.mobile-sticky-submit');
    if (stickySubmit && window.innerWidth <= 640) {
        const allFormInputs = document.querySelectorAll('input, select');
        allFormInputs.forEach(inp => {
            inp.addEventListener('focus', () => { 
                stickySubmit.style.transform = 'translateY(150%)'; 
            });
            inp.addEventListener('blur', () => { 
                setTimeout(() => { 
                    stickySubmit.style.transform = 'translateY(0)'; 
                }, 200); 
            });
        });
    }

    // === 3. Loading State & Contextual Interstitial Modal ===
    const keForm = document.getElementById('keForm');
    const submitBtns = document.querySelectorAll('.btn-submit');
    const modal = document.getElementById('upsellModal');
    let formIsSubmitting = false;

    if (keForm) {
        keForm.addEventListener('submit', function(e) {
            // Dacă deja trimitem, oprim orice dublu click
            if (formIsSubmitting) {
                e.preventDefault();
                return;
            }
            
            // Oprim formularul din drum ca să executăm animația
            e.preventDefault(); 
            formIsSubmitting = true;

            // Faza 1: Labor Illusion (0ms)
            submitBtns.forEach(btn => {
                btn.style.pointerEvents = 'none';
                btn.style.background = '#475569';
                btn.style.boxShadow = 'none';
                btn.innerHTML = '🔄 Daten werden verschlüsselt...';
            });

            // Faza 2: Validare vizuală (400ms)
            setTimeout(() => {
                submitBtns.forEach(btn => {
                    btn.innerHTML = '📄 PDF wird generiert...';
                });
            }, 400);

            // Faza 3: Routing & Modal (800ms)
            setTimeout(() => {
                const type = document.querySelector('input[name="type"]').value;
                const anbieterRaw = document.querySelector('input[name="anbieter"]').value || '';
                const anbieter = anbieterRaw.toLowerCase();

                // Dacă NU este telefonie mobilă, trimitem formularul direct (bypass)
                if (type !== 'handy') {
                    HTMLFormElement.prototype.submit.call(keForm);
                    return;
                }

                // Logica inteligentă pentru Telecom (Handy)
                let affiliateLink = '';
                let modalText = '';
                let btnText = '';
                let btnColor = '';

                if (anbieter.includes('telekom') || anbieter.includes('congstar') || anbieter.includes('fraenk')) {
                    // Cazul 1: Tariffuxx (Aceeași rețea, mai ieftin)
                    affiliateLink = 'https://www.tariffuxx.de/handytarife?r=1126248&subid=modal_gen';
                    modalText = `Sie kündigen bei <strong>${anbieterRaw}</strong>. Wussten Sie, dass Sie Ihre Rufnummer mitnehmen und im gleichen Netz bleiben können, aber bis zu 50% sparen?`;
                    btnText = '🔥 Im gleichen Netz bleiben & sparen';
                    btnColor = '#3B82F6'; 
                } else if (anbieter.includes('o2') || anbieter.includes('vodafone') || anbieter.includes('drillisch') || anbieter.includes('1&1') || anbieter.includes('freenet') || anbieter.includes('telefonica')) {
                    // Cazul 2: Telekom via Awin (Network Upgrade)
                    affiliateLink = 'https://www.awin1.com/awclick.php?gid=361937&mid=11430&awinaffid=2838186&linkid=4581533&clickref=modal_gen';
                    modalText = `Schlechtes Netz bei <strong>${anbieterRaw}</strong>? Sichern Sie sich jetzt das beste D1-Netz Deutschlands (Telekom) und nehmen Sie Ihre Rufnummer einfach mit.`;
                    btnText = '🔥 Zum besten Netz Deutschlands wechseln';
                    btnColor = '#E20074'; 
                } else {
                    // Cazul 3: Check24 (Fallback Comparator)
                    affiliateLink = 'https://a.check24.net/misc/click.php?pid=1169420&aid=18&deep=handytarife&cat=7';
                    modalText = `Sie kündigen bei <strong>${anbieterRaw}</strong>. Zahlen Sie künftig nicht mehr als nötig! Vergleichen Sie jetzt Tarife und sichern Sie sich exklusive Wechselboni.`;
                    btnText = '🔥 Handytarife vergleichen & sparen';
                    btnColor = '#1E40AF'; 
                }

                // Inserăm datele dinamice în Modal
                document.getElementById('modalText').innerHTML = modalText;
                const mainCta = document.getElementById('modalMainCta');
                mainCta.innerText = btnText;
                mainCta.style.background = btnColor;
                mainCta.href = affiliateLink;
                
                // Afișăm Modalul
                modal.style.display = 'flex';
            }, 800);
        });
    }

    // === 4. Modal Interactions ===
    const modalMainCta = document.getElementById('modalMainCta');
    const modalSkip = document.getElementById('modalSkip');

    if (modalMainCta) {
        modalMainCta.addEventListener('click', function() {
            // Lăsăm link-ul să se deschidă (are target="_blank"), dar închidem modalul și forțăm generarea PDF-ului
            setTimeout(() => {
                modal.style.display = 'none';
                HTMLFormElement.prototype.submit.call(keForm);
            }, 150);
        });
    }

    if (modalSkip) {
        modalSkip.addEventListener('click', function() {
            modal.style.display = 'none';
            HTMLFormElement.prototype.submit.call(keForm);
        });
    }
});
</script>

</body>
</html>