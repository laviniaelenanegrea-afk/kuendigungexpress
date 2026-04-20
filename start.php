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
footer a { text-decoration: none; color: inherit; } /* Search bar */
.search-wrap { margin-bottom: 28px; }
.search-wrap label { display: block; font-size: 14px; font-weight: 700; color: var(--muted); margin-bottom: 8px; text-align: center; }
.search-input-wrap { position: relative; max-width: 560px; margin: 0 auto; }
.search-input-wrap input { width: 100%; padding: 14px 48px 14px 18px; font-size: 16px; border: 2px solid var(--border); border-radius: 14px; background: var(--card); color: var(--text); outline: none; transition: border-color .2s; font-family: Arial, sans-serif; }
.search-input-wrap input:focus { border-color: var(--primary); }
.search-input-wrap .search-icon { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 18px; background: none; border: none; cursor: pointer; padding: 4px; line-height: 1; }
/* Mobile */
.site-header { display: none; } @media (max-width: 640px) { html { height: auto !important; } body { display: block !important; min-height: 100vh; padding-bottom: 112px !important; } .site-header { display: flex; justify-content: center; align-items: center; position: sticky; top: 0; z-index: 1000; background: #fff; border-bottom: 1px solid var(--border); padding: 14px; } .site-header .brand a { font-size: 18px; font-weight: 900; color: var(--text); text-decoration: none; } .wrap { display: block; padding: 20px 16px 0; } .wrap > header { display: none; } .hero { margin-bottom: 24px; } .hero h1 { font-size: 22px; max-width: 100%; } .hero-sub { font-size: 14px; } .steps-row { gap: 6px; } .steps-row .step { font-size: 12px; padding: 4px 10px; } .selection-grid { grid-template-columns: 1fr; gap: 14px; } .selection-card { padding: 22px 20px; border-radius: 18px; } .selection-card h2 { font-size: 18px; } .selection-cta { align-self: stretch; justify-content: center; } footer { margin-top: 28px; padding: 10px 0 16px; } .mobile-cta { display: flex; position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid var(--border); padding: 12px; gap: 10px; z-index: 1000; } .mobile-cta a { flex: 1; background: var(--primary); color: #fff; text-align: center; padding: 13px 8px; border-radius: 14px; font-weight: 800; font-size: 14px; text-decoration: none; display: block; }
}
</style> <!-- Microsoft Clarity --> <script type="text/javascript"> (function(c,l,a,r,i,t,y){ c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)}; t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i; y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y); })(window, document, "clarity", "script", "vz58ddxva5"); </script> </head><body> <header class="site-header"> <div class="brand"><a href="/">KündigungExpress</a></div>
</header> <div class="wrap"> <header> <div class="brand"><a href="/">KündigungExpress</a></div> <nav> <a href="/hilfe.html">Hilfe / FAQ</a> <a href="/impressum.html">Impressum</a> <a href="/datenschutz.html">Datenschutz</a> </nav> </header> <section class="hero"> <h1>Welche Kündigung möchten Sie erstellen?</h1> <p class="hero-sub">Wählen Sie Ihre Vertragsart. In zwei Minuten haben Sie ein rechtssicheres Kündigungsschreiben als PDF.</p> <div class="steps-row"> <div class="step"><div class="step-num">1</div> Vertragsart wählen</div> <div class="step"><div class="step-num">2</div> Daten eingeben</div> <div class="step"><div class="step-num">3</div> PDF herunterladen</div> </div> </section> <section class="search-wrap">
  <label for="providerSearch">Welchen Anbieter möchten Sie kündigen?</label>
  <div class="search-input-wrap" id="searchWrap">
    <input type="text" id="providerSearch" placeholder="z.B. Telekom, McFit, O2 ..." autocomplete="off">
    <span class="search-icon">🔍</span>
  </div>
  <div id="ksList" style="display:none;position:fixed;background:#fff;border:1px solid #E2E8F0;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.12);z-index:99999;max-height:260px;overflow-y:auto;"></div>
</section>
 <section class="selection-grid"> <a class="selection-card" href="/formular.php?type=handy"> <div class="selection-icon">📱</div> <h2>Handyvertrag kündigen</h2> <p>Rechtssichere Kündigung für alle deutschen Mobilfunkanbieter – Telekom, Vodafone, O2 und mehr.</p> <ul class="selection-features"> <li>Alle deutschen Anbieter</li> <li>TKG-Reform 2021 berücksichtigt</li> <li>Fristgerechte Formulierung</li> </ul> <div class="selection-cta">📱 Handy kündigen →</div> </a> <a class="selection-card" href="/formular.php?type=fitness"> <div class="selection-icon">🏋️</div> <h2>Fitnessstudio kündigen</h2> <p>Mitgliedschaft beenden für McFit, FitX, Clever Fit und alle weiteren Studios in Deutschland.</p> <ul class="selection-features"> <li>Alle Studios unterstützt</li> <li>Optional: direkt per E-Mail senden</li> <li>Sofort als PDF verfügbar</li> </ul> <div class="selection-cta">🏋️ Fitness kündigen →</div> </a> </section> <footer> © 2026 KündigungExpress · <a href="/impressum.html">Impressum</a> · <a href="/datenschutz.html">Datenschutz</a> <p>Erstellt mit <a href="https://digital-firmen.de" target="_blank">digital-firmen.de</a></p> </footer>
