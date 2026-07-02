<?php
/**
 * Stripe Webhook endpoint
 *
 * Primește event-uri server-to-server de la Stripe (independent de browser-ul user-ului).
 * Garantează că scrisoarea pleacă la LX + email se trimite, chiar dacă userul închide tab-ul după plată.
 *
 * Config webhook în Stripe Dashboard:
 * URL:    https://www.kuendigungexpress.de/stripe-webhook.php
 * Events: checkout.session.completed
 *
 * Idempotency: ambele (acest webhook + versand-success.php) verifică /_orders/{session_id}.json
 * la început. Primul care ajunge procesează, al doilea găsește order-ul gata și sare procesarea.
 *
 * Stripe poate trimite același event de mai multe ori — fiecare retry trece prin idempotency check.
 */

header('Content-Type: text/plain; charset=utf-8');

error_log('[stripe-webhook] Request received from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

require_once __DIR__ . '/stripe-php/init.php';
require_once __DIR__ . '/dompdf/autoload.inc.php';
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/lx-api.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

$config   = require __DIR__ . '/_stripe_config.php';
$lxConfig = require __DIR__ . '/_lx_config.php';

$mode = $config['mode'] ?? 'test';
$secretKey     = $config[$mode]['secret_key']     ?? '';
$webhookSecret = $config[$mode]['webhook_secret'] ?? '';

if ($secretKey === '' || $webhookSecret === '') {
    http_response_code(500);
    error_log('[stripe-webhook] Config incomplete: secret_key or webhook_secret empty for mode=' . $mode);
    echo 'config_error';
    exit;
}

\Stripe\Stripe::setApiKey($secretKey);

// ===== 1. Verifică semnătura webhook =====
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if ($payload === false || $payload === '' || $sigHeader === '') {
    http_response_code(400);
    error_log('[stripe-webhook] Empty payload or missing signature header');
    echo 'bad_request';
    exit;
}

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    error_log('[stripe-webhook] Invalid payload: ' . $e->getMessage());
    echo 'invalid_payload';
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    error_log('[stripe-webhook] Invalid signature: ' . $e->getMessage());
    echo 'invalid_signature';
    exit;
}

// ===== 2. Procesez DOAR checkout.session.completed =====
$eventType = $event->type ?? '';
if ($eventType !== 'checkout.session.completed') {
    http_response_code(200);
    error_log('[stripe-webhook] Ignored event type: ' . $eventType);
    echo 'ignored';
    exit;
}

$session = $event->data->object ?? null;
if (!$session) {
    http_response_code(400);
    error_log('[stripe-webhook] Event has no session object');
    echo 'no_session';
    exit;
}

$sessionId = (string)($session->id ?? '');
if ($sessionId === '') {
    http_response_code(400);
    error_log('[stripe-webhook] Session has no ID');
    echo 'no_session_id';
    exit;
}

$paymentStatus = (string)($session->payment_status ?? '');
if ($paymentStatus !== 'paid') {
    http_response_code(200); 
    error_log('[stripe-webhook] Session ' . $sessionId . ' not paid (status: ' . $paymentStatus . ')');
    echo 'not_paid';
    exit;
}

$token = (string)($session->metadata->token ?? '');
if ($token === '' || !preg_match('/^[a-f0-9]{32}$/', $token)) {
    http_response_code(400);
    error_log('[stripe-webhook] Invalid token in session metadata: ' . $token);
    echo 'invalid_token';
    exit;
}

// ===== 3. Idempotency check — order există deja? =====
$orderDir = __DIR__ . '/_orders';
if (!is_dir($orderDir)) {
    @mkdir($orderDir, 0700, true);
    @file_put_contents($orderDir . '/.htaccess', "Require all denied\n");
}
$orderKey  = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sessionId);
$orderFile = $orderDir . '/' . $orderKey . '.json';

function webhookGenerateOrderNumber(): string {
    return 'KE-' . strtoupper(bin2hex(random_bytes(3)));
}

$orderNumber = '';

