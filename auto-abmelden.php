<?php
declare(strict_types=1);

/* Live-Zähler pentru social proof: numărul de Abmeldungen plătite */
$orderCount = 0;
$ordersDir = __DIR__ . '/_orders';
if (is_dir($ordersDir)) {
    foreach (glob($ordersDir . '/*.json') ?: [] as $of) {
        $o = @json_decode((string)@file_get_contents($of), true);
        if (is_array($o)
            && (string)($o['type'] ?? '') === 'abmeldung'
            && (string)($o['paymentStatus'] ?? '') === 'paid') {
            $orderCount++;
        }
    }
}
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<link rel="preconnect" href="https://www.clarity.ms">
<link rel="dns-prefetch" href="//www.clarity.ms">
<meta name="color-scheme" content="light">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#15803D">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<title>Auto abmelden lassen — Online-Service ohne Behördengang | KündigungExpress</title>
<meta name="description" content="Kfz-Abmeldung online für 24,90 € pauschal — ohne Termin, ohne eID. Amtliche Bestätigung werktags binnen 24 Stunden. 100 % Erfolgs-Garantie, sicher via zertifiziertem Partner.">
<link rel="canonical" href="https://www.kuendigungexpress.de/auto-abmelden.html">
<meta property="og:type" content="website">
<meta property="og:site_name" content="KündigungExpress">
<meta property="og:image" content="https://www.kuendigungexpress.de/og-image.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:title" content="Auto abmelden lassen — Online-Service ohne Behördengang | KündigungExpress">
<meta property="og:description" content="Kfz-Abmeldung online für 24,90 € pauschal — ohne Termin, ohne eID. Amtliche Bestätigung werktags binnen 24 Stunden. 100 % Erfolgs-Garantie.">
<meta property="og:url" content="https://www.kuendigungexpress.de/auto-abmelden.html">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Auto abmelden lassen — Online-Service ohne Behördengang">
<meta name="twitter:description" content="Kfz-Abmeldung online für 24,90 € pauschal — ohne Termin, ohne eID. Amtliche Bestätigung werktags binnen 24 Stunden.">
<meta name="twitter:image" content="https://www.kuendigungexpress.de/og-image.png">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" href="/favicon-32.png" sizes="32x32">
<link rel="icon" type="image/png" href="/favicon-16.png" sizes="16x16">
<link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">

<script type="application/ld+json">
{"@context":"https://schema.org","@type":"Service","name":"Online-Kfz-Abmeldung","serviceType":"Kfz-Außerbetriebsetzung","provider":{"@type":"Organization","name":"KündigungExpress","url":"https://www.kuendigungexpress.de/"},"areaServed":{"@type":"Country","name":"Deutschland"},"description":"Wir übernehmen die Außerbetriebsetzung Ihres Fahrzeugs online. Ohne Behördengang, ohne Termin, ohne eID. Pauschalpreis inkl. aller Behördengebühren, amtliche Bestätigung werktags binnen 24 Stunden.","offers":{"@type":"Offer","price":"24.90","priceCurrency":"EUR","availability":"https://schema.org/InStock","url":"https://www.kuendigungexpress.de/abmelden-auftrag.html"}}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","dateModified":"2026-09-23T12:00:00+02:00","mainEntity":[
  {"@type":"Question","name":"Ist die Online-Abmeldung rechtlich anerkannt?","acceptedAnswer":{"@type":"Answer","text":"Ja. Wir übermitteln die Außerbetriebsetzung über die zertifizierte Großkundenschnittstelle (GKS) des Kraftfahrt-Bundesamts (KBA) an die zuständige Zulassungsstelle. Sie erhalten die amtliche Abmeldebestätigung als PDF per E-Mail — rechtlich identisch mit einer Abmeldung am Schalter."}},
  {"@type":"Question","name":"Wie schnell erhalte ich die amtliche Bestätigung?","acceptedAnswer":{"@type":"Answer","text":"Werktags in der Regel binnen 24 Stunden nach Ihrer Bestellung. Bestellungen am Wochenende bearbeiten wir am nächsten Werktag."}},
  {"@type":"Question","name":"Was passiert, wenn die Abmeldung nicht klappt?","acceptedAnswer":{"@type":"Answer","text":"100 % Erfolgs-Garantie: Wenn die Abmeldung aus Gründen, die auf unserer Seite liegen, nicht durchgeführt werden kann, erstatten wir den vollen Betrag von 24,90 €."}},
  {"@type":"Question","name":"Was passiert mit Kfz-Steuer und Versicherung?","acceptedAnswer":{"@type":"Answer","text":"Die Zulassungsstelle informiert Zoll (Kfz-Steuer) und Versicherer automatisch. Die Kfz-Steuer endet anteilig ab dem Abmeldedatum. Ihre Versicherung geht meist in eine beitragsfreie Ruheversicherung über (bis zu 18 Monate)."}},
  {"@type":"Question","name":"Ist meine Bezahlung sicher?","acceptedAnswer":{"@type":"Answer","text":"Ja. Die Bezahlung läuft über Stripe (PCI-DSS-zertifiziert). Verfügbare Zahlarten: Kredit- und Debitkarte, PayPal, Apple Pay, Google Pay, SEPA-Lastschrift, Klarna. SSL-verschlüsselte Übertragung, DSGVO-konforme Datenverarbeitung."}},
  {"@type":"Question","name":"Auch für Motorräder, Wohnmobile oder Anhänger?","acceptedAnswer":{"@type":"Answer","text":"Ja. Wir übernehmen die Abmeldung für alle Fahrzeugarten mit Sicherheitscodes: PKW, Motorräder, Wohnmobile, Anhänger. Voraussetzung ist die Erstzulassung nach dem 01.01.2015."}}
]}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"HowTo","name":"So beauftragen Sie Ihre Kfz-Abmeldung online","description":"In drei Schritten zur amtlichen Abmeldebestätigung: Daten eingeben, sicher bezahlen, Bestätigung per E-Mail erhalten.","totalTime":"PT5M","estimatedCost":{"@type":"MonetaryAmount","currency":"EUR","value":"24.90"},"step":[
  {"@type":"HowToStep","position":1,"name":"Daten eingeben","text":"Geben Sie in wenigen Minuten Kennzeichen, FIN und die Sicherheitscodes aus Fahrzeugschein und Kennzeichen ein."},
  {"@type":"HowToStep","position":2,"name":"Sicher bezahlen","text":"24,90 € pauschal inkl. aller Behördengebühren. Zahlung per Karte, PayPal, Apple Pay, Google Pay, SEPA oder Klarna."},
  {"@type":"HowToStep","position":3,"name":"Bestätigung erhalten","text":"Sie erhalten die amtliche Abmeldebestätigung als PDF per E-Mail — werktags in der Regel binnen 24 Stunden."}
]}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"BreadcrumbList","itemListElement":[
  {"@type":"ListItem","position":1,"name":"Startseite","item":"https://www.kuendigungexpress.de/"},
  {"@type":"ListItem","position":2,"name":"Auto abmelden","item":"https://www.kuendigungexpress.de/auto-abmelden.html"}
]}
</script>

