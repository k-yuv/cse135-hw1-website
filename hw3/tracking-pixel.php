<?php
// tracking-pixel.php — Serves a 1x1 transparent GIF and logs the request

// Prevent caching so every page view generates a new request
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Set content type to GIF image
header('Content-Type: image/gif');

// The smallest valid GIF (43 bytes) — a 1x1 transparent pixel
// This is the GIF89a header + a single transparent pixel
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

// Log the hit (in production, write to a database or log file)
$data = [
    'timestamp' => date('c'),
    'ip'        => $_SERVER['REMOTE_ADDR'] ?? '',
    'ua'        => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'referer'   => $_SERVER['HTTP_REFERER'] ?? '',
    'page'      => $_GET['page'] ?? '',
    'type'      => $_GET['t'] ?? 'pageview',
    'language'  => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
];

// Append to a JSON Lines file
$logFile = __DIR__ . '/pixel-hits.jsonl';
file_put_contents($logFile, json_encode($data) . "\n", FILE_APPEND | LOCK_EX);

?>