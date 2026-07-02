<?php
header('Content-Type: application/json');

/* =========================================================
   CSRF — accept doar same-origin requests
   ========================================================= */
$fetchSite = (string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
$csrfOk = false;
if ($fetchSite !== '') {
    $csrfOk = in_array($fetchSite, ['same-origin', 'same-site'], true);
} else {
    // Fallback Referer pentru browsere foarte vechi
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
    error_log('[resend] CSRF block — Sec-Fetch-Site: ' . $fetchSite);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Anfrage abgelehnt.']);
    exit;
}

/* =========================================================
   RATE LIMIT — max 3 cereri/15 min per IP
   ========================================================= */
$clientIp = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$ipHash   = substr(hash('sha256', $clientIp), 0, 16);

$rateLimitDir = __DIR__ . '/_rate_limit';
if (!is_dir($rateLimitDir)) {
    @mkdir($rateLimitDir, 0700, true);
    @file_put_contents($rateLimitDir . '/.htaccess', "Require all denied\n");
}

$rateLimitFile = $rateLimitDir . '/resend_' . $ipHash . '.json';
$now           = time();
$windowSec     = 900;  // 15 minute
$maxRequests   = 3;

$rateData = ['count' => 0, 'windowStart' => $now];
if (file_exists($rateLimitFile)) {
    $tmp = json_decode((string)@file_get_contents($rateLimitFile), true);
    if (is_array($tmp) && isset($tmp['count'], $tmp['windowStart'])) {
        $rateData = $tmp;
    }
}

// Window expirat — reset
if ($now - (int)$rateData['windowStart'] > $windowSec) {
    $rateData = ['count' => 0, 'windowStart' => $now];
}

if ((int)$rateData['count'] >= $maxRequests) {
    $waitSec = $windowSec - ($now - (int)$rateData['windowStart']);
    $waitMin = (int)ceil($waitSec / 60);
    error_log('[resend] Rate limit hit for IP ' . $ipHash . ' (' . $rateData['count'] . ' in window)');
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Zu viele Anfragen. Bitte versuchen Sie es in ca. ' . $waitMin . ' Minuten erneut.',
    ]);
    exit;
}

// Incrementăm înainte de processing (atomic-ish — nu folosim flock pentru simplitate;
// race minimă pe IP single-user e acceptabilă)
$rateData['count']++;
@file_put_contents($rateLimitFile, json_encode($rateData));

/* =========================================================
   INPUT VALIDATION
   ========================================================= */
$data = json_decode(file_get_contents('php://input'), true);
$orderNumber = trim((string)($data['orderNumber'] ?? ''));

// Format așteptat: KE-XXXXXX (6 hex chars uppercase)
if (!preg_match('/^KE-[A-F0-9]{6}$/', $orderNumber)) {
    echo json_encode(['success' => false, 'message' => 'Verarbeitungsfehler.']);
    exit;
}

/* =========================================================
   1. Căutare comandă
   ========================================================= */
$foundOrder = null;
$foundFile  = null;
foreach (glob(__DIR__ . '/_orders/*.json') as $file) {
    $content = json_decode(file_get_contents($file), true);
    if (isset($content['orderNumber']) && (string)$content['orderNumber'] === $orderNumber) {
        $foundOrder = $content;
        $foundFile  = $file;
        break;
    }
}

if (!$foundOrder) {
    echo json_encode(['success' => false, 'message' => 'Bestellung nicht gefunden.']);
    exit;
}

/* =========================================================
   PER-ORDER COOLDOWN — max 1x/2 min pe comandă specifică
   Previne spam pe orders specifice chiar dacă atacatorul rotește IP
   ========================================================= */