if (file_exists($orderFile)) {
    $existingOrder = json_decode(@file_get_contents($orderFile), true);
    if (is_array($existingOrder)) {
        $orderNumber = (string)($existingOrder['orderNumber'] ?? '');
    }
    http_response_code(200);
    error_log('[stripe-webhook] Order already processed for session: ' . $sessionId);
    echo 'already_processed';
    exit;
}

$orderNumber = webhookGenerateOrderNumber();

// ===== 3b. Atomic lock — previne race condition cu versand-success.php =====
// Ambele endpoint-uri pot ajunge aici simultan după plată.
// mkdir() este atomic pe ext4/IONOS — primul câștigă.
// Webhook așteaptă până la ~20s pentru lock (sub 30s Stripe timeout),
// verificând în paralel dacă orderFile apare între timp (success page a terminat).

$lockDir     = $orderDir . '/_lock_' . $orderKey;
$lockMaxAge  = 60;  // secunde — lock mai vechi = stale (proces crashed)
$lockWaitMax = 20;  // secunde — cât așteptăm să prindem lock-ul
$haveLock    = false;
$waitStart   = time();

do {
    // Stale lock check
    if (is_dir($lockDir) && (time() - (int)@filemtime($lockDir)) > $lockMaxAge) {
        @rmdir($lockDir);
        error_log('[stripe-webhook] Removed stale lock for ' . $sessionId);
    }

    if (@mkdir($lockDir, 0700)) {
        $haveLock = true;
        break;
    }

    // Lock e ținut de altcineva — poate success page a finalizat între timp?
    clearstatcache(true, $orderFile);
    if (file_exists($orderFile)) {
        http_response_code(200);
        error_log('[stripe-webhook] Order completed by other process during wait for: ' . $sessionId);
        echo 'completed_by_other';
        exit;
    }

    sleep(1);
} while ((time() - $waitStart) < $lockWaitMax);

if (!$haveLock) {
    // Lock-ul a fost ținut > 20s — celălalt proces e probabil stuck.
    // Returnăm 500 ca Stripe să facă retry mai târziu (5s, 30s, 5min, …)
    http_response_code(500);
    error_log('[stripe-webhook] Lock acquire timeout for ' . $sessionId);
    echo 'lock_timeout';
    exit;
}

// Garantăm cleanup lock chiar dacă scriptul crash-uiește
register_shutdown_function(function () use ($lockDir) {
    @rmdir($lockDir);
});

// ===== 4. Load form data =====
$dataFile = __DIR__ . '/_data/' . $token . '.json';
if (!file_exists($dataFile)) {
    http_response_code(200); 
    error_log('[stripe-webhook] Form data missing for token: ' . $token . ' (session ' . $sessionId . ')');
    echo 'form_data_missing';
    exit;
}

$form = json_decode(@file_get_contents($dataFile), true);
if (!is_array($form)) {
    http_response_code(200);
    error_log('[stripe-webhook] Form data corrupted for token: ' . $token);
    echo 'form_data_corrupted';
    exit;
}

if (empty(trim((string)($form['email'] ?? '')))) {
    $stripeEmail = '';
    try {
        $sessionWithDetails = \Stripe\Checkout\Session::retrieve([
            'id' => $sessionId,
            'expand' => ['customer_details'],
        ]);
        $stripeEmail = (string)($sessionWithDetails->customer_details->email ?? $sessionWithDetails->customer_email ?? '');
    } catch (\Throwable $e) {
        $stripeEmail = (string)($session->customer_email ?? '');
    }
    $stripeEmail = trim($stripeEmail);
    if ($stripeEmail !== '' && filter_var($stripeEmail, FILTER_VALIDATE_EMAIL)) {
        $form['email'] = $stripeEmail;
        error_log('[stripe-webhook] Email taken from Stripe: ' . $stripeEmail);
    }
}

// ===== 5. Procesez: PDF + LX + Email =====
$firstName = trim((string)($form['firstName'] ?? ''));
$lastName  = trim((string)($form['lastName'] ?? ''));
$type      = (string)($form['type'] ?? 'handy');
$zip       = (string)($form['zip'] ?? '');
$city      = trim((string)($form['city'] ?? ''));
$studio    = trim((string)($form['anbieter'] ?? $form['studio'] ?? ''));

