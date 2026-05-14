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
    <button>Anmelden</button>
    </form></div>
    </body></html>';
    exit;
}

/* =========================================================
   DATA COLLECTION
   ========================================================= */
$dataDir       = __DIR__ . '/_data';
$ordersDir     = __DIR__ . '/_orders';
$emailFile     = __DIR__ . '/_data/email_list.csv';
$affiliateLog  = __DIR__ . '/_data/affiliate_clicks.log';

$cutoff = mktime(0, 0, 0, 2, 2, 2026);

$submissions = $fitnessSubmissions = $handySubmissions = $kfzSubmissions = 0;
$pdfLast7 = $pdfLast24h = $pdfLast30 = 0;
$emailSentCount = $hasEmailCount = $hasContractCount = 0;
$providerCounts = [];
$dailyCounts = [];
$dailyByType = ['fitness' => [], 'handy' => [], 'kfz' => []];
$emailRateByType = ['fitness' => ['sent' => 0, 'total' => 0], 'handy' => ['sent' => 0, 'total' => 0], 'kfz' => ['sent' => 0, 'total' => 0]];
$firstSubmission = null;
$lastSubmission = null;
$hourOfDay = array_fill(0, 24, 0);

$sevenDaysAgo    = time() - (7 * 86400);
$oneDayAgo       = time() - 86400;
$fourteenDaysAgo = time() - (14 * 86400);
$thirtyDaysAgo   = time() - (30 * 86400);

