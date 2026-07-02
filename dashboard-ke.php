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
$aiVisitsLog   = __DIR__ . '/_data/ai_visits.log';
$pdfLogFile    = __DIR__ . '/_data/pdf_generated.log';   // append-only, supraviețuiește cron
$counterFile   = __DIR__ . '/_counter.txt';              // total all-time PDF

// Emailul cu care testezi Versand (comenzile tale de test se exclud din venit)
$TEST_EMAIL    = 'laviniaelena.negrea@gmail.com';

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

// PDF total all-time (imun la cron). Include teste/batch-uri istorice.
$pdfCounterAllTime = is_file($counterFile) ? (int)trim((string)@file_get_contents($counterFile)) : null;

// Sursă primară pentru breakdown = pdf_generated.log (ts \t type \t provider \t emailSent \t hasEmail)
// (supraviețuiește cron-ului; acumulează istoric de la deploy-ul patch-ului încoace)
if (is_file($pdfLogFile)) {
    $fh = fopen($pdfLogFile, 'r');
    if ($fh) {
        while (($line = fgets($fh)) !== false) {
            $p = explode("\t", rtrim($line, "\r\n"));
            if (count($p) < 3) continue;
            $ts = (int)$p[0];
            if ($ts < $cutoff) continue;
            $type = $p[1] !== '' ? $p[1] : 'fitness';
            if (!in_array($type, ['handy', 'kfz'])) $type = 'fitness';
            $prov      = ($p[2] !== '') ? $p[2] : 'unbekannt';
            $emailSent = isset($p[3]) && $p[3] === '1';
            $hasEmail  = isset($p[4]) && $p[4] === '1';

            $submissions++;
            if ($type === 'handy') $handySubmissions++;
            elseif ($type === 'kfz') $kfzSubmissions++;
            else $fitnessSubmissions++;

            if ($ts >= $sevenDaysAgo)  $pdfLast7++;
            if ($ts >= $oneDayAgo)     $pdfLast24h++;
            if ($ts >= $thirtyDaysAgo) $pdfLast30++;

            if ($emailSent) $emailSentCount++;
            if ($hasEmail)  $hasEmailCount++;

            $emailRateByType[$type]['total']++;
            if ($emailSent) $emailRateByType[$type]['sent']++;

            $providerCounts[$prov] = ($providerCounts[$prov] ?? 0) + 1;

            if ($ts >= $fourteenDaysAgo) {
                $day = date('Y-m-d', $ts);
                $dailyCounts[$day] = ($dailyCounts[$day] ?? 0) + 1;
                $dailyByType[$type][$day] = ($dailyByType[$type][$day] ?? 0) + 1;
            }
            if ($firstSubmission === null || $ts < $firstSubmission) $firstSubmission = $ts;
            if ($lastSubmission === null || $ts > $lastSubmission)   $lastSubmission = $ts;
            if ($ts >= $thirtyDaysAgo) $hourOfDay[(int)date('G', $ts)]++;
        }
        fclose($fh);
    }
}

// hasContract NU e în log → eșantion din _data/*.json rămas (recent, best-effort)
$contractSample = 0;
if (is_dir($dataDir)) {
    foreach (glob($dataDir . '/*.json') as $file) {
        $data = json_decode((string)@file_get_contents($file), true);
        if (!is_array($data) || ($data['createdAt'] ?? 0) < $cutoff) continue;
        $contractSample++;
        if (!empty($data['hasContract'])) $hasContractCount++;
    }
}
arsort($providerCounts);
$topProviders = array_slice($providerCounts, 0, 10, true);

$purchases = 0;
$recentPurchases = [];

// Versand orders — tier split + real revenue din amount_cents
$versandStandard = $versandEinschreiben = 0;        // count all-time per tier
$versandStandard7d = $versandEinschreiben7d = 0;     // count last 7 days per tier
$revenueCents = 0;                                   // revenue all-time (cents)
$revenueCents7d = 0;                                 // revenue last 7 days (cents)
$testCount = $testCount7d = 0;                       // comenzi de test (excluse)
$testRevenueCents = 0;                               // venit din teste (exclus)