$address = trim(($form['street'] ?? '') . "\n" . ($form['zip'] ?? '') . ' ' . ($form['city'] ?? ''));
$addressCompact = trim(trim((string)($form['street'] ?? '')) . ', ' . trim((string)($form['zip'] ?? '')) . ' ' . trim((string)($form['city'] ?? '')), ' ,');
$addressCompact = str_replace(['Straße', 'straße'], ['Str.', 'str.'], $addressCompact);
$studioAddress = trim(($form['studioStreet'] ?? '') . "\n" . ($form['studioZip'] ?? '') . ' ' . ($form['studioCity'] ?? ''));
$emailLine = !empty($form['email']) ? 'E-Mail: ' . htmlspecialchars((string)$form['email']) : '';

$terminationLine = (($form['terminationMode'] ?? '') === 'specific_date' && !empty($form['terminationDate']))
    ? 'zum ' . htmlspecialchars(date('d.m.Y', strtotime((string)$form['terminationDate'])))
    : 'zum nächstmöglichen Zeitpunkt';

$plate = (string)($form['plate'] ?? '');
$tier  = (string)($form['versandTier'] ?? 'standard');
$signature = (string)($form['signature'] ?? '');

$name       = trim($firstName . ' ' . $lastName);
$hilfsweise = '';
$plateLine  = '';

$rawContract = trim((string)($form['contractNo'] ?? ''));
$isNachgereicht = (empty($rawContract) || mb_strtolower($rawContract, 'UTF-8') === 'wird nachgereicht');

if ($type === 'kfz') {
    $templateName = 'kfz-kuendigung.html';
    $contractPrefix = 'Versicherungsschein-Nr.';
} elseif ($type === 'fitness') {
    $templateName = 'fitness-kuendigung.html';
    $contractPrefix = 'Mitgliedsnummer/Vertragsnummer';
} else {
    $templateName = 'handy-kuendigung.html';
    $contractPrefix = 'Vertragsnummer';
}

if ($isNachgereicht) {
    $contractLine = $contractPrefix . ' wird nachgereicht';
} else {
    $contractLine = $contractPrefix . ' ' . htmlspecialchars($rawContract);
}

if ($type === 'kfz' && !empty($plate)) {
    $contractLine .= ' | Kennzeichen: ' . htmlspecialchars($plate);
}

$templatePath = __DIR__ . '/templates/' . $templateName;
if (!file_exists($templatePath)) {
    http_response_code(500);
    error_log('[stripe-webhook] Template missing: ' . $templatePath);
    echo 'template_missing';
    exit;
}
$template = @file_get_contents($templatePath);

$signatureImageHtml = '';
$signatureInstructionHtml = '';
$tmpSigFile = null;

if ($signature !== '' && preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $signature, $sigMatch) && strlen($signature) < 250000) {
    $pngBinary = base64_decode($sigMatch[1], true);
    if ($pngBinary !== false && strlen($pngBinary) >= 50 && substr($pngBinary, 0, 8) === "\x89PNG\r\n\x1a\n") {
        $sigDirCheck = __DIR__ . '/pdf';
        if (!is_dir($sigDirCheck)) {
            mkdir($sigDirCheck, 0755, true);
        }
        $tmpSigFile = $sigDirCheck . '/sig_wh_' . bin2hex(random_bytes(8)) . '.png';

        $writeOk = false;
        if (function_exists('imagecreatefromstring')) {
            $srcImg = @imagecreatefromstring($pngBinary);
            if ($srcImg !== false) {
                $width = imagesx($srcImg);
                $height = imagesy($srcImg);
                $dstImg = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($dstImg, 255, 255, 255);
                imagefill($dstImg, 0, 0, $white);
                imagecopy($dstImg, $srcImg, 0, 0, 0, 0, $width, $height);
                imagesavealpha($dstImg, false);
                imagealphablending($dstImg, true);
                $writeOk = imagepng($dstImg, $tmpSigFile, 6);
                imagedestroy($srcImg);
                imagedestroy($dstImg);
            }
        }
        if (!$writeOk) {
            $writeOk = file_put_contents($tmpSigFile, $pngBinary) !== false;
        }

        if ($writeOk) {
            $signatureImageHtml = '<img src="' . htmlspecialchars($tmpSigFile, ENT_QUOTES) . '" style="display:block; height: 13mm; margin: 0 0 0 2mm;" alt="Unterschrift">';
        } else {
            $tmpSigFile = null;
        }
    }
}