</div> <div class="mobile-cta"> <a href="/formular.php?type=fitness">🏋️ Fitness</a> <a href="/formular.php?type=handy">📱 Handy</a>
</div><script src="/search-ke.js"></script>
</body>"1&1":"/1und1-kuendigung.html","7/11 Fitness":"/7-11-fitness-kuendigung.html","ALDI TALK":"/aldi-talk-kuendigung.html","Ay Yildiz":"/ay-yildiz-kuendigung.html","Blau":"/blau-kuendigung.html","Body & Soul":"/body-and-soul-kuendigung.html","Bodystreet":"/bodystreet-kuendigung.html","Clever Fit":"/clever-fit-kuendigung.html","congstar":"/congstar-kuendigung.html","DeutschlandSIM":"/deutschlandsim-kuendigung.html","discoTEL":"/discotel-kuendigung.html","Drillisch":"/drillisch-kuendigung.html","EDEKA Mobil":"/edeka-mobil-kuendigung.html","EDEKA smart":"/edeka-smart-kuendigung.html","Fitness Express":"/fitness-express-kuendigung.html","Fitness First":"/fitness-first-kuendigung.html","FitnessKING":"/fitnessking-kuendigung.html","FitX":"/fitx-kuendigung.html","FONIC":"/fonic-kuendigung.html","fraenk":"/fraenk-kuendigung.html","freenet":"/freenet-kuendigung.html","HIGH Mobile":"/high-mobile-kuendigung.html","HIGH5":"/high5-kuendigung.html","Holmes Place":"/holmes-place-kuendigung.html","INJOY":"/injoy-kuendigung.html","ja! Mobil":"/ja-mobil-kuendigung.html","John Reed":"/john-reed-kuendigung.html","Kaufland Mobil":"/kaufland-mobil-kuendigung.html","Kieser Training":"/kieser-training-kuendigung.html","klarmobil":"/klarmobil-kuendigung.html","Lebara":"/lebara-kuendigung.html","Lidl Connect":"/lidl-connect-kuendigung.html","Lycamobile":"/lycamobile-kuendigung.html","MAXXIM":"/maxxim-kuendigung.html","McFit":"/mcfit-kuendigung.html","mobilcom-debitel":"/mobilcom-debitel-kuendigung.html","Mrs. Sporty":"/mrs-sporty-kuendigung.html","NORMA Connect":"/norma-connect-kuendigung.html","O2":"/o2-kuendigung.html","Ortel Mobile":"/ortel-mobile-kuendigung.html","otelo":"/otelo-kuendigung.html","Pfitzenmeier":"/pfitzenmeier-kuendigung.html","PremiumSIM":"/premiumsim-kuendigung.html","SIM.de":"/simde-kuendigung.html","simplytel":"/simplytel-kuendigung.html","smartmobil":"/smartmobil-kuendigung.html","superSelect":"/superselect-kuendigung.html","Tchibo Mobil":"/tchibo-mobil-kuendigung.html","Telekom":"/telekom-kuendigung.html","Urban Sports Club":"/urban-sports-club-kuendigung.html","Vodafone":"/vodafone-kuendigung.html","WinSIM":"/winsim-kuendigung.html","yourfone":"/yourfone-kuendigung.html"};
var KL=document.getElementById('ksList');
var KI=document.getElementById('providerSearch');
function ksf(q){
  q=(q||'').trim().toLowerCase();
  if(!q){KL.style.display='none';return;}
  var h='';
  for(var n in KU){
    if(n.toLowerCase().indexOf(q)!==-1){
      h+='<a href="'+KU[n]+'" style="display:block;padding:12px 16px;font-size:15px;color:#0F172A;text-decoration:none;border-bottom:1px solid #E2E8F0;font-family:Arial,sans-serif;">'+n+'</a>';
    }
  }
  if(!h)h='<div style="padding:12px 16px;color:#94A3B8;font-size:14px;">Kein Anbieter gefunden</div>';
  KL.innerHTML=h;
  var r=KI.getBoundingClientRect();
  KL.style.top=(r.bottom+window.scrollY+4)+'px';
  KL.style.left=r.left+'px';
  KL.style.width=r.width+'px';
  KL.style.display='block';
}
document.addEventListener('click',function(e){if(e.target!==KI)KL.style.display='none';});
</script>
</body>"1&1":"/1und1-kuendigung.html","7/11 Fitness":"/7-11-fitness-kuendigung.html","ALDI TALK":"/aldi-talk-kuendigung.html","Ay Yildiz":"/ay-yildiz-kuendigung.html","Blau":"/blau-kuendigung.html","Body & Soul":"/body-and-soul-kuendigung.html","Bodystreet":"/bodystreet-kuendigung.html","Clever Fit":"/clever-fit-kuendigung.html","congstar":"/congstar-kuendigung.html","DeutschlandSIM":"/deutschlandsim-kuendigung.html","discoTEL":"/discotel-kuendigung.html","Drillisch":"/drillisch-kuendigung.html","EDEKA Mobil":"/edeka-mobil-kuendigung.html","EDEKA smart":"/edeka-smart-kuendigung.html","Fitness Express":"/fitness-express-kuendigung.html","Fitness First":"/fitness-first-kuendigung.html","FitnessKING":"/fitnessking-kuendigung.html","FitX":"/fitx-kuendigung.html","FONIC":"/fonic-kuendigung.html","fraenk":"/fraenk-kuendigung.html","freenet":"/freenet-kuendigung.html","HIGH Mobile":"/high-mobile-kuendigung.html","HIGH5":"/high5-kuendigung.html","Holmes Place":"/holmes-place-kuendigung.html","INJOY":"/injoy-kuendigung.html","ja! Mobil":"/ja-mobil-kuendigung.html","John Reed":"/john-reed-kuendigung.html","Kaufland Mobil":"/kaufland-mobil-kuendigung.html","Kieser Training":"/kieser-training-kuendigung.html","klarmobil":"/klarmobil-kuendigung.html","Lebara":"/lebara-kuendigung.html","Lidl Connect":"/lidl-connect-kuendigung.html","Lycamobile":"/lycamobile-kuendigung.html","MAXXIM":"/maxxim-kuendigung.html","McFit":"/mcfit-kuendigung.html","mobilcom-debitel":"/mobilcom-debitel-kuendigung.html","Mrs. Sporty":"/mrs-sporty-kuendigung.html","NORMA Connect":"/norma-connect-kuendigung.html","O2":"/o2-kuendigung.html","Ortel Mobile":"/ortel-mobile-kuendigung.html","otelo":"/otelo-kuendigung.html","Pfitzenmeier":"/pfitzenmeier-kuendigung.html","PremiumSIM":"/premiumsim-kuendigung.html","SIM.de":"/simde-kuendigung.html","simplytel":"/simplytel-kuendigung.html","smartmobil":"/smartmobil-kuendigung.html","superSelect":"/superselect-kuendigung.html","Tchibo Mobil":"/tchibo-mobil-kuendigung.html","Telekom":"/telekom-kuendigung.html","Urban Sports Club":"/urban-sports-club-kuendigung.html","Vodafone":"/vodafone-kuendigung.html","WinSIM":"/winsim-kuendigung.html","yourfone":"/yourfone-kuendigung.html"};