if (is_dir($ordersDir)) {
    foreach (glob($ordersDir . '/*.json') as $file) {
        $order = json_decode(file_get_contents($file), true);
        if (!is_array($order)) continue;
        $ots = (int)($order['createdAt'] ?? 0);
        if ($ots < $cutoff) continue;

        // Doar comenzi plătite (paymentStatus='paid' sau au amount_cents > 0)
        $isPaid = (($order['paymentStatus'] ?? '') === 'paid') || ((int)($order['amount_cents'] ?? 0) > 0);
        if (!$isPaid) continue;

        $tier  = (string)($order['tier'] ?? ($order['versandTier'] ?? 'standard'));
        $cents = (int)($order['amount_cents'] ?? 0);
        if ($cents <= 0) $cents = ($tier === 'einschreiben') ? 899 : 399; // fallback din tier

        // --- Filtru TESTE: email-ul tău / Stripe TEST mode / LetterXpress test ---
        $uemail = strtolower(trim((string)($order['userEmail'] ?? ($order['email'] ?? ''))));
        $live   = $order['livemode'] ?? null;
        $lxmode = strtolower((string)($order['lxMode'] ?? ''));
        $isTestOrder =
            ($uemail !== '' && $uemail === strtolower(trim($TEST_EMAIL)))
            || ($live === false || $live === 'false' || $live === 0 || $live === '0')
            || ($lxmode === 'test' || $lxmode === 'sandbox');
        if ($isTestOrder) {
            $testCount++; $testRevenueCents += $cents;
            if ($ots >= $sevenDaysAgo) { $testCount7d++; }
            continue;
        }

        $purchases++;
        $recentPurchases[] = $ots;

        $revenueCents += $cents;
        if ($tier === 'einschreiben') $versandEinschreiben++; else $versandStandard++;

        if ($ots >= $sevenDaysAgo) {
            $revenueCents7d += $cents;
            if ($tier === 'einschreiben') $versandEinschreiben7d++; else $versandStandard7d++;
        }
    }
}
rsort($recentPurchases);
$revenue       = round($revenueCents / 100, 2);
$revenue7d     = round($revenueCents7d / 100, 2);
$avgOrderValue = $purchases > 0 ? round($revenueCents / $purchases / 100, 2) : 0;
$lastPurchase  = !empty($recentPurchases) ? date('d.m.Y H:i', $recentPurchases[0]) : '—';

