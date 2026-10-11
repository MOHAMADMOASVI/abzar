<?php
/**
 * پروکسی کوچک برای خواندن HTML یک صفحه (برای پیش‌نمایش متا/OG).
 * فقط http/https عمومی؛ IP های خصوصی و لوکال مسدود.
 */
require __DIR__ . '/auth.php';
require __DIR__ . '/ratelimit.php';
abzar_require_page();
abzar_rate_limit('fetch', abzar_is_logged_in() ? 120 : 30, 3600);

header('Content-Type: application/json; charset=utf-8');

function out($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

$url = trim($_GET['url'] ?? '');
if ($url === '') out(['ok' => false, 'error' => 'آدرس خالی است']);
if (!preg_match('~^https?://~i', $url)) $url = 'https://' . $url;

$parts = parse_url($url);
if (!$parts || empty($parts['host'])) out(['ok' => false, 'error' => 'آدرس معتبر نیست']);

$host = $parts['host'];
$ips = gethostbynamel($host) ?: [];
if (!$ips) out(['ok' => false, 'error' => 'دامنه پیدا نشد']);
foreach ($ips as $ip) {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        out(['ok' => false, 'error' => 'دسترسی به آدرس‌های داخلی مجاز نیست']);
    }
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 4,
    CURLOPT_TIMEOUT        => 12,
    CURLOPT_CONNECTTIMEOUT => 6,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; AbzarMetaPreview/1.0)',
    CURLOPT_RANGE          => '0-524287', // حداکثر ~۵۱۲KB
    CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
    CURLOPT_REDIR_PROTOCOLS=> CURLPROTO_HTTP | CURLPROTO_HTTPS,
]);
$body = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

if ($body === false) out(['ok' => false, 'error' => 'دریافت نشد: ' . $err]);
if ($code >= 400) out(['ok' => false, 'error' => 'کد HTTP ' . $code]);

// فقط بخش head برای پارس متاها کافی است
if (mb_strlen($body) > 300000) $body = mb_substr($body, 0, 300000);
out(['ok' => true, 'html' => $body, 'finalUrl' => $final]);
