<?php
/**
 * محدودکننده نرخ درخواست (sliding window) بر اساس IP — فایل‌محور، بدون دیتابیس.
 * شمارنده‌ها در config/rl/ نگهداری می‌شوند (وب‌دسترسی config با .htaccess بسته است).
 */

function abzar_client_ip(): string
{
    // پشت CDN/پروکسی معتبر می‌توان X-Forwarded-For را هم بررسی کرد؛ پیش‌فرض امن: REMOTE_ADDR
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * اگر سقف پر شده باشد، پاسخ 429 (JSON) می‌دهد و خارج می‌شود.
 * @param string $bucket نام سطل (مثل 'ai' یا 'media')
 * @param int    $max    حداکثر درخواست در پنجره
 * @param int    $window طول پنجره به ثانیه
 */
function abzar_rate_limit(string $bucket, int $max, int $window): void
{
    $dir = __DIR__ . '/config/rl';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $key  = hash('sha256', $bucket . '|' . abzar_client_ip());
    $file = $dir . '/' . $key . '.json';
    $now  = time();

    $fh = @fopen($file, 'c+');
    if ($fh === false) return; // اگر نوشتنی نبود، مسدود نکن
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $ts  = json_decode($raw ?: '[]', true);
    if (!is_array($ts)) $ts = [];
    // فقط زمان‌های داخل پنجره را نگه دار
    $ts = array_values(array_filter($ts, fn($t) => is_int($t) && $t > $now - $window));

    if (count($ts) >= $max) {
        flock($fh, LOCK_UN);
        fclose($fh);
        $wait = ($ts[0] + $window) - $now;
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        header('Retry-After: ' . max(1, $wait));
        echo json_encode([
            'ok'      => false,
            'error'   => 'rate_limited',
            'message' => 'سقف استفاده پر شده — حدود ' . max(1, (int)ceil($wait / 60)) . ' دقیقه دیگر دوباره امتحان کن.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ts[] = $now;
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($ts));
    flock($fh, LOCK_UN);
    fclose($fh);

    // پاک‌سازی گاه‌به‌گاه فایل‌های کهنه (۱٪ درخواست‌ها)
    if (random_int(1, 100) === 1) {
        foreach ((glob($dir . '/*.json') ?: []) as $f) {
            if (filemtime($f) < $now - 86400) @unlink($f);
        }
    }
}
