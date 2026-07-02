<?php
declare(strict_types=1);

/* =========================================================
   VERSAND SUCCESS — endpoint declanșat după Stripe Checkout success_url
   Pași: 1. Verifică Stripe session paid; 2. Load form data;
         3. Generează PDF cu semnătura; 4. Salvează order;
         5. (Faza C) trimite la LX API; 6. Afișează pagina succes
   ========================================================= */

ignore_user_abort(true);
set_time_limit(60);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/_mail_log/php_errors.log');
error_reporting(E_ALL);

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
$stripeKey = $config[$mode]['secret_key'] ?? '';

if ($stripeKey === '') {
    http_response_code(500);
    echo 'Stripe-Konfiguration fehlt.';
    exit;
}

\Stripe\Stripe::setApiKey($stripeKey);

/* =========================================================
   INPUT VALIDATION
   ========================================================= */

$sessionId = (string)($_GET['session_id'] ?? '');
$token     = (string)($_GET['t'] ?? '');

if (!preg_match('/^cs_(test|live)_[a-zA-Z0-9]+$/', $sessionId)) {
    http_response_code(400);
    echo 'Ungültige Stripe Session.';
    exit;
}
if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
    http_response_code(400);
    echo 'Ungültiger Token.';
    exit;
}

/* =========================================================
   ORDER CACHE — dacă există deja, arătăm direct download (idempotent)
   ========================================================= */

$ordersDir = __DIR__ . '/_orders';
if (!is_dir($ordersDir)) {
    mkdir($ordersDir, 0755, true);
}

$orderKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $sessionId);
$orderFile = $ordersDir . '/' . $orderKey . '.json';

function generateOrderNumber(): string {
    return 'KE-' . strtoupper(bin2hex(random_bytes(3))); // 3 bytes = 6 hex uppercase
}

$orderNumber = '';
if (file_exists($orderFile)) {
    $order = json_decode(file_get_contents($orderFile), true);
    if (is_array($order) && !empty($order['downloadUrl'])) {
        $orderNumber = (string)($order['orderNumber'] ?? '');
        if ($orderNumber === '') {
            $orderNumber = generateOrderNumber();
            $order['orderNumber'] = $orderNumber;
            file_put_contents($orderFile, json_encode($order, JSON_UNESCAPED_UNICODE));
        }
        renderSuccessPage(
            $order['downloadUrl'],
            (string)($order['tier'] ?? 'standard'),
            (string)($order['anbieter'] ?? $order['studio'] ?? ''),
            (bool)($order['lxQueued'] ?? false),
            $orderNumber
        );
        exit;
    }
}

$orderNumber = generateOrderNumber();

/* =========================================================
   ATOMIC LOCK — previne race condition cu stripe-webhook.php
   ambele endpoint-uri pot ajunge aici simultan după plată.
   mkdir() este atomic pe ext4/IONOS — primul câștigă lock-ul.
   ========================================================= */

$lockDir   = __DIR__ . '/_orders/_lock_' . $orderKey;
$lockMaxAge = 60; // secunde — lock mai vechi de atât = stale (proces crashed)

// Curăță lock stale dacă există
if (is_dir($lockDir) && (time() - (int)@filemtime($lockDir)) > $lockMaxAge) {
    @rmdir($lockDir);
    error_log('[versand-success] Removed stale lock for ' . $sessionId);
}