var ks_inp = document.getElementById('providerSearch');
var ks_list = document.getElementById('ksList');

function ks_pos() {
  var r = ks_inp.getBoundingClientRect();
  ks_list.style.top = (r.bottom + window.scrollY + 4) + 'px';
  ks_list.style.left = r.left + 'px';
  ks_list.style.width = r.width + 'px';
}

function ks_filter(q) {
  q = (q || '').trim().toLowerCase();
  if (!q) { ks_list.style.display = 'none'; return; }
  var html = '';
  for (var name in ks_urls) {
    if (name.toLowerCase().indexOf(q) !== -1) {
      html += '<li onclick="window.location.href=\'' + ks_urls[name] + '\'" style="padding:12px 16px;cursor:pointer;font-size:14px;border-bottom:1px solid #E2E8F0;font-family:Arial,sans-serif;color:#0F172A;" onmouseover="this.style.background=\'#F0FDF4\'" onmouseout="this.style.background=\'\'">'+name+'</li>';
    }
  }
  if (!html) html = '<li style="padding:12px 16px;color:#94A3B8;font-size:14px;font-family:Arial,sans-serif;">Kein Anbieter gefunden</li>';
  ks_list.innerHTML = html;
  ks_pos();
  ks_list.style.display = 'block';
}

function ks_go() {
  var v = ks_inp.value.trim();
  if (ks_urls[v]) { window.location.href = ks_urls[v]; return; }
  var lo = v.toLowerCase();
  for (var name in ks_urls) {
    if (name.toLowerCase().indexOf(lo) !== -1) { window.location.href = ks_urls[name]; return; }
  }
}

