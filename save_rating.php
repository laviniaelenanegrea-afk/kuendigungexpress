<?php
declare(strict_types=1);

// Headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Helper pentru a returna erori și a opri execuția
function send_response(array $data): void {
    echo json_encode($data);
    exit;
}

// Citim payload-ul trimis prin fetch (AJAX)
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data) || !isset($data['rating'])) {
    send_response(['success' => false, 'error' => 'invalid_payload']);
}

$rating = (int)$data['rating'];

// Validare de bază
if ($rating < 1 || $rating > 5) {
    send_response(['success' => false, 'error' => 'invalid_rating']);
}

$file = __DIR__ . '/_ratings.json';

// Verificare write permission ÎNAINTE de a încerca scrierea
// Dacă fișierul nu există, verificăm dacă putem scrie în director
if (!file_exists($file)) {
    if (!is_writable(__DIR__)) {
        // Director ne-scriibil — returnăm eroare clară
        send_response([
            'success' => false,
            'error' => 'directory_not_writable',
            'hint' => 'Verifică chmod pe directorul root (trebuie 755 sau 775)'
        ]);
    }
} else {
    if (!is_writable($file)) {
        send_response([
            'success' => false,
            'error' => 'file_not_writable',
            'hint' => 'Rulează chmod 666 pe _ratings.json'
        ]);
    }
}

// Baseline pentru prima utilizare
$stats = [
    'total_score' => 264.6, // 4.9 * 54
    'count'       => 54
];

// Dacă fișierul există, încărcăm datele existente
if (file_exists($file)) {
    $content = @file_get_contents($file);
    if ($content !== false) {
        $current_data = json_decode($content, true);
        if (is_array($current_data) && isset($current_data['count'], $current_data['total_score'])) {
            $stats = $current_data;
        }
    }
}

// Update math
$stats['total_score'] += $rating;
$stats['count'] += 1;

// Calculăm noua medie
$new_average = round($stats['total_score'] / $stats['count'], 1);

// Salvăm — VERIFICĂM return value
$write_result = @file_put_contents($file, json_encode($stats), LOCK_EX);

if ($write_result === false) {
    send_response([
        'success' => false,
        'error'   => 'write_failed',
        'hint'    => 'PHP nu a putut scrie în _ratings.json. Verifică permisiunile.'
    ]);
}

// Succes
send_response([
    'success'     => true,
    'new_average' => number_format($new_average, 1, ',', '.'),
    'new_count'   => $stats['count']
]);