<style>
  :root {
    --bg: #F7F9FC;
    --card: #FFFFFF;
    --text: #0F172A;
    --muted: #475569;
    --border: #E2E8F0;
    --btn: #15803D;
    --btnText: #FFFFFF;
    --soft: #F1F5F9;
    --soft-strong: #EAF7EF;
    --primary: #15803D;
    --primary-dark: #166534;
  }

  html { scroll-behavior: smooth; overflow-x: clip; }
  * { box-sizing: border-box; }
  body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    padding-top: 70px;
    margin: 0;
    line-height: 1.6;
    overflow-x: clip;
    max-width: 100vw;
    background: var(--bg);
    color: var(--text);
  }
  @media (max-width: 820px) { body { padding-top: 64px; } }
  .cv-auto { content-visibility: auto; contain-intrinsic-size: 800px; }

  /* HEADER */
  .apple-header {
    position: fixed; top: 0; left: 0; width: 100%; z-index: 9999;
    background-color: rgba(251, 251, 253, 0.85);
    backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(226, 232, 240, 0.7);
  }
  .apple-header-inner {
    max-width: 1100px; width: 100%; margin: 0 auto; padding: 20px 16px;
    display: flex; justify-content: space-between; align-items: center; box-sizing: border-box;
  }
  .apple-brand a { font-size: 22px; font-weight: 900; color: var(--text); text-decoration: none; letter-spacing: -0.5px; }
  .apple-nav { display: flex; gap: 24px; align-items: center; }
  .apple-nav a { font-size: 14px; font-weight: 500; color: var(--muted); text-decoration: none; }
  .apple-nav a:hover { color: var(--text); }
  @media (max-width: 820px) {
    .apple-header-inner { justify-content: center; padding: 16px; }
    .apple-nav { display: none; }
  }

  /* HERO */
  .hero-edge-wrapper {
    background-color: #FBFBFD;
    border-bottom: 1px solid var(--border);
    padding: 48px 16px 72px;
  }
  .hero-inner { max-width: 1100px; margin: 0 auto; }
  .hero-split { display: grid; grid-template-columns: 1.4fr 1fr; gap: 48px; align-items: start; }
  .hero-visual { align-self: start; }
  @media (max-width: 820px) {
    .hero-split { grid-template-columns: 1fr; gap: 0; }
    .hero-visual { display: none; }
  }

  .hero-h1 {
    font-size: clamp(30px, 4.4vw, 46px);
    font-weight: 900;
    letter-spacing: -0.5px;
    line-height: 1.15;
    margin: 0 0 18px;
    color: var(--text);
    text-wrap: balance;
    text-align: left;
  }
  @media (max-width: 640px) {
    .hero-h1 { letter-spacing: -0.2px; font-size: clamp(27px, 7.5vw, 36px); }
  }
  .hero-h1 .accent { color: var(--primary); }
  .hero-content {
    display: flex; flex-direction: column; align-items: flex-start; gap: 0;
  }
  .hero-content .cta-primary { margin-top: 12px; }
  .hero-subtitle {
    margin: 0 0 44px;
    font-size: clamp(15px, 1.8vw, 18px);
    line-height: 1.55;
    color: var(--muted);
    max-width: 560px;
  }
  @media (max-width: 820px) {
    .hero-subtitle { margin-bottom: 40px; }
  }
  .hero-price {
    display: inline-flex; align-items: baseline; gap: 8px;
    background: var(--soft-strong);
    border: 1px solid rgba(22,163,74,0.25);
    padding: 8px 16px;
    border-radius: 100px;
    margin-bottom: 20px;
    font-weight: 700;
  }
  .hero-price .price-num { font-size: 20px; color: var(--primary-dark); font-weight: 900; }
  .hero-price .price-lbl { font-size: 13px; color: var(--muted); }

  .cta-primary {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    background: var(--btn); color: #fff !important;
    padding: 16px 30px;
    border-radius: 14px;
    font-weight: 900; font-size: 17px;
    text-decoration: none !important;
    white-space: nowrap;
    box-shadow: 0 8px 20px rgba(22,163,74,0.28);
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .cta-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(22,163,74,0.35); }
  .cta-primary::after { content: "→"; font-weight: 700; }
  @media (max-width: 400px) {
    .cta-primary { font-size: 15px; padding: 15px 20px; }
  }

  .hero-trustbar {
    display: flex; gap: 8px; flex-wrap: wrap; margin-top: 24px;
  }
  .trust-pill {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 12.5px; font-weight: 700; color: var(--text);
    background: var(--card); border: 1px solid var(--border);
    padding: 6px 12px; border-radius: 100px;
    box-shadow: 0 2px 6px rgba(15,23,42,0.03);
  }
  .trust-pill svg { flex-shrink: 0; }

  /* HERO VISUAL */
  .hero-visual-card {
    background: linear-gradient(165deg, #F0FDF4 0%, #FFFFFF 72%);
    border: 1px solid var(--border);
    border-radius: 20px;
    box-shadow: 0 32px 64px -16px rgba(15,23,42,0.12), 0 0 1px rgba(15,23,42,0.08);
    padding: 26px 28px 22px;
    max-width: 440px;
    margin-left: auto;
  }
  @media (max-width: 820px) { .hero-visual-card { margin: 0 auto; } }
  .hero-visual-card .badges { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; margin-top: 14px; }
  .hero-visual-card .badges span {
    display: inline-flex; align-items: center; gap: 4px;
    background: #F0FDF4; color: var(--primary-dark); border: 1px solid #BBF7D0;
    font-size: 12px; font-weight: 700; padding: 5px 11px; border-radius: 99px;
  }

  /* PAGE FLOW */
  .wrap { max-width: 960px; margin: 0 auto; padding: 48px 24px; }
  .page-flow { display: flex; flex-direction: column; gap: clamp(40px, 6vw, 60px); }

  h2 {
    font-size: clamp(24px, 3.5vw, 30px);
    font-weight: 800;
    letter-spacing: -0.5px;
    margin: 0 0 16px;
    color: var(--text);
    text-wrap: balance;
  }
  .section-intro { color: var(--muted); font-size: 16px; line-height: 1.7; margin: 0 0 28px; max-width: 640px; }
  .section-center { text-align: center; }
  .section-center .section-intro { margin-left: auto; margin-right: auto; }

  .section-soft {
    background: #FFFFFF; border: 1px solid var(--border);
    border-radius: 24px; padding: 36px;
    box-shadow: 0 2px 12px rgba(15,23,42,0.04);
  }
  @media (max-width: 640px) {
    .section-soft { padding: 26px 20px; border-radius: 18px; }
  }

  /* 3-STEP HOW IT WORKS */
  .steps-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 24px;
  }
  @media (max-width: 820px) { .steps-grid { grid-template-columns: 1fr; gap: 16px; } }
  .step-card {
    background: #fff; border: 1px solid var(--border); border-radius: 16px;
    padding: 24px 22px; position: relative; text-align: left;
  }
  .step-num {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--primary); color: #fff;
    font-weight: 900; font-size: 16px; margin-bottom: 12px;
  }
  .step-title { font-weight: 800; font-size: 17px; color: var(--text); margin: 0 0 8px; }
  .step-desc { color: var(--muted); font-size: 14.5px; line-height: 1.6; margin: 0; }

  /* BENEFITS GRID */
  .benefits-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 18px; margin-top: 24px;
  }
  @media (max-width: 640px) { .benefits-grid { grid-template-columns: 1fr; } }
  .benefit-card {
    display: flex; gap: 14px; align-items: flex-start;
    background: #fff; border: 1px solid var(--border); border-radius: 16px;
    padding: 20px 22px;
  }
  .benefit-icon {
    flex-shrink: 0; width: 36px; height: 36px;
    display: inline-flex; align-items: center; justify-content: center;
    background: var(--soft-strong); border-radius: 10px;
    font-size: 18px;
  }
  .benefit-text { min-width: 0; overflow-wrap: break-word; }
  .benefit-text h3 { font-size: 15.5px; font-weight: 800; color: var(--text); margin: 0 0 4px; }
  .benefit-text p { font-size: 14px; color: var(--muted); margin: 0; line-height: 1.55; }

  /* GARANTIE BADGE */
  .garantie-wrap {
    background: linear-gradient(160deg, #F0FDF4 0%, #FFFFFF 100%);
    border: 2px solid var(--primary);
    border-radius: 24px;
    padding: 34px 32px;
    text-align: center;
    box-shadow: 0 12px 32px rgba(22,163,74,0.12);
  }
  .garantie-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 64px; height: 64px; border-radius: 50%;
    background: var(--primary); color: #fff;
    margin-bottom: 16px;
    box-shadow: 0 8px 20px rgba(22,163,74,0.3);
  }
  .garantie-wrap h2 { color: var(--primary-dark); }
  .garantie-wrap p { max-width: 560px; margin: 12px auto 0; color: var(--text); font-size: 15.5px; line-height: 1.65; text-wrap: balance; }

  /* CHECKLIST */
  .checklist { list-style: none; padding: 0; margin: 24px 0 0; display: grid; gap: 12px; }
  .checklist li {
    position: relative;
    background: #fff; border: 1px solid var(--border); border-radius: 12px;
    padding: 14px 18px 14px 44px;
    color: var(--text); font-size: 15px; line-height: 1.55;
    overflow-wrap: break-word;
  }
  .checklist li::before {
    content: "✓";
    position: absolute; left: 16px; top: 12px;
    color: var(--primary);
    font-weight: 900; font-size: 18px;
    line-height: 1.4;
  }
  .checklist-hint { font-size: 13.5px; color: var(--muted); margin-top: 14px; text-align: right; }
  .checklist-hint a { color: var(--primary) !important; font-weight: 600 !important; text-decoration: none !important; }

  /* VERGLEICH TABLE */
  .vergleich {
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 24px;
  }
  @media (max-width: 640px) { .vergleich { grid-template-columns: 1fr; } }
  .vergleich-col {
    background: #fff; border: 1px solid var(--border);
    border-radius: 16px; padding: 22px 24px;
  }
  .vergleich-col.highlight { border-color: var(--primary); box-shadow: 0 8px 24px rgba(22,163,74,0.1); }
  .vergleich-col h3 { font-size: 16px; font-weight: 800; margin: 0 0 14px; color: var(--text); }
  .vergleich-col.highlight h3 { color: var(--primary-dark); }
  .vergleich-col ul { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; }
  .vergleich-col li { font-size: 14px; color: var(--muted); line-height: 1.5; padding-left: 20px; position: relative; }
  .vergleich-col li::before { content: "•"; position: absolute; left: 6px; color: var(--muted); }
  .vergleich-col.highlight li::before { content: "✓"; color: var(--primary); font-weight: 900; left: 4px; }

  /* SONDERFÄLLE */
  .sonderfaelle-grid {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-top: 24px;
  }
  @media (max-width: 640px) { .sonderfaelle-grid { grid-template-columns: 1fr; } }
  .sonderfall-card {
    display: flex; align-items: center; gap: 14px;
    background: #fff; border: 1px solid var(--border); border-radius: 14px;
    padding: 18px 20px;
    text-decoration: none !important;
    transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
  }
  .sonderfall-card:hover {
    border-color: var(--primary);
    box-shadow: 0 6px 18px rgba(22,163,74,0.1);
    transform: translateY(-1px);
  }
  .sonderfall-icon {
    flex-shrink: 0; width: 40px; height: 40px;
    display: inline-flex; align-items: center; justify-content: center;
    background: var(--soft-strong); border-radius: 10px; font-size: 20px;
  }
  .sonderfall-text { min-width: 0; }
  .sonderfall-text h3 { font-size: 15px; font-weight: 800; color: var(--text) !important; margin: 0 0 2px; }
  .sonderfall-text p { font-size: 13px; color: var(--muted) !important; margin: 0; line-height: 1.45; }
  .sonderfall-card::after {
    content: "→"; margin-left: auto; flex-shrink: 0;
    color: var(--primary); font-weight: 700; font-size: 16px;
  }

  /* FAQ */
  .faq-accordion {
    background: #fff; border: 1px solid var(--border); border-radius: 14px;
    margin-bottom: 10px; overflow: hidden; transition: all .25s ease;
  }
  .faq-accordion[open] { box-shadow: 0 4px 16px rgba(15,23,42,0.05); border-color: rgba(22,163,74,0.3); }
  .faq-summary {
    padding: 18px 20px; font-weight: 700; font-size: 15.5px; color: var(--text);
    cursor: pointer; list-style: none;
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
  }
  .faq-summary::-webkit-details-marker { display: none; }
  .faq-summary::after {
    content: "+"; font-size: 24px; line-height: 1; color: var(--primary);
    font-weight: 400; transition: transform .3s ease; flex-shrink: 0;
  }
  .faq-accordion[open] .faq-summary::after { transform: rotate(45deg); }
  .faq-content { padding: 0 20px 20px; color: var(--muted); line-height: 1.7; font-size: 14.5px; margin: 0; }
  .faq-content a { color: var(--primary) !important; font-weight: 600 !important; text-decoration: none !important; }

  /* FINAL CTA */
  .final-cta-wrap {
    background: linear-gradient(160deg, #F0FDF4 0%, #FFFFFF 100%);
    border: 1px solid rgba(22,163,74,0.2);
    border-radius: 24px;
    padding: 40px 32px;
    text-align: center;
    box-shadow: 0 12px 32px rgba(22,163,74,0.1);
  }
  .final-cta-wrap h2 { margin-bottom: 10px; }
  .final-cta-wrap p { color: var(--muted); font-size: 15px; margin: 0 auto 24px; max-width: 500px; }
  .final-cta-note { margin-top: 14px; font-size: 12.5px; color: var(--muted); }

  /* MOBILE STICKY CTA */
  .mobile-sticky-cta {
    display: none;
    position: fixed; bottom: 0; left: 0; right: 0;
    background: #fff; border-top: 1px solid var(--border);
    padding: 12px 16px;
    padding-bottom: calc(12px + env(safe-area-inset-bottom));
    z-index: 2147483647;
    box-shadow: 0 -8px 32px rgba(15,23,42,0.1);
    transform: translateY(0);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease;
    opacity: 1;
  }
  .mobile-sticky-cta.is-hidden {
    transform: translateY(120%);
    opacity: 0;
    pointer-events: none;
  }
  .mobile-sticky-cta a {
    display: block; width: 100%; text-align: center;
    background: var(--btn); color: #fff !important;
    padding: 15px; border-radius: 12px;
    font-weight: 900; font-size: 16px; text-decoration: none !important;
  }
  @media (max-width: 820px) {
    .mobile-sticky-cta { display: block; }
    body { padding-bottom: 76px; }
  }

  @media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
      animation-duration: .001ms !important;
      transition-duration: .001ms !important;
    }
  }