if (!@mkdir($lockDir, 0700)) {
    // Webhook procesează simultan. Așteaptă scurt apoi re-verifică orderFile.
    error_log('[versand-success] Lock contention for ' . $sessionId . ' — waiting');
    sleep(3);

    if (file_exists($orderFile)) {
        $order = json_decode(@file_get_contents($orderFile), true);
        if (is_array($order) && !empty($order['downloadUrl'])) {
            $orderNumber = (string)($order['orderNumber'] ?? $orderNumber);
            if (empty($order['orderNumber'])) {
                $order['orderNumber'] = $orderNumber;
                @file_put_contents($orderFile, json_encode($order, JSON_UNESCAPED_UNICODE));
            }
            renderSuccessPage(
                $order['downloadUrl'],
                (string)($order['tier'] ?? 'standard'),
                (string)($order['anbieter'] ?? $order['studio'] ?? ''),
                (bool)($order['lxQueued'] ?? false),
                $orderNumber
            );
            exit;
        }
    }

    // Tot nu e gata — afișează pagină tranzitorie cu auto-refresh
    http_response_code(200);
    header('Cache-Control: no-store');
    echo '<!doctype html><html lang="de"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<meta name="robots" content="noindex,nofollow">'
       . '<meta http-equiv="refresh" content="5">'
       . '<title>Wird verarbeitet… | KündigungExpress</title>'
       . '<style>body{font-family:Arial,sans-serif;background:#F7F9FC;color:#0F172A;'
       . 'display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}'
       . '.card{background:#fff;border:1px solid #E2E8F0;border-radius:20px;padding:36px 32px;'
       . 'text-align:center;max-width:480px;box-shadow:0 8px 32px rgba(15,23,42,.06)}'
       . '.spinner{width:48px;height:48px;border:4px solid #E2E8F0;border-top-color:#16A34A;'
       . 'border-radius:50%;margin:0 auto 18px;animation:spin 1s linear infinite}'
       . '@keyframes spin{to{transform:rotate(360deg)}}'
       . 'h1{font-size:20px;font-weight:900;margin:0 0 10px;letter-spacing:-.01em}'
       . 'p{font-size:14px;color:#475569;line-height:1.6;margin:0}</style></head>'
       . '<body><div class="card"><div class="spinner"></div>'
       . '<h1>Ihre Bestellung wird verarbeitet</h1>'
       . '<p>Zahlung bestätigt — wir bereiten den Versand vor. '
       . 'Diese Seite aktualisiert sich automatisch in wenigen Sekunden…</p></div></body></html>';
    exit;
}

// Garantăm cleanup lock chiar dacă scriptul crash-uiește
register_shutdown_function(function () use ($lockDir) {
    @rmdir($lockDir);
});

/* =========================================================
   STRIPE SESSION VERIFICATION
   ========================================================= */

try {
    $session = \Stripe\Checkout\Session::retrieve($sessionId);
} catch (\Throwable $e) {
    error_log('[versand-success] Stripe retrieve failed: ' . $e->getMessage());
    http_response_code(400);
    echo 'Session-Abruf fehlgeschlagen.';
    exit;
}

if (($session->payment_status ?? '') !== 'paid') {
    http_response_code(402);
    echo 'Zahlung noch nicht abgeschlossen oder fehlgeschlagen.';
    exit;
}

$metaToken = (string)($session->metadata->token ?? '');
if ($metaToken !== $token) {
    error_log('[versand-success] Token mismatch — URL: ' . $token . ' vs Stripe: ' . $metaToken);
    http_response_code(403);
    echo 'Token stimmt nicht überein.';
    exit;
}

/* =========================================================
   LOAD FORM DATA
   ========================================================= */

$dataFile = __DIR__ . '/_data/' . $token . '.json';
if (!file_exists($dataFile)) {
    http_response_code(410);
    echo 'Formulardaten abgelaufen oder nicht gefunden.';
    exit;
}

$form = json_decode(file_get_contents($dataFile), true);
if (!is_array($form)) {
    http_response_code(500);
    echo 'Formulardaten beschädigt.';
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
        error_log('[versand-success] Email taken from Stripe: ' . $stripeEmail);
    }
}

/* =========================================================
   GENERATE PDF
   ========================================================= */

$type = (string)($form['type'] ?? 'handy');
$templateFile = __DIR__ . '/templates/' . match($type) {
    'kfz'     => 'kfz-kuendigung.html',
    'fitness' => 'fitness-kuendigung.html',
    default   => 'handy-kuendigung.html',
};

if (!file_exists($templateFile)) {
    http_response_code(500);
    echo 'PDF-Vorlage fehlt.';
    exit;
}

$template = file_get_contents($templateFile);

$firstName = trim((string)($form['firstName'] ?? ''));
$lastName  = trim((string)($form['lastName'] ?? ''));
$name      = trim($firstName . ' ' . $lastName);

$address = trim(($form['street'] ?? '') . "\n" . ($form['zip'] ?? '') . ' ' . ($form['city'] ?? ''));
$addressCompact = trim(trim((string)($form['street'] ?? '')) . ', ' . trim((string)($form['zip'] ?? '')) . ' ' . trim((string)($form['city'] ?? '')), ' ,');
$addressCompact = str_replace(['Straße', 'straße'], ['Str.', 'str.'], $addressCompact);

$studio        = trim((string)($form['anbieter'] ?? $form['studio'] ?? ''));
$studioAddress = trim(($form['studioStreet'] ?? '') . "\n" . ($form['studioZip'] ?? '') . ' ' . ($form['studioCity'] ?? ''));

$emailLine = '';
if (!empty($form['email'])) {
    $emailLine = 'E-Mail: ' . htmlspecialchars((string)$form['email']);
}

$rawContract = trim((string)($form['contractNo'] ?? ''));
$isNachgereicht = (empty($rawContract) || mb_strtolower($rawContract, 'UTF-8') === 'wird nachgereicht');

