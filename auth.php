<?php
/**
 * احراز هویت ساده با رمز عبور (session-based).
 * رمز فقط یک بار (بار اول) در صفحه‌ی setup.php ساخته و به‌صورت hash ذخیره می‌شود.
 * فایل رمز: config/credentials.php  →  <?php return ['hash' => '...'];
 */

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $secure,
    ]);
    session_name('ABZARSESS');
    session_start();
}

if (!defined('ABZAR_CRED_FILE')) {
    define('ABZAR_CRED_FILE', __DIR__ . '/config/credentials.php');
}

/** hash رمز ذخیره‌شده را برمی‌گرداند یا null اگر هنوز رمزی ست نشده. */
function abzar_stored_hash(): ?string
{
    if (!is_file(ABZAR_CRED_FILE)) {
        return null;
    }
    $c = include ABZAR_CRED_FILE;
    return (is_array($c) && !empty($c['hash'])) ? $c['hash'] : null;
}

/** آیا رمزی تنظیم شده است؟ */
function abzar_password_is_set(): bool
{
    return abzar_stored_hash() !== null;
}

/** ذخیره‌ی رمز جدید (hash). */
function abzar_save_password(string $plain): bool
{
    $dir = dirname(ABZAR_CRED_FILE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $php  = "<?php\nreturn " . var_export(['hash' => $hash], true) . ";\n";
    return file_put_contents(ABZAR_CRED_FILE, $php, LOCK_EX) !== false;
}

/** بررسی درستی رمز واردشده. */
function abzar_verify_password(string $plain): bool
{
    $hash = abzar_stored_hash();
    return $hash !== null && password_verify($plain, $hash);
}

/** آیا کاربر وارد شده است؟ */
function abzar_is_logged_in(): bool
{
    return !empty($_SESSION['abzar_auth']);
}

/** کاربر را وارد‌شده علامت بزن. */
function abzar_login(): void
{
    session_regenerate_id(true);
    $_SESSION['abzar_auth'] = true;
}

/** خروج. */
function abzar_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * محافظت از صفحه: اگر وارد نشده باشد، به login (یا setup در بار اول) هدایت می‌شود.
 */
function abzar_require_auth(): void
{
    if (abzar_is_logged_in()) {
        return;
    }
    if (!abzar_password_is_set()) {
        header('Location: setup.php');
        exit;
    }
    header('Location: login.php');
    exit;
}

/** آیا «حالت عمومی» فعال است؟ (config/site.php → ['public' => bool]) */
function abzar_public_mode(): bool
{
    static $public = null;
    if ($public === null) {
        $f = __DIR__ . '/config/site.php';
        $c = is_file($f) ? include $f : [];
        $public = is_array($c) && !empty($c['public']);
    }
    return $public;
}

/**
 * محافظت از صفحات ابزار: در حالت عمومی بدون ورود هم باز می‌شوند؛
 * وگرنه مثل قبل ورود لازم است. (صفحات مدیریتی همچنان abzar_require_auth)
 */
function abzar_require_page(): void
{
    if (abzar_public_mode() || abzar_is_logged_in()) {
        return;
    }
    abzar_require_auth();
}

/** آیا این نشست کپچا را در یک ساعت اخیر حل کرده است؟ */
function abzar_captcha_passed(): bool
{
    return !empty($_SESSION['abzar_captcha_ok'])
        && time() - (int)$_SESSION['abzar_captcha_ok'] < 3600;
}
