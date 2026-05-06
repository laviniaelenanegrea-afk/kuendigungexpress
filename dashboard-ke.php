<?php
declare(strict_types=1);

/* =========================================================
   SIMPLE AUTH — change this password
   ========================================================= */
$PASSWORD = '227100Yoshidomo.';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pw'])) {
    if ($_POST['pw'] === $PASSWORD) {
        $_SESSION['ke_auth'] = true;
    }
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

if (empty($_SESSION['ke_auth'])) {
    http_response_code(401);
    echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><title>Stats</title>
    <style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,sans-serif;background:#F7F9FC;display:flex;align-items:center;justify-content:center;min-height:100vh}
    .card{background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:32px;width:100%;max-width:360px;text-align:center}
    h1{font-size:18px;font-weight:900;margin-bottom:20px}
    input{width:100%;padding:10px 14px;border:1px solid #E2E8F0;border-radius:10px;font-size:14px;margin-bottom:12px}
    button{width:100%;background:#16A34A;color:#fff;border:none;padding:12px;border-radius:10px;font-weight:900;font-size:15px;cursor:pointer}
    </style></head><body>
    <div class="card"><h1>KündigungExpress Stats</h1>
    <form method="post">
    <input type="password" name="pw" placeholder="Passwort" autofocus>
    <button type="submit">Einloggen</button>
    </form></div></body></html>';
    exit;
}

/* =========================================================
   COUNT FILES
   ========================================================= */
$dataDir   = __DIR__ . '/_data';
$ordersDir = __DIR__ . '/_orders';
$emailFile = __DIR__ . '/_data/email_list.csv';

// Form submissions — JSON files in _data (exclude email_list.csv)
$submissions = 0;
$fitnessSubmissions = 0;
$handySubmissions = 0;
$kfzSubmissions = 0;
$cutoff = mktime(0, 0, 0, 2, 2, 2026); // only count from 02.02.2026

if (is_dir($dataDir)) {
    foreach (glob($dataDir . '/*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data) && ($data['createdAt'] ?? 0) >= $cutoff) {
            $submissions++;
            $t = $data['type'] ?? 'fitness';
            if ($t === 'handy') {
                $handySubmissions++;
            } elseif ($t === 'kfz') {
                $kfzSubmissions++;
            } else {
                $fitnessSubmissions++;
            }
        }
    }
}

// Legacy purchases (pre-free model) — kept for historical reference
$purchases = 0;
$recentPurchases = [];
if (is_dir($ordersDir)) {
    foreach (glob($ordersDir . '/*.json') as $file) {
        $order = json_decode(file_get_contents($file), true);
        if (is_array($order) && ($order['createdAt'] ?? 0) >= $cutoff) {
            $purchases++;
            $recentPurchases[] = $order['createdAt'];
        }
    }
}
rsort($recentPurchases);

// Additional PDF-level metrics (new Free + Affiliate model, live since 14.04.2026)
$pdfLast7 = 0;
$pdfLast24h = 0;
$emailSentCount = 0;
$providerCounts = [];
$dailyCounts = []; // for last 14-day trend (total)
$dailyByType = ['fitness' => [], 'handy' => [], 'kfz' => []]; // per-type daily
$sevenDaysAgo = time() - (7 * 24 * 60 * 60);
$oneDayAgo = time() - (24 * 60 * 60);
$fourteenDaysAgo = time() - (14 * 24 * 60 * 60);

if (is_dir($dataDir)) {
    foreach (glob($dataDir . '/*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data) || ($data['createdAt'] ?? 0) < $cutoff) continue;
        $ts = (int)$data['createdAt'];
        if ($ts >= $sevenDaysAgo) $pdfLast7++;
        if ($ts >= $oneDayAgo)    $pdfLast24h++;
        if (!empty($data['emailSent'])) $emailSentCount++;
        // Provider tally
        $prov = $data['provider'] ?? 'unbekannt';
        if (!isset($providerCounts[$prov])) $providerCounts[$prov] = 0;
        $providerCounts[$prov]++;
        // Daily breakdown last 14 days
        if ($ts >= $fourteenDaysAgo) {
            $day = date('Y-m-d', $ts);
            if (!isset($dailyCounts[$day])) $dailyCounts[$day] = 0;
            $dailyCounts[$day]++;
            // Per type
            $t = $data['type'] ?? 'fitness';
            if (!in_array($t, ['handy', 'kfz'])) $t = 'fitness';
            if (!isset($dailyByType[$t][$day])) $dailyByType[$t][$day] = 0;
            $dailyByType[$t][$day]++;
        }
    }
}
// Sort providers by frequency
arsort($providerCounts);
$topProviders = array_slice($providerCounts, 0, 10, true);

// Email-send rate (out of PDFs where anbieter's email was available)
$emailRate = $submissions > 0 ? round(($emailSentCount / $submissions) * 100, 1) : 0;


// Email subscribers
$emailCount = 0;
if (file_exists($emailFile)) {
    $lines = file($emailFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $emailCount = count($lines);
}

// Conversion rate
$convRate = $submissions > 0 ? round(($purchases / $submissions) * 100, 1) : 0;

// Revenue
$revenue = round($purchases * 4.99, 2);

// Last purchase
$lastPurchase = !empty($recentPurchases) ? date('d.m.Y H:i', $recentPurchases[0]) : '—';

// Purchases last 7 days
$sevenDaysAgo = time() - (7 * 24 * 60 * 60);
$recentCount = count(array_filter($recentPurchases, fn($t) => $t >= $sevenDaysAgo));

?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>KündigungExpress – Stats</title>
  <meta name="robots" content="noindex, nofollow">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: Arial, sans-serif;
      background: #F7F9FC;
      color: #0F172A;
      min-height: 100vh;
      padding: 32px 16px;
    }
    .wrap { max-width: 720px; margin: 0 auto; }
    h1 {
      font-size: 22px;
      font-weight: 900;
      margin-bottom: 6px;
    }
    .subtitle {
      font-size: 13px;
      color: #64748B;
      margin-bottom: 28px;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 14px;
      margin-bottom: 24px;
    }
    .stat-card {
      background: #fff;
      border: 1px solid #E2E8F0;
      border-radius: 16px;
      padding: 20px 18px;
    }
    .stat-label {
      font-size: 12px;
      color: #64748B;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 8px;
    }
    .stat-value {
      font-size: 32px;
      font-weight: 900;
      color: #0F172A;
      line-height: 1;
    }
    .stat-value.green { color: #16A34A; }
    .stat-value.blue  { color: #2563EB; }
    .stat-sub {
      font-size: 12px;
      color: #94A3B8;
      margin-top: 6px;
    }
    .section {
      background: #fff;
      border: 1px solid #E2E8F0;
      border-radius: 16px;
      padding: 20px 18px;
      margin-bottom: 14px;
    }
    .section h2 {
      font-size: 14px;
      font-weight: 800;
      margin-bottom: 14px;
      color: #0F172A;
    }
    .funnel {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .funnel-row {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .funnel-bar-wrap {
      flex: 1;
      background: #F1F5F9;
      border-radius: 6px;
      height: 28px;
      overflow: hidden;
    }
    .funnel-bar {
      height: 100%;
      border-radius: 6px;
      display: flex;
      align-items: center;
      padding-left: 10px;
      font-size: 12px;
      font-weight: 700;
      color: #fff;
      min-width: 40px;
      transition: width 0.3s ease;
    }
    .funnel-bar.visits  { background: #3B82F6; }
    .funnel-bar.forms   { background: #F59E0B; }
    .funnel-bar.paid    { background: #16A34A; }
    .funnel-label {
      font-size: 13px;
      color: #475569;
      width: 120px;
      flex-shrink: 0;
    }
    .funnel-count {
      font-size: 13px;
      font-weight: 800;
      color: #0F172A;
      width: 40px;
      text-align: right;
      flex-shrink: 0;
    }
    .note {
      font-size: 12px;
      color: #94A3B8;
      margin-top: 10px;
      font-style: italic;
    }
    .split {
      display: flex;
      gap: 14px;
    }
    .split .section { flex: 1; margin-bottom: 0; }
    .type-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid #F1F5F9;
      font-size: 14px;
    }
    .type-row:last-child { border-bottom: none; }
    .type-badge {
      font-size: 11px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 6px;
    }
    .badge-fitness { background: #DCFCE7; color: #166534; }
    .badge-handy   { background: #DBEAFE; color: #1E40AF; }
    .badge-kfz     { background: #FEF3C7; color: #92400E; }
    footer {
      text-align: center;
      font-size: 12px;
      color: #94A3B8;
      margin-top: 24px;
    }
    @media (max-width: 480px) {
      .split { flex-direction: column; }
    }
  </style>
</head>
<body>
  <div class="wrap">

    <h1>KündigungExpress – Stats</h1>
    <p class="subtitle">Stand: <?= date('d.m.Y H:i') ?> · Free + Affiliate Modell · Live seit 14.04.2026</p>

    <!-- TOP METRICS -->
    <div class="grid">
      <div class="stat-card">
        <div class="stat-label">PDFs erstellt</div>
        <div class="stat-value green"><?= $submissions ?></div>
        <div class="stat-sub">Gesamt (ab 02.02.2026)</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Letzte 7 Tage</div>
        <div class="stat-value"><?= $pdfLast7 ?></div>
        <div class="stat-sub"><?= $pdfLast24h ?> in letzten 24h</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Fitness PDFs</div>
        <div class="stat-value" style="color:#166534"><?= $fitnessSubmissions ?></div>
        <div class="stat-sub"><?= $submissions > 0 ? round(($fitnessSubmissions/$submissions)*100) : 0 ?>% aller PDFs</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Handy PDFs</div>
        <div class="stat-value" style="color:#1E40AF"><?= $handySubmissions ?></div>
        <div class="stat-sub"><?= $submissions > 0 ? round(($handySubmissions/$submissions)*100) : 0 ?>% aller PDFs</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">KFZ PDFs</div>
        <div class="stat-value" style="color:#D97706"><?= $kfzSubmissions ?></div>
        <div class="stat-sub"><?= $submissions > 0 ? round(($kfzSubmissions/$submissions)*100) : 0 ?>% aller PDFs</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">E-Mail an Anbieter</div>
        <div class="stat-value blue"><?= $emailRate ?>%</div>
        <div class="stat-sub"><?= $emailSentCount ?> direkt versendet</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">E-Mail-Liste</div>
        <div class="stat-value"><?= $emailCount ?></div>
        <div class="stat-sub">Fristen-Abonnenten</div>
      </div>
    </div>

    <!-- TREND: LAST 14 DAYS -->
    <div class="section">
      <h2>PDFs pro Tag — letzte 14 Tage</h2>
      <div style="display:flex;gap:12px;margin-bottom:14px;font-size:12px;">
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#16A34A;margin-right:4px;"></span>Fitness</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#2563EB;margin-right:4px;"></span>Handy</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#D97706;margin-right:4px;"></span>KFZ</span>
      </div>
      <div class="funnel">
        <?php
        $maxDaily = !empty($dailyCounts) ? max($dailyCounts) : 1;
        for ($i = 13; $i >= 0; $i--) {
            $day = date('Y-m-d', time() - ($i * 86400));
            $total = $dailyCounts[$day] ?? 0;
            $fit = $dailyByType['fitness'][$day] ?? 0;
            $han = $dailyByType['handy'][$day] ?? 0;
            $kfz = $dailyByType['kfz'][$day] ?? 0;
            $pctFit = $maxDaily > 0 ? round(($fit / $maxDaily) * 100) : 0;
            $pctHan = $maxDaily > 0 ? round(($han / $maxDaily) * 100) : 0;
            $pctKfz = $maxDaily > 0 ? round(($kfz / $maxDaily) * 100) : 0;
            $label = date('d.m', strtotime($day));
            ?>
            <div class="funnel-row">
              <div class="funnel-label"><?= $label ?></div>
              <div class="funnel-bar-wrap" style="display:flex;">
                <?php if ($fit > 0): ?><div style="width:<?= max($pctFit, 3) ?>%;background:#16A34A;height:100%;border-radius:6px 0 0 6px;"></div><?php endif; ?>
                <?php if ($han > 0): ?><div style="width:<?= max($pctHan, 3) ?>%;background:#2563EB;height:100%;<?= $fit === 0 ? 'border-radius:6px 0 0 6px;' : '' ?>"></div><?php endif; ?>
                <?php if ($kfz > 0): ?><div style="width:<?= max($pctKfz, 3) ?>%;background:#D97706;height:100%;border-radius:0 6px 6px 0;"></div><?php endif; ?>
              </div>
              <div class="funnel-count"><?= $total ?></div>
            </div>
            <?php
        }
        ?>
      </div>
      <p class="note">Tägliche PDF-Generierung nach Kategorie. Trend-Erkennung für die 15.06.2026 Bewertung.</p>
    </div>

    <!-- SPLIT: TOP PROVIDERS + SEITENÜBERSICHT + LEGACY -->
    <div class="split" style="flex-wrap:wrap;">
      <div class="section">
        <h2>Top 10 Anbieter</h2>
        <?php if (!empty($topProviders)): ?>
          <?php foreach ($topProviders as $prov => $count):
            $provLower = strtolower($prov);
            $badgeClass = 'badge-fitness';
            if (strpos($provLower, 'telekom') !== false || strpos($provLower, 'vodafone') !== false || strpos($provLower, 'o2') !== false || strpos($provLower, 'congstar') !== false || strpos($provLower, '1und1') !== false || strpos($provLower, 'blau') !== false || strpos($provLower, 'aldi') !== false || strpos($provLower, 'freenet') !== false || strpos($provLower, 'klarmobil') !== false || strpos($provLower, 'lidl') !== false || strpos($provLower, 'mobilcom') !== false || strpos($provLower, 'simde') !== false || strpos($provLower, 'fraenk') !== false || strpos($provLower, 'drillisch') !== false) $badgeClass = 'badge-handy';
            if (strpos($provLower, 'allianz') !== false || strpos($provLower, 'huk') !== false || strpos($provLower, 'axa') !== false || strpos($provLower, 'ergo') !== false || strpos($provLower, 'devk') !== false || strpos($provLower, 'generali') !== false || strpos($provLower, 'versicherung') !== false || strpos($provLower, 'zurich') !== false || strpos($provLower, 'hdi') !== false || strpos($provLower, 'r+v') !== false || strpos($provLower, 'lvm') !== false || strpos($provLower, 'vhv') !== false || strpos($provLower, 'cosmosdirekt') !== false) $badgeClass = 'badge-kfz';
          ?>
            <div class="type-row">
              <span><?= htmlspecialchars($prov) ?> <span class="type-badge <?= $badgeClass ?>" style="margin-left:4px;"><?= $badgeClass === 'badge-kfz' ? 'KFZ' : ($badgeClass === 'badge-handy' ? 'Handy' : 'Fitness') ?></span></span>
              <strong><?= $count ?></strong>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color:#64748B;font-size:13px;">Keine Daten verfügbar.</p>
        <?php endif; ?>
      </div>
      <div class="section">
        <h2>Seitenübersicht</h2>
        <div class="type-row">
          <span><span class="type-badge badge-handy">Handy</span> Seiten</span>
          <strong>38</strong>
        </div>
        <div class="type-row">
          <span><span class="type-badge badge-fitness">Fitness</span> Seiten</span>
          <strong>17</strong>
        </div>
        <div class="type-row">
          <span><span class="type-badge badge-kfz">KFZ</span> Seiten</span>
          <strong>33</strong>
        </div>
        <div class="type-row" style="border-top:1px solid #E2E8F0;padding-top:10px;margin-top:4px;">
          <span>Sitemap gesamt</span>
          <strong>104</strong>
        </div>
        <h2 style="margin-top:16px;">Affiliate-Partner</h2>
        <div class="type-row">
          <span>Check24 <span class="type-badge badge-handy">Handy</span></span>
          <strong style="color:#16A34A;">✓</strong>
        </div>
        <div class="type-row">
          <span>Tariffuxx <span class="type-badge badge-handy">Handy</span></span>
          <strong style="color:#16A34A;">✓</strong>
        </div>
        <div class="type-row">
          <span>AWIN Telekom <span class="type-badge badge-handy">Handy</span></span>
          <strong style="color:#16A34A;">✓</strong>
        </div>
        <div class="type-row">
          <span>CRASH 30GB <span class="type-badge badge-handy">Handy</span></span>
          <strong style="color:#16A34A;">40€</strong>
        </div>
        <div class="type-row">
          <span>Tarifcheck <span class="type-badge badge-kfz">KFZ</span></span>
          <strong style="color:#F59E0B;">beantragt</strong>
        </div>
      </div>
    </div>

    <!-- LEGACY MODEL -->
    <div class="section" style="margin-top:14px;">
      <h2>Altmodell (vor 14.04.2026)</h2>
      <div style="display:flex;gap:24px;flex-wrap:wrap;">
        <div class="type-row" style="flex:1;min-width:140px;">
          <span>Stripe Käufe gesamt</span>
          <strong><?= $purchases ?></strong>
        </div>
        <div class="type-row" style="flex:1;min-width:140px;">
          <span>Umsatz gesamt (€4,99)</span>
          <strong>€<?= number_format($revenue, 2, ',', '.') ?></strong>
        </div>
        <div class="type-row" style="flex:1;min-width:140px;">
          <span>Letzter Kauf</span>
          <strong style="font-size:13px;"><?= $lastPurchase ?></strong>
        </div>
      </div>
      <p style="color:#64748B;font-size:12px;margin-top:10px;line-height:1.5;">Referenz vom alten bezahlten Modell. Ab 14.04.2026 läuft das Free+Affiliate Modell.</p>
    </div>

  </div>
  <footer>KündigungExpress · Internes Dashboard · <a href="" style="color:#64748B !important;font-weight:normal !important;text-decoration:none !important;">Aktualisieren</a></footer>
</body>
</html>