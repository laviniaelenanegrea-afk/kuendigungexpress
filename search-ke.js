var KU={"1&1":"/1und1-kuendigung.html","7/11 Fitness":"/7-11-fitness-kuendigung.html","ALDI TALK":"/aldi-talk-kuendigung.html","Ay Yildiz":"/ay-yildiz-kuendigung.html","Blau":"/blau-kuendigung.html","Body & Soul":"/body-and-soul-kuendigung.html","Bodystreet":"/bodystreet-kuendigung.html","Clever Fit":"/clever-fit-kuendigung.html","congstar":"/congstar-kuendigung.html","DeutschlandSIM":"/deutschlandsim-kuendigung.html","discoTEL":"/discotel-kuendigung.html","Drillisch":"/drillisch-kuendigung.html","EDEKA Mobil":"/edeka-mobil-kuendigung.html","EDEKA smart":"/edeka-smart-kuendigung.html","Fitness Express":"/fitness-express-kuendigung.html","Fitness First":"/fitness-first-kuendigung.html","FitnessKING":"/fitnessking-kuendigung.html","FitX":"/fitx-kuendigung.html","FONIC":"/fonic-kuendigung.html","fraenk":"/fraenk-kuendigung.html","freenet":"/freenet-kuendigung.html","HIGH Mobile":"/high-mobile-kuendigung.html","HIGH5":"/high5-kuendigung.html","Holmes Place":"/holmes-place-kuendigung.html","INJOY":"/injoy-kuendigung.html","ja! Mobil":"/ja-mobil-kuendigung.html","John Reed":"/john-reed-kuendigung.html","Kaufland Mobil":"/kaufland-mobil-kuendigung.html","Kieser Training":"/kieser-training-kuendigung.html","klarmobil":"/klarmobil-kuendigung.html","Lebara":"/lebara-kuendigung.html","Lidl Connect":"/lidl-connect-kuendigung.html","Lycamobile":"/lycamobile-kuendigung.html","MAXXIM":"/maxxim-kuendigung.html","McFit":"/mcfit-kuendigung.html","mobilcom-debitel":"/mobilcom-debitel-kuendigung.html","Mrs. Sporty":"/mrs-sporty-kuendigung.html","NORMA Connect":"/norma-connect-kuendigung.html","O2":"/o2-kuendigung.html","Ortel Mobile":"/ortel-mobile-kuendigung.html","otelo":"/otelo-kuendigung.html","Pfitzenmeier":"/pfitzenmeier-kuendigung.html","PremiumSIM":"/premiumsim-kuendigung.html","SIM.de":"/simde-kuendigung.html","simplytel":"/simplytel-kuendigung.html","smartmobil":"/smartmobil-kuendigung.html","superSelect":"/superselect-kuendigung.html","Tchibo Mobil":"/tchibo-mobil-kuendigung.html","Telekom":"/telekom-kuendigung.html","Urban Sports Club":"/urban-sports-club-kuendigung.html","Vodafone":"/vodafone-kuendigung.html","WinSIM":"/winsim-kuendigung.html","yourfone":"/yourfone-kuendigung.html"};

document.addEventListener('DOMContentLoaded', function() {
  var KI = document.getElementById('providerSearch');
  var KL = document.getElementById('ksList');
  if (!KI || !KL) return;

  function ksf(q) {
    q = (q || '').trim().toLowerCase();
    if (!q) { KL.style.display = 'none'; return; }
    var h = '';
    for (var n in KU) {
      if (n.toLowerCase().indexOf(q) !== -1) {
        h += '<a href="' + KU[n] + '" style="display:block;padding:12px 16px;font-size:15px;color:#0F172A;text-decoration:none;border-bottom:1px solid #E2E8F0;font-family:Arial,sans-serif;">' + n + '</a>';
      }
    }
    if (!h) h = '<div style="padding:12px 16px;color:#94A3B8;font-size:14px;">Kein Anbieter gefunden</div>';
    KL.innerHTML = h;
    var r = KI.getBoundingClientRect();
    KL.style.top = (r.bottom + window.scrollY + 4) + 'px';
    KL.style.left = r.left + 'px';
    KL.style.width = r.width + 'px';
    KL.style.display = 'block';
  }

  KI.addEventListener('input', function() { ksf(this.value); });

  KI.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      var f = KL.querySelector('a');
      if (f) f.click();
    }
  });

  document.addEventListener('click', function(e) {
    if (e.target !== KI) KL.style.display = 'none';
  });
});