if ($type === 'kfz') {
    $contractPrefix = 'Versicherungsschein-Nr.';
} elseif ($type === 'fitness') {
    $contractPrefix = 'Mitgliedsnummer/Vertragsnummer';
} else {
    $contractPrefix = 'Vertragsnummer';
}

if ($isNachgereicht) {
    $contractLine = $contractPrefix . ' wird nachgereicht';
} else {
    $contractLine = $contractPrefix . ' ' . htmlspecialchars($rawContract);
}

$plate = (string)($form['plate'] ?? '');
if ($type === 'kfz' && !empty($plate)) {
    $contractLine .= ' | Kennzeichen: ' . htmlspecialchars($plate);
}
$plateLine = '';
$terminationLine = (($form['terminationMode'] ?? '') === 'specific_date' && !empty($form['terminationDate']))
    ? 'zum ' . htmlspecialchars(date('d.m.Y', strtotime((string)$form['terminationDate'])))
    : 'zum nächstmöglichen Zeitpunkt';

$city = trim((string)($form['city'] ?? ''));
$hilfsweise = '';

$signature = (string)($form['signature'] ?? '');
$signatureImageHtml = '';
$signatureInstructionHtml = '';
$tmpSigFile = null; 

$sigDebug = [
    'session'         => $sessionId,
    'sig_present'     => $signature !== '',
    'sig_length'      => strlen($signature),
    'sig_prefix'      => substr($signature, 0, 40),
    'regex_match'     => preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $signature) === 1,
];

if ($signature !== '' && preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/=]+)$/', $signature, $sigMatch) && strlen($signature) < 250000) {
    $pngBinary = base64_decode($sigMatch[1], true);
    $sigDebug['base64_decoded'] = $pngBinary !== false;
    $sigDebug['png_size'] = $pngBinary !== false ? strlen($pngBinary) : 0;

    if ($pngBinary !== false && strlen($pngBinary) >= 50) {
        $magicBytes = substr($pngBinary, 0, 8);
        $sigDebug['magic_ok'] = $magicBytes === "\x89PNG\r\n\x1a\n";
        $sigDebug['magic_hex'] = bin2hex($magicBytes);

        if ($magicBytes === "\x89PNG\r\n\x1a\n") {
            $sigDirCheck = __DIR__ . '/pdf';
            if (!is_dir($sigDirCheck)) {
                mkdir($sigDirCheck, 0755, true);
            }
            $tmpSigFile = $sigDirCheck . '/sig_tmp_' . bin2hex(random_bytes(8)) . '.png';
            $writeOk = file_put_contents($tmpSigFile, $pngBinary);
            $sigDebug['file_written'] = $writeOk !== false;
            $sigDebug['file_path'] = $tmpSigFile;
            $sigDebug['file_exists'] = file_exists($tmpSigFile);
            $sigDebug['file_size_on_disk'] = file_exists($tmpSigFile) ? filesize($tmpSigFile) : 0;

            if ($writeOk !== false && function_exists('imagecreatefrompng')) {
                $srcImg = @imagecreatefrompng($tmpSigFile);
                if ($srcImg !== false) {
                    $w = imagesx($srcImg);
                    $h = imagesy($srcImg);
                    $flatImg = imagecreatetruecolor($w, $h);
                    $white = imagecolorallocate($flatImg, 255, 255, 255);
                    imagefilledrectangle($flatImg, 0, 0, $w, $h, $white);
                    imagealphablending($flatImg, true);
                    imagecopy($flatImg, $srcImg, 0, 0, 0, 0, $w, $h);
                    @imagepng($flatImg, $tmpSigFile, 9);
                    imagedestroy($srcImg);
                    imagedestroy($flatImg);
                    $sigDebug['gd_flatten_ok'] = true;
                    $sigDebug['file_size_after_flatten'] = file_exists($tmpSigFile) ? filesize($tmpSigFile) : 0;
                } else {
                    $sigDebug['gd_flatten_ok'] = false;
                    $sigDebug['gd_flatten_err'] = 'imagecreatefrompng returned false';
                }
            }

            if ($writeOk !== false) {
                $signatureImageHtml = '<img src="' . htmlspecialchars($tmpSigFile, ENT_QUOTES) . '" style="display:block; height: 13mm; margin: 0 0 0 2mm;" alt="Unterschrift">';
                $sigDebug['img_tag_built'] = true;
            } else {
                error_log('[versand-success] Failed to write signature PNG: ' . $tmpSigFile);
                $tmpSigFile = null;
            }
        } else {
            error_log('[versand-success] Signature data is not a valid PNG (magic bytes: ' . bin2hex($magicBytes) . ')');
        }
    } else {
        error_log('[versand-success] Signature base64_decode failed or too small');
    }
} else {
    $sigDebug['fail_reason'] = $signature === '' ? 'empty' : (strlen($signature) >= 250000 ? 'too_large' : 'regex_no_match');
}