$html = str_replace(
    ['{{name}}', '{{address}}', '{{address_compact}}', '{{studio}}', '{{studio_address}}', '{{email_line}}',
     '{{contract_line}}', '{{termination_line}}', '{{date}}', '{{city}}', '{{hilfsweise}}', '{{plate_line}}',
     '{{signature_image}}'],
    [nl2br(htmlspecialchars($name)), nl2br(htmlspecialchars($address)), htmlspecialchars($addressCompact),
     htmlspecialchars($studio), nl2br(htmlspecialchars($studioAddress)),
     $emailLine, $contractLine, $terminationLine, date('d.m.Y'), htmlspecialchars($city), $hilfsweise, $plateLine,
     $signatureImageHtml],
    $template
);

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Arial');
$options->set('chroot', [__DIR__ . '/pdf', __DIR__ . '/templates', __DIR__]);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfBinary = $dompdf->output();

$pdfDir = __DIR__ . '/pdf';
if (!is_dir($pdfDir)) mkdir($pdfDir, 0755, true);

// Filename folosește pdfToken random (128-bit entropy), NU sessionId.
// Previne ghicirea URL-urilor PDF din session ID-uri leak-uite (PII).
// Fallback la random nou dacă pdfToken lipsește (backward compat).
$pdfToken = (string)($form['pdfToken'] ?? '');
if (!preg_match('/^[a-f0-9]{32}$/', $pdfToken)) {
    $pdfToken = bin2hex(random_bytes(16));
}

$pdfFilename = 'versand_' . $pdfToken . '.pdf';
$pdfPath = $pdfDir . '/' . $pdfFilename;
file_put_contents($pdfPath, $pdfBinary);

if ($tmpSigFile !== null && file_exists($tmpSigFile)) {
    @unlink($tmpSigFile);
}

// ===== 6. LX call =====
$lxResult = sendToLetterXpress($pdfPath, $form, $tier, $lxConfig);
if (!$lxResult['ok']) {
    error_log('[stripe-webhook] LX API failed for session ' . $sessionId . ': ' . $lxResult['message']);
    // Alert admin — plata e OK, dar scrisoarea NU a fost queued
    sendLxFailureAlert(
        $sessionId,
        $orderNumber,
        $lxResult,
        $form,
        trim((string)($form['email'] ?? '')),
        'webhook'
    );
}