if (is_dir($dataDir)) {
    foreach (glob($dataDir . '/*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data) || ($data['createdAt'] ?? 0) < $cutoff) continue;
        $ts = (int)$data['createdAt'];
        $type = $data['type'] ?? 'fitness';
        if (!in_array($type, ['handy', 'kfz'])) $type = 'fitness';

        $submissions++;
        if ($type === 'handy') $handySubmissions++;
        elseif ($type === 'kfz') $kfzSubmissions++;
        else $fitnessSubmissions++;

        if ($ts >= $sevenDaysAgo) $pdfLast7++;
        if ($ts >= $oneDayAgo)    $pdfLast24h++;
        if ($ts >= $thirtyDaysAgo) $pdfLast30++;

        if (!empty($data['emailSent'])) $emailSentCount++;
        if (!empty($data['hasEmail']))  $hasEmailCount++;
        if (!empty($data['hasContract'])) $hasContractCount++;

        $emailRateByType[$type]['total']++;
        if (!empty($data['emailSent'])) $emailRateByType[$type]['sent']++;

        $prov = $data['provider'] ?? 'unbekannt';
        $providerCounts[$prov] = ($providerCounts[$prov] ?? 0) + 1;

        if ($ts >= $fourteenDaysAgo) {
            $day = date('Y-m-d', $ts);
            $dailyCounts[$day] = ($dailyCounts[$day] ?? 0) + 1;
            $dailyByType[$type][$day] = ($dailyByType[$type][$day] ?? 0) + 1;
        }

        if ($firstSubmission === null || $ts < $firstSubmission) $firstSubmission = $ts;
        if ($lastSubmission === null || $ts > $lastSubmission)   $lastSubmission = $ts;

        if ($ts >= $thirtyDaysAgo) {
            $hourOfDay[(int)date('G', $ts)]++;
        }
    }
}
arsort($providerCounts);
$topProviders = array_slice($providerCounts, 0, 10, true);

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
$revenue = round($purchases * 4.99, 2);
$lastPurchase = !empty($recentPurchases) ? date('d.m.Y H:i', $recentPurchases[0]) : '—';

$emailCount = 0;
if (file_exists($emailFile)) {
    $lines = file($emailFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $emailCount = count($lines);
}

$emailRate    = $submissions > 0 ? round(($emailSentCount / $submissions) * 100, 1) : 0;
$emailOptIn   = $submissions > 0 ? round(($hasEmailCount / $submissions) * 100, 1) : 0;
$contractRate = $submissions > 0 ? round(($hasContractCount / $submissions) * 100, 1) : 0;

$minutesSinceLastPdf = $lastSubmission ? (int)round((time() - $lastSubmission) / 60) : null;
$activityStatus = 'inactive';
$activityColor  = '#94A3B8';
if ($minutesSinceLastPdf !== null) {
    if ($minutesSinceLastPdf < 360)        { $activityStatus = 'aktiv';   $activityColor = '#16A34A'; }
    elseif ($minutesSinceLastPdf < 1440)   { $activityStatus = 'ruhig';   $activityColor = '#F59E0B'; }
    else                                    { $activityStatus = 'inaktiv'; $activityColor = '#DC2626'; }
}

$avg7d = $pdfLast7 > 0 ? round($pdfLast7 / 7, 1) : 0;
$projectionMonth = (int)round($avg7d * 30);

$affiliateClicks7d = ['check24' => 0, 'tariffuxx' => 0, 'telekom_awin' => 0, 'plankpad_awin' => 0, 'tarifcheck24' => 0, 'crash' => 0];
if (file_exists($affiliateLog)) {
    $handle = fopen($affiliateLog, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $parts = explode("\t", trim($line));
            if (count($parts) >= 2) {
                $clickTs = (int)$parts[0];
                $partner = strtolower($parts[1]);
                if ($clickTs >= $sevenDaysAgo && isset($affiliateClicks7d[$partner])) {
                    $affiliateClicks7d[$partner]++;
                }
            }
        }
        fclose($handle);
    }
}
$totalAffiliateClicks7d = array_sum($affiliateClicks7d);
$affiliateCTR7d = $pdfLast7 > 0 ? round(($totalAffiliateClicks7d / $pdfLast7) * 100, 1) : 0;
$hasAffiliateLog = file_exists($affiliateLog);

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
    html { overflow-x: hidden; }
    body {
      font-family: Arial, sans-serif;
      background: #F7F9FC;
      color: #0F172A;
      min-height: 100vh;
      padding: 32px 16px;
      overflow-x: hidden;
      max-width: 100vw;
    }
    .wrap { max-width: 900px; margin: 0 auto; }
    h1 { font-size: 22px; font-weight: 900; margin-bottom: 6px; }
    .subtitle { font-size: 13px; color: #64748B; margin-bottom: 8px; }
    .live-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      background: #fff;
      border: 1px solid #E2E8F0;
      margin-bottom: 24px;
    }
    .live-dot { width: 8px; height: 8px; border-radius: 50%; animation: pulse 2s infinite; }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.4; }
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 12px;
      margin-bottom: 20px;
    }
    .stat-card {
      background: #fff;
      border: 1px solid #E2E8F0;
      border-radius: 14px;
      padding: 16px 14px;
      min-width: 0;
    }
    .stat-label {
      font-size: 11px;
      color: #64748B;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin-bottom: 6px;
    }
    .stat-value {
      font-size: 28px;
      font-weight: 900;
      color: #0F172A;
      line-height: 1;
      word-break: break-word;
    }
    .stat-value.green   { color: #16A34A; }
    .stat-value.blue    { color: #2563EB; }
    .stat-value.amber   { color: #D97706; }
    .stat-value.red     { color: #DC2626; }
    .stat-sub {
      font-size: 11px;
      color: #94A3B8;
      margin-top: 4px;
      line-height: 1.4;
    }
    .section {
      background: #fff;
      border: 1px solid #E2E8F0;
      border-radius: 14px;
      padding: 18px 16px;
      margin-bottom: 14px;
    }
    .section h2 {
      font-size: 13px;
      font-weight: 800;
      margin-bottom: 12px;
      color: #0F172A;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .funnel { display: flex; flex-direction: column; gap: 6px; }
    .funnel-row { display: flex; align-items: center; gap: 10px; }
    .funnel-bar-wrap {
      flex: 1;
      background: #F1F5F9;
      border-radius: 6px;
      height: 24px;
      overflow: hidden;
      min-width: 0;
    }
    .funnel-label { font-size: 12px; color: #475569; width: 50px; flex-shrink: 0; }
    .funnel-count { font-size: 12px; font-weight: 800; color: #0F172A; width: 30px; text-align: right; flex-shrink: 0; }
    .note { font-size: 11px; color: #94A3B8; margin-top: 10px; font-style: italic; line-height: 1.5; }
    .split { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; }
    .split .section { margin-bottom: 0; }
    .type-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 7px 0;
      border-bottom: 1px solid #F1F5F9;
      font-size: 13px;
      gap: 8px;
    }
    .type-row:last-child { border-bottom: none; }
    .type-badge {
      font-size: 10px;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 4px;
      white-space: nowrap;
    }
    .badge-fitness { background: #DCFCE7; color: #166534; }
    .badge-handy   { background: #DBEAFE; color: #1E40AF; }
    .badge-kfz     { background: #FEF3C7; color: #92400E; }
    .partner-status {
      font-size: 11px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 999px;
      white-space: nowrap;
    }
    .status-live    { background: #DCFCE7; color: #166534; }
    .status-pending { background: #FEF3C7; color: #92400E; }
    .status-rejected { background: #FEE2E2; color: #991B1B; }
    .hour-bar { display: flex; gap: 2px; height: 50px; align-items: flex-end; margin-top: 8px; }
    .hour-cell {
      flex: 1;
      background: #16A34A;
      border-radius: 2px 2px 0 0;
      min-height: 2px;
    }
    .hour-labels { display: flex; justify-content: space-between; font-size: 9px; color: #94A3B8; margin-top: 4px; }
    .warn-box {
      background: #FEF3C7;
      border-radius: 8px;
      padding: 10px 12px;
      color: #92400E;
      font-size: 12px;
      margin-top: 12px;
      line-height: 1.5;
    }
    footer { text-align: center; font-size: 12px; color: #94A3B8; margin-top: 24px; }
    footer a { color: #2563EB; text-decoration: none; font-weight: 600; }
    @media (max-width: 480px) {
      body { padding: 16px 10px; }
      .stat-value { font-size: 24px; }
      .grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
      .stat-card { padding: 12px 10px; }
    }
  </style>
</head>
<body>
  <div class="wrap">

    <h1>KündigungExpress – Stats</h1>
    <p class="subtitle">Stand: <?= date('d.m.Y H:i') ?> · Free + Affiliate Modell · Live seit 14.04.2026</p>
    <div class="live-badge">
      <span class="live-dot" style="background:<?= $activityColor ?>"></span>
      Status: <strong style="margin-left:2px;"><?= $activityStatus ?></strong>
      <?php if ($minutesSinceLastPdf !== null): ?>
        · letzte PDF vor
        <?php if ($minutesSinceLastPdf < 60): ?>
          <?= $minutesSinceLastPdf ?> Min.
        <?php elseif ($minutesSinceLastPdf < 1440): ?>
          <?= round($minutesSinceLastPdf/60, 1) ?> Std.
        <?php else: ?>
          <?= round($minutesSinceLastPdf/1440, 1) ?> Tagen
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- TOP METRICS — VOLUME -->
    <div class="grid">
      <div class="stat-card">
        <div class="stat-label">PDFs gesamt</div>
        <div class="stat-value green"><?= $submissions ?></div>
        <div class="stat-sub">seit 02.02.2026</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Letzte 7 Tage</div>
        <div class="stat-value"><?= $pdfLast7 ?></div>
        <div class="stat-sub">⌀ <?= $avg7d ?>/Tag · <?= $pdfLast24h ?> in 24h</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Letzte 30 Tage</div>
        <div class="stat-value"><?= $pdfLast30 ?></div>
        <div class="stat-sub">Hochrechnung: <?= $projectionMonth ?>/Monat</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">E-Mail-Liste</div>
        <div class="stat-value blue"><?= $emailCount ?></div>
        <div class="stat-sub">Fristen-Abos</div>
      </div>
    </div>

    <!-- BREAKDOWN BY TYPE -->
    <div class="grid">
      <div class="stat-card">
        <div class="stat-label">Fitness</div>
        <div class="stat-value" style="color:#166534"><?= $fitnessSubmissions ?></div>
        <div class="stat-sub"><?= $submissions > 0 ? round(($fitnessSubmissions/$submissions)*100) : 0 ?>% aller PDFs</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Handy</div>
        <div class="stat-value" style="color:#1E40AF"><?= $handySubmissions ?></div>
        <div class="stat-sub"><?= $submissions > 0 ? round(($handySubmissions/$submissions)*100) : 0 ?>% aller PDFs</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">KFZ</div>
        <div class="stat-value amber"><?= $kfzSubmissions ?></div>
        <div class="stat-sub"><?= $submissions > 0 ? round(($kfzSubmissions/$submissions)*100) : 0 ?>% aller PDFs</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">PDF + E-Mail Versand</div>
        <div class="stat-value"><?= $emailRate ?>%</div>
        <div class="stat-sub"><?= $emailSentCount ?> direkt versendet</div>
      </div>
    </div>

    <!-- USER BEHAVIOR -->
    <div class="grid">
      <div class="stat-card">
        <div class="stat-label">Vertragsnr. angegeben</div>
        <div class="stat-value"><?= $contractRate ?>%</div>
        <div class="stat-sub"><?= $hasContractCount ?> mit Nummer</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">E-Mail-Opt-in</div>
        <div class="stat-value"><?= $emailOptIn ?>%</div>
        <div class="stat-sub"><?= $hasEmailCount ?> haben E-Mail angegeben</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Affiliate-Clicks 7d</div>
        <div class="stat-value <?= $hasAffiliateLog ? 'green' : '' ?>"><?= $hasAffiliateLog ? $totalAffiliateClicks7d : '—' ?></div>
        <div class="stat-sub"><?= $hasAffiliateLog ? $affiliateCTR7d . '% vs PDFs' : 'Tracking nicht aktiv' ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Erste PDF</div>
        <div class="stat-value" style="font-size:18px;"><?= $firstSubmission ? date('d.m.Y', $firstSubmission) : '—' ?></div>
        <div class="stat-sub"><?= $firstSubmission ? floor((time() - $firstSubmission) / 86400) . ' Tage aktiv' : '' ?></div>
      </div>
    </div>

    <!-- TREND: LAST 14 DAYS -->
    <div class="section">
      <h2>PDFs pro Tag — letzte 14 Tage</h2>
      <div style="display:flex;gap:14px;margin-bottom:12px;font-size:11px;flex-wrap:wrap;">
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#16A34A;margin-right:4px;vertical-align:middle;"></span>Fitness</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#2563EB;margin-right:4px;vertical-align:middle;"></span>Handy</span>
        <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#D97706;margin-right:4px;vertical-align:middle;"></span>KFZ</span>
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
            $weekday = date('D', strtotime($day));
            $isWeekend = in_array($weekday, ['Sat', 'Sun']);
            ?>
            <div class="funnel-row">
              <div class="funnel-label" style="<?= $isWeekend ? 'color:#94A3B8;font-style:italic;' : '' ?>"><?= $label ?></div>
              <div class="funnel-bar-wrap" style="display:flex;">
                <?php if ($fit > 0): ?><div style="width:<?= max($pctFit, 3) ?>%;background:#16A34A;height:100%;"></div><?php endif; ?>
                <?php if ($han > 0): ?><div style="width:<?= max($pctHan, 3) ?>%;background:#2563EB;height:100%;"></div><?php endif; ?>
                <?php if ($kfz > 0): ?><div style="width:<?= max($pctKfz, 3) ?>%;background:#D97706;height:100%;"></div><?php endif; ?>
              </div>
              <div class="funnel-count"><?= $total ?></div>
            </div>
            <?php
        }
        ?>
      </div>
      <p class="note">Tägliche Verteilung. Wochenenden kursiv. Trend-Check für 15.06.2026 Scaling-Bewertung.</p>
    </div>

    <!-- HOUR OF DAY -->
    <div class="section">
      <h2>Aktivität nach Uhrzeit — letzte 30 Tage</h2>
      <?php $maxHour = max($hourOfDay) ?: 1; ?>
      <div class="hour-bar">
        <?php for ($h = 0; $h < 24; $h++):
          $pct = round(($hourOfDay[$h] / $maxHour) * 100);
        ?>
          <div class="hour-cell" style="height:<?= max($pct, 4) ?>%;" title="<?= $h ?>:00 — <?= $hourOfDay[$h] ?> PDFs"></div>
        <?php endfor; ?>
      </div>
      <div class="hour-labels">
        <span>0h</span><span>6h</span><span>12h</span><span>18h</span><span>23h</span>
      </div>
      <p class="note">Wann generieren Nutzer PDFs? Hilft Affiliate-Push-Timing und E-Mail-Send-Zeit zu optimieren.</p>
    </div>

    <!-- TOP PROVIDERS + EMAIL RATE BY TYPE -->
    <div class="split">
      <div class="section">
        <h2>Top 10 Anbieter</h2>
        <?php if (!empty($topProviders)): ?>
          <?php foreach ($topProviders as $prov => $count):
            $provLower = strtolower($prov);
            $badgeClass = 'badge-fitness';
            $badgeLabel = 'Fitness';
            if (preg_match('/telekom|vodafone|o2|congstar|1und1|blau|aldi|freenet|klarmobil|lidl|mobilcom|simde|fraenk|drillisch|tchibo|otelo|fonic|winsim|premiumsim/i', $prov)) {
              $badgeClass = 'badge-handy'; $badgeLabel = 'Handy';
            }
            if (preg_match('/allianz|huk|axa|ergo|devk|generali|versicherung|zurich|hdi|r\+v|lvm|vhv|cosmosdirekt|adac|gothaer|provinzial|signal|verti|wgv|itzehoer|alte-leipziger|nuernberger|wuerttembergische|sparkassen|oeffentliche/i', $prov)) {
              $badgeClass = 'badge-kfz'; $badgeLabel = 'KFZ';
            }
          ?>
            <div class="type-row">
              <span><?= htmlspecialchars($prov) ?> <span class="type-badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></span>
              <strong><?= $count ?></strong>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color:#64748B;font-size:13px;">Keine Daten verfügbar.</p>
        <?php endif; ?>
      </div>

      <div class="section">
        <h2>E-Mail-Versand pro Kategorie</h2>
        <?php foreach (['fitness' => 'Fitness', 'handy' => 'Handy', 'kfz' => 'KFZ'] as $type => $label):
          $stats = $emailRateByType[$type];
          $rate = $stats['total'] > 0 ? round(($stats['sent'] / $stats['total']) * 100, 1) : 0;
          $badge = $type === 'handy' ? 'badge-handy' : ($type === 'kfz' ? 'badge-kfz' : 'badge-fitness');
        ?>
          <div class="type-row">
            <span><span class="type-badge <?= $badge ?>"><?= $label ?></span></span>
            <strong><?= $rate ?>% <span style="color:#94A3B8;font-weight:normal;font-size:11px;">(<?= $stats['sent'] ?>/<?= $stats['total'] ?>)</span></strong>
          </div>
        <?php endforeach; ?>
        <p class="note">Wer hat die Kündigung direkt per E-Mail versendet?</p>
      </div>
    </div>

    <!-- AFFILIATE PARTNERS -->
    <div class="section">
      <h2>Affiliate-Partner — Click-Tracking 7 Tage</h2>
      <div class="split" style="gap:10px;">
        <div>
          <p style="font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Handy</p>
          <div class="type-row">
            <span>Check24</span>
            <span><strong style="margin-right:6px;"><?= $hasAffiliateLog ? $affiliateClicks7d['check24'] : '—' ?></strong><span class="partner-status status-live">live</span></span>
          </div>
          <div class="type-row">
            <span>Tariffuxx</span>
            <span><strong style="margin-right:6px;"><?= $hasAffiliateLog ? $affiliateClicks7d['tariffuxx'] : '—' ?></strong><span class="partner-status status-live">live</span></span>
          </div>
          <div class="type-row">
            <span>AWIN Telekom</span>
            <span><strong style="margin-right:6px;"><?= $hasAffiliateLog ? $affiliateClicks7d['telekom_awin'] : '—' ?></strong><span class="partner-status status-live">live</span></span>
          </div>
          <div class="type-row">
            <span>CRASH 30GB</span>
            <span><strong style="margin-right:6px;"><?= $hasAffiliateLog ? $affiliateClicks7d['crash'] : '—' ?></strong><span class="partner-status status-live">40€/Sale</span></span>
          </div>
        </div>
        <div>
          <p style="font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Fitness</p>
          <div class="type-row">
            <span>AWIN Plankpad</span>
            <span><strong style="margin-right:6px;"><?= $hasAffiliateLog ? $affiliateClicks7d['plankpad_awin'] : '—' ?></strong><span class="partner-status status-live">live</span></span>
          </div>
          <p style="font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;margin:14px 0 6px;">KFZ</p>
          <div class="type-row">
            <span>Tarifcheck24</span>
            <span><strong style="margin-right:6px;"><?= $hasAffiliateLog ? $affiliateClicks7d['tarifcheck24'] : '—' ?></strong><span class="partner-status status-live">live</span></span>
          </div>
        </div>
      </div>
      <?php if (!$hasAffiliateLog): ?>
        <div class="warn-box">
          ⚠ Click-Tracking nicht aktiv. Affiliate-Snippet auf den Seiten implementieren, um Live-CTR-Daten zu erhalten.
        </div>
      <?php endif; ?>
    </div>

    <!-- SITE OVERVIEW -->
    <div class="section">
      <h2>Seitenübersicht & Conversion pro Kategorie</h2>
      <div class="split">
        <div>
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
            <span><strong>Sitemap gesamt</strong></span>
            <strong>104</strong>
          </div>
        </div>
        <div>
          <p style="font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;margin-bottom:6px;">PDF-Yield pro Seite</p>
          <?php
          $sitePerType = ['handy' => 38, 'fitness' => 17, 'kfz' => 33];
          $pdfPerType = ['fitness' => $fitnessSubmissions, 'handy' => $handySubmissions, 'kfz' => $kfzSubmissions];
          foreach (['fitness' => 'Fitness', 'handy' => 'Handy', 'kfz' => 'KFZ'] as $t => $label):
            $perPage = $sitePerType[$t] > 0 ? round($pdfPerType[$t] / $sitePerType[$t], 1) : 0;
            $badge = $t === 'handy' ? 'badge-handy' : ($t === 'kfz' ? 'badge-kfz' : 'badge-fitness');
          ?>
            <div class="type-row">
              <span><span class="type-badge <?= $badge ?>"><?= $label ?></span></span>
              <strong><?= $perPage ?> <span style="color:#94A3B8;font-weight:normal;font-size:11px;">PDFs/Seite</span></strong>
            </div>
          <?php endforeach; ?>
          <p class="note">Welche Kategorie liefert pro Seite die meisten Conversions?</p>
        </div>
      </div>
    </div>

    <!-- LEGACY -->
    <div class="section">
      <h2>Altmodell (vor 14.04.2026) — Referenz</h2>
      <div class="split">
        <div class="type-row" style="border-bottom:none;">
          <span>Stripe Käufe gesamt</span>
          <strong><?= $purchases ?></strong>
        </div>
        <div class="type-row" style="border-bottom:none;">
          <span>Umsatz gesamt</span>
          <strong>€<?= number_format($revenue, 2, ',', '.') ?></strong>
        </div>
        <div class="type-row" style="border-bottom:none;">
          <span>Letzter Kauf</span>
          <strong style="font-size:12px;"><?= $lastPurchase ?></strong>
        </div>
      </div>
      <p class="note">Historischer Referenzwert. Free + Affiliate seit 14.04.2026.</p>
    </div>

  </div>
  <footer>
    KündigungExpress · Internes Dashboard ·
    <a href="">Aktualisieren</a>
  </footer>
</body>
</html>
