<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use AtrId\Auth\Config;
use AtrId\Auth\OAuthClient;

session_start();

$config = new Config([
    'client_id' => 'PASTE_CLIENT_ID',
    'client_secret' => 'PASTE_CLIENT_SECRET',
    'redirect_uri' => 'https://your-site.tld/examples/basic_flow.php',
]);

$oauth = new OAuthClient($config);

if (isset($_GET['start'])) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['atr_state'] = $state;
    header('Location: ' . $oauth->buildAuthorizeUrl($state));
    exit;
}

$callback = $oauth->parseCallback($_GET);

if ($callback['error'] !== '') {
    echo 'OAuth error: ' . htmlspecialchars($callback['error'], ENT_QUOTES, 'UTF-8');
    exit;
}

if ($callback['code'] !== '') {
    if (!hash_equals((string)($_SESSION['atr_state'] ?? ''), $callback['state'])) {
        echo 'Invalid state';
        exit;
    }
    unset($_SESSION['atr_state']);

    $token = $oauth->exchangeCodeForToken($callback['code']);
    $profile = $oauth->resolveUserProfile($token);

    echo '<pre>';
    echo "Token:\n" . htmlspecialchars(json_encode($token, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') . "\n\n";
    echo "Profile:\n" . htmlspecialchars(json_encode($profile, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8');
    echo '</pre>';
    exit;
}

?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ATR-ID SDK Example</title>
</head>
<body>
    <h1>ATR-ID SDK Example</h1>
    <a href="?start=1">Login with ATR-ID</a>
</body>
</html>
