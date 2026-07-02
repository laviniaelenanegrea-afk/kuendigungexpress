<?php
/**
 * event.php
 * Beacon endpoint pentru tracking click-uri affiliate + AI referral traffic.
 *
 * MODURI:
 * 1. GET  cu 'go=partner&u=<url>'  → REDIRECT server-side affiliate (adblock-proof) [NOU]
 * 2. GET  cu 'ai_visit=1'          → log AI referral visit (server-side)
 * 3. POST cu 'partner'             → tracking affiliate click (legacy, navigator.sendBeacon)
 *
 * Toate scriu în:
 *   _data/affiliate_clicks.log  — affiliate clicks  (ts \t partner \t page \t referrer)
 *   _data/ai_visits.log         — AI referral visits (ts \t source \t page)
 */
declare(strict_types=1);

/* ============================================================
   HELPERS comune
   ============================================================ */
$ALLOWED_PARTNERS = ['check24', 'tariffuxx', 'telekom_awin', 'plankpad_awin', 'tarifcheck', 'amazon', 'crash', 'volders'];

/**
 * Whitelist hosturi destinație pentru redirect (anti open-redirect).
 * FAIL-CLOSED: dacă hostul destinației nu e aici, NU se face redirect (400).
 *
 * ⚠️ VERIFICĂ/COMPLETEAZĂ cu hosturile reale din linkurile tale affiliate.
 * Match: exact SAU subdomeniu (ex. 'awin1.com' acceptă și 'www.awin1.com').
 */
$ALLOWED_DEST_HOSTS = [
    'check24.de',
    'check24.net',
    'tariffuxx.de',
    'awin1.com',                 // AWIN (telekom_awin, volders) — linkuri cread.php
    'partner-versicherung.de',   // tarifcheck (a.partner-versicherung.de)
    'tarifcheck.de',             // tarifcheck (variantă)
    'crash-tarife.de',           // crash
    'amazon.de',                 // (Amazon rămâne link DIRECT — nu se rutează prin redirect)
    'amzn.to',
];

function host_allowed(string $host, array $whitelist): bool {
    $host = strtolower($host);
    foreach ($whitelist as $base) {
        $base = strtolower($base);
        if ($host === $base) return true;
        // subdomeniu: trebuie să se termine cu '.base'
        $suffix = '.' . $base;
        if (strlen($host) > strlen($suffix) && substr($host, -strlen($suffix)) === $suffix) {
            return true;
        }
    }
    return false;
}

function client_page(): string {
    if (empty($_REQUEST['page'])) return '';
    $p = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', (string)$_REQUEST['page']);
    return substr((string)$p, 0, 100);
}

function client_referrer(): string {
    if (empty($_SERVER['HTTP_REFERER'])) return 'direct';
    $host = parse_url((string)$_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if (!$host) return 'direct';
    $host = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $host);
    $host = substr((string)$host, 0, 60);
    return $host !== '' ? $host : 'direct';
}

