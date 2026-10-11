<?php
/**
 * کپچای تصویری با GD — بدون سرویس خارجی، پرتابل روی هاست اشتراکی.
 *  GET  captcha.php?img=1  → تصویر PNG (کد در session ذخیره می‌شود)
 *  POST {captcha:"..."}    → بررسی؛ اگر درست بود session یک ساعت «تأییدشده» می‌ماند
 */
require __DIR__ . '/auth.php';

if (isset($_GET['img'])) {
    // کد ۵ حرفی بدون کاراکترهای شبیه‌به‌هم
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 5; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
    $_SESSION['abzar_captcha'] = $code;

    $w = 200; $h = 64;
    $im = imagecreatetruecolor($w, $h);
    $bg     = imagecolorallocate($im, 241, 245, 251);
    $inkSft = imagecolorallocate($im, 187, 205, 240);
    imagefilledrectangle($im, 0, 0, $w, $h, $bg);

    // خطوط نویز
    for ($i = 0; $i < 6; $i++) {
        imageline($im, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $inkSft);
    }
    // نقطه‌های نویز
    for ($i = 0; $i < 220; $i++) {
        imagesetpixel($im, random_int(0, $w - 1), random_int(0, $h - 1), $inkSft);
    }

    // هر حرف را جدا، بزرگ‌شده و با جابه‌جایی عمودی تصادفی بکش
    $palette = [
        imagecolorallocate($im, 37, 99, 235),
        imagecolorallocate($im, 29, 78, 216),
        imagecolorallocate($im, 30, 64, 175),
    ];
    for ($i = 0; $i < 5; $i++) {
        $cw = imagefontwidth(5); $ch = imagefontheight(5);
        $tmp = imagecreatetruecolor($cw + 2, $ch + 2);
        $tbg = imagecolorallocate($tmp, 241, 245, 251);
        imagefilledrectangle($tmp, 0, 0, $cw + 2, $ch + 2, $tbg);
        imagestring($tmp, 5, 1, 1, $code[$i], $palette[array_rand($palette)]);
        $scale = random_int(26, 32) / 10; // ~2.6x تا 3.2x
        $dw = (int)(($cw + 2) * $scale); $dh = (int)(($ch + 2) * $scale);
        $dx = 8 + $i * 38 + random_int(-3, 3);
        $dy = random_int(2, max(3, $h - $dh - 2));
        imagecopyresized($im, $tmp, $dx, $dy, 0, 0, $dw, $dh, $cw + 2, $ch + 2);
        imagedestroy($tmp);
    }

    header('Content-Type: image/png');
    header('Cache-Control: no-store');
    imagepng($im);
    imagedestroy($im);
    exit;
}

// --- بررسی پاسخ ---
header('Content-Type: application/json; charset=utf-8');
$in = json_decode(file_get_contents('php://input'), true);
$answer = strtoupper(trim((string)($in['captcha'] ?? $_POST['captcha'] ?? '')));
$expected = (string)($_SESSION['abzar_captcha'] ?? '');
unset($_SESSION['abzar_captcha']); // یک‌بارمصرف

if ($expected !== '' && $answer !== '' && hash_equals($expected, $answer)) {
    $_SESSION['abzar_captcha_ok'] = time();
    echo json_encode(['ok' => true]);
} else {
    usleep(400000);
    echo json_encode(['ok' => false, 'message' => 'کد امنیتی اشتباه است.'], JSON_UNESCAPED_UNICODE);
}
