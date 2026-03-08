# ATR-ID PHP SDK

Universal PHP OAuth2 client for ATR-ID.

## Repository Contents

- `src/` - SDK source classes
- `bootstrap.php` - autoloader for non-Composer projects
- `examples/basic_flow.php` - Composer-based example
- `examples/basic_flow_no_composer.php` - non-Composer example
- `instalation.txt` - detailed step-by-step installation and usage guide
- `CHANGELOG.md` - release notes
- `SECURITY.md` - security reporting policy

## Install

```bash
composer require atrcore/atr-id-php-sdk
```

If you are using this local folder directly:

```bash
cd atr-id-php-sdk
composer dump-autoload

## Use Without Composer

If you do not use Composer, include the bundled bootstrap autoloader:

```php
<?php
require __DIR__ . '/atr-id-php-sdk/bootstrap.php';
```

Then use classes normally:

```php
use AtrId\Auth\Config;
use AtrId\Auth\OAuthClient;
```

You can run the bundled non-Composer example directly:

`examples/basic_flow_no_composer.php`
```

## Quick Start

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use AtrId\Auth\Config;
use AtrId\Auth\OAuthClient;

session_start();

$config = new Config([
    'client_id' => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    'redirect_uri' => 'https://example.com/callback.php',
    // Optional:
    // 'authorize_url' => 'https://id.atrcore.live/authorize.php',
    // 'token_url' => 'https://id.atrcore.live/api_oauth_token.php',
    // 'userinfo_url' => 'https://id.atrcore.live/api_oauth_userinfo.php',
]);

$oauth = new OAuthClient($config);

$state = bin2hex(random_bytes(16));
$_SESSION['atr_state'] = $state;

header('Location: ' . $oauth->buildAuthorizeUrl($state));
exit;
```

## Callback Handler

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use AtrId\Auth\Config;
use AtrId\Auth\OAuthClient;

session_start();

$config = new Config([
    'client_id' => 'YOUR_CLIENT_ID',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    'redirect_uri' => 'https://example.com/callback.php',
]);

$oauth = new OAuthClient($config);
$callback = $oauth->parseCallback($_GET);

if ($callback['error'] !== '') {
    exit('OAuth error: ' . $callback['error']);
}

if ($callback['code'] === '' || $callback['state'] === '') {
    exit('Invalid callback payload');
}

if (!hash_equals((string)($_SESSION['atr_state'] ?? ''), $callback['state'])) {
    exit('Invalid state');
}
unset($_SESSION['atr_state']);

$token = $oauth->exchangeCodeForToken($callback['code']);
$profile = $oauth->resolveUserProfile($token);

// $profile contains at least email when successful.
echo 'Login success for: ' . htmlspecialchars((string)$profile['email'], ENT_QUOTES, 'UTF-8');
```

## Notes

- Includes fallback parsing for callbacks like:
  - `/?atr_id_callback=1?code=...&state=...`
- Sends `redirect_uri` during token exchange for provider compatibility.
- Works without WordPress.