error_log('[versand-success SIG DEBUG] ' . json_encode($sigDebug));

if ($signatureImageHtml === '') {
    error_log('[versand-success] WARNING: Versand without signature embedded — session: ' . $sessionId);
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

$debugDir = __DIR__ . '/_mail_log';
if (!is_dir($debugDir)) mkdir($debugDir, 0755, true);
file_put_contents($debugDir . '/last_pdf_html.html', $html);

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Arial');
$options->set('chroot', [__DIR__ . '/pdf', __DIR__ . '/templates', __DIR__]);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfDir = __DIR__ . '/pdf';
if (!is_dir($pdfDir)) {
    mkdir($pdfDir, 0755, true);
}

// Filename folosește pdfToken random (128-bit entropy), NU sessionId.
// Previne ghicirea URL-urilor PDF din session ID-uri leak-uite (PII).
// Fallback la random nou dacă pdfToken lipsește (backward compat cu vechi formData).
$pdfToken = (string)($form['pdfToken'] ?? '');
if (!preg_match('/^[a-f0-9]{32}$/', $pdfToken)) {
    $pdfToken = bin2hex(random_bytes(16));
}

$pdfFilename = 'versand_' . $pdfToken . '.pdf';
$pdfPath = $pdfDir . '/' . $pdfFilename;
file_put_contents($pdfPath, $dompdf->output());

if ($tmpSigFile !== null && file_exists($tmpSigFile)) {
    @unlink($tmpSigFile);
}

$downloadUrl = '/pdf/' . $pdfFilename;

/* =========================================================
   LETTERXPRESS API CALL
   ========================================================= */

$lxTier = (string)($form['versandTier'] ?? 'standard');
$lxResult = sendToLetterXpress($pdfPath, $form, $lxTier, $lxConfig);

if (!$lxResult['ok']) {
    error_log('[versand-success] LX API failed for session ' . $sessionId . ': ' . $lxResult['message']);
    // Alert admin — plata e OK, dar scrisoarea NU a fost queued
    sendLxFailureAlert(
        $sessionId,
        $orderNumber,
        $lxResult,
        $form,
        trim((string)($form['email'] ?? '')),
        'success_page'
    );
}

/* =========================================================
   EMAIL CONFIRMATION
   ========================================================= */

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

        $tierLabel = $lxTier === 'einschreiben' ? 'Einwurfeinschreiben' : 'Standardbrief';
        $tierPrice = $lxTier === 'einschreiben' ? '8,99 €' : '3,99 €';
        
        $providerSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower((string)@iconv('UTF-8', 'ASCII//TRANSLIT', $studio)));
        $providerSlug = trim($providerSlug, '-');
        if ($providerSlug === '') $providerSlug = 'anbieter';

        $mail->addAttachment($pdfPath, 'Kuendigung-' . $providerSlug . '.pdf');
        
        $mail->isHTML(true);
        $mail->Subject = 'Kündigung an ' . $studio . ' — Versand bestätigt [' . $orderNumber . ']';

        if ($lxResult['ok']) {
            $statusText = "Ihre Kündigung wurde erfolgreich an unseren Druck- und Versand-Partner LetterXpress übergeben "
                        . "und wird innerhalb von 1–2 Werktagen verschickt.";
        } else {
            $statusText = "Ihre Zahlung wurde bestätigt. Die Übergabe an unseren Versand-Partner wird in Kürze nachgeholt — "
                        . "Sie erhalten von uns Bescheid, sobald die Sendung auf dem Weg ist.";
        }

        $paymentIntent = (string)($session->payment_intent ?? '');
        
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b; line-height: 1.6; background-color: #ffffff; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px;'>
            
            <div style='margin-bottom: 24px;'>
                <h2 style='margin: 0 0 12px 0; font-size: 22px; color: #0f172a; font-weight: 800; letter-spacing: -0.02em;'>Vielen Dank für Ihre Bestellung, " . htmlspecialchars($firstName) . "!</h2>
                <p style='margin: 0; font-size: 15px; color: #475569;'>Wir haben Ihren Auftrag erfolgreich entgegengenommen. Im Anhang dieser E-Mail finden Sie eine digitale Kopie Ihres fertigen Kündigungsschreibens als PDF zur Aufbewahrung.</p>
            </div>

            <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;'>
                <h3 style='margin: 0 0 14px 0; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;'>Bestelldetails</h3>
                
                <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
                    <tr>
                        <td style='padding: 6px 0; color: #64748b; width: 40%;'>Bestellnummer:</td>
                        <td style='padding: 6px 0; font-weight: 700; color: #0f172a; font-family: monospace; font-size: 15px;'>" . htmlspecialchars($orderNumber) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #64748b;'>Empfänger:</td>
                        <td style='padding: 6px 0; font-weight: 600; color: #0f172a;'>" . htmlspecialchars($studio) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #64748b;'>Versandart:</td>
                        <td style='padding: 6px 0; color: #0f172a;'>" . htmlspecialchars($tierLabel) . " (" . htmlspecialchars($tierPrice) . ")</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #64748b;'>Datum:</td>
                        <td style='padding: 6px 0; color: #0f172a;'>" . date('d.m.Y') . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 6px 0; color: #64748b;'>Zahlung:</td>
                        <td style='padding: 6px 0; color: #16a34a; font-weight: 700;'>✓ Bestätigt</td>
                    </tr>" . 
                    ($paymentIntent !== '' ? "
                    <tr>
                        <td style='padding: 6px 0; color: #64748b;'>Transaktion:</td>
                        <td style='padding: 6px 0; color: #475569; font-size: 12px; font-family: monospace;'>" . htmlspecialchars($paymentIntent) . " (Stripe)</td>
                    </tr>" : "") . "
                </table>
                
                <div style='margin-top: 14px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; font-style: italic;'>
                    (Kleinunternehmer §19 UStG · keine MwSt. ausgewiesen)
                </div>
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

            ($lxTier === 'einschreiben' ? "
            <div style='background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 14px; margin-bottom: 24px; font-size: 13.5px; color: #1e40af;'>
                📬 <strong>Hinweis zum Einwurfeinschreiben:</strong> Sie erhalten zusätzlich einen offiziellen Zustellnachweis (Tracking-Link) per E-Mail, sobald die Zustellung erfolgreich abgeschlossen wurde.
            </div>" : "") . "

            <div style='border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 14px; color: #475569;'>
                <p style='margin: 0 0 12px 0;'>Bei Fragen oder Problemen antworten Sie einfach direkt auf diese E-Mail. Unser Support hilft Ihnen gerne weiter.</p>
                <p style='margin: 0; font-weight: 700; color: #0f172a;'>Mit freundlichen Grüßen,<br><span style='color: #16a34a;'>KündigungExpress</span></p>
            </div>
            
            <div style='margin-top: 24px; padding-top: 12px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #94a3b8;'>
                <a href='https://kuendigungexpress.de' style='color: #94a3b8; text-decoration: none;'>kuendigungexpress.de</a>
            </div>
        </div>";

        $mail->send();
        $emailSent = true;
    } catch (MailException $e) {
        error_log('[versand-success] Email failed for ' . $userEmail . ': ' . $e->getMessage());
    } catch (\Throwable $e) {
        error_log('[versand-success] Email generic error: ' . $e->getMessage());
    }
}