</style>

<script src="/cookie-consent.js" defer></script>
<link rel="preload" href="/style.css?v=30" as="style">
<link rel="stylesheet" href="/style.css?v=30">
<meta property="article:modified_time" content="2026-09-23T12:00:00+02:00">
</head>
<body>

<header class="apple-header">
  <div class="apple-header-inner">
    <div class="apple-brand"><a href="/">KündigungExpress</a></div>
    <nav class="apple-nav">
      <a href="/hilfe.html">Hilfe / FAQ</a>
      <a href="/unsere-mission.html">Über uns</a>
      <a href="/impressum.html">Impressum</a>
      <a href="/datenschutz.html">Datenschutz</a>
    </nav>
  </div>
</header>

<!-- HERO -->
<div class="hero-edge-wrapper">
  <section class="hero-inner">
    <div class="hero-split">
      <div class="hero-content">
        <div class="hero-price">
          <span class="price-num">24,90 &euro;</span>
          <span class="price-lbl">pauschal &middot; inkl. aller Geb&uuml;hren</span>
        </div>
        <h1 class="hero-h1">
          Ihr Auto abmelden lassen &mdash; <span class="accent">online in 5 Minuten,</span> amtliche Best&auml;tigung binnen 24 Stunden
        </h1>
        <p class="hero-subtitle">
          Kein Beh&ouml;rdengang. Kein Termin. Kein eID. Wir &uuml;bernehmen die Au&szlig;erbetriebsetzung Ihres Fahrzeugs komplett online &uuml;ber die zertifizierte Schnittstelle des Kraftfahrt-Bundesamts.
        </p>
        <a href="/abmelden-auftrag.html" class="cta-primary">Jetzt in 5 Minuten beauftragen</a>
        <div class="hero-trustbar">
          <span class="trust-pill">
            <svg width="14" height="14" fill="none" stroke="var(--primary)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            100 % Erfolgs-Garantie
          </span>
          <span class="trust-pill">
            <svg width="14" height="14" fill="none" stroke="var(--primary)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
            SSL &amp; DSGVO
          </span>
          <span class="trust-pill">
            <svg width="14" height="14" fill="none" stroke="var(--primary)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
            Zertifizierter Partner
          </span>
        </div>
      </div>

      <div class="hero-visual">
        <div class="hero-visual-card">
          <svg viewBox="0 0 440 250" width="100%" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Abgemeldetes Fahrzeug">
            <ellipse cx="220" cy="212" rx="152" ry="12" fill="#0F172A" opacity="0.06"/>
            <circle cx="150" cy="192" r="27" fill="#0F172A"/><circle cx="150" cy="192" r="10" fill="#CBD5E1"/>
            <circle cx="300" cy="192" r="27" fill="#0F172A"/><circle cx="300" cy="192" r="10" fill="#CBD5E1"/>
            <path d="M70 176 L70 156 Q70 150 76 150 L114 150 L142 116 Q148 108 158 108 L246 108 Q258 108 265 117 L290 150 L352 158 Q366 160 372 170 L372 176 Q372 182 366 182 L327 182 A27 27 0 0 0 273 182 L177 182 A27 27 0 0 0 123 182 L76 182 Q70 182 70 176 Z" fill="#16A34A"/>
            <path d="M152 148 L170 122 Q173 118 178 118 L206 118 L206 148 Z" fill="#DCFCE7"/>
            <path d="M216 148 L216 118 L244 118 Q252 118 256 123 L272 148 Z" fill="#DCFCE7"/>
            <circle cx="344" cy="90" r="30" fill="#15803D"/>
            <circle cx="344" cy="90" r="30" fill="none" stroke="#ffffff" stroke-opacity="0.5" stroke-width="2"/>
            <path d="M331 90 l9 9 l16 -18" fill="none" stroke="#ffffff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <div style="text-align:center; margin-top: 6px;">
            <div style="font-weight: 800; font-size: 17px; color: var(--text);">Fahrzeug abgemeldet</div>
            <div style="font-size: 13px; color: var(--muted); margin-top: 2px;">Amtliche Bestätigung per E-Mail</div>
          </div>
          <div class="badges">
            <span>werktags &le; 24 Std.</span>
            <span>ohne Termin</span>
            <span>ohne eID</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="wrap">