$emailCount = 0;
if (file_exists($emailFile)) {
    $lines = file($emailFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $emailCount = count($lines);
}

$emailRate    = $submissions > 0 ? round(($emailSentCount / $submissions) * 100, 1) : 0;
$emailOptIn   = $submissions > 0 ? round(($hasEmailCount / $submissions) * 100, 1) : 0;
$contractRate = $contractSample > 0 ? round(($hasContractCount / $contractSample) * 100, 1) : 0;

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

/* =========================================================
   AFFILIATE TRACKING — clicks + source attribution
   Log format: timestamp \t partner \t page \t referrer_host
   ========================================================= */
$affiliateClicks7d = [
    'check24'       => 0,
    'tariffuxx'     => 0,
    'telekom_awin'  => 0,
    'tarifcheck'    => 0,
    'amazon'        => 0,
    'crash'         => 0,
    'volders'       => 0,
];
// All-time clicks per partner (toate click-urile din log, nu doar 7 zile)
$affiliateClicksAll = $affiliateClicks7d;

// Source attribution: clicks per referrer category (last 7 days)
$sourceClicks7d = ['google' => 0, 'bing' => 0, 'yahoo' => 0, 'intern' => 0, 'direct' => 0, 'other' => 0];

// Matrix: partner × source (last 7 days)
$partnerSourceMatrix = [];
foreach (array_keys($affiliateClicks7d) as $p) {
    $partnerSourceMatrix[$p] = ['google' => 0, 'bing' => 0, 'yahoo' => 0, 'intern' => 0, 'direct' => 0, 'other' => 0];
}

function categorizeReferrer(string $host): string {
    $host = strtolower($host);
    if ($host === 'direct' || $host === '') return 'direct';
    if (strpos($host, 'google.') !== false) return 'google';
    if (strpos($host, 'bing.') !== false) return 'bing';
    if (strpos($host, 'yahoo.') !== false) return 'yahoo';
    if (strpos($host, 'kuendigungexpress') !== false) return 'intern';
    return 'other';
}

if (file_exists($affiliateLog)) {
    $handle = fopen($affiliateLog, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $parts = explode("\t", trim($line));
            if (count($parts) >= 2) {
                $clickTs = (int)$parts[0];
                $partner = strtolower($parts[1]);
                $referrer = $parts[3] ?? 'direct';
                if (isset($affiliateClicksAll[$partner])) {
                    $affiliateClicksAll[$partner]++;   // all-time
                }
                if ($clickTs >= $sevenDaysAgo && isset($affiliateClicks7d[$partner])) {
                    $affiliateClicks7d[$partner]++;
                    $sourceCat = categorizeReferrer($referrer);
                    $sourceClicks7d[$sourceCat]++;
                    $partnerSourceMatrix[$partner][$sourceCat]++;
                }
            }
        }
        fclose($handle);
    }
}
$totalAffiliateClicks7d = array_sum($affiliateClicks7d);
$totalAffiliateClicksAll = array_sum($affiliateClicksAll);
$affiliateCTR7d = $pdfLast7 > 0 ? round(($totalAffiliateClicks7d / $pdfLast7) * 100, 1) : 0;
$hasAffiliateLog = file_exists($affiliateLog);

/* =========================================================
   AI-VISITS — Besuche von KI-Assistenten (ChatGPT, Claude, etc.)
   Log format: timestamp \t source \t page
   ========================================================= */
$aiVisitsBySource = [];   // all-time per sursă
$aiVisitsBySource7d = []; // last 7 days per sursă
$aiVisitsTotal = 0;
$aiVisitsTotal7d = 0;
$aiVisitsTopPages = [];
$hasAiVisitsLog = file_exists($aiVisitsLog);