document.addEventListener('click', function(e) {
  if (e.target !== ks_inp && e.target.id !== 'searchBtn') ks_list.style.display = 'none';
});

ks_inp.addEventListener('keydown', function(e) { if (e.key === 'Enter') ks_go(); });
document.getElementById('searchBtn').addEventListener('click', ks_go);
</script>
</body>
  "1&1":"/1und1-kuendigung.html",
  "7/11 Fitness":"/7-11-fitness-kuendigung.html",
  "ALDI TALK":"/aldi-talk-kuendigung.html",
  "Ay Yildiz":"/ay-yildiz-kuendigung.html",
  "Blau":"/blau-kuendigung.html",
  "Body & Soul":"/body-and-soul-kuendigung.html",
  "Bodystreet":"/bodystreet-kuendigung.html",
  "Clever Fit":"/clever-fit-kuendigung.html",
  "congstar":"/congstar-kuendigung.html",
  "DeutschlandSIM":"/deutschlandsim-kuendigung.html",
  "discoTEL":"/discotel-kuendigung.html",
  "Drillisch":"/drillisch-kuendigung.html",
  "EDEKA Mobil":"/edeka-mobil-kuendigung.html",
  "EDEKA smart":"/edeka-smart-kuendigung.html",
  "Fitness Express":"/fitness-express-kuendigung.html",
  "Fitness First":"/fitness-first-kuendigung.html",
  "FitnessKING":"/fitnessking-kuendigung.html",
  "FitX":"/fitx-kuendigung.html",
  "FONIC":"/fonic-kuendigung.html",
  "fraenk":"/fraenk-kuendigung.html",
  "freenet":"/freenet-kuendigung.html",
  "HIGH Mobile":"/high-mobile-kuendigung.html",
  "HIGH5":"/high5-kuendigung.html",
  "Holmes Place":"/holmes-place-kuendigung.html",
  "INJOY":"/injoy-kuendigung.html",
  "ja! Mobil":"/ja-mobil-kuendigung.html",
  "John Reed":"/john-reed-kuendigung.html",
  "Kaufland Mobil":"/kaufland-mobil-kuendigung.html",
  "Kieser Training":"/kieser-training-kuendigung.html",
  "klarmobil":"/klarmobil-kuendigung.html",
  "Lebara":"/lebara-kuendigung.html",
  "Lidl Connect":"/lidl-connect-kuendigung.html",
  "Lycamobile":"/lycamobile-kuendigung.html",
  "MAXXIM":"/maxxim-kuendigung.html",
  "McFit":"/mcfit-kuendigung.html",
  "mobilcom-debitel":"/mobilcom-debitel-kuendigung.html",
  "Mrs. Sporty":"/mrs-sporty-kuendigung.html",
  "NORMA Connect":"/norma-connect-kuendigung.html",
  "O2":"/o2-kuendigung.html",
  "Ortel Mobile":"/ortel-mobile-kuendigung.html",
  "otelo":"/otelo-kuendigung.html",
  "Pfitzenmeier":"/pfitzenmeier-kuendigung.html",
  "PremiumSIM":"/premiumsim-kuendigung.html",
  "SIM.de":"/simde-kuendigung.html",
  "simplytel":"/simplytel-kuendigung.html",
  "smartmobil":"/smartmobil-kuendigung.html",
  "superSelect":"/superselect-kuendigung.html",
  "Tchibo Mobil":"/tchibo-mobil-kuendigung.html",
  "Telekom":"/telekom-kuendigung.html",
  "Urban Sports Club":"/urban-sports-club-kuendigung.html",
  "Vodafone":"/vodafone-kuendigung.html",
  "WinSIM":"/winsim-kuendigung.html",
  "yourfone":"/yourfone-kuendigung.html"
};

function tryRedirect() {
  var val = document.getElementById('providerSearch').value.trim();
  if (urls[val]) { window.location.href = urls[val]; return; }
  /* partial match — find first provider that starts with input */
  var lower = val.toLowerCase();
  for (var key in urls) {
    if (key.toLowerCase().indexOf(lower) === 0) { window.location.href = urls[key]; return; }
  }
  /* broader match */
  for (var key in urls) {
    if (key.toLowerCase().indexOf(lower) !== -1) { window.location.href = urls[key]; return; }
  }
}

