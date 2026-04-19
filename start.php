<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#16A34A">
<meta name="apple-mobile-web-app-status-bar-style" content="default">

<title>Kündigung starten – Fitness oder Handyvertrag | KündigungExpress</title>
<meta name="description" content="Wählen Sie die Art Ihres Vertrags: Fitnessstudio oder Handyvertrag. Kündigung in 2 Minuten erstellen. einmalige Zahlung.">
<link rel="canonical" href="https://www.kuendigungexpress.de/start.php">
<link rel="icon" href="/favicon.ico">
<style>
:root { --bg: #F7F9FC; --card: #FFFFFF; --text: #0F172A; --muted: #475569; --border: #E2E8F0; --primary: #16A34A; --soft: #F1F5F9;
}
* { box-sizing: border-box; margin: 0; padding: 0; } html, body { height: 100%; } body { font-family: Arial, sans-serif; background: var(--bg); color: var(--text); display: flex; flex-direction: column; min-height: 100vh;
} .wrap { max-width: 860px; width: 100%; margin: 0 auto; padding: 28px 24px; flex: 1 0 auto; display: flex; flex-direction: column;
} /* Header */
.wrap > header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
header a { text-decoration: none; color: inherit; }
.wrap > header .brand a { font-weight: 900; font-size: 20px; color: #0F172A; text-decoration: none; }
nav a { margin-left: 16px; color: #475569; font-size: 14px; font-weight: normal; text-decoration: none; }
nav a:hover { color: var(--text); } /* Hero */
.hero { text-align: center; margin-bottom: 32px;
}
.hero h1 { font-size: clamp(24px, 4vw, 36px); line-height: 1.15; margin: 0 auto 12px; max-width: 22ch;
}
.hero-sub { font-size: 16px; color: var(--muted); max-width: 560px; margin: 0 auto 20px; line-height: 1.7;
}
.steps-row { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; font-size: 13px; color: #334155;
}
.steps-row .step { display: flex; align-items: center; gap: 6px; background: var(--soft); border: 1px solid var(--border); border-radius: 999px; padding: 5px 12px;
}
.step-num { width: 20px; height: 20px; background: var(--primary); color: #fff; border-radius: 50%; font-size: 11px; font-weight: 900; display: flex; align-items: center; justify-content: center;
}
.price-note { margin-top: 16px; font-weight: 700; color: var(--primary); font-size: 15px;
} /* Selection cards */
.selection-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 32px;
} .selection-card { display: flex; flex-direction: column; background: var(--card); border: 1px solid var(--border); border-radius: 22px; padding: 32px 28px; text-decoration: none; color: inherit; transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; position: relative;
}
.selection-card:hover { transform: translateY(-4px); border-color: var(--primary); box-shadow: 0 0 0 2px rgba(22,163,74,0.1), 0 14px 36px rgba(22,163,74,0.12);
} .selection-icon { font-size: 32px; margin-bottom: 16px; line-height: 1; } .selection-card h2 { font-size: 20px; font-weight: 800; margin: 0 0 10px; color: var(--text);
} .selection-card p { font-size: 14px; color: var(--muted); line-height: 1.65; margin: 0 0 20px; flex: 1;
} .selection-features { list-style: none; margin: 0 0 20px; display: flex; flex-direction: column; gap: 6px;
}
.selection-features li { font-size: 13px; color: #334155; display: flex; align-items: center; gap: 7px;
}
.selection-features li::before { content: '✓'; color: var(--primary); font-weight: 900; font-size: 13px;
} .selection-cta { display: inline-flex; align-items: center; gap: 6px; background: var(--primary); color: #fff; padding: 12px 20px; border-radius: 12px; font-weight: 800; font-size: 15px; transition: filter .15s ease; align-self: flex-start;
}
.selection-card:hover .selection-cta { filter: brightness(.95);
} /* Footer */
footer { margin-top: auto; text-align: center; font-size: 12px; color: #64748B; padding: 32px 0 20px;
}
footer a { text-decoration: none; color: inherit; } /* Mobile */
.site-header { display: none; } @media (max-width: 640px) { html { height: auto !important; } body { display: block !important; min-height: 100vh; padding-bottom: 112px !important; } .site-header { display: flex; justify-content: center; align-items: center; position: sticky; top: 0; z-index: 1000; background: #fff; border-bottom: 1px solid var(--border); padding: 14px; } .site-header .brand a { font-size: 18px; font-weight: 900; color: var(--text); text-decoration: none; } .wrap { display: block; padding: 20px 16px 0; } .wrap > header { display: none; } .hero { margin-bottom: 24px; } .hero h1 { font-size: 22px; max-width: 100%; } .hero-sub { font-size: 14px; } .steps-row { gap: 6px; } .steps-row .step { font-size: 12px; padding: 4px 10px; } .selection-grid { grid-template-columns: 1fr; gap: 14px; } .selection-card { padding: 22px 20px; border-radius: 18px; } .selection-card h2 { font-size: 18px; } .selection-cta { align-self: stretch; justify-content: center; } footer { margin-top: 28px; padding: 10px 0 16px; } .mobile-cta { display: flex; position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid var(--border); padding: 12px; gap: 10px; z-index: 1000; } .mobile-cta a { flex: 1; background: var(--primary); color: #fff; text-align: center; padding: 13px 8px; border-radius: 14px; font-weight: 800; font-size: 14px; text-decoration: none; display: block; }
}
</style> <!-- Microsoft Clarity --> <script type="text/javascript"> (function(c,l,a,r,i,t,y){ c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)}; t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i; y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y); })(window, document, "clarity", "script", "vz58ddxva5"); </script> </head><body> <header class="site-header"> <div class="brand"><a href="/">KündigungExpress</a></div>
</header> <div class="wrap"> <header> <div class="brand"><a href="/">KündigungExpress</a></div> <nav> <a href="/hilfe.html">Hilfe / FAQ</a> <a href="/impressum.html">Impressum</a> <a href="/datenschutz.html">Datenschutz</a> </nav> </header> <section class="hero"> <h1>Welche Kündigung möchten Sie erstellen?</h1> <p class="hero-sub">Wählen Sie Ihre Vertragsart. In zwei Minuten haben Sie ein rechtssicheres Kündigungsschreiben als PDF.</p> <div class="steps-row"> <div class="step"><div class="step-num">1</div> Vertragsart wählen</div> <div class="step"><div class="step-num">2</div> Daten eingeben</div> <div class="step"><div class="step-num">3</div> PDF herunterladen</div> </div> </section> <section class="selection-grid"> <a class="selection-card" href="/formular.php?type=handy"> <div class="selection-icon">📱</div> <h2>Handyvertrag kündigen</h2> <p>Rechtssichere Kündigung für alle deutschen Mobilfunkanbieter – Telekom, Vodafone, O2 und mehr.</p> <ul class="selection-features"> <li>Alle deutschen Anbieter</li> <li>TKG-Reform 2021 berücksichtigt</li> <li>Fristgerechte Formulierung</li> </ul> <div class="selection-cta">📱 Handy kündigen →</div> </a> <a class="selection-card" href="/formular.php?type=fitness"> <div class="selection-icon">🏋️</div> <h2>Fitnessstudio kündigen</h2> <p>Mitgliedschaft beenden für McFit, FitX, Clever Fit und alle weiteren Studios in Deutschland.</p> <ul class="selection-features"> <li>Alle Studios unterstützt</li> <li>Optional: direkt per E-Mail senden</li> <li>Sofort als PDF verfügbar</li> </ul> <div class="selection-cta">🏋️ Fitness kündigen →</div> </a> </section> <footer> © 2026 KündigungExpress · <a href="/impressum.html">Impressum</a> · <a href="/datenschutz.html">Datenschutz</a> <p>Erstellt mit <a href="https://digital-firmen.de" target="_blank">digital-firmen.de</a></p> </footer>
</div> <div class="mobile-cta"> <a href="/formular.php?type=fitness">🏋️ Fitness</a> <a href="/formular.php?type=handy">📱 Handy</a>
</div> </body>
</html>