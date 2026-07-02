<?php
declare(strict_types=1);

/* =========================================================
   STRIPE CHECKOUT — endpoint declanșat la submit Versand
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

/* =========================================================
   CSRF PROTECTION — Sec-Fetch-Site
   ========================================================= */
// Browser-ele moderne (Chrome 76+, Firefox 90+, Safari 16+) trimit acest header
// automat, indicând originea request-ului. Acceptăm doar same-origin/same-site.
// Pentru browsere foarte vechi (sub <2% trafic), header e absent → fallback la
// Referer check ca defense in depth.
$fetchSite = (string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
$fetchMode = (string)($_SERVER['HTTP_SEC_FETCH_MODE'] ?? '');

$csrfOk = false;
if ($fetchSite !== '') {
    // Browser modern — trust the header
    $csrfOk = in_array($fetchSite, ['same-origin', 'same-site'], true);
} else {
    // Browser fără Sec-Fetch — verifică Referer
    $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
    if ($referer !== '') {
        $refHost = parse_url($referer, PHP_URL_HOST);
        $ownHost = $_SERVER['HTTP_HOST'] ?? '';
        // Acceptăm www. și non-www
        $csrfOk = ($refHost !== null) &&
                  (strcasecmp($refHost, $ownHost) === 0 ||
                   strcasecmp($refHost, 'www.' . $ownHost) === 0 ||
                   strcasecmp('www.' . $refHost, $ownHost) === 0);
    }
}

if (!$csrfOk) {
    error_log('[stripe-checkout] CSRF block — Sec-Fetch-Site: "' . $fetchSite
            . '", Referer: "' . ($_SERVER['HTTP_REFERER'] ?? '(none)') . '"');
    http_response_code(403);
    echo 'Anfrage abgelehnt (Cross-Origin nicht erlaubt).';
    exit;
}

require_once __DIR__ . '/stripe-php/init.php';
$config = require __DIR__ . '/_stripe_config.php';

/* =========================================================
   CONFIG + STRIPE INIT
   ========================================================= */

$mode = $config['mode'] ?? 'test';
$stripeKey = $config[$mode]['secret_key'] ?? '';

if ($stripeKey === '' || strpos($stripeKey, 'sk_') !== 0) {
    error_log('[stripe-checkout] Secret key fehlt oder ungültig — mode: ' . $mode);
    http_response_code(500);
    echo 'Zahlungssystem nicht konfiguriert. Bitte später erneut versuchen.';
    exit;
}

\Stripe\Stripe::setApiKey($stripeKey);

/* =========================================================
   INPUT VALIDATION
   ========================================================= */

// Tier — controlează prețul SERVER-SIDE (security: nu folosim amount din POST)
$tier = (string)($_POST['versandTier'] ?? '');
if (!in_array($tier, ['standard', 'einschreiben'], true)) {
    http_response_code(400);
    echo 'Ungültiger Versand-Tarif.';
    exit;
}

// AGB + Widerrufsverzicht — obligatoriu pentru contract valid (§356 Abs. 4 BGB)
// Frontend forțează checkbox-urile, dar validăm și aici (defense in depth, anti-tampering)
$agbAccepted    = (string)($_POST['agbAccepted'] ?? '0');
$widerrufWaiver = (string)($_POST['widerrufWaiver'] ?? '0');
if ($agbAccepted !== '1' || $widerrufWaiver !== '1') {
    http_response_code(400);
    echo 'AGB und Widerrufsverzicht müssen bestätigt werden, bevor die Bestellung abgeschlossen werden kann.';
    exit;
}

$amount = (int)($config['prices'][$tier] ?? 0);
if ($amount <= 0) {
    http_response_code(500);
    echo 'Preiskonfiguration fehlt.';
    exit;
}

$tierLabel = (string)($config['tier_labels'][$tier] ?? 'Versand-Service');

// Signature — base64 PNG data URL, max 250KB
$signature = (string)($_POST['signature'] ?? '');
if ($signature === '' || !preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $signature)) {
    http_response_code(400);
    echo 'Unterschrift fehlt oder ungültig. Bitte erneut unterschreiben.';
    exit;
}
if (strlen($signature) > 250000) {
    http_response_code(400);
    echo 'Unterschrift zu groß.';
    exit;
}

// Form data — required fields
$requiredFields = ['type', 'firstName', 'lastName', 'street', 'zip', 'city', 'anbieter', 'studioStreet', 'studioZip', 'studioCity', 'email'];
foreach ($requiredFields as $field) {
    $val = trim((string)($_POST[$field] ?? ''));
    if ($val === '') {
        http_response_code(400);
        echo 'Pflichtfeld fehlt: ' . htmlspecialchars($field);
        exit;
    }
}

// Email — validare strictă format (e cheia pentru confirmare + Sendungsnachweis)
$emailRaw = trim((string)$_POST['email']);
if (!filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo 'Ungültige E-Mail-Adresse. Bitte geben Sie eine korrekte Adresse für die Bestellbestätigung ein.';
    exit;
}

$type = (string)$_POST['type'];
if (!in_array($type, ['handy', 'kfz', 'fitness'], true)) {
    http_response_code(400);
    echo 'Ungültiger Vertragstyp.';
    exit;
}