/* =========================================================
   SAVE ORDER + CLEANUP DATA
   ========================================================= */

$order = [
    'createdAt'     => time(),
    'processedBy'   => 'success_page',
    'sessionId'     => $sessionId,
    'token'         => $token,
    'tier'          => $lxTier,
    'amount_cents'  => (int)($form['amount_cents'] ?? 0),
    'anbieter'      => $studio,
    'type'          => $type,
    'pdfPath'       => $pdfPath,
    'pdfToken'      => $pdfToken,
    'downloadUrl'   => $downloadUrl,
    'lxQueued'      => (bool)$lxResult['ok'],
    'lxStatus'      => $lxResult['ok'] ? ($lxConfig['mode'] === 'test' ? 'warenkorb' : 'queued') : 'failed',
    'lxJobId'       => $lxResult['jobId'],
    'lxMessage'     => $lxResult['message'],
    'lxMode'        => $lxConfig['mode'] ?? 'test',
    'paymentStatus' => 'paid',
    'userEmail'     => $userEmail,
    'emailSent'     => $emailSent,
    'orderNumber'   => $orderNumber,
    'paymentIntent' => $paymentIntent,
];

file_put_contents($orderFile, json_encode($order, JSON_UNESCAPED_UNICODE));

$order['signature']     = $signature;
$order['formData']      = $form;
file_put_contents($orderFile, json_encode($order, JSON_UNESCAPED_UNICODE));

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

renderSuccessPage($downloadUrl, $order['tier'], $studio, (bool)$lxResult['ok'], $orderNumber);
exit;