<main class="page-flow">

  <!-- 1) HOW IT WORKS -->
  <section class="section-soft cv-auto section-center" aria-labelledby="how-title">
    <h2 id="how-title">So funktioniert's &mdash; in 3 Schritten</h2>
    <p class="section-intro">Sie geben die Daten ein, wir &uuml;bermitteln die Abmeldung an die Beh&ouml;rde. Ohne Umwege.</p>
    <div class="steps-grid">
      <div class="step-card">
        <div class="step-num">1</div>
        <h3 class="step-title">Daten eingeben</h3>
        <p class="step-desc">In wenigen Minuten: Kennzeichen, FIN und die Sicherheitscodes aus Fahrzeugschein und Kennzeichen.</p>
      </div>
      <div class="step-card">
        <div class="step-num">2</div>
        <h3 class="step-title">Sicher bezahlen</h3>
        <p class="step-desc">24,90 &euro; pauschal, inkl. aller Beh&ouml;rdengeb&uuml;hren. Karte, PayPal, Apple Pay, Google Pay, SEPA oder Klarna.</p>
      </div>
      <div class="step-card">
        <div class="step-num">3</div>
        <h3 class="step-title">Bestätigung erhalten</h3>
        <p class="step-desc">Amtliche Abmeldebestätigung als PDF per E-Mail &mdash; werktags in der Regel binnen 24 Stunden.</p>
      </div>
    </div>
  </section>

  <!-- 2) WARUM WIR -->
  <section class="section-soft cv-auto" aria-labelledby="warum-title">
    <h2 id="warum-title">Warum KündigungExpress</h2>
    <p class="section-intro">Kein Callcenter, keine Warteschleife. Direkter Kontakt und transparenter Ablauf.</p>
    <div class="benefits-grid">
      <div class="benefit-card">
        <div class="benefit-icon">🏛️</div>
        <div class="benefit-text">
          <h3>Kein Beh&ouml;rdengang</h3>
          <p>Keine Termine, keine Wartezeit, kein Personalausweis mit eID n&ouml;tig.</p>
        </div>
      </div>
      <div class="benefit-card">
        <div class="benefit-icon">💶</div>
        <div class="benefit-text">
          <h3>Alles im Pauschalpreis</h3>
          <p>24,90 &euro; &mdash; inklusive aller Beh&ouml;rdengeb&uuml;hren. Keine versteckten Kosten.</p>
        </div>
      </div>
      <div class="benefit-card">
        <div class="benefit-icon">⚖️</div>
        <div class="benefit-text">
          <h3>Rechtssicher &uuml;bermittelt</h3>
          <p>&Uuml;ber die zertifizierte Gro&szlig;kundenschnittstelle (GKS) des Kraftfahrt-Bundesamts.</p>
        </div>
      </div>
      <div class="benefit-card">
        <div class="benefit-icon">⏱️</div>
        <div class="benefit-text">
          <h3>Werktags &le; 24 Stunden</h3>
          <p>Amtliche Best&auml;tigung als PDF per E-Mail &mdash; meist deutlich schneller.</p>
        </div>
      </div>
      <div class="benefit-card">
        <div class="benefit-icon">🔒</div>
        <div class="benefit-text">
          <h3>Sicher &amp; DSGVO-konform</h3>
          <p>SSL-verschl&uuml;sselt. Zahlung &uuml;ber Stripe (PCI-DSS). Sensible Codes werden nach Abschluss gel&ouml;scht.</p>
        </div>
      </div>
      <div class="benefit-card">
        <div class="benefit-icon">📞</div>
        <div class="benefit-text">
          <h3>Direkter Kontakt</h3>
          <p>Bei Fragen antworten Sie einfach auf Ihre Bestätigung &mdash; kein Callcenter dazwischen.</p>
        </div>
      </div>
    </div>
  <?php if ($orderCount >= 10): ?>
    <p style="margin-top: 22px; text-align: center; font-size: 14px; color: var(--muted);">
      Bereits <strong style="color: var(--primary-dark);"><?= $orderCount ?> Kfz-Abmeldungen</strong> &uuml;ber K&uuml;ndigungExpress erfolgreich beauftragt.
    </p>
  <?php endif; ?>
  </section>

  <!-- 3) ERFOLGS-GARANTIE -->
  <section class="garantie-wrap cv-auto" aria-labelledby="garantie-title">
    <div class="garantie-icon">
      <svg width="30" height="30" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        <path d="M9 12l2 2 4-4"/>
      </svg>
    </div>
    <h2 id="garantie-title">100 % Erfolgs-Garantie</h2>
    <p>Klappt die Abmeldung aus Gr&uuml;nden, die auf <strong>unserer Seite</strong> liegen, nicht &mdash; erstatten wir den vollen Betrag von 24,90 &euro;. Fair, transparent, ohne Wenn und&nbsp;Aber.</p>
  </section>

  <!-- 4) WAS SIE BRAUCHEN -->
  <section class="section-soft cv-auto" aria-labelledby="brauchen-title">
    <h2 id="brauchen-title">Was Sie brauchen</h2>
    <p class="section-intro">Diese drei Voraussetzungen m&uuml;ssen erf&uuml;llt sein, damit die Online-Abmeldung m&ouml;glich ist:</p>
    <ul class="checklist">
      <li>Zulassungsbescheinigung Teil I mit dem freigelegten <strong>7-stelligen Sicherheitscode</strong></li>
      <li>Die <strong>3-stelligen Codes unter den Stempelplaketten</strong> der Kennzeichen (vorne und/oder hinten)</li>
      <li>Fahrzeug wurde <strong>nach dem 01.01.2015 zugelassen</strong> (Voraussetzung f&uuml;r i-Kfz)</li>
    </ul>
    <p class="checklist-hint"><a href="/sicherheitscode-fahrzeugschein-finden.html">Wo finde ich diese Codes? &rarr;</a></p>
  </section>

  <!-- 5) VERGLEICH -->
  <section class="section-soft cv-auto" aria-labelledby="vergleich-title">
    <h2 id="vergleich-title">Selbst zur Zulassungsstelle vs. mit K&uuml;ndigungExpress</h2>
    <div class="vergleich">
      <div class="vergleich-col">
        <h3>Selbst zur Zulassungsstelle</h3>
        <ul>
          <li>Termin oft erst in 2&ndash;6 Wochen verf&uuml;gbar</li>
          <li>Fahrt zur Beh&ouml;rde + Wartezeit vor Ort</li>
          <li>15&ndash;30 Minuten am Schalter</li>
          <li>Kennzeichen mitbringen und entstempeln lassen</li>
          <li>Amtliche Geb&uuml;hr ~16,80 &euro; am Schalter</li>
        </ul>
      </div>
      <div class="vergleich-col highlight">
        <h3>Mit K&uuml;ndigungExpress</h3>
        <ul>
          <li>Sofort m&ouml;glich, ohne Termin</li>
          <li>5 Minuten Dateneingabe von zu Hause</li>
          <li>Amtliche Best&auml;tigung werktags &le; 24 Std.</li>
          <li>Kennzeichen bleibt bei Ihnen (Entstempelung entf&auml;llt)</li>
          <li>24,90 &euro; pauschal, inkl. aller Geb&uuml;hren</li>
        </ul>
      </div>
    </div>
  </section>

  <!-- 5b) SONDERFÄLLE -->
  <section class="section-soft cv-auto" aria-labelledby="sonder-title">
    <h2 id="sonder-title">Ihre Situation im Detail</h2>
    <p class="section-intro">Ob Verkauf, Umzug oder Motorrad &mdash; f&uuml;r besondere F&auml;lle finden Sie hier die passenden Infos. Der Ablauf bleibt gleich: wir &uuml;bernehmen die Abmeldung f&uuml;r Sie.</p>
    <div class="sonderfaelle-grid">
      <a href="/auto-abmelden-verkauf.html" class="sonderfall-card">
        <span class="sonderfall-icon">🤝</span>
        <span class="sonderfall-text">
          <h3>Auto verkauft</h3>
          <p>Selbst abmelden oder K&auml;ufer ummelden lassen?</p>
        </span>
      </a>
      <a href="/motorrad-saisonkennzeichen-abmelden.html" class="sonderfall-card">
        <span class="sonderfall-icon">🏍️</span>
        <span class="sonderfall-text">
          <h3>Motorrad &amp; Saisonfahrzeug</h3>
          <p>Wohnmobil, Cabrio oder Saisonkennzeichen.</p>
        </span>
      </a>
      <a href="/auto-abmelden-ausland.html" class="sonderfall-card">
        <span class="sonderfall-icon">✈️</span>
        <span class="sonderfall-text">
          <h3>Umzug ins Ausland</h3>
          <p>Fahrzeug in Deutschland abmelden vor dem Umzug.</p>
        </span>
      </a>
      <a href="/auto-abmelden-ohne-papiere.html" class="sonderfall-card">
        <span class="sonderfall-icon">📄</span>
        <span class="sonderfall-text">
          <h3>Papiere fehlen</h3>
          <p>Fahrzeugschein, Kennzeichen oder Codes verloren.</p>
        </span>
      </a>
    </div>
  </section>

  <!-- 6) FAQ -->
  <section class="cv-auto" aria-labelledby="faq-title">
    <h2 id="faq-title" style="margin-bottom: 20px;">H&auml;ufig gestellte Fragen</h2>

    <details class="faq-accordion">
      <summary class="faq-summary">Ist die Online-Abmeldung rechtlich anerkannt?</summary>
      <p class="faq-content">Ja. Wir &uuml;bermitteln die Au&szlig;erbetriebsetzung &uuml;ber die zertifizierte Gro&szlig;kundenschnittstelle (GKS) des Kraftfahrt-Bundesamts an die zust&auml;ndige Zulassungsstelle. Sie erhalten die amtliche Abmeldebest&auml;tigung als PDF per E-Mail &mdash; rechtlich identisch mit einer Abmeldung am Schalter.</p>
    </details>

    <details class="faq-accordion">
      <summary class="faq-summary">Wie schnell erhalte ich die amtliche Best&auml;tigung?</summary>
      <p class="faq-content">Werktags in der Regel binnen 24 Stunden nach Ihrer Bestellung. Bestellungen am Wochenende bearbeiten wir am n&auml;chsten Werktag.</p>
    </details>

    <details class="faq-accordion">
      <summary class="faq-summary">Was passiert, wenn die Abmeldung nicht klappt?</summary>
      <p class="faq-content"><strong>100 % Erfolgs-Garantie:</strong> Wenn die Abmeldung aus Gr&uuml;nden, die auf unserer Seite liegen, nicht durchgef&uuml;hrt werden kann, erstatten wir den vollen Betrag von 24,90 &euro;.</p>
    </details>

    <details class="faq-accordion">
      <summary class="faq-summary">Was passiert mit Kfz-Steuer und Versicherung?</summary>
      <p class="faq-content">Die Zulassungsstelle informiert Zoll (Kfz-Steuer) und Versicherer automatisch. Die Kfz-Steuer endet anteilig ab dem Abmeldedatum. Ihre Versicherung geht meist in eine beitragsfreie Ruheversicherung &uuml;ber (bis zu 18 Monate). Ob Sie aktiv k&uuml;ndigen oder wechseln sollten, h&auml;ngt davon ab, was mit dem Fahrzeug passiert &mdash; bei Verkauf oder neuem Fahrzeug lohnt oft die aktive <a href="/kfz-versicherung-kuendigen.html">KFZ-Versicherung k&uuml;ndigen</a>.</p>
    </details>

    <details class="faq-accordion">
      <summary class="faq-summary">Ist meine Bezahlung sicher?</summary>
      <p class="faq-content">Ja. Die Bezahlung l&auml;uft &uuml;ber Stripe (PCI-DSS-zertifiziert). Verf&uuml;gbare Zahlarten: Kredit- und Debitkarte, PayPal, Apple Pay, Google Pay, SEPA-Lastschrift, Klarna. SSL-verschl&uuml;sselte &Uuml;bertragung, DSGVO-konforme Datenverarbeitung. Sensible Sicherheitscodes werden nach Abschluss der Abmeldung automatisch gel&ouml;scht.</p>
    </details>

    <details class="faq-accordion">
      <summary class="faq-summary">Auch f&uuml;r Motorr&auml;der, Wohnmobile oder Anh&auml;nger?</summary>
      <p class="faq-content">Ja. Wir &uuml;bernehmen die Abmeldung f&uuml;r alle Fahrzeugarten mit Sicherheitscodes: PKW, Motorr&auml;der, Wohnmobile, Anh&auml;nger. Voraussetzung ist die Erstzulassung nach dem 01.01.2015 und die vorhandenen Sicherheitscodes auf Fahrzeugschein und Kennzeichen.</p>
    </details>

    <details class="faq-accordion">
      <summary class="faq-summary">Was, wenn die Sicherheitscodes fehlen oder das Fahrzeug &auml;lter ist?</summary>
      <p class="faq-content">Fahrzeuge, die vor dem 01.01.2015 zugelassen wurden, oder Fahrzeuge ohne Sicherheitscodes k&ouml;nnen nicht online abgemeldet werden &mdash; hier ist die Abmeldung pers&ouml;nlich bei der Zulassungsstelle n&ouml;tig. Details finden Sie unter <a href="/auto-abmelden-ohne-papiere.html">Auto abmelden ohne Fahrzeugschein</a>.</p>
    </details>
  </section>

  <!-- 7) FINAL CTA -->
  <section class="final-cta-wrap cv-auto">
    <h2>Jetzt in 5 Minuten beauftragen</h2>
    <p>24,90 &euro; pauschal, inkl. aller Geb&uuml;hren. Amtliche Best&auml;tigung werktags binnen 24 Stunden.</p>
    <a href="/abmelden-auftrag.html" class="cta-primary">Abmeldung beauftragen</a>
    <div class="final-cta-note">100 % Erfolgs-Garantie &middot; SSL &amp; DSGVO &middot; Zahlung &uuml;ber Stripe</div>
  </section>