document.getElementById('providerSearch').addEventListener('change', tryRedirect);
document.getElementById('providerSearch').addEventListener('keydown', function(e) {
  if (e.key === 'Enter') tryRedirect();
});
document.getElementById('searchBtn').addEventListener('click', tryRedirect);
</script>
</body>
  "1&1":"/1und1-kuendigung.html",
  "7/11 Fitness":"/7-11-fitness-kuendigung.html",
  "ALDI TALK":"/aldi-talk-kuendigung.html",
  "Ay Yildiz":"/ay-yildiz-kuendigung.html",
  "Blau":"/blau-kuendigung.html",
  "Body & Soul":"/body-and-soul-kuendigung.html",
  "Bodystreet":"/bodystreet-kuendigung.html",
  "Clever Fit":"/clever-fit-kuendigung.html",
  "congstar":"/congstar-kuendigung.html",
  "DeutschlandSIM":"/deutschlandsim-kuendigung.html",
  "discoTEL":"/discotel-kuendigung.html",
  "Drillisch":"/drillisch-kuendigung.html",
  "EDEKA Mobil":"/edeka-mobil-kuendigung.html",
  "EDEKA smart":"/edeka-smart-kuendigung.html",
  "Fitness Express":"/fitness-express-kuendigung.html",
  "Fitness First":"/fitness-first-kuendigung.html",
  "FitnessKING":"/fitnessking-kuendigung.html",
  "FitX":"/fitx-kuendigung.html",
  "FONIC":"/fonic-kuendigung.html",
  "fraenk":"/fraenk-kuendigung.html",
  "freenet":"/freenet-kuendigung.html",
  "HIGH Mobile":"/high-mobile-kuendigung.html",
  "HIGH5":"/high5-kuendigung.html",
  "Holmes Place":"/holmes-place-kuendigung.html",
  "INJOY":"/injoy-kuendigung.html",
  "ja! Mobil":"/ja-mobil-kuendigung.html",
  "John Reed":"/john-reed-kuendigung.html",
  "Kaufland Mobil":"/kaufland-mobil-kuendigung.html",
  "Kieser Training":"/kieser-training-kuendigung.html",
  "klarmobil":"/klarmobil-kuendigung.html",
  "Lebara":"/lebara-kuendigung.html",
  "Lidl Connect":"/lidl-connect-kuendigung.html",
  "Lycamobile":"/lycamobile-kuendigung.html",
  "MAXXIM":"/maxxim-kuendigung.html",
  "McFit":"/mcfit-kuendigung.html",
  "mobilcom-debitel":"/mobilcom-debitel-kuendigung.html",
  "Mrs. Sporty":"/mrs-sporty-kuendigung.html",
  "NORMA Connect":"/norma-connect-kuendigung.html",
  "O2":"/o2-kuendigung.html",
  "Ortel Mobile":"/ortel-mobile-kuendigung.html",
  "otelo":"/otelo-kuendigung.html",
  "Pfitzenmeier":"/pfitzenmeier-kuendigung.html",
  "PremiumSIM":"/premiumsim-kuendigung.html",
  "SIM.de":"/simde-kuendigung.html",
  "simplytel":"/simplytel-kuendigung.html",
  "smartmobil":"/smartmobil-kuendigung.html",
  "superSelect":"/superselect-kuendigung.html",
  "Tchibo Mobil":"/tchibo-mobil-kuendigung.html",
  "Telekom":"/telekom-kuendigung.html",
  "Urban Sports Club":"/urban-sports-club-kuendigung.html",
  "Vodafone":"/vodafone-kuendigung.html",
  "WinSIM":"/winsim-kuendigung.html",
  "yourfone":"/yourfone-kuendigung.html"
};
document.getElementById('providerSearch').addEventListener('change', function() {
  var url = urls[this.value];
  if (url) window.location.href = url;
});
</script>
</body>
  {name:"1&1",url:"/1und1-kuendigung.html",cat:"Handy"},
  {name:"7/11 Fitness",url:"/7-11-fitness-kuendigung.html",cat:"Fitness"},
  {name:"ALDI TALK",url:"/aldi-talk-kuendigung.html",cat:"Handy"},
  {name:"Ay Yildiz",url:"/ay-yildiz-kuendigung.html",cat:"Handy"},
  {name:"Blau",url:"/blau-kuendigung.html",cat:"Handy"},
  {name:"Body & Soul",url:"/body-and-soul-kuendigung.html",cat:"Fitness"},
  {name:"Bodystreet",url:"/bodystreet-kuendigung.html",cat:"Fitness"},
  {name:"Clever Fit",url:"/clever-fit-kuendigung.html",cat:"Fitness"},
  {name:"congstar",url:"/congstar-kuendigung.html",cat:"Handy"},
  {name:"DeutschlandSIM",url:"/deutschlandsim-kuendigung.html",cat:"Handy"},
  {name:"discoTEL",url:"/discotel-kuendigung.html",cat:"Handy"},
  {name:"Drillisch",url:"/drillisch-kuendigung.html",cat:"Handy"},
  {name:"EDEKA Mobil",url:"/edeka-mobil-kuendigung.html",cat:"Handy"},
  {name:"EDEKA smart",url:"/edeka-smart-kuendigung.html",cat:"Handy"},
  {name:"Fitness Express",url:"/fitness-express-kuendigung.html",cat:"Fitness"},
  {name:"Fitness First",url:"/fitness-first-kuendigung.html",cat:"Fitness"},
  {name:"FitnessKING",url:"/fitnessking-kuendigung.html",cat:"Fitness"},
  {name:"FitX",url:"/fitx-kuendigung.html",cat:"Fitness"},
  {name:"FONIC",url:"/fonic-kuendigung.html",cat:"Handy"},
  {name:"fraenk",url:"/fraenk-kuendigung.html",cat:"Handy"},
  {name:"freenet",url:"/freenet-kuendigung.html",cat:"Handy"},
  {name:"HIGH Mobile",url:"/high-mobile-kuendigung.html",cat:"Handy"},
  {name:"HIGH5",url:"/high5-kuendigung.html",cat:"Fitness"},
  {name:"Holmes Place",url:"/holmes-place-kuendigung.html",cat:"Fitness"},
  {name:"INJOY",url:"/injoy-kuendigung.html",cat:"Fitness"},
  {name:"ja! Mobil",url:"/ja-mobil-kuendigung.html",cat:"Handy"},
  {name:"John Reed",url:"/john-reed-kuendigung.html",cat:"Fitness"},
  {name:"Kaufland Mobil",url:"/kaufland-mobil-kuendigung.html",cat:"Handy"},
  {name:"Kieser Training",url:"/kieser-training-kuendigung.html",cat:"Fitness"},
  {name:"klarmobil",url:"/klarmobil-kuendigung.html",cat:"Handy"},
  {name:"Lebara",url:"/lebara-kuendigung.html",cat:"Handy"},
  {name:"Lidl Connect",url:"/lidl-connect-kuendigung.html",cat:"Handy"},
  {name:"Lycamobile",url:"/lycamobile-kuendigung.html",cat:"Handy"},
  {name:"MAXXIM",url:"/maxxim-kuendigung.html",cat:"Handy"},
  {name:"McFit",url:"/mcfit-kuendigung.html",cat:"Fitness"},
  {name:"mobilcom-debitel",url:"/mobilcom-debitel-kuendigung.html",cat:"Handy"},
  {name:"Mrs. Sporty",url:"/mrs-sporty-kuendigung.html",cat:"Fitness"},
  {name:"NORMA Connect",url:"/norma-connect-kuendigung.html",cat:"Handy"},
  {name:"O2",url:"/o2-kuendigung.html",cat:"Handy"},
  {name:"Ortel Mobile",url:"/ortel-mobile-kuendigung.html",cat:"Handy"},
  {name:"otelo",url:"/otelo-kuendigung.html",cat:"Handy"},
  {name:"Pfitzenmeier",url:"/pfitzenmeier-kuendigung.html",cat:"Fitness"},
  {name:"PremiumSIM",url:"/premiumsim-kuendigung.html",cat:"Handy"},
  {name:"SIM.de",url:"/simde-kuendigung.html",cat:"Handy"},
  {name:"simplytel",url:"/simplytel-kuendigung.html",cat:"Handy"},
  {name:"smartmobil",url:"/smartmobil-kuendigung.html",cat:"Handy"},
  {name:"superSelect",url:"/superselect-kuendigung.html",cat:"Handy"},
  {name:"Tchibo Mobil",url:"/tchibo-mobil-kuendigung.html",cat:"Handy"},
  {name:"Telekom",url:"/telekom-kuendigung.html",cat:"Handy"},
  {name:"Urban Sports Club",url:"/urban-sports-club-kuendigung.html",cat:"Fitness"},
  {name:"Vodafone",url:"/vodafone-kuendigung.html",cat:"Handy"},
  {name:"WinSIM",url:"/winsim-kuendigung.html",cat:"Handy"},
  {name:"yourfone",url:"/yourfone-kuendigung.html",cat:"Handy"}
];