/* =========================================================
   SUCCESS PAGE HELPER
   ========================================================= */

function renderSuccessPage(string $downloadUrl, string $tier, string $anbieter, bool $lxQueued, string $orderNumber = ''): void {
    $tierLabel = $tier === 'einschreiben' ? 'Einwurfeinschreiben' : 'Standardbrief';
    
    $now = new DateTime('now', new DateTimeZone('Europe/Berlin'));
    $hour = (int)$now->format('H');
    $dayOfWeek = (int)$now->format('N');
    
    $isWeekend = ($dayOfWeek >= 6);
    $isAfterCutoff = ($hour >= 14);
    
    $printDate = clone $now;
    if ($isWeekend || $isAfterCutoff) {
        do {
            $printDate->modify('+1 day');
        } while ((int)$printDate->format('N') >= 6);
    }
    
    $deliveryStart = clone $printDate;
    do {
        $deliveryStart->modify('+1 day');
    } while ((int)$deliveryStart->format('N') >= 6);
    
    $deliveryEnd = clone $deliveryStart;
    $daysToAdd = 2;
    while ($daysToAdd > 0) {
        $deliveryEnd->modify('+1 day');
        if ((int)$deliveryEnd->format('N') < 6) {
            $daysToAdd--;
        }
    }
    
    $dateToday = $now->format('d.m.');
    $datePrint = $printDate->format('d.m.');
    $dateDeliveryStr = $deliveryStart->format('d.m.') . ' – ' . $deliveryEnd->format('d.m.');
    $isTodayPrint = ($now->format('Y-m-d') === $printDate->format('Y-m-d'));
    ?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#16A34A">
<title>Vielen Dank — Versand bestätigt | KündigungExpress</title>
<style>
:root {
  --bg: #F7F9FC; --card: #FFFFFF; --text: #0F172A; --muted: #475569;
  --border: #E2E8F0; --primary: #16A34A; --primary-dark: #15803D; --primary-soft: #F0FDF4;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: Arial, sans-serif !important;
  background: var(--bg) !important;
  color: var(--text) !important;
  display: block !important;
}
.wrap { max-width: 600px; margin: 0 auto; padding: 32px 20px 8px; }
.success-card {
  background: var(--card); border: 1px solid var(--border);
  border-radius: 24px; padding: 36px 28px; text-align: center;
  box-shadow: 0 8px 32px rgba(22,163,74,0.08);
  margin-bottom: 16px;
}
.check-circle {
  width: 72px; height: 72px;
  background: linear-gradient(135deg, #16A34A, #22C55E);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 18px;
  font-size: 36px; color: #fff;
  box-shadow: 0 8px 20px rgba(22,163,74,0.3);
}
h1 {
  font-size: clamp(22px, 4vw, 28px);
  font-weight: 900; margin-bottom: 10px;
  letter-spacing: -0.01em;
}
.success-sub {
  font-size: 15px; color: var(--muted);
  line-height: 1.65; margin-bottom: 26px;
  max-width: 460px; margin-left: auto; margin-right: auto;
}

.timeline-wrap {
  background: var(--bg); border: 1px solid var(--border);
  border-radius: 16px; margin: 0 auto 26px; text-align: left;
  max-width: 460px;
}
.tl-header {
  background: #F8FAFC; padding: 12px 18px;
  border-bottom: 1px solid var(--border); border-radius: 16px 16px 0 0;
  display: flex; justify-content: space-between; align-items: center;
}
.tl-order { font-family: monospace; font-size: 13.5px; font-weight: 800; color: #475569; letter-spacing: 0.5px;}
.tl-tier { font-size: 11.5px; font-weight: 800; color: #166534; background: #DCFCE7; padding: 4px 10px; border-radius: 6px; border: 1px solid #BBF7D0; }
.tl-body { padding: 22px 18px 4px; }
.tl-step { display: flex; gap: 16px; margin-bottom: 24px; position: relative; }
.tl-step:not(:last-child)::after {
  content: ''; position: absolute; left: 15px; top: 34px; bottom: -20px;
  width: 2px; background: #E2E8F0;
}
.tl-icon {
  width: 32px; height: 32px; border-radius: 50%;
  background: #F1F5F9; border: 2px solid #CBD5E1;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px; flex-shrink: 0; position: relative; z-index: 2;
}
.tl-step.done .tl-icon { background: var(--primary); border-color: var(--primary); color: #fff; }
.tl-step.done:not(:last-child)::after { background: var(--primary); }
.tl-content { padding-top: 5px; }
.tl-content h3 { font-size: 14.5px; font-weight: 800; color: var(--text); margin-bottom: 4px; display: flex; flex-wrap: wrap; align-items: center; gap: 8px;}
.tl-date { font-weight: 700; font-size: 11.5px; color: var(--muted); background: #F1F5F9; padding: 2px 6px; border-radius: 6px; white-space: nowrap; }
.tl-step.done .tl-date { color: #166534; background: #DCFCE7; }
.tl-content p { font-size: 13px; color: var(--muted); line-height: 1.5; margin: 0; }
.tl-content p strong { color: var(--text); }

.btn-download {
  display: flex; align-items: center; justify-content: center; gap: 10px;
  background: #fff; color: var(--primary);
  border: 2px solid var(--primary);
  padding: 14px 22px; border-radius: 14px;
  font-weight: 800; font-size: 14.5px;
  text-decoration: none; cursor: pointer;
  transition: all 0.15s ease;
  margin: 0 auto;
  max-width: 360px;
}
.btn-download:hover { background: var(--primary-soft); transform: translateY(-1px); }
.back-link { margin-top: 16px; text-align: center; font-size: 13px; color: var(--muted); }
.back-link a { color: var(--primary); text-decoration: none; font-weight: 700; }

.ke-trust-strip { max-width: 880px; margin: 0 auto; padding: 16px 24px 8px; display: flex; flex-wrap: wrap; justify-content: center; gap: 12px 20px; font-size: 12px; color: #64748B; }
.ke-trust-strip span { display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }
.ke-footer { max-width: 880px; margin: 0 auto; padding: 8px 24px 24px; text-align: center; }
.ke-footer p { font-size: 12px; color: #94A3B8; line-height: 1.6; margin: 0 0 4px; text-align: center; }
.ke-footer a { color: #64748B; text-decoration: none; }
.ke-footer a:hover { color: #0F172A; text-decoration: underline; }

@media (max-width: 720px) {
  .wrap { padding: 24px 14px 8px; }
  .success-card { padding: 28px 20px; border-radius: 18px; }
  .check-circle { width: 60px; height: 60px; font-size: 30px; }
  .tl-header { padding: 12px 14px; }
  .tl-body { padding: 20px 14px 4px; }
  .ke-trust-strip { padding: 14px 16px 4px; gap: 8px 14px; font-size: 11px; }
  .ke-footer { padding: 4px 16px 20px; }
  .ke-footer p { font-size: 11.5px; }
  .btn-download { max-width: 100%; }
  .back-link a { display: inline-block; padding: 4px 2px; }
}
@media (max-width: 400px) {
  .tl-step { gap: 12px; }
  .tl-icon { width: 28px; height: 28px; font-size: 12px; }
  .tl-step:not(:last-child)::after { left: 13px; }
  .tl-content h3 { font-size: 13px; }
  .tl-header { flex-wrap: wrap; gap: 6px; }
  .success-sub { font-size: 14px; }
}
</style>
</head>
<body>
<div class="wrap">
  <div class="success-card">
    <div class="check-circle">✓</div>
    <h1>Erledigt — Kündigung ist auf dem Weg!</h1>
    <p class="success-sub">
      Ihre Kündigung an <strong><?= htmlspecialchars($anbieter) ?></strong> wird jetzt gedruckt und per Post versendet. Die Bestätigung kommt gleich per E-Mail.
    </p>

    <?php if (!$lxQueued): ?>
    <div style="background: #FEF2F2; border: 1px solid #FECACA; border-radius: 12px; padding: 12px 16px; margin: -10px auto 20px; max-width: 460px; text-align: left; font-size: 13px; color: #991B1B;">
        ⚠️ <strong>Hinweis:</strong> Die Zahlung war erfolgreich, aber die System-Übergabe verzögert sich kurz. Wir kümmern uns umgehend manuell darum.
    </div>
    <?php endif; ?>

    <div class="timeline-wrap">
      <div class="tl-header">
        <?php if ($orderNumber !== ''): ?>
          <span class="tl-order"><?= htmlspecialchars($orderNumber) ?></span>
        <?php else: ?>
          <span class="tl-order">Kündigung</span>
        <?php endif; ?>
        <span class="tl-tier"><?= htmlspecialchars($tierLabel) ?></span>
      </div>
      <div class="tl-body">
        
        <div class="tl-step done">
          <div class="tl-icon">✓</div>
          <div class="tl-content">
            <h3>Bestellung bestätigt <span class="tl-date">Heute, <?= $dateToday ?></span></h3>
            <p>Zahlung erfolgreich. Dokument wurde generiert.</p>
          </div>
        </div>

        <div class="tl-step <?= $isTodayPrint ? 'done' : '' ?>">
          <div class="tl-icon">🖨️</div>
          <div class="tl-content">
            <h3>Druck & Postübergabe <span class="tl-date"><?= $isTodayPrint ? 'Heute, ' : '' ?><?= $datePrint ?></span></h3>
            <p>Das Schreiben wird gedruckt, kuvertiert und an die Deutsche Post übergeben.</p>
          </div>
        </div>

        <div class="tl-step">
          <div class="tl-icon">📬</div>
          <div class="tl-content">
            <h3>Zustellung <span class="tl-date"><?= $dateDeliveryStr ?></span></h3>
            <p>Voraussichtliche Zustellung bei <strong><?= htmlspecialchars($anbieter) ?></strong>.</p>
            <?php if ($tier === 'einschreiben'): ?>
            <p style="margin-top: 4px; font-size: 12px; color: var(--primary-dark); font-weight: 600;">📬 Tracking-Link folgt per E-Mail, sobald der Brief bei der Post eingescannt wird (1–2 Werktage).</p>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
    <a class="btn-download" href="<?= htmlspecialchars($downloadUrl) ?>" target="_blank">
      📄 Ihre Kündigung als PDF sichern
    </a>
<div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #E2E8F0;">
        <p style="font-size: 13px; color: #64748B; margin-bottom: 12px;">Keine Bestätigungs-E-Mail erhalten? Auch im Spam-Ordner suchen.</p>
        <button id="resendEmailBtn" onclick="resendEmail('<?= htmlspecialchars($orderNumber) ?>')" 
                style="background: #ffffff; border: 1px solid #CBD5E1; color: #475569; padding: 10px 18px; border-radius: 10px; font-size: 13.5px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
            🔄 PDF erneut per E-Mail senden
        </button>
        <div id="resendStatus" style="margin-top: 12px; font-size: 13px; font-weight: 700;"></div>
    </div>
  </div>

  <div class="back-link">
    Weiteren Vertrag kündigen?
    <a href="/formular.php?type=handy">📱 Handyvertrag</a> ·
    <a href="/formular.php?type=kfz">🚗 KFZ-Versicherung</a> ·
    <a href="/formular.php?type=fitness">🏋️ Fitness</a>
  </div>
</div>

<div class="ke-trust-strip">
  <span>🔒 SSL-verschlüsselt</span>
  <span>🛡️ DSGVO-konform</span>
  <span>⚖️ Muster-Vorlage, keine Rechtsberatung</span>
</div>

<footer class="ke-footer">
  <p>© 2026 KündigungExpress · <a href="/impressum.html">Impressum</a> · <a href="/datenschutz.html">Datenschutz</a> · <a href="/agb.html">AGB</a> · <a href="/hilfe.html">Hilfe</a> · <a href="#" onclick="return keResetConsent(event)">Cookie-Einstellungen</a></p>
</footer>
<script>
function keResetConsent(e) {
  if (e && e.preventDefault) e.preventDefault();
  try {
    localStorage.removeItem('cookieConsent');
    document.cookie = 'cookieConsent=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
  } catch(err) {}
  location.reload();
  return false;
}
function resendEmail(orderNo) {
    const btn = document.getElementById('resendEmailBtn');
    const status = document.getElementById('resendStatus');
    
    btn.disabled = true;
    btn.style.opacity = "0.5";
    status.innerText = "Sende E-Mail...";

    fetch('/resend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ orderNumber: orderNo })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            status.innerText = "✅ E-Mail wurde erfolgreich erneut gesendet!";
            status.style.color = "#16A34A";
            btn.style.display = "none";
        } else {
            status.innerText = "❌ Fehler: " + (data.message || "Unbekannt");
            status.style.color = "#DC2626";
            btn.disabled = false;
            btn.style.opacity = "1";
        }
    })
    .catch(() => {
        status.innerText = "❌ Verbindung fehlgeschlagen.";
        status.style.color = "#DC2626";
        btn.disabled = false;
        btn.style.opacity = "1";
    });
}
</script>
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
    'event': 'purchase',
    'transaction_id': '<?= htmlspecialchars($orderNumber) ?>',
    'value': <?= $tier === 'einschreiben' ? '8.99' : '3.99' ?>,
    'currency': 'EUR',
    'items': [{
        'item_id': '<?= htmlspecialchars($tier) ?>',
        'item_name': '<?= $tier === "einschreiben" ? "Einwurfeinschreiben" : "Standardbrief" ?>',
        'price': <?= $tier === 'einschreiben' ? '8.99' : '3.99' ?>,
        'quantity': 1
    }]
});
</script>
</body>
</html>
    <?php
}