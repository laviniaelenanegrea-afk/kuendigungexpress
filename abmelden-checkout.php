<?php
declare(strict_types=1);

/* =========================================================
   ABMELDEN CHECKOUT — endpoint declanșat la submit Abmelde-Auftrag
   Oglindit pe stripe-checkout.php (Versand). Fără signature, fără
   studio-address. Salvează _data/{token}.json (inkl. Sicherheitscodes)
   și creează Stripe Checkout Session (Pauschalpreis 'abmeldung').
   ========================================================= */

ignore_user_abort(true);
set_time_limit(30);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/_mail_log/php_errors.log');
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Methode nicht erlaubt.';
    exit;
}

/* ===== CSRF PROTECTION — Sec-Fetch-Site (identisch zu stripe-checkout.php) ===== */
$fetchSite = (string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
$csrfOk = false;
if ($fetchSite !== '') {
    $csrfOk = in_array($fetchSite, ['same-origin', 'same-site'], true);
} else {
    $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
    if ($referer !== '') {
        $refHost = parse_url($referer, PHP_URL_HOST);
        $ownHost = $_SERVER['HTTP_HOST'] ?? '';
        $csrfOk = ($refHost !== null) &&
                  (strcasecmp($refHost, $ownHost) === 0 ||
                   strcasecmp($refHost, 'www.' . $ownHost) === 0 ||
                   strcasecmp('www.' . $refHost, $ownHost) === 0);
    }
}
if (!$csrfOk) {
    error_log('[abmelden-checkout] CSRF block — Sec-Fetch-Site: "' . $fetchSite . '"');
    http_response_code(403);
    echo 'Anfrage abgelehnt (Cross-Origin nicht erlaubt).';
    exit;
}

require_once __DIR__ . '/stripe-php/init.php';
$config = require __DIR__ . '/_stripe_config.php';

$mode = $config['mode'] ?? 'test';
$stripeKey = $config[$mode]['secret_key'] ?? '';
if ($stripeKey === '' || strpos($stripeKey, 'sk_') !== 0) {
    error_log('[abmelden-checkout] Secret key fehlt/ungültig — mode: ' . $mode);
    http_response_code(500);
    echo 'Zahlungssystem nicht konfiguriert. Bitte später erneut versuchen.';
    exit;
}
\Stripe\Stripe::setApiKey($stripeKey);

/* ===== INPUT VALIDATION (server-side, spiegelt das Frontend) ===== */

// Einwilligungen — Pflicht (§356 Abs. 4 BGB + Halter/Vollmacht). Anti-tampering.
$consentExec   = (string)($_POST['consent_exec'] ?? '0');
$consentHalter = (string)($_POST['consent_halter'] ?? '0');
if ($consentExec !== '1' || $consentHalter !== '1') {
    http_response_code(400);
    echo 'Bitte bestätigen Sie die sofortige Ausführung und die Halter-/Vollmachtserklärung.';
    exit;
}

// FIN: 17 Zeichen, ohne I/O/Q
$fin = strtoupper(preg_replace('/\s+/', '', (string)($_POST['fin'] ?? '')));
if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $fin)) {
    http_response_code(400);
    echo 'Ungültige Fahrzeugidentifizierungsnummer (FIN).';
    exit;
}

// Sicherheitscode ZB Teil I: 7 Zeichen
$zbCode = trim((string)($_POST['zb_code'] ?? ''));
if (mb_strlen($zbCode) !== 7) {
    http_response_code(400);
    echo 'Ungültiger Sicherheitscode der Zulassungsbescheinigung Teil I.';
    exit;
}

// Kennzeichen: 2–5 Buchstaben + 1–4 Ziffern (+E/H)
$plateRaw  = trim((string)($_POST['plate'] ?? ''));
$plateNorm = strtoupper(preg_replace('/[\s\-]+/', '', $plateRaw));
if (!preg_match('/^[A-ZÄÖÜ]{2,5}[0-9]{1,4}[EH]?$/u', $plateNorm)) {
    http_response_code(400);
    echo 'Ungültiges Kennzeichen.';
    exit;
}

// Sicherheitscode hinten: 3 Zeichen
$codeBack = trim((string)($_POST['code_back'] ?? ''));
if (mb_strlen($codeBack) !== 3) {
    http_response_code(400);
    echo 'Ungültiger Sicherheitscode (hinten).';
    exit;
}

// Vorderes Kennzeichen + Code (optional)
$frontExists = isset($_POST['front_exists']);
$codeFront   = trim((string)($_POST['code_front'] ?? ''));
if ($frontExists && $codeFront !== '' && mb_strlen($codeFront) !== 3) {
    http_response_code(400);
    echo 'Ungültiger Sicherheitscode (vorn).';
    exit;
}
if (!$frontExists) { $codeFront = ''; }

// Kennzeichenart + Verwertungsnachweis
$plateType = (string)($_POST['plate_type'] ?? 'zivil');
if (!in_array($plateType, ['zivil', 'e', 'oldtimer'], true)) { $plateType = 'zivil'; }
$verwertung = (string)($_POST['verwertung'] ?? 'kein');
if (!in_array($verwertung, ['kein', 'lag_vor', 'nicht_abfall', 'ausland'], true)) { $verwertung = 'kein'; }