/* =========================================================
   SAVE FORM DATA + SIGNATURE în /_data/{token}.json
   Token = key random 32 chars, identifică sesiunea
   La versand-success.php (după plată) îl folosim ca să găsim datele
   ========================================================= */

$token = bin2hex(random_bytes(16)); // 32 chars hex

// pdfToken — separat de session token, folosit DOAR pentru filename PDF
// Previne ghicirea URL-urilor PDF din session ID-uri Stripe (PII protection)
$pdfToken = bin2hex(random_bytes(16)); // 32 chars hex, 128 bits entropy

$dataDir = __DIR__ . '/_data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$formData = [
    'type'              => $type,
    'firstName'         => (string)$_POST['firstName'],
    'lastName'          => (string)$_POST['lastName'],
    'street'            => (string)$_POST['street'],
    'zip'               => (string)$_POST['zip'],
    'city'              => (string)$_POST['city'],
    'email'             => (string)($_POST['email'] ?? ''),
    'anbieter'          => (string)$_POST['anbieter'],
    'studio'            => (string)$_POST['anbieter'], // alias pentru compatibility cu success_final.php
    'studioStreet'      => (string)$_POST['studioStreet'],
    'studioZip'         => (string)$_POST['studioZip'],
    'studioCity'        => (string)$_POST['studioCity'],
    'terminationMode'   => (string)($_POST['terminationMode'] ?? 'next_possible'),
    'terminationDate'   => (string)($_POST['terminationDate'] ?? ''),
    'contractNo'        => (string)($_POST['contractNo'] ?? ''),
    'plate'             => (string)($_POST['plate'] ?? ''),
    'providerEmail'     => (string)($_POST['providerEmail'] ?? ''),
    'sendEmail'         => (string)($_POST['sendEmail'] ?? '0'),
    'trustpilotConsent' => (string)($_POST['trustpilotConsent'] ?? '0'),
    'signature'         => $signature,
    'versandTier'       => $tier,
    'amount_cents'      => $amount,
    'pdfToken'          => $pdfToken,
    'agbAcceptedAt'     => time(),
    'widerrufWaiverAt'  => time(),
    'ipAddress'         => $_SERVER['REMOTE_ADDR'] ?? '',
    'userAgent'         => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200),
    'createdAt'         => time(),
];

$dataFile = $dataDir . '/' . $token . '.json';
if (file_put_contents($dataFile, json_encode($formData, JSON_UNESCAPED_UNICODE)) === false) {
    error_log('[stripe-checkout] Failed to write data file: ' . $dataFile);
    http_response_code(500);
    echo 'Daten konnten nicht gespeichert werden.';
    exit;
}

/* =========================================================
   STRIPE CHECKOUT SESSION
   ========================================================= */

$siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
         . ($_SERVER['HTTP_HOST'] ?? 'kuendigungexpress.de');
$siteUrl = rtrim($siteUrl, '/');

$customerEmail = trim((string)($formData['email'] ?? ''));
$customerEmail = filter_var($customerEmail, FILTER_VALIDATE_EMAIL) ? $customerEmail : null;

try {
    $sessionParams = [
        'mode'                  => 'payment',
        'payment_method_types'  => ['card'],
        'line_items'            => [[
            'price_data' => [
                'currency'     => 'eur',
                'product_data' => [
                    'name'        => $tierLabel,
                    'description' => 'Kündigung an ' . substr((string)$formData['anbieter'], 0, 100) . ' — wird innerhalb 1-2 Werktagen versendet',
                ],
                'unit_amount'  => $amount,
            ],
            'quantity' => 1,
        ]],
        'success_url' => $siteUrl . '/versand-success.php?session_id={CHECKOUT_SESSION_ID}&t=' . urlencode($token),
        'cancel_url'  => $siteUrl . '/formular.php?type=' . urlencode($type) . '&anbieter=' . urlencode($formData['anbieter']) . '&cancelled=1&t=' . urlencode($token),
        'metadata'    => [
            'token'        => $token,
            'versandTier'  => $tier,
            'contractType' => $type,
            'anbieter'     => substr((string)$formData['anbieter'], 0, 200),
        ],
        // Locale german pentru checkout UI
        'locale' => 'de',
        // Kleinunternehmer §19 UStG: nu colectăm VAT
        // Stripe va trimite automat receipt via email dacă completăm customer_email
    ];

    if ($customerEmail !== null) {
        $sessionParams['customer_email'] = $customerEmail;
    }

    $session = \Stripe\Checkout\Session::create($sessionParams);

    // Redirect la Stripe Checkout (CSP form-action permite acum checkout.stripe.com)
    header('Location: ' . $session->url, true, 303);
    exit;

} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log('[stripe-checkout] Stripe API error: ' . $e->getMessage());
    @unlink($dataFile); // cleanup data file dacă session failed
    http_response_code(500);
    echo 'Zahlungssystem-Fehler: ' . htmlspecialchars($e->getMessage());
    exit;
} catch (\Throwable $e) {
    error_log('[stripe-checkout] Generic error: ' . $e->getMessage());
    @unlink($dataFile);
    http_response_code(500);
    echo 'Unerwarteter Fehler. Bitte später erneut versuchen.';
    exit;
}
