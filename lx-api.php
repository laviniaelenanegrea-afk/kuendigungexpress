<?php
declare(strict_types=1);

/**
 * LetterXpress API v3 — wrapper pentru trimitere scrisori
 * 
 * Endpoint: POST https://api.letterxpress.de/v3/printjobs
 * Doc: https://www.letterxpress.de/versandwege/api
 * 
 * Folosit din /versand-success.php după ce plata Stripe e confirmată.
 * 
 * Funcția returnează array structured:
 *   ['ok' => true,  'jobId' => '12345', 'message' => '...', 'response' => [...] ]
 *   ['ok' => false, 'jobId' => null,    'message' => 'error desc', 'response' => [...] ]
 * 
 * NIMIC nu se trimite la LX dacă pdfPath nu există sau form-ul e incomplet.
 */

/**
 * Trimite o scrisoare la LetterXpress
 * 
 * @param string $pdfPath    Path absolut la fișierul PDF generat (cu signature embedded)
 * @param array  $form       Form data (cu adresa destinatarului)
 * @param string $tier       'standard' sau 'einschreiben'
 * @param array  $lxConfig   Config-ul încărcat din _lx_config.php
 * @return array             ['ok' => bool, 'jobId' => string|null, 'message' => string, 'response' => array|null]
 */
function sendToLetterXpress(string $pdfPath, array $form, string $tier, array $lxConfig): array {

    // Validare PDF
    if (!file_exists($pdfPath) || !is_readable($pdfPath)) {
        return ['ok' => false, 'jobId' => null, 'message' => 'PDF file not found: ' . $pdfPath, 'response' => null];
    }

    $pdfBytes = file_get_contents($pdfPath);
    if ($pdfBytes === false || strlen($pdfBytes) < 100) {
        return ['ok' => false, 'jobId' => null, 'message' => 'PDF file empty or unreadable', 'response' => null];
    }

    $base64 = base64_encode($pdfBytes);
    $checksum = md5($base64); // LX cere md5 din string-ul base64 (nu din PDF binary)

    // Validare config
    $username = trim((string)($lxConfig['username'] ?? ''));
    $apikey   = trim((string)($lxConfig['apikey'] ?? ''));
    $mode     = trim((string)($lxConfig['mode'] ?? 'test'));
    $endpoint = rtrim((string)($lxConfig['endpoint'] ?? 'https://api.letterxpress.de/v3/'), '/') . '/';

    if ($username === '' || $apikey === '') {
        return ['ok' => false, 'jobId' => null, 'message' => 'LX credentials missing', 'response' => null];
    }
    if (!in_array($mode, ['test', 'live'], true)) {
        return ['ok' => false, 'jobId' => null, 'message' => 'LX mode invalid', 'response' => null];
    }

    // Tier → registered code
    $tierRegistered = $lxConfig['tier_registered'] ?? [];
    $registered = $tierRegistered[$tier] ?? null;

    // Specification
    $spec = $lxConfig['specification'] ?? [
        'color' => '1', 'mode' => 'simplex', 'shipping' => 'national'
    ];

    // Filename pentru reference (apare în Warenkorb LX, ușor de identificat)
    $anbieter = preg_replace('/[^A-Za-z0-9_-]/', '_', substr((string)($form['anbieter'] ?? $form['studio'] ?? 'Kuendigung'), 0, 30));
    $filename = 'Kuendigung_' . $anbieter . '_' . date('Ymd_His') . '.pdf';

    // Construim payload
    $payload = [
        'auth' => [
            'username' => $username,
            'apikey'   => $apikey,
            'mode'     => $mode,
        ],
        'letter' => [
            'base64_file'          => $base64,
            'base64_file_checksum' => $checksum,
            'specification'        => $spec,
            'filename_original'    => $filename,
        ],
    ];

    // Adăugăm registered DOAR pentru Einschreiben
    if ($registered !== null && $registered !== '') {
        $payload['letter']['registered'] = $registered; // 'r1' = Einwurfeinschreiben
    }

    // POST request via cURL
    $url = $endpoint . 'printjobs';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => 'KuendigungExpress/1.0 (+https://www.kuendigungexpress.de)',
    ]);

    $rawResponse = curl_exec($ch);
    $httpCode    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError   = curl_error($ch);
    curl_close($ch);

    if ($rawResponse === false) {
        return [
            'ok'       => false,
            'jobId'    => null,
            'message'  => 'cURL error: ' . $curlError,
            'response' => null,
        ];
    }

    $decoded = json_decode((string)$rawResponse, true);

    // LX răspunde de obicei cu structură:
    //   {"status": 200, "message": "OK", "data": {"letter_id": "12345", "amount": 0.96, ...}}
    // sau eroare:
    //   {"status": 400, "message": "Error description"}
    
    if ($httpCode < 200 || $httpCode >= 300 || !is_array($decoded)) {
        return [
            'ok'       => false,
            'jobId'    => null,
            'message'  => 'LX API error HTTP ' . $httpCode . ': ' . ($decoded['message'] ?? substr((string)$rawResponse, 0, 200)),
            'response' => $decoded ?: ['raw' => substr((string)$rawResponse, 0, 500)],
            'httpCode' => $httpCode,
        ];
    }

    // Acceptăm și status: 200 explicit în body
    $statusOk = isset($decoded['status']) ? ((int)$decoded['status'] === 200) : true;
    if (!$statusOk) {
        return [
            'ok'       => false,
            'jobId'    => null,
            'message'  => 'LX rejected: ' . ($decoded['message'] ?? 'unknown'),
            'response' => $decoded,
            'httpCode' => $httpCode,
        ];
    }

    // Extragem job ID — LX returnează în 'data' obiect
    $data = $decoded['data'] ?? [];
    $jobId = (string)($data['letter_id'] ?? $data['id'] ?? $data['printjob_id'] ?? '');

    return [
        'ok'       => true,
        'jobId'    => $jobId !== '' ? $jobId : null,
        'message'  => 'Letter accepted: ' . ($decoded['message'] ?? 'OK'),
        'response' => $decoded,
        'httpCode' => $httpCode,
    ];
}

/**
 * Verifică statusul unui printjob existent și extrage tracking number dacă e disponibil.
 *
 * LX API v3: GET /v3/printjobs/{id} cu auth în body (curl trimite body si pe GET)
 * Status-uri posibile: 'neu', 'in_produktion', 'produziert', 'versandt', 'zugestellt', 'error'
 * Sendungsnummer: disponibil când status = 'versandt' sau 'zugestellt' (doar pentru Einschreiben)
 *
 * @return array [
 *   'ok'              => bool,
 *   'status'          => string|null,     // statusul jobului la LX
 *   'sendungsnummer'  => string|null,     // tracking number Deutsche Post
 *   'tracking_url'    => string|null,     // link direct Deutsche Post
 *   'message'         => string,
 *   'response'        => array|null,
 * ]
 */
function getLxJobStatus(string $jobId, array $lxConfig): array {
    if ($jobId === '') {
        return ['ok' => false, 'status' => null, 'sendungsnummer' => null, 'tracking_url' => null, 'message' => 'No job ID provided', 'response' => null];
    }

    $username = trim((string)($lxConfig['username'] ?? ''));
    $apikey   = trim((string)($lxConfig['apikey'] ?? ''));
    $mode     = trim((string)($lxConfig['mode'] ?? 'live'));
    $endpoint = rtrim((string)($lxConfig['endpoint'] ?? 'https://api.letterxpress.de/v3/'), '/') . '/';

    if ($username === '' || $apikey === '') {
        return ['ok' => false, 'status' => null, 'sendungsnummer' => null, 'tracking_url' => null, 'message' => 'LX credentials missing', 'response' => null];
    }

    $url = $endpoint . 'printjobs/' . urlencode($jobId);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        // LX v3: citirea unui job se face cu GET, dar auth merge tot in body (curl trimite body si pe GET)
        CURLOPT_CUSTOMREQUEST  => 'GET',
        CURLOPT_POSTFIELDS     => json_encode([
            'auth' => ['username' => $username, 'apikey' => $apikey, 'mode' => $mode],
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => 'KuendigungExpress/1.0 (+https://www.kuendigungexpress.de)',
    ]);

    $rawResponse = curl_exec($ch);
    $httpCode    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError   = curl_error($ch);
    curl_close($ch);

    if ($rawResponse === false || $curlError !== '') {
        return ['ok' => false, 'status' => null, 'sendungsnummer' => null, 'tracking_url' => null, 'message' => 'cURL error: ' . $curlError, 'response' => null];
    }

    $decoded = json_decode((string)$rawResponse, true);

    if ($httpCode < 200 || $httpCode >= 300 || !is_array($decoded)) {
        return ['ok' => false, 'status' => null, 'sendungsnummer' => null, 'tracking_url' => null,
            'message' => 'LX HTTP ' . $httpCode . ': ' . substr((string)$rawResponse, 0, 200), 'response' => $decoded];
    }

    $data = $decoded['data'] ?? $decoded;

    // Status la nivel de job: queue, hold, done, canceled, draft
    $jobStatus = (string)(
        $data['status'] ?? $data['letter_status'] ?? $data['state'] ?? ''
    );

    // LX v3: Sendungsnummer (Einschreiben) e in data.items[].tracking_code,
    // disponibil seara zilei de expediere. tracking_status e textul Deutsche Post.
    $sendungsnummer = '';
    $trackingStatus = '';
    if (isset($data['items']) && is_array($data['items'])) {
        foreach ($data['items'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $tc = trim((string)($item['tracking_code'] ?? ''));
            if ($tc !== '' && $tc !== '--') {
                $sendungsnummer = $tc;
                $trackingStatus = trim((string)($item['tracking_status'] ?? ''));
                break;
            }
        }
    }

    // Fallback pentru eventuale variante de raspuns (job fara items[])
    if ($sendungsnummer === '') {
        $sendungsnummer = trim((string)(
            $data['tracking_code'] ?? $data['sendungsnummer'] ??
            $data['tracking_number'] ?? $data['tracking'] ?? ''
        ));
    }
    if ($sendungsnummer === '--') {
        $sendungsnummer = '';
    }

    $trackingUrl = '';
    if ($sendungsnummer !== '') {
        // Deutsche Post tracking URL — pagina noua incarca direct statusul (param piececode).
        // Vechiul simpleQueryResult.html nu mai pre-completeaza numarul.
        $trackingUrl = 'https://www.deutschepost.de/de/s/sendungsverfolgung.html?piececode='
                     . urlencode($sendungsnummer);
    }

    return [
        'ok'              => true,
        'status'          => $jobStatus !== '' ? $jobStatus : null,
        'sendungsnummer'  => $sendungsnummer !== '' ? $sendungsnummer : null,
        'tracking_status' => $trackingStatus !== '' ? $trackingStatus : null,
        'tracking_url'    => $trackingUrl !== '' ? $trackingUrl : null,
        'message'         => 'OK',
        'response'        => $decoded,
    ];
}

/**
 * Trimite email de alertă către admin când LX call eșuează după Stripe payment confirmed.
 *
 * Scenario: plata a trecut, dar scrisoarea NU a fost pusă în coadă la LetterXpress.
 * User-ul primește email de "Versand bestätigt" cu mesaj de delay — Lavinia trebuie
 * să facă follow-up MANUAL: verifică LX Guthaben, re-upload PDF în LX dashboard,
 * sau apelează API direct cu PDF deja generat în /pdf/.
 *
 * Non-blocking: dacă alert-ul eșuează, scriptul principal continuă (doar log).
 *
 * @param string $sessionId   Stripe Checkout Session ID
 * @param string $orderNumber Order number KE-XXXXXX
 * @param array  $lxResult    Rezultatul de la sendToLetterXpress()
 * @param array  $form        Form data (anbieter, versandTier etc)
 * @param string $userEmail   Email-ul user-ului (pentru reference)
 * @param string $source      'webhook' sau 'success_page' — pentru debug
 */
function sendLxFailureAlert(
    string $sessionId,
    string $orderNumber,
    array  $lxResult,
    array  $form,
    string $userEmail,
    string $source = 'unknown'
): void {
    $smtpFile = __DIR__ . '/_smtp_config.php';
    if (!file_exists($smtpFile)) {
        error_log('[lx-alert] SMTP config missing, cannot send admin alert for ' . $orderNumber);
        return;
    }

    // Default admin email — încearcă să citească din _app_config dacă există
    $adminEmail = 'kontakt@kuendigungexpress.de';
    $appCfgFile = __DIR__ . '/_app_config.php';
    if (file_exists($appCfgFile)) {
        try {
            $appCfg = require $appCfgFile;
            if (is_array($appCfg) && !empty($appCfg['contact_email'])) {
                $adminEmail = (string)$appCfg['contact_email'];
            }
        } catch (\Throwable $e) {
            // continuă cu default
        }
    }

    try {
        $smtp = require $smtpFile;
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->Port       = (int)$smtp['port'];
        $mail->SMTPSecure = $smtp['encryption'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($smtp['username'], 'KE System Alert');
        $mail->addAddress($adminEmail);

        $anbieter   = (string)($form['anbieter'] ?? $form['studio'] ?? '?');
        $tier       = (string)($form['versandTier'] ?? '?');
        $tierLabel  = $tier === 'einschreiben' ? 'Einschreiben (8,99 €)' : 'Standard (3,99 €)';
        $orderKey   = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sessionId);
        $lxMessage  = (string)($lxResult['message'] ?? 'no message');
        $lxHttp     = isset($lxResult['httpCode']) ? (string)$lxResult['httpCode'] : '?';

        $mail->Subject = '[ALERT] LX Versand fehlgeschlagen — ' . $orderNumber
                       . ' (' . substr($anbieter, 0, 30) . ')';

        $mail->isHTML(true);
        $mail->Body = "
<div style='font-family: Arial, sans-serif; max-width: 620px; color: #1e293b; line-height: 1.5;'>
  <h2 style='color: #dc2626; margin: 0 0 12px;'>⚠️ LX Übergabe fehlgeschlagen</h2>
  <p style='font-size: 14px;'>
    Die Stripe-Zahlung wurde erfolgreich verarbeitet, aber die Übergabe an
    LetterXpress ist <strong>NICHT</strong> erfolgt. Der Kunde hat eine
    Bestätigungs-E-Mail mit Hinweis auf nachgeholten Versand erhalten.
    <br><br>
    <strong style='color: #dc2626;'>Manuelle Aktion erforderlich.</strong>
  </p>

  <table style='border-collapse: collapse; font-family: monospace; font-size: 13px; margin: 16px 0; width: 100%;'>
    <tr><td style='padding: 6px 12px 6px 0; color: #64748b; white-space: nowrap;'>Bestellnummer:</td>
        <td style='padding: 6px 0; font-weight: 700; color: #0f172a;'>" . htmlspecialchars($orderNumber) . "</td></tr>
    <tr><td style='padding: 6px 12px 6px 0; color: #64748b;'>Verarbeitet von:</td>
        <td style='padding: 6px 0;'>" . htmlspecialchars($source) . "</td></tr>
    <tr><td style='padding: 6px 12px 6px 0; color: #64748b; vertical-align: top;'>Session ID:</td>
        <td style='padding: 6px 0; word-break: break-all;'>" . htmlspecialchars($sessionId) . "</td></tr>
    <tr><td style='padding: 6px 12px 6px 0; color: #64748b;'>Empfänger:</td>
        <td style='padding: 6px 0;'>" . htmlspecialchars($anbieter) . "</td></tr>
    <tr><td style='padding: 6px 12px 6px 0; color: #64748b;'>Versandart:</td>
        <td style='padding: 6px 0;'>" . htmlspecialchars($tierLabel) . "</td></tr>
    <tr><td style='padding: 6px 12px 6px 0; color: #64748b;'>Kunden-E-Mail:</td>
        <td style='padding: 6px 0;'>" . htmlspecialchars($userEmail !== '' ? $userEmail : '(none)') . "</td></tr>
    <tr><td style='padding: 6px 12px 6px 0; color: #64748b;'>LX HTTP:</td>
        <td style='padding: 6px 0;'>" . htmlspecialchars($lxHttp) . "</td></tr>
    <tr><td style='padding: 6px 12px 6px 0; color: #64748b; vertical-align: top;'>LX Message:</td>
        <td style='padding: 6px 0; color: #dc2626;'>" . htmlspecialchars($lxMessage) . "</td></tr>
    <tr><td style='padding: 6px 12px 6px 0; color: #64748b;'>Zeit:</td>
        <td style='padding: 6px 0;'>" . date('Y-m-d H:i:s') . "</td></tr>
  </table>

  <div style='background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 12px 16px; margin-top: 16px;'>
    <strong style='color: #92400e;'>Recommended actions:</strong>
    <ol style='margin: 8px 0 0; padding-left: 20px; color: #78350f; font-size: 13.5px;'>
      <li>LX-Guthaben prüfen: <a href='https://www.letterxpress.de/' style='color: #92400e;'>letterxpress.de</a></li>
      <li>Order JSON öffnen: <code>_orders/" . htmlspecialchars($orderKey) . ".json</code></li>
      <li>PDF bereit unter: <code>/pdf/versand_" . htmlspecialchars($orderKey) . ".pdf</code></li>
      <li>Manuell hochladen ins LX-Web-Frontend oder erneut via API</li>
    </ol>
  </div>
</div>";

        $mail->AltBody = "LX Versand fehlgeschlagen\n"
            . "Bestellnummer: " . $orderNumber . "\n"
            . "Source: " . $source . "\n"
            . "Session: " . $sessionId . "\n"
            . "Anbieter: " . $anbieter . "\n"
            . "Tier: " . $tierLabel . "\n"
            . "Kunden-E-Mail: " . $userEmail . "\n"
            . "LX HTTP: " . $lxHttp . "\n"
            . "LX Message: " . $lxMessage . "\n"
            . "Zeit: " . date('Y-m-d H:i:s') . "\n\n"
            . "PDF: /pdf/versand_" . $orderKey . ".pdf\n"
            . "Order JSON: _orders/" . $orderKey . ".json";

        $mail->send();
        error_log('[lx-alert] Admin alert sent for failed LX call — order ' . $orderNumber);
    } catch (\Throwable $e) {
        error_log('[lx-alert] Failed to send admin alert for ' . $orderNumber . ': ' . $e->getMessage());
    }
}

/**
 * Verifică credit-ul disponibil (pentru low balance alert sau debug)
 * @return array ['ok' => bool, 'balance' => float|null, 'message' => string]
 */
function checkLxBalance(array $lxConfig): array {
    $username = (string)($lxConfig['username'] ?? '');
    $apikey   = (string)($lxConfig['apikey'] ?? '');
    $endpoint = rtrim((string)($lxConfig['endpoint'] ?? 'https://api.letterxpress.de/v3/'), '/') . '/';

    // GET /v3/balance cu auth în query string sau header — LX v3 acceptă auth ca params query
    $url = $endpoint . 'balance';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'auth' => ['username' => $username, 'apikey' => $apikey, 'mode' => $lxConfig['mode'] ?? 'test'],
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $raw = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'balance' => null, 'message' => 'HTTP ' . $httpCode];
    }
    $decoded = json_decode((string)$raw, true);
    $balance = (float)($decoded['data']['balance'] ?? $decoded['balance'] ?? 0);
    return ['ok' => true, 'balance' => $balance, 'message' => 'OK'];
}