var inp = document.getElementById('providerSearch');
var lst = document.getElementById('autocompleteList');
var cur = -1;
var hits = [];

function renderList() {
  if (!hits.length) {
    lst.innerHTML = '<div class="autocomplete-no-result">Kein Anbieter gefunden</div>';
  } else {
    var html = '';
    for (var i = 0; i < hits.length; i++) {
      var icon = hits[i].cat === 'Handy' ? '📱' : '🏋️';
      html += '<div class="autocomplete-item" data-url="' + hits[i].url + '">' +
        '<span class="item-icon">' + icon + '</span>' +
        '<span class="item-name">' + hits[i].name + '</span>' +
        '<span class="item-type">' + hits[i].cat + '</span>' +
        '</div>';
    }
    lst.innerHTML = html;
  }
  lst.style.display = 'block';
  cur = -1;
}

function closeList() {
  lst.style.display = 'none';
  cur = -1;
}

function goTo(url) {
  window.location.href = url;
}

inp.addEventListener('input', function() {
  var q = this.value.trim().toLowerCase();
  if (!q) { closeList(); return; }
  hits = providers.filter(function(p) {
    return p.name.toLowerCase().indexOf(q) !== -1;
  });
  renderList();
});

inp.addEventListener('keydown', function(e) {
  var items = lst.querySelectorAll('.autocomplete-item');
  if (!items.length) return;
  if (e.key === 'ArrowDown') {
    e.preventDefault();
    cur = Math.min(cur + 1, items.length - 1);
    items.forEach(function(el, i) { el.classList.toggle('active', i === cur); });
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    cur = Math.max(cur - 1, 0);
    items.forEach(function(el, i) { el.classList.toggle('active', i === cur); });
  } else if (e.key === 'Enter') {
    if (cur >= 0 && hits[cur]) goTo(hits[cur].url);
    else if (hits.length === 1) goTo(hits[0].url);
  } else if (e.key === 'Escape') {
    closeList();
  }
});

lst.addEventListener('mousedown', function(e) {
  var item = e.target.closest ? e.target.closest('.autocomplete-item') : null;
  if (!item) {
    var el = e.target;
    while (el && el !== lst) {
      if (el.classList && el.classList.contains('autocomplete-item')) { item = el; break; }
      el = el.parentNode;
    }
  }
  if (item && item.dataset.url) {
    e.preventDefault();
    goTo(item.dataset.url);
  }
});

document.addEventListener('click', function(e) {
  var el = e.target;
  var inside = false;
  while (el) {
    if (el.id === 'autocompleteList' || el.id === 'providerSearch') { inside = true; break; }
    el = el.parentNode;
  }
  if (!inside) closeList();
});
</script>
</body>
  {name:"1&1",url:"/1und1-kuendigung.html",type:"📱"},
  {name:"7/11 Fitness",url:"/7-11-fitness-kuendigung.html",type:"🏋️"},
  {name:"ALDI TALK",url:"/aldi-talk-kuendigung.html",type:"📱"},
  {name:"Ay Yildiz",url:"/ay-yildiz-kuendigung.html",type:"📱"},
  {name:"Blau",url:"/blau-kuendigung.html",type:"📱"},
  {name:"Body & Soul",url:"/body-and-soul-kuendigung.html",type:"🏋️"},
  {name:"Bodystreet",url:"/bodystreet-kuendigung.html",type:"🏋️"},
  {name:"Clever Fit",url:"/clever-fit-kuendigung.html",type:"🏋️"},
  {name:"congstar",url:"/congstar-kuendigung.html",type:"📱"},
  {name:"DeutschlandSIM",url:"/deutschlandsim-kuendigung.html",type:"📱"},
  {name:"discoTEL",url:"/discotel-kuendigung.html",type:"📱"},
  {name:"Drillisch",url:"/drillisch-kuendigung.html",type:"📱"},
  {name:"EDEKA Mobil",url:"/edeka-mobil-kuendigung.html",type:"📱"},
  {name:"EDEKA smart",url:"/edeka-smart-kuendigung.html",type:"📱"},
  {name:"Fitness Express",url:"/fitness-express-kuendigung.html",type:"🏋️"},
  {name:"Fitness First",url:"/fitness-first-kuendigung.html",type:"🏋️"},
  {name:"FitnessKING",url:"/fitnessking-kuendigung.html",type:"🏋️"},
  {name:"FitX",url:"/fitx-kuendigung.html",type:"🏋️"},
  {name:"FONIC",url:"/fonic-kuendigung.html",type:"📱"},
  {name:"fraenk",url:"/fraenk-kuendigung.html",type:"📱"},
  {name:"freenet",url:"/freenet-kuendigung.html",type:"📱"},
  {name:"HIGH Mobile",url:"/high-mobile-kuendigung.html",type:"📱"},
  {name:"HIGH5",url:"/high5-kuendigung.html",type:"🏋️"},
  {name:"Holmes Place",url:"/holmes-place-kuendigung.html",type:"🏋️"},
  {name:"INJOY",url:"/injoy-kuendigung.html",type:"🏋️"},
  {name:"ja! Mobil",url:"/ja-mobil-kuendigung.html",type:"📱"},
  {name:"John Reed",url:"/john-reed-kuendigung.html",type:"🏋️"},
  {name:"Kaufland Mobil",url:"/kaufland-mobil-kuendigung.html",type:"📱"},
  {name:"Kieser Training",url:"/kieser-training-kuendigung.html",type:"🏋️"},
  {name:"klarmobil",url:"/klarmobil-kuendigung.html",type:"📱"},
  {name:"Lebara",url:"/lebara-kuendigung.html",type:"📱"},
  {name:"Lidl Connect",url:"/lidl-connect-kuendigung.html",type:"📱"},
  {name:"Lycamobile",url:"/lycamobile-kuendigung.html",type:"📱"},
  {name:"MAXXIM",url:"/maxxim-kuendigung.html",type:"📱"},
  {name:"McFit",url:"/mcfit-kuendigung.html",type:"🏋️"},
  {name:"mobilcom-debitel",url:"/mobilcom-debitel-kuendigung.html",type:"📱"},
  {name:"Mrs. Sporty",url:"/mrs-sporty-kuendigung.html",type:"🏋️"},
  {name:"NORMA Connect",url:"/norma-connect-kuendigung.html",type:"📱"},
  {name:"O2",url:"/o2-kuendigung.html",type:"📱"},
  {name:"Ortel Mobile",url:"/ortel-mobile-kuendigung.html",type:"📱"},
  {name:"otelo",url:"/otelo-kuendigung.html",type:"📱"},
  {name:"Pfitzenmeier",url:"/pfitzenmeier-kuendigung.html",type:"🏋️"},
  {name:"PremiumSIM",url:"/premiumsim-kuendigung.html",type:"📱"},
  {name:"SIM.de",url:"/simde-kuendigung.html",type:"📱"},
  {name:"simplytel",url:"/simplytel-kuendigung.html",type:"📱"},
  {name:"smartmobil",url:"/smartmobil-kuendigung.html",type:"📱"},
  {name:"superSelect",url:"/superselect-kuendigung.html",type:"📱"},
  {name:"Tchibo Mobil",url:"/tchibo-mobil-kuendigung.html",type:"📱"},
  {name:"Telekom",url:"/telekom-kuendigung.html",type:"📱"},
  {name:"Urban Sports Club",url:"/urban-sports-club-kuendigung.html",type:"🏋️"},
  {name:"Vodafone",url:"/vodafone-kuendigung.html",type:"📱"},
  {name:"WinSIM",url:"/winsim-kuendigung.html",type:"📱"},
  {name:"yourfone",url:"/yourfone-kuendigung.html",type:"📱"},
];