if ($hasAiVisitsLog) {
    $handle = fopen($aiVisitsLog, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $parts = explode("\t", trim($line));
            if (count($parts) >= 2) {
                $vts    = (int)$parts[0];
                $source = strtolower(trim($parts[1]));
                $page   = $parts[2] ?? '';
                if ($source === '') continue;

                $aiVisitsBySource[$source] = ($aiVisitsBySource[$source] ?? 0) + 1;
                $aiVisitsTotal++;
                if ($page !== '') {
                    $aiVisitsTopPages[$page] = ($aiVisitsTopPages[$page] ?? 0) + 1;
                }
                if ($vts >= $sevenDaysAgo) {
                    $aiVisitsBySource7d[$source] = ($aiVisitsBySource7d[$source] ?? 0) + 1;
                    $aiVisitsTotal7d++;
                }
            }
        }
        fclose($handle);
    }
    arsort($aiVisitsBySource);
    arsort($aiVisitsTopPages);
    $aiVisitsTopPages = array_slice($aiVisitsTopPages, 0, 5, true);
}

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
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 14px;
      color: #0F172A;
    }
    .funnel-row {
      display: grid;
      grid-template-columns: 48px 1fr 36px;
      gap: 10px;
      align-items: center;
      margin-bottom: 6px;
    }
    .funnel-label { font-size: 11px; color: #64748B; font-weight: 700; }
    .funnel-bar-wrap {
      height: 22px;
      background: #F1F5F9;
      border-radius: 4px;
      overflow: hidden;
    }
    .funnel-count { font-size: 13px; font-weight: 700; text-align: right; }
    .hour-bar {
      display: grid;
      grid-template-columns: repeat(24, 1fr);
      gap: 2px;
      height: 60px;
      align-items: flex-end;
      margin-bottom: 4px;
    }
    .hour-cell { background: #2563EB; border-radius: 2px 2px 0 0; min-height: 2px; }
    .hour-labels {
      display: flex;
      justify-content: space-between;
      font-size: 10px;
      color: #94A3B8;
    }
    .split { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 640px) { .split { grid-template-columns: 1fr; } }
    .type-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid #F1F5F9;
      font-size: 13px;
    }
    .type-row:last-child { border-bottom: none; }
    .type-badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 700;
      margin-left: 6px;
    }
    .badge-handy   { background: #DBEAFE; color: #1E40AF; }
    .badge-fitness { background: #DCFCE7; color: #16A34A; }
    .badge-kfz     { background: #FEF3C7; color: #D97706; }
    .partner-status {
      display: inline-block;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 9px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .status-live    { background: #DCFCE7; color: #16A34A; }
    .status-pending { background: #FEF3C7; color: #D97706; }
    .warn-box {
      background: #FEF3C7;
      border: 1px solid #FDE68A;
      color: #92400E;
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 12px;
      margin-top: 10px;
    }
    .note {
      font-size: 11px;
      color: #94A3B8;
      margin-top: 10px;
      font-style: italic;
    }
    /* Source attribution matrix */
    .source-matrix {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
    }
    .source-matrix th, .source-matrix td {
      padding: 8px 6px;
      text-align: center;
      border-bottom: 1px solid #F1F5F9;
    }
    .source-matrix th {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      color: #64748B;
      letter-spacing: 0.04em;
    }
    .source-matrix td:first-child, .source-matrix th:first-child {
      text-align: left;
      font-weight: 700;
    }
    .source-matrix td.zero { color: #CBD5E1; }
    .source-matrix td.has-clicks { font-weight: 700; }
    .source-matrix tr.total-row {
      background: #F8FAFC;
      font-weight: 900;
    }
    .source-matrix tr.total-row td { border-top: 1px solid #CBD5E1; }
    .source-pill {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      margin-right: 6px;
      margin-bottom: 6px;
    }
    .source-google  { background: #FEE2E2; color: #991B1B; }
    .source-bing    { background: #DBEAFE; color: #1E40AF; }
    .source-yahoo   { background: #EDE9FE; color: #6D28D9; }
    .source-intern  { background: #DCFCE7; color: #15803D; }
    .source-direct  { background: #F1F5F9; color: #475569; }
    .source-other   { background: #FEF3C7; color: #92400E; }
    footer {
      text-align: center;
      margin-top: 30px;
      font-size: 11px;
      color: #94A3B8;
    }
    footer a { color: #64748B; text-decoration: underline; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>KündigungExpress — Stats</h1>
    <p class="subtitle">Live-Daten · Free + Affiliate Modell (seit 14.04.2026)</p>
    <div class="live-badge">
      <span class="live-dot" style="background:<?= $activityColor ?>;"></span>
      <span>Status: <strong><?= $activityStatus ?></strong></span>
      <?php if ($minutesSinceLastPdf !== null): ?>
        <span style="color:#94A3B8;">· letzte PDF vor <?= $minutesSinceLastPdf < 60 ? $minutesSinceLastPdf . ' Min' : floor($minutesSinceLastPdf/60) . 'h' ?></span>
      <?php endif; ?>
    </div>

    <!-- TOP-LEVEL METRICS -->
    <div class="grid">
      <div class="stat-card">
        <div class="stat-label">PDFs gesamt (all-time)</div>
        <div class="stat-value"><?= $pdfCounterAllTime !== null ? number_format($pdfCounterAllTime, 0, ',', '.') : $submissions ?></div>
        <div class="stat-sub"><?= $pdfCounterAllTime !== null ? '_counter.txt · inkl. Tests/Batches' : 'seit 02.02.2026' ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">PDFs (Logging)</div>
        <div class="stat-value"><?= $submissions ?></div>
        <div class="stat-sub">detailliert, seit Log-Update</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Letzte 7 Tage</div>
        <div class="stat-value green"><?= $pdfLast7 ?></div>
        <div class="stat-sub"><?= $avg7d ?>/Tag · ~<?= $projectionMonth ?>/Monat hochgerechnet</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Letzte 24h</div>
        <div class="stat-value blue"><?= $pdfLast24h ?></div>
        <div class="stat-sub">PDFs heute</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Fitness</div>
        <div class="stat-value green"><?= $fitnessSubmissions ?></div>
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
            if (preg_match('/allianz|huk|axa|ergo|devk|generali|versicherung|zurich|hdi|r\+v|lvm|vhv|cosmosdirekt|adac|gothaer|provinzial|signal|verti|wgv|itzehoer|alte-leipziger|nuernberger|wuerttembergische|sparkassen|oeffentliche|neodigital|baloise|barmenia/i', $prov)) {
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
      <h2>Affiliate-Partner — Click-Tracking</h2>
      <p style="font-size:12px;color:#64748B;margin:-6px 0 12px;">Format: <strong>7 Tage</strong> / <span style="color:#94A3B8;">gesamt</span></p>
      <div class="split" style="gap:10px;">
        <div>
          <p style="font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Handy</p>
          <div class="type-row">
            <span>Check24</span>
            <span><strong style="margin-right:4px;"><?= $hasAffiliateLog ? $affiliateClicks7d['check24'] : '—' ?></strong><span style="color:#94A3B8;margin-right:6px;">/ <?= $hasAffiliateLog ? $affiliateClicksAll['check24'] : '—' ?></span><span class="partner-status status-live">live</span></span>
          </div>
          <div class="type-row">
            <span>Tariffuxx</span>
            <span><strong style="margin-right:4px;"><?= $hasAffiliateLog ? $affiliateClicks7d['tariffuxx'] : '—' ?></strong><span style="color:#94A3B8;margin-right:6px;">/ <?= $hasAffiliateLog ? $affiliateClicksAll['tariffuxx'] : '—' ?></span><span class="partner-status status-live">live</span></span>
          </div>
          <div class="type-row">
            <span>AWIN Telekom</span>
            <span><strong style="margin-right:4px;"><?= $hasAffiliateLog ? $affiliateClicks7d['telekom_awin'] : '—' ?></strong><span style="color:#94A3B8;margin-right:6px;">/ <?= $hasAffiliateLog ? $affiliateClicksAll['telekom_awin'] : '—' ?></span><span class="partner-status status-live">live</span></span>
          </div>
          <div class="type-row">
            <span>CRASH 30GB</span>
            <span><strong style="margin-right:4px;"><?= $hasAffiliateLog ? $affiliateClicks7d['crash'] : '—' ?></strong><span style="color:#94A3B8;margin-right:6px;">/ <?= $hasAffiliateLog ? $affiliateClicksAll['crash'] : '—' ?></span><span class="partner-status status-live">40€/Sale</span></span>
          </div>
        </div>
        <div>
          <p style="font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Fitness</p>
          <div class="type-row">
            <span>Amazon (3 Produkte)</span>
            <span><strong style="margin-right:4px;"><?= $hasAffiliateLog ? $affiliateClicks7d['amazon'] : '—' ?></strong><span style="color:#94A3B8;margin-right:6px;">/ <?= $hasAffiliateLog ? $affiliateClicksAll['amazon'] : '—' ?></span><span class="partner-status status-live">live</span></span>
          </div>
          <p style="font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;margin:14px 0 6px;">KFZ</p>
          <div class="type-row">
            <span>Tarifcheck</span>
            <span><strong style="margin-right:4px;"><?= $hasAffiliateLog ? $affiliateClicks7d['tarifcheck'] : '—' ?></strong><span style="color:#94A3B8;margin-right:6px;">/ <?= $hasAffiliateLog ? $affiliateClicksAll['tarifcheck'] : '—' ?></span><span class="partner-status status-live">70€/Sale</span></span>
          </div>
        </div>
      </div>
      <?php if ($hasAffiliateLog): ?>
        <p style="font-size:12px;color:#64748B;margin-top:12px;">Gesamt: <strong><?= $totalAffiliateClicks7d ?></strong> Klicks (7 Tage) · <strong><?= $totalAffiliateClicksAll ?></strong> Klicks (gesamt)</p>
      <?php endif; ?>
      <?php if (!$hasAffiliateLog): ?>
        <div class="warn-box">
          ⚠ Click-Tracking nicht aktiv. Affiliate-Snippet auf den Seiten implementieren, um Live-CTR-Daten zu erhalten.
        </div>
      <?php endif; ?>
    </div>

    <!-- AI-VISITS -->
    <div class="section">
      <h2>KI-Besuche — Traffic von KI-Assistenten</h2>
      <p style="font-size:12px;color:#64748B;margin:-6px 0 12px;">Besuche von ChatGPT, Claude, Copilot, Perplexity &amp; Co. (via Referrer/UTM erkannt)</p>
      <?php if ($hasAiVisitsLog && $aiVisitsTotal > 0): ?>
        <div class="split" style="gap:10px;">
          <div>
            <p style="font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Nach Quelle (gesamt)</p>
            <?php
            $aiSourceLabels = [
              'chatgpt' => 'ChatGPT', 'claude' => 'Claude', 'copilot' => 'Copilot',
              'bing' => 'Bing/Copilot', 'perplexity' => 'Perplexity', 'gemini' => 'Gemini',
              'bard' => 'Gemini (Bard)', 'you' => 'You.com', 'phind' => 'Phind'
            ];
            foreach ($aiVisitsBySource as $src => $cnt):
              $label = $aiSourceLabels[$src] ?? ucfirst($src);
              $cnt7d = $aiVisitsBySource7d[$src] ?? 0;
            ?>
              <div class="type-row">
                <span><?= htmlspecialchars($label) ?></span>
                <span><strong style="margin-right:4px;"><?= $cnt7d ?></strong><span style="color:#94A3B8;margin-right:6px;">/ <?= $cnt ?></span><span class="partner-status status-live">AI</span></span>
              </div>
            <?php endforeach; ?>
            <p style="font-size:12px;color:#64748B;margin-top:10px;">Gesamt: <strong><?= $aiVisitsTotal7d ?></strong> (7 Tage) · <strong><?= $aiVisitsTotal ?></strong> (gesamt)</p>
          </div>
          <div>
            <p style="font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Top-Seiten (KI-Besuche)</p>
            <?php foreach ($aiVisitsTopPages as $pg => $cnt): ?>
              <div class="type-row">
                <span style="font-size:12px;word-break:break-all;"><?= htmlspecialchars($pg) ?></span>
                <span><strong><?= $cnt ?></strong></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <p style="font-size:11px;color:#94A3B8;margin-top:10px;">Hinweis: Claude.ai (rel=noreferrer) und ChatGPT Atlas bleiben teils unsichtbar — echte KI-Sichtbarkeit liegt höher.</p>
      <?php else: ?>
        <div class="warn-box">
          Noch keine KI-Besuche erfasst (oder <code>_data/ai_visits.log</code> fehlt). Sobald KI-Assistenten auf Seiten verlinken, erscheinen sie hier.
        </div>
      <?php endif; ?>
    </div>

    <!-- SOURCE ATTRIBUTION -->
    <?php if ($hasAffiliateLog && $totalAffiliateClicks7d > 0): ?>
    <div class="section">
      <h2>Cross-Source Attribution — Affiliate Clicks nach Herkunft (7 Tage)</h2>

      <!-- Summary pills -->
      <div style="margin-bottom:16px;">
        <?php
        $sourceLabels = ['google' => 'Google', 'bing' => 'Bing', 'yahoo' => 'Yahoo', 'intern' => 'Intern', 'direct' => 'Direkt', 'other' => 'Andere'];
        foreach ($sourceLabels as $src => $label):
          if ($sourceClicks7d[$src] === 0) continue;
          $pct = $totalAffiliateClicks7d > 0 ? round(($sourceClicks7d[$src] / $totalAffiliateClicks7d) * 100) : 0;
        ?>
          <span class="source-pill source-<?= $src ?>"><?= $label ?>: <?= $sourceClicks7d[$src] ?> (<?= $pct ?>%)</span>
        <?php endforeach; ?>
      </div>

      <!-- Matrix table -->
      <div style="overflow-x:auto;">
        <table class="source-matrix">
          <thead>
            <tr>
              <th>Partner</th>
              <th>Google</th>
              <th>Bing</th>
              <th>Yahoo</th>
              <th>Intern</th>
              <th>Direkt</th>
              <th>Andere</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $partnerLabels = [
              'tariffuxx'     => 'Tariffuxx',
              'check24'       => 'Check24',
              'telekom_awin'  => 'AWIN Telekom',
              'crash'         => 'CRASH',
              'amazon'        => 'Amazon',
              'tarifcheck'    => 'Tarifcheck',
            ];
            foreach ($partnerLabels as $partner => $plabel):
              $row = $partnerSourceMatrix[$partner];
              $rowTotal = array_sum($row);
              if ($rowTotal === 0) continue; // Skip partners cu 0 clicks
            ?>
              <tr>
                <td><?= $plabel ?></td>
                <td class="<?= $row['google'] === 0 ? 'zero' : 'has-clicks' ?>"><?= $row['google'] ?: '·' ?></td>
                <td class="<?= $row['bing'] === 0 ? 'zero' : 'has-clicks' ?>"><?= $row['bing'] ?: '·' ?></td>
                <td class="<?= $row['yahoo'] === 0 ? 'zero' : 'has-clicks' ?>"><?= $row['yahoo'] ?: '·' ?></td>
                <td class="<?= $row['intern'] === 0 ? 'zero' : 'has-clicks' ?>"><?= $row['intern'] ?: '·' ?></td>
                <td class="<?= $row['direct'] === 0 ? 'zero' : 'has-clicks' ?>"><?= $row['direct'] ?: '·' ?></td>
                <td class="<?= $row['other'] === 0 ? 'zero' : 'has-clicks' ?>"><?= $row['other'] ?: '·' ?></td>
                <td><strong><?= $rowTotal ?></strong></td>
              </tr>
            <?php endforeach; ?>
            <tr class="total-row">
              <td>TOTAL</td>
              <td><?= $sourceClicks7d['google'] ?: '·' ?></td>
              <td><?= $sourceClicks7d['bing'] ?: '·' ?></td>
              <td><?= $sourceClicks7d['yahoo'] ?: '·' ?></td>
              <td><?= $sourceClicks7d['intern'] ?: '·' ?></td>
              <td><?= $sourceClicks7d['direct'] ?: '·' ?></td>
              <td><?= $sourceClicks7d['other'] ?: '·' ?></td>
              <td><strong><?= $totalAffiliateClicks7d ?></strong></td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="note">Zeigt, welche Suchmaschine welchen Partner antreibt. Google war bisher 30% des Traffic-Mix, Bing 26%, Yahoo 11%, Direkt/Intern 33%.</p>
    </div>
    <?php elseif ($hasAffiliateLog && $totalAffiliateClicks7d === 0): ?>
    <div class="section">
      <h2>Cross-Source Attribution — Affiliate Clicks nach Herkunft (7 Tage)</h2>
      <p style="color:#64748B;font-size:13px;">Noch keine Affiliate-Clicks in den letzten 7 Tagen. Daten erscheinen automatisch sobald Klicks eintreffen.</p>
    </div>
    <?php endif; ?>

    <!-- SITE OVERVIEW -->
    <div class="section">
      <h2>Seitenübersicht & Conversion pro Kategorie</h2>
      <div class="split">
        <div>
          <div class="type-row">
            <span><span class="type-badge badge-handy">Handy</span> Seiten</span>
            <strong>40</strong>
          </div>
          <div class="type-row">
            <span><span class="type-badge badge-fitness">Fitness</span> Seiten</span>
            <strong>27</strong>
          </div>
          <div class="type-row">
            <span><span class="type-badge badge-kfz">KFZ</span> Seiten</span>
            <strong>36</strong>
          </div>
          <div class="type-row" style="color:#94A3B8;">
            <span>Hub / Info / Static</span>
            <strong>25</strong>
          </div>
          <div class="type-row" style="border-top:1px solid #E2E8F0;padding-top:10px;margin-top:4px;">
            <span><strong>Sitemap gesamt</strong></span>
            <strong>128</strong>
          </div>
        </div>
        <div>
          <p style="font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;margin-bottom:6px;">PDF-Yield pro Seite</p>
          <?php
          $sitePerType = ['handy' => 40, 'fitness' => 27, 'kfz' => 36];
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

    <!-- VERSAND-SERVICE (Stripe) -->
    <div class="section">
      <h2>Versand-Service — Bezahlte Bestellungen</h2>
      <div class="split" style="gap:10px;">
        <div>
          <p style="font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Bestellungen nach Tarif</p>
          <div class="type-row">
            <span>Standardbrief · 3,99 €</span>
            <span><strong style="margin-right:4px;"><?= $versandStandard7d ?></strong><span style="color:#94A3B8;margin-right:6px;">/ <?= $versandStandard ?></span></span>
          </div>
          <div class="type-row">
            <span>Einwurfeinschreiben · 8,99 €</span>
            <span><strong style="margin-right:4px;"><?= $versandEinschreiben7d ?></strong><span style="color:#94A3B8;margin-right:6px;">/ <?= $versandEinschreiben ?></span></span>
          </div>
          <div class="type-row" style="border-bottom:none;">
            <span>Bestellungen gesamt</span>
            <strong><?= $purchases ?></strong>
          </div>
          <p style="font-size:11px;color:#64748B;margin-top:8px;">Format: <strong>7 Tage</strong> / <span style="color:#94A3B8;">gesamt</span></p>
        </div>
        <div>
          <p style="font-size:11px;color:#64748B;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Umsatz</p>
          <div class="type-row">
            <span>Umsatz 7 Tage</span>
            <strong>€<?= number_format($revenue7d, 2, ',', '.') ?></strong>
          </div>
          <div class="type-row">
            <span>Umsatz gesamt</span>
            <strong>€<?= number_format($revenue, 2, ',', '.') ?></strong>
          </div>
          <div class="type-row">
            <span>Ø Bestellwert</span>
            <strong>€<?= number_format($avgOrderValue, 2, ',', '.') ?></strong>
          </div>
          <div class="type-row" style="border-bottom:none;">
            <span>Letzte Bestellung</span>
            <strong style="font-size:12px;"><?= $lastPurchase ?></strong>
          </div>
        </div>
      </div>
      <?php if ($testCount > 0): ?>
      <p class="note" style="background:#FFFBEB;border-color:#FDE68A;color:#92400E;">
        <strong><?= $testCount ?></strong> Test-Bestellungen ausgeschlossen
        (€<?= number_format($testRevenueCents/100, 2, ',', '.') ?>, deine eigene E-Mail / Stripe-Test).
        Oben stehen nur <strong>echte Kunden-Bestellungen</strong>.
      </p>
      <?php endif; ?>
      <p class="note">Echte Kunden-Werte aus <code>_orders/*.json</code> (gefiltert: ohne <code><?= htmlspecialchars($TEST_EMAIL) ?></code> / Test-Mode). Free PDF + Affiliate bleibt das Kernmodell; Versand ist optionaler Zusatzumsatz.</p>
    </div>

  </div>
  <footer>
    KündigungExpress · Internes Dashboard ·
    <a href="">Aktualisieren</a>
  </footer>
</body>
</html>