</main>
</div>

<footer class="cv-auto" style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; margin-top: 20px; padding: 16px 0;">
  <p class="brand-disclaimer" style="width: 100%; text-align: center; margin: 0 0 5px; color: #64748B; font-size: 12px;">&copy; 2026 K&uuml;ndigungExpress &middot; <a href="/unsere-mission.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">&Uuml;ber uns</a> &middot; <a href="/wie-wir-kuendigungexpress-gebaut-haben.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Unsere Geschichte</a> &middot; <a href="/impressum.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Impressum</a> &middot; <a href="/datenschutz.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Datenschutz</a> &middot; <a href="/agb.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">AGB</a> &middot; <a href="/hilfe.html" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Hilfe</a> &middot; <a href="#" onclick="return keResetConsent(event)" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;cursor:pointer;">Cookie-Einstellungen</a></p>
  <p class="brand-disclaimer" style="width: 100%; text-align: center; margin: 0; color: #64748B; font-size: 12px;">Erstellt mit <a href="https://digital-firmen.de" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;" target="_blank">digital-firmen.de</a></p>
</footer>

<div class="mobile-sticky-cta">
  <a href="/abmelden-auftrag.html">Abmeldung beauftragen &mdash; 24,90 &euro;</a>
</div>

<script>
(function(){
  'use strict';
  var heroWrap = document.querySelector('.hero-edge-wrapper');
  var heroVisual = document.querySelector('.hero-visual');
  var heroContent = document.querySelector('.hero-content');
  var stickyCta = document.querySelector('.mobile-sticky-cta');

  // === Parallax on hero image (desktop only) ===
  if (heroWrap && heroVisual && heroContent && window.matchMedia('(min-width: 821px) and (prefers-reduced-motion: no-preference)').matches) {
    var ticking = false;
    function updateParallax(){
      var rect = heroWrap.getBoundingClientRect();
      var heroH = heroWrap.offsetHeight || 1;
      var scrolled = -rect.top;
      // progress 0..1 pe măsură ce hero-ul iese din ecran
      var progress = Math.min(1, Math.max(0, scrolled / heroH));
      // poza coboară cu până la 60% din înălțimea hero-content, alunecând pe lângă text
      var contentH = heroContent.offsetHeight;
      var visualH = heroVisual.offsetHeight;
      var maxTranslate = Math.max(120, contentH - visualH + 60);
      var y = (maxTranslate * progress).toFixed(1);
      // setProperty cu important — imun la reguli din style.css extern
      heroVisual.style.setProperty('transform', 'translate3d(0,' + y + 'px,0)', 'important');
      heroVisual.style.setProperty('will-change', 'transform', 'important');
      ticking = false;
    }
    function onScroll(){
      if (!ticking) { window.requestAnimationFrame(updateParallax); ticking = true; }
    }
    window.addEventListener('load', updateParallax);
    document.addEventListener('DOMContentLoaded', updateParallax);
    updateParallax();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', updateParallax);
  }

  // === Hide sticky CTA when hero is visible in viewport (mobile) ===
  if (stickyCta && heroWrap && 'IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if (entry.isIntersecting && entry.intersectionRatio > 0.4) {
          stickyCta.classList.add('is-hidden');
        } else {
          stickyCta.classList.remove('is-hidden');
        }
      });
    }, { threshold: [0, 0.4, 1] });
    observer.observe(heroWrap);
  }
})();
</script>

<script src="/clarity-loader.js" async></script>
</body>
</html>