function log_append(string $file, string $line): void {
    $dir = __DIR__ . '/_data';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

/* ============================================================
   MODE 1: REDIRECT affiliate server-side (GET ?go=partner&u=<url>)
   — adblock-proof: navigare first-party, log în PHP, apoi 302.
   ============================================================ */
if (isset($_GET['go'])) {
    $partner = strtolower(preg_replace('/[^a-z0-9_]/', '', (string)$_GET['go']));

    if (!in_array($partner, $ALLOWED_PARTNERS, true)) {
        http_response_code(400);
        exit('invalid partner');
    }

    $dest = trim((string)($_GET['u'] ?? ''));
    // elimină CR/LF (anti header-injection) și whitespace
    $dest = str_replace(["\r", "\n", "\t", ' '], ['', '', '', '%20'], $dest);

    $scheme = strtolower((string)parse_url($dest, PHP_URL_SCHEME));
    $host   = (string)parse_url($dest, PHP_URL_HOST);

    $valid =
        $dest !== '' &&
        in_array($scheme, ['http', 'https'], true) &&
        $host !== '' &&
        host_allowed($host, $ALLOWED_DEST_HOSTS) &&
        filter_var($dest, FILTER_VALIDATE_URL) !== false;

    if (!$valid) {
        // log respins pentru debugging (host necunoscut etc.), fără să redirecteze
        log_append(__DIR__ . '/_data/affiliate_rejected.log',
            time() . "\t" . $partner . "\t" . substr($host, 0, 60) . "\t" . substr($dest, 0, 120) . "\n");
        http_response_code(400);
        exit('invalid destination');
    }

    // log click reușit (același format ca legacy — dashboard/_ke-stats rămân compatibile)
    $line = time() . "\t" . $partner . "\t" . client_page() . "\t" . client_referrer() . "\n";
    log_append(__DIR__ . '/_data/affiliate_clicks.log', $line);

    header('Referrer-Policy: no-referrer-when-downgrade');
    header('Cache-Control: no-store');
    header('Location: ' . $dest, true, 302);
    exit;
}

/* ============================================================
   MODE 2: AI Visit Logging (GET ?ai_visit=1)
   ============================================================ */
if (isset($_GET['ai_visit']) && $_GET['ai_visit'] === '1') {
    $aiSource = '';
    $aiPage = '';

    if (!empty($_SERVER['HTTP_REFERER'])) {
        $refHost = strtolower((string)(parse_url((string)$_SERVER['HTTP_REFERER'], PHP_URL_HOST) ?? ''));
        $aiMap = [
            'chatgpt.com' => 'chatgpt',
            'chat.openai.com' => 'chatgpt',
            'claude.ai' => 'claude',
            'copilot.microsoft.com' => 'copilot',
            'copilot.com' => 'copilot',
            'perplexity.ai' => 'perplexity',
            'www.perplexity.ai' => 'perplexity',
            'gemini.google.com' => 'gemini',
            'bard.google.com' => 'gemini',
            'you.com' => 'youcom',
            'duckduckgo.com' => 'ddg-ai',
            'phind.com' => 'phind',
        ];
        foreach ($aiMap as $host => $source) {
            if (strpos($refHost, $host) !== false) { $aiSource = $source; break; }
        }
    }

    if (empty($aiSource) && !empty($_GET['utm_source'])) {
        $utm = strtolower(preg_replace('/[^a-z0-9\.\-]/', '', (string)$_GET['utm_source']));
        $utmMap = [
            'chatgpt' => 'chatgpt', 'chatgpt.com' => 'chatgpt',
            'claude' => 'claude', 'claude.ai' => 'claude',
            'copilot' => 'copilot', 'copilot.com' => 'copilot',
            'perplexity' => 'perplexity', 'gemini' => 'gemini',
        ];
        if (isset($utmMap[$utm])) $aiSource = $utmMap[$utm];
    }

    if (empty($aiSource)) { http_response_code(204); exit; }

    if (!empty($_GET['page'])) {
        $aiPage = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', (string)$_GET['page']);
        $aiPage = substr((string)$aiPage, 0, 100);
    }

    log_append(__DIR__ . '/_data/ai_visits.log', time() . "\t" . $aiSource . "\t" . $aiPage . "\n");
    http_response_code(204);
    exit;
}

/* ============================================================
   MODE 3: Affiliate click tracking (POST) — legacy sendBeacon
   ============================================================ */
$partner = $_POST['partner'] ?? '';
if (!in_array($partner, $ALLOWED_PARTNERS, true)) {
    http_response_code(400);
    exit('invalid partner');
}

$line = time() . "\t" . $partner . "\t" . client_page() . "\t" . client_referrer() . "\n";
log_append(__DIR__ . '/_data/affiliate_clicks.log', $line);

http_response_code(204);
exit;