$orderCooldown = 120; // 2 minute
$lastResendAt  = (int)($foundOrder['lastResendAt'] ?? 0);
if ($lastResendAt > 0 && ($now - $lastResendAt) < $orderCooldown) {
    $waitSec = $orderCooldown - ($now - $lastResendAt);
    error_log('[resend] Order cooldown for ' . $orderNumber . ' — ' . $waitSec . 's remaining');
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Bitte warten Sie ' . $waitSec . ' Sekunden bevor Sie erneut anfordern.',
    ]);
    exit;
}

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

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
    $mail->addAddress($foundOrder['userEmail']);
    
    // --- EXTRAGERE DATE COMANDĂ ---
    $tierLabel = ($foundOrder['tier'] ?? 'standard') === 'einschreiben' ? 'Einwurfeinschreiben' : 'Standardbrief';
    $tierPrice = ($foundOrder['tier'] ?? 'standard') === 'einschreiben' ? '8,99 €' : '3,99 €';
    $paymentIntent = $foundOrder['paymentIntent'] ?? ''; 
    $createdAt = $foundOrder['createdAt'] ?? time();
    
    // Provider name
    $studioName = '';
    foreach (['anbieter', 'studio', 'provider'] as $_k) {
        $_v = trim((string)($foundOrder[$_k] ?? ''));
        if ($_v !== '') { $studioName = $_v; break; }
    }
    
    // Fallback
    if ($studioName === '') {
        $type = $foundOrder['type'] ?? '';
        if ($type === 'handy') $studioName = 'Ihren Mobilfunkanbieter';
        elseif ($type === 'kfz') $studioName = 'Ihre KFZ-Versicherung';
        elseif ($type === 'fitness') $studioName = 'Ihr Fitnessstudio';
        else $studioName = 'Ihren Anbieter';
    }

    // Atașament cu nume standardizat
    $pdfPath = $_SERVER['DOCUMENT_ROOT'] . $foundOrder['downloadUrl'];
    $providerSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower((string)@iconv('UTF-8', 'ASCII//TRANSLIT', $studioName)));
    $providerSlug = trim($providerSlug, '-');
    if ($providerSlug === '') $providerSlug = 'anbieter';

    $mail->addAttachment($pdfPath, 'Kuendigung-' . $providerSlug . '.pdf');

    $mail->isHTML(true);
    $mail->Subject = 'Kopie: Kündigung an ' . $studioName . ' [' . $orderNumber . ']';

    $mail->Body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #1e293b; line-height: 1.6; background-color: #ffffff; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px;'>
        
        <div style='margin-bottom: 24px;'>
            <h2 style='margin: 0 0 12px 0; font-size: 22px; color: #0f172a; font-weight: 800; letter-spacing: -0.02em;'>Ihre angeforderte Kopie</h2>
            <p style='margin: 0; font-size: 15px; color: #475569;'>Guten Tag,<br><br>da Sie die Unterlagen erneut angefordert haben, senden wir Ihnen hiermit vereinbarungsgemäß eine vollständige Kopie Ihrer Kündigung für <strong>" . htmlspecialchars($studioName) . "</strong> als PDF-Anhang.</p>
        </div>

        <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px;'>
            <h3 style='margin: 0 0 14px 0; font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b;'>Zusammenfassung Ihrer Bestellung</h3>
            
            <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
                <tr>
                    <td style='padding: 6px 0; color: #64748b; width: 40%;'>Bestellnummer:</td>
                    <td style='padding: 6px 0; font-weight: 700; color: #0f172a; font-family: monospace; font-size: 15px;'>" . htmlspecialchars($orderNumber) . "</td>
                </tr>
                <tr>
                    <td style='padding: 6px 0; color: #64748b;'>Empfänger:</td>
                    <td style='padding: 6px 0; font-weight: 600; color: #0f172a;'>" . htmlspecialchars($studioName) . "</td>
                </tr>
                <tr>
                    <td style='padding: 6px 0; color: #64748b;'>Versandart:</td>
                    <td style='padding: 6px 0; color: #0f172a;'>" . htmlspecialchars($tierLabel) . " (" . htmlspecialchars($tierPrice) . ")</td>
                </tr>
                <tr>
                    <td style='padding: 6px 0; color: #64748b;'>Datum:</td>
                    <td style='padding: 6px 0; color: #0f172a;'>" . date('d.m.Y', $createdAt) . "</td>
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
        <div style='background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; padding: 16px; margin-bottom: 24px; font-size: 14px; color: #92400e; font-weight: 500;'>
            💡 <strong>Hinweis zur Zustellung:</strong> Sollte diese E-Mail erneut schwer zu finden sein, prüfen Sie bitte auch Ihren Werbe- oder Spam-Ordner und fügen Sie unsere Adresse zu Ihren Kontakten hinzu.
        </div>

        <div style='margin-bottom: 24px; padding-top: 8px;'>
            <h3 style='margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #0f172a;'>Wie geht es mit dem Brief weiter?</h3>
            <ol style='margin: 0; padding-left: 20px; font-size: 14px; color: #475569;'>
                <li style='margin-bottom: 8px;'>Ihr Schreiben wird ausgedruckt, kuvertiert und für den Versand vorbereitet.</li>
                <li style='margin-bottom: 8px;'>Der Versand erfolgt zuverlässig über unseren Partner per Deutsche Post.</li>
            </ol>
        </div>

        <div style='border-top: 1px solid #e2e8f0; padding-top: 20px; font-size: 14px; color: #475569;'>
            <p style='margin: 0 0 12px 0;'>Falls Sie weitere Fragen haben, können Sie einfach auf diese Nachricht antworten.</p>
            <p style='margin: 0; font-weight: 700; color: #0f172a;'>Mit freundlichen Grüßen,<br><span style='color: #16a34a;'>Ihr KündigungExpress-Team</span></p>
        </div>
        
        <div style='margin-top: 24px; padding-top: 12px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #94a3b8;'>
            <a href='https://kuendigungexpress.de' style='color: #94a3b8; text-decoration: none;'>kuendigungexpress.de</a>
        </div>
    </div>";
    
    $mail->send();

    // Înregistrez resend în order JSON pentru audit + cooldown enforcement
    if ($foundFile !== null) {
        $foundOrder['lastResendAt'] = $now;
        $resendLog = is_array($foundOrder['resendLog'] ?? null) ? $foundOrder['resendLog'] : [];
        $resendLog[] = ['ts' => $now, 'ipHash' => $ipHash];
        // Păstrăm maxim 20 entries pentru a evita order JSON umflat
        if (count($resendLog) > 20) {
            $resendLog = array_slice($resendLog, -20);
        }
        $foundOrder['resendLog'] = $resendLog;
        @file_put_contents($foundFile, json_encode($foundOrder, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('[resend] Email failed for ' . $orderNumber . ': ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'E-Mail konnte nicht gesendet werden.']);
}