<?php
/**
 * LetterXpress API configuration
 * 
 * Test mode: scrisorile NU se trimit. Apar în Warenkorb-ul LX (panou web).
 *            Acolo pot fi verificate manual (PDF + adresa), șterse sau trimise.
 *            Auto-curățare după 7 zile dacă rămân acolo.
 * Live mode: scrisorile sunt PROCESATE direct și trimise via Deutsche Post.
 * 
 * Schimbă 'mode' la 'live' DOAR după ce:
 * 1. Ai testat în test mode și verificat în Warenkorb LX că PDF + adresa sunt corecte
 * 2. Ai destul Guthaben în contul LX (verifică în panou sau via /v3/balance)
 * 3. Stripe e și el switch-uit la 'live' mode în _stripe_config.php
 */

return [
    'mode'     => 'live', // 'test' sau 'live'
    'username' => 'LXPApi72283',
    'apikey'   => 'e787a601448039b093ae0dbd8381c83ae0d48806882c4f3118355fce5529b51a',
    'endpoint' => 'https://api.letterxpress.de/v3/',

    // Specifications default pentru toate scrisori
    'specification' => [
        'color'    => '1',        // 1 = S/W (cel mai ieftin), 4 = Farbe
        'mode'     => 'simplex',  // 1 față (cel mai ieftin); 'duplex' = 2 fețe
        'shipping' => 'national', // Deutschland; 'international' costă mai mult
    ],

    // Mapare tier → registered code (registered API field LX)
    // Standard = fără registered (Standardsendung normală)
    // Einschreiben = r1 (Einschreiben Einwurf, exact ce vinde Lavinia la 8,99€)
    'tier_registered' => [
        'standard'     => null, // fără înregistrare
        'einschreiben' => 'r1', // Einwurfeinschreiben — match perfect pricing 3,41€ Aufschlag
    ],
];
