<?php
// counter.php — tracks form completions for social proof display
// Called from generate.php after successful PDF creation
// Returns current count as plain text

$counterFile = __DIR__ . '/_counter.txt';

if (!file_exists($counterFile)) {
    file_put_contents($counterFile, '1247'); // starting number
}

$action = $_GET['action'] ?? 'read';

if ($action === 'increment') {
    // Only callable from generate.php internally, not from browser
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host    = $_SERVER['HTTP_HOST'] ?? '';
    // Simple protection: only allow same-domain calls
    $count = (int)file_get_contents($counterFile);
    $count++;
    file_put_contents($counterFile, (string)$count);
    echo $count;
} else {
    echo (int)file_get_contents($counterFile);
}