const input = document.getElementById('providerSearch');
const list  = document.getElementById('autocompleteList');
let activeIdx = -1;
let filtered  = [];

function show(items) {
  activeIdx = -1;
  filtered  = items;
  if (!items.length) {
    list.innerHTML = '<div class="autocomplete-no-result">Kein Anbieter gefunden</div>';
  } else {
    list.innerHTML = items.map((p,i) =>
      '<div class="autocomplete-item" data-url="'+p.url+'" data-idx="'+i+'">' +
      '<span class="item-icon">'+p.type+'</span>' +
      '<span class="item-name">'+p.name+'</span>' +
      '<span class="item-type">'+(p.type==='📱'?'Handy':'Fitness')+'</span>' +
      '</div>'
    ).join('');
  }
  list.classList.add('open');
}

function hide() { list.classList.remove('open'); activeIdx = -1; }

function navigate(dir) {
  const items = list.querySelectorAll('.autocomplete-item');
  if (!items.length) return;
  activeIdx = Math.max(0, Math.min(activeIdx + dir, items.length - 1));
  items.forEach((el,i) => el.classList.toggle('active', i === activeIdx));
  if (items[activeIdx]) items[activeIdx].scrollIntoView({block:'nearest'});
}

input.addEventListener('keyup', function(e) {
  if (e.key === 'ArrowDown') { navigate(1); return; }
  if (e.key === 'ArrowUp')   { navigate(-1); return; }
  if (e.key === 'Escape')    { hide(); return; }
  if (e.key === 'Enter') {
    if (activeIdx >= 0 && filtered[activeIdx]) {
      window.location.href = filtered[activeIdx].url;
    } else if (filtered.length === 1) {
      window.location.href = filtered[0].url;
    }
    return;
  }
  const q = input.value.trim().toLowerCase();
  if (!q) { hide(); return; }
  show(providers.filter(p => p.name.toLowerCase().includes(q)));
});

/* mousedown fires BEFORE blur — item click works correctly */
list.addEventListener('mousedown', function(e) {
  const item = e.target.closest('.autocomplete-item');
  if (item && item.dataset.url) {
    e.preventDefault();
    window.location.href = item.dataset.url;
  }
});

document.addEventListener('click', function(e) {
  if (!e.target.closest('.search-input-wrap')) hide();
});

})();
</script>
</body>