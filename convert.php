<?php
/**
 * ابزار — تبدیل و کاهش حجم تصویر (خروجی: WebP / JPG / PNG)
 *
 * خواندن ورودی:
 *  - فرمت‌هایی که GD می‌خوانَد (JPEG/PNG/GIF/WEBP/BMP) → مستقیم با GD (همه‌جا کار می‌کند).
 *    برای JPEG جهت عکس از روی EXIF اصلاح می‌شود.
 *  - بقیه (HEIC/TIFF/…) → اول Imagick، بعد sips؛ فقط اگر روی سرور موجود باشند.
 */

ini_set('memory_limit', '1536M');
ini_set('display_errors', '0');
set_time_limit(300);

/** جهت عکس JPEG را بر اساس EXIF اصلاح می‌کند. */
function abzar_apply_exif_orientation($img, string $srcPath)
{
    if (!function_exists('exif_read_data')) {
        return $img;
    }
    try {
        $exif = @exif_read_data($srcPath);
    } catch (Throwable $e) {
        return $img;
    }
    $o = ($exif && !empty($exif['Orientation'])) ? (int) $exif['Orientation'] : 1;
    if ($o <= 1) {
        return $img;
    }
    switch ($o) {
        case 2: imageflip($img, IMG_FLIP_HORIZONTAL); break;
        case 3: $img = imagerotate($img, 180, 0); break;
        case 4: imageflip($img, IMG_FLIP_VERTICAL); break;
        case 5: $img = imagerotate($img, -90, 0); imageflip($img, IMG_FLIP_HORIZONTAL); break;
        case 6: $img = imagerotate($img, -90, 0); break;
        case 7: $img = imagerotate($img, 90, 0); imageflip($img, IMG_FLIP_HORIZONTAL); break;
        case 8: $img = imagerotate($img, 90, 0); break;
    }
    return $img;
}

/** فرمت‌هایی که GD نمی‌خوانَد را با Imagick یا sips به منبع GD تبدیل می‌کند. */
function abzar_decode_fallback(string $srcPath, string $origName)
{
    if (class_exists('Imagick')) {
        try {
            $im = new Imagick();
            $im->readImage($srcPath);
            if (method_exists($im, 'autoOrient')) {
                @$im->autoOrient();
            }
            $im->setImageFormat('png');
            $blob = $im->getImageBlob();
            $im->clear();
            $g = @imagecreatefromstring($blob);
            if ($g) {
                return $g;
            }
        } catch (Throwable $e) {
        }
    }
    if (function_exists('exec')) {
        $sipsTmp = tempnam(sys_get_temp_dir(), 'abzar_') . '.png';
        $cmd = 'sips -s format png ' . escapeshellarg($srcPath)
             . ' --out ' . escapeshellarg($sipsTmp) . ' 2>&1';
        @exec($cmd, $out, $ret);
        if ($ret === 0 && is_file($sipsTmp) && filesize($sipsTmp) > 0) {
            $g = @imagecreatefrompng($sipsTmp);
            @unlink($sipsTmp);
            if ($g) {
                return $g;
            }
        }
        if (is_file($sipsTmp)) {
            @unlink($sipsTmp);
        }
    }
    throw new RuntimeException('این فرمت روی این سرور پشتیبانی نشد (مثلاً HEIC به Imagick با libheif نیاز دارد): ' . $origName);
}

/** تصویر را روی پس‌زمینه‌ی سفید صاف می‌کند (برای خروجی JPEG که شفافیت ندارد). */
function abzar_flatten($img)
{
    $w = imagesx($img);
    $h = imagesy($img);
    $canvas = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefilledrectangle($canvas, 0, 0, $w, $h, $white);
    imagealphablending($canvas, true);
    imagecopy($canvas, $img, 0, 0, 0, 0, $w, $h);
    return $canvas;
}

/** تصویر GD را با فرمت و کیفیت داده‌شده کدگذاری می‌کند و بایت‌ها را برمی‌گرداند. */
function abzar_encode($img, string $format, int $quality): string
{
    ob_start();
    switch ($format) {
        case 'jpg':
            $flat = abzar_flatten($img);
            $ok = imagejpeg($flat, null, $quality);
            imagedestroy($flat);
            break;
        case 'png':
            imagealphablending($img, false);
            imagesavealpha($img, true);
            // PNG بی‌اتلاف است؛ سطح فشرده‌سازی همیشه بیشینه تا کمترین حجم به دست آید
            $ok = imagepng($img, null, 9);
            break;
        case 'webp':
        default:
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $ok = imagewebp($img, null, $quality);
            break;
    }
    $bytes = ob_get_clean();
    if (!$ok || $bytes === '' || $bytes === false) {
        throw new RuntimeException('ساخت فایل خروجی ناموفق بود.');
    }
    return $bytes;
}

/**
 * هسته‌ی تبدیل.
 * @param int $clientOrigSize اندازه‌ی فایل اصلی سمت کاربر (وقتی مرورگر قبل از آپلود
 *                            تبدیل کرده — مثل HEIC→JPEG — از اندازه‌ی آپلودی کوچک‌تر است).
 * @return array{0:string,1:string,2:string}  [بایت‌ها، نام خروجی، mime]
 * @throws RuntimeException
 */
function abzar_convert(string $srcPath, string $origName, int $quality, int $maxDim, string $format, int $clientOrigSize = 0): array
{
    if (!is_file($srcPath) || filesize($srcPath) === 0) {
        throw new RuntimeException('فایل ورودی خالی یا نامعتبر است.');
    }

    $quality = max(1, min(100, $quality));
    $maxDim  = max(0, $maxDim);
    $format  = strtolower($format);
    if (!in_array($format, ['webp', 'jpg', 'png'], true)) {
        $format = 'webp';
    }

    $data = file_get_contents($srcPath);
    $info = @getimagesizefromstring($data);
    $type = $info ? $info[2] : null;

    $gdReadable = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP, IMAGETYPE_BMP];

    if (in_array($type, $gdReadable, true)) {
        $img = @imagecreatefromstring($data);
        if ($img && $type === IMAGETYPE_JPEG) {
            $img = abzar_apply_exif_orientation($img, $srcPath);
        }
    } else {
        $img = abzar_decode_fallback($srcPath, $origName);
    }

    if (!$img) {
        throw new RuntimeException('خواندن تصویر ناموفق بود: ' . $origName);
    }

    if (function_exists('imagepalettetotruecolor')) {
        imagepalettetotruecolor($img);
    }

    // کاهش ابعاد (با حفظ نسبت)
    $didResize = false;
    $w = imagesx($img);
    $h = imagesy($img);
    if ($maxDim > 0 && ($w > $maxDim || $h > $maxDim)) {
        $scale = $maxDim / max($w, $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $resized = imagescale($img, $nw, $nh, IMG_BICUBIC);
        if ($resized !== false) {
            imagedestroy($img);
            $img = $resized;
            $didResize = true;
        }
    }

    $exts  = ['webp' => 'webp', 'jpg' => 'jpg', 'png' => 'png'];
    $mimes = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'png' => 'image/png'];
    $ext  = $exts[$format];
    $mime = $mimes[$format];

    $bytes = abzar_encode($img, $format, $quality);

    // محافظ «بزرگ‌تر نشدن»: هدفِ ابزار کم کردن حجم است، پس خروجی نباید از اصل بزرگ‌تر باشد.
    // مبنا = اندازه‌ی فایل اصلیِ کاربر (اگر مرورگر قبل از آپلود تبدیل کرده، از آپلودی کوچک‌تر است).
    $uploadLen = strlen($data);
    $benchmark = ($clientOrigSize > 0 && $clientOrigSize < $uploadLen) ? $clientOrigSize : $uploadLen;
    $sameFormat = ($type === IMAGETYPE_WEBP && $format === 'webp')
               || ($type === IMAGETYPE_JPEG && $format === 'jpg')
               || ($type === IMAGETYPE_PNG  && $format === 'png');

    if (strlen($bytes) >= $benchmark) {
        // فرمت اتلاف‌دار: کیفیت را پله‌پله کم کن تا خروجی واقعاً از اصل کوچک‌تر شود (تا کف ۴۰)
        if ($format !== 'png') {
            for ($q = $quality - 10; $q >= 40; $q -= 10) {
                $try = abzar_encode($img, $format, $q);
                if (strlen($try) < strlen($bytes)) {
                    $bytes = $try;
                }
                if (strlen($try) < $benchmark) {
                    break;
                }
            }
        }
        // بازکدگذاری سودی نداشت و فرمت هم همان است → همان فایل اصلی برگردانده شود
        if (strlen($bytes) >= $uploadLen && $sameFormat && !$didResize && $benchmark === $uploadLen) {
            $bytes = $data;
        }
    }
    imagedestroy($img);

    $base = preg_replace('/\.[^.\/\\\\]+$/', '', basename($origName));
    if ($base === '' || $base === null) {
        $base = 'image';
    }
    return [$bytes, $base . '.' . $ext, $mime];
}

/* ------------------------- بخش وب (HTTP) ------------------------- */

if (PHP_SAPI === 'cli') {
    return;
}

require __DIR__ . '/auth.php';
require __DIR__ . '/ratelimit.php';
if (!abzar_is_logged_in() && !abzar_public_mode()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'دسترسی غیرمجاز؛ دوباره وارد شوید.'], JSON_UNESCAPED_UNICODE);
    exit;
}
// سقف تبدیل: کاربر واردشده سخاوتمندانه، مهمان محدودتر
abzar_rate_limit('convert', abzar_is_logged_in() ? 300 : 60, 3600);

function abzar_fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_FILES['image']) || !isset($_FILES['image']['error'])) {
    abzar_fail('فایلی دریافت نشد.');
}
if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errs = [
        UPLOAD_ERR_INI_SIZE  => 'حجم فایل از حد مجاز سرور بیشتر است.',
        UPLOAD_ERR_FORM_SIZE => 'حجم فایل بیش از حد مجاز فرم است.',
        UPLOAD_ERR_PARTIAL   => 'آپلود ناقص انجام شد.',
        UPLOAD_ERR_NO_FILE   => 'فایلی انتخاب نشد.',
    ];
    abzar_fail($errs[$_FILES['image']['error']] ?? 'خطای آپلود.');
}

$quality = isset($_POST['quality']) ? (int) $_POST['quality'] : 82;
$maxDim  = (isset($_POST['maxdim']) && $_POST['maxdim'] !== '') ? (int) $_POST['maxdim'] : 0;
$format  = isset($_POST['format']) ? (string) $_POST['format'] : 'webp';
$clientOrigSize = isset($_POST['origsize']) ? max(0, (int) $_POST['origsize']) : 0;

$origName = $_FILES['image']['name'] ?: 'image';
$origSize = filesize($_FILES['image']['tmp_name']);

try {
    [$bytes, $outName, $mime] = abzar_convert($_FILES['image']['tmp_name'], $origName, $quality, $maxDim, $format, $clientOrigSize);
} catch (Throwable $e) {
    abzar_fail($e->getMessage(), 422);
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($bytes));
header('X-Orig-Size: ' . $origSize);
header('X-New-Size: ' . strlen($bytes));
header('X-Out-Name: ' . rawurlencode($outName));
header('Cache-Control: no-store');
echo $bytes;