// Kontakt
$firstName = trim((string)($_POST['firstName'] ?? ''));
$lastName  = trim((string)($_POST['lastName'] ?? ''));
if ($firstName === '' || $lastName === '') {
    http_response_code(400);
    echo 'Bitte geben Sie Vor- und Nachnamen an.';
    exit;
}
$emailRaw = trim((string)($_POST['email'] ?? ''));
if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo 'Ungültige E-Mail-Adresse. Bitte geben Sie eine korrekte Adresse für die Bestätigung ein.';
    exit;
}

$amount = (int)($config['prices']['abmeldung'] ?? 0);
if ($amount <= 0) {
    http_response_code(500);
    echo 'Preiskonfiguration fehlt.';
    exit;
}

/* ===== SAVE _data/{token}.json ===== */
$token      = bin2hex(random_bytes(16)); // 32 hex
$erledigtKey = bin2hex(random_bytes(8));  // secret pentru linkul "Als erledigt markieren" (purge coduri)

$dataDir = __DIR__ . '/_data';
if (!is_dir($dataDir)) { mkdir($dataDir, 0755, true); }

$now = time();
$formData = [
    'type'             => 'abmeldung',
    'fin'              => $fin,
    'zb_code'          => $zbCode,
    'plate'            => $plateNorm,
    'plate_display'    => $plateRaw,
    'code_back'        => $codeBack,
    'code_front'       => $codeFront,
    'front_exists'     => $frontExists ? '1' : '0',
    'plate_type'       => $plateType,
    'verwertung'       => $verwertung,
    'firstName'        => $firstName,
    'lastName'         => $lastName,
    'email'            => $emailRaw,
    'street'           => trim((string)($_POST['street'] ?? '')),
    'zip'              => trim((string)($_POST['zip'] ?? '')),
    'city'             => trim((string)($_POST['city'] ?? '')),
    'amount_cents'     => $amount,
    'erledigtKey'      => $erledigtKey,
    'consentExecAt'    => $now,
    'consentHalterAt'  => $now,
    'consentExecText'  => 'Ich verlange ausdrücklich, dass KündigungExpress mit der Abmeldung sofort nach Zahlung beginnt. Mir ist bekannt, dass ich mein 14-tägiges Widerrufsrecht verliere, sobald die Abmeldung vollständig durchgeführt ist (§ 356 Abs. 4 BGB).',
    'consentHalterText'=> 'Ich versichere, Halter des Fahrzeugs zu sein oder vom Halter zur Abmeldung bevollmächtigt zu sein.',
    'ipAddress'        => $_SERVER['REMOTE_ADDR'] ?? '',
    'userAgent'        => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200),
    'createdAt'        => $now,
];

$dataFile = $dataDir . '/' . $token . '.json';
if (file_put_contents($dataFile, json_encode($formData, JSON_UNESCAPED_UNICODE)) === false) {
    error_log('[abmelden-checkout] Failed to write data file: ' . $dataFile);
    http_response_code(500);
    echo 'Daten konnten nicht gespeichert werden.';
    exit;
}

/* ===== STRIPE CHECKOUT SESSION ===== */
$siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
         . ($_SERVER['HTTP_HOST'] ?? 'kuendigungexpress.de');
$siteUrl = rtrim($siteUrl, '/');

try {
    $sessionParams = [
        'mode' => 'payment',
        'line_items' => [[
            'price_data' => [
                'currency'     => 'eur',
                'product_data' => [
                    'name'        => (string)($config['tier_labels']['abmeldung'] ?? 'Kfz-Abmeldung'),
                    'description' => sprintf(
                        'Wir übermitteln die Außerbetriebsetzung Ihres Fahrzeugs (Kennzeichen %s) an das KBA bzw. die Zulassungsstelle. Pauschalpreis inkl. aller Behördengebühren.',
                        substr($plateRaw !== '' ? $plateRaw : $plateNorm, 0, 20)
                    ),
                ],
                'unit_amount' => $amount,
            ],
            'quantity' => 1,
        ]],
        'success_url' => $siteUrl . '/abmelden-success.php?session_id={CHECKOUT_SESSION_ID}&t=' . urlencode($token),
        'cancel_url'  => $siteUrl . '/abmelden-auftrag.html?cancelled=1',
        'metadata'    => [
            'token'        => $token,
            'contractType' => 'abmeldung',
            'plate'        => substr($plateNorm, 0, 20),
        ],
        'locale' => 'de',
        'customer_email' => $emailRaw,
    ];

    $session = \Stripe\Checkout\Session::create($sessionParams);

    header('Location: ' . $session->url, true, 303);
    exit;

} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log('[abmelden-checkout] Stripe API error: ' . $e->getMessage());
    @unlink($dataFile);
    http_response_code(500);
    echo 'Zahlungssystem-Fehler: ' . htmlspecialchars($e->getMessage());
    exit;
} catch (\Throwable $e) {
    error_log('[abmelden-checkout] Generic error: ' . $e->getMessage());
    @unlink($dataFile);
    http_response_code(500);
    echo 'Unerwarteter Fehler. Bitte später erneut versuchen.';
    exit;
}