// ===== 7. Email confirmation =====
$userEmail = trim((string)($form['email'] ?? ''));
$emailSent = false;
if ($userEmail !== '' && filter_var($userEmail, FILTER_VALIDATE_EMAIL) && file_exists(__DIR__ . '/_smtp_config.php')) {
    try {
        $smtp = require __DIR__ . '/_smtp_config.php';
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->Port       = (int)$smtp['port'];
        $mail->SMTPSecure = $smtp['encryption'];
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($smtp['username'], 'KündigungExpress');
        $mail->addAddress($userEmail, $firstName . ' ' . $lastName);

        $tierLabel = $tier === 'einschreiben' ? 'Einwurfeinschreiben' : 'Standardbrief';
        $tierPrice = $tier === 'einschreiben' ? '8,99 €' : '3,99 €';
        
        $providerSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower((string)@iconv('UTF-8', 'ASCII//TRANSLIT', $studio)));
        $providerSlug = trim($providerSlug, '-');
        if ($providerSlug === '') $providerSlug = 'anbieter';

        $mail->addAttachment($pdfPath, 'Kuendigung-' . $providerSlug . '.pdf');
        
        $mail->isHTML(true);
        $mail->Subject = 'Kündigung an ' . $studio . ' — Versand bestätigt [' . $orderNumber . ']';

        $paymentIntent = (string)($session->payment_intent ?? '');

        if ($lxResult['ok']) {
            $statusText = "Ihre Kündigung wurde erfolgreich an unseren Druck- und Versand-Partner übergeben "
                        . "und wird innerhalb von 1–2 Werktagen verschickt.";
        } else {
            $statusText = "Ihre Zahlung wurde bestätigt. Die Übergabe an unseren Versand-Partner wird in Kürze nachgeholt — "
                        . "Sie erhalten von uns Bescheid, sobald die Sendung auf dem Weg ist.";
        }

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b; line-height: 1.6; background-color: #ffffff; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px;'>
            <div style='margin-bottom: 24px;'>
                <h2 style='margin: 0 0 12px 0; font-size: 22px; color: #0f172a; font-weight: 800; letter-spacing: -0.02em;'>Vielen Dank für Ihre Bestellung, " . htmlspecialchars($firstName) . "!</h2>
                <p style='margin: 0; font-size: 15px; color: #475569;'>Wir haben Ihren Auftrag erfolgreich entgegengenommen. Im Anhang dieser E-Mail finden Sie eine digitale Kopie Ihres fertigen Kündigungsschreibens als PDF zur Aufbewahrung.</p>
            </div>
            <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;'>
                <h3 style='margin: 0 0 14px 0; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;'>Bestelldetails</h3>
                <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
                    <tr><td style='padding: 6px 0; color: #64748b; width: 40%;'>Bestellnummer:</td><td style='padding: 6px 0; font-weight: 700; color: #0f172a; font-family: monospace; font-size: 15px;'>" . htmlspecialchars($orderNumber) . "</td></tr>
                    <tr><td style='padding: 6px 0; color: #64748b;'>Empfänger:</td><td style='padding: 6px 0; font-weight: 600; color: #0f172a;'>" . htmlspecialchars($studio) . "</td></tr>
                    <tr><td style='padding: 6px 0; color: #64748b;'>Versandart:</td><td style='padding: 6px 0; color: #0f172a;'>" . htmlspecialchars($tierLabel) . " (" . htmlspecialchars($tierPrice) . ")</td></tr>
                    <tr><td style='padding: 6px 0; color: #64748b;'>Datum:</td><td style='padding: 6px 0; color: #0f172a;'>" . date('d.m.Y') . "</td></tr>
                    <tr><td style='padding: 6px 0; color: #64748b;'>Zahlung:</td><td style='padding: 6px 0; color: #16a34a; font-weight: 700;'>✓ Bestätigt</td></tr>" .
                    ($paymentIntent !== '' ? "
                    <tr><td style='padding: 6px 0; color: #64748b;'>Transaktion:</td><td style='padding: 6px 0; color: #475569; font-size: 12px; font-family: monospace;'>" . htmlspecialchars($paymentIntent) . " (Stripe)</td></tr>" : "") . "
                </table>
                <div style='margin-top: 14px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; font-style: italic;'>(Kleinunternehmer §19 UStG · keine MwSt. ausgewiesen)</div>
            </div>
            <div style='background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 16px; margin-bottom: 24px; font-size: 14px; color: #166534; font-weight: 500;'>
                ℹ️ " . htmlspecialchars($statusText) . "
            </div>
            <div style='margin-bottom: 24px; padding-top: 8px;'>
                <h3 style='margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #0f172a;'>Was passiert jetzt?</h3>
                <ol style='margin: 0; padding-left: 20px; font-size: 14px; color: #475569;'>
                    <li style='margin-bottom: 8px;'>Wir drucken Ihre Kündigung mit Ihrer original hinterlegten Unterschrift.</li>
                    <li style='margin-bottom: 8px;'>Das Schreiben wird professionell kuvertiert und frankiert.</li>
                    <li style='margin-bottom: 8px;'>Der physische Versand erfolgt via Deutsche Post — spätestens nächsten Werktag bis 14 Uhr.</li>
                    <li style='margin-bottom: 8px;'>Die Zustellung beim Empfänger erfolgt in der Regel innerhalb der nächsten 1–3 Werktage.</li>
                </ol>
            </div>" .
            ($tier === 'einschreiben' ? "
            <div style='background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 14px; margin-bottom: 24px; font-size: 13.5px; color: #1e40af;'>
                📬 <strong>Hinweis zum Einschreiben:</strong> Sie erhalten zusätzlich einen offiziellen Sendungsnachweis (Tracking-Link) per E-Mail, sobald die Zustellung erfolgreich abgeschlossen wurde.
            </div>" : "") . "
            <div style='border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 14px; color: #475569;'>
                <p style='margin: 0 0 12px 0;'>Bei Fragen oder Problemen antworten Sie einfach direkt auf diese E-Mail.</p>
                <p style='margin: 0; font-weight: 700; color: #0f172a;'>Mit freundlichen Grüßen,<br><span style='color: #16a34a;'>KündigungExpress</span></p>
            </div>
            <div style='margin-top: 24px; padding-top: 12px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #94a3b8;'>
                <a href='https://kuendigungexpress.de' style='color: #94a3b8; text-decoration: none;'>kuendigungexpress.de</a>
            </div>
        </div>";

        $mail->send();
        $emailSent = true;
    } catch (\Throwable $e) {
        error_log('[stripe-webhook] Email failed for ' . $userEmail . ': ' . $e->getMessage());
    }
}

// ===== 8. Save order =====
$order = [
    'sessionId'        => $sessionId,
    'token'            => $token,
    'createdAt'        => time(),
    'processedBy'      => 'webhook',
    'pdfFilename'      => $pdfFilename,
    'pdfToken'         => $pdfToken,
    'downloadUrl'      => '/pdf/' . $pdfFilename,
    'type'             => $type,
    'anbieter'         => $studio,
    'tier'             => $tier,
    'amount_cents'     => $tier === 'einschreiben' ? 899 : 399,
    'lxQueued'         => (bool)$lxResult['ok'],
    'lxStatus'         => $lxResult['ok'] ? ($lxConfig['mode'] === 'test' ? 'warenkorb' : 'queued') : 'failed',
    'lxJobId'          => $lxResult['jobId'],
    'lxMessage'        => $lxResult['message'],
    'lxMode'           => $lxConfig['mode'] ?? 'test',
    'paymentStatus'    => 'paid',
    'userEmail'        => $userEmail,
    'emailSent'        => $emailSent,
    'orderNumber'      => $orderNumber,
    'paymentIntent'    => (string)($session->payment_intent ?? ''),
];

file_put_contents($orderFile, json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
@chmod($orderFile, 0600);

// Salvez și formData + signature SEPARAT, ca success_page (consistență pentru resend.php,
// debugging, manual replay în caz de LX failure). Notă: PII sensitive.
$order['signature'] = (string)($form['signature'] ?? '');
$order['formData']  = $form;
file_put_contents($orderFile, json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
@chmod($orderFile, 0600);

/* === Dashboard PDF-Logging (Versand) ===
   Fluxul gratis logheaza in generate.php; Versand il sarea, deci PDF-urile platite
   nu apareau in dashboard (PDFs heute / letzte PDF / total / breakdown).
   Marker atomic (mkdir) = exactly-once per comanda, imun la retry webhook / dublu endpoint. */
$logMarker = __DIR__ . '/_orders/_logged_' . $orderKey;
if (@mkdir($logMarker, 0700)) {
    $counterFile = __DIR__ . '/_counter.txt';
    $cnt = is_file($counterFile) ? (int)trim((string)@file_get_contents($counterFile)) : 0;
    @file_put_contents($counterFile, (string)($cnt + 1), LOCK_EX);

    $pdfLogLine = implode("\t", [
        time(),
        (string)($order['type'] ?? 'fitness'),
        str_replace(["\t", "\n", "\r"], ' ', (string)($order['anbieter'] ?? '')),
        !empty($order['emailSent']) ? '1' : '0',
        !empty($order['userEmail']) ? '1' : '0',
    ]) . "\n";
    @file_put_contents(__DIR__ . '/_data/pdf_generated.log', $pdfLogLine, FILE_APPEND | LOCK_EX);
}

@unlink($dataFile);

// ===== 9. Return 200 OK către Stripe =====
http_response_code(200);
error_log('[stripe-webhook] Successfully processed session: ' . $sessionId);
echo 'ok';
exit;