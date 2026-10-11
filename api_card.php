<?php
/**
 * استعلام نام دارنده کارت بانکی از طریق سرویس زیبال (facility/cardInquiry).
 * توکن در config/inquiry.php ذخیره می‌شود و هرگز به مرورگر نمی‌رسد.
 * ورودی: JSON { card: "16 رقم" } — خروجی: { ok, name } یا { ok:false, message }
 * چون هر استعلام هزینه دارد: مهمان‌ها کپچا + سقف ۵ در ساعت؛ مدیر ۳۰ در ساعت.
 */
require __DIR__ . '/auth.php';
require __DIR__ . '/ratelimit.php';
abzar_require_page();

header('Content-Type: application/json; charset=utf-8');

function card_out($arr, $code = 200){
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

$cfgFile = __DIR__ . '/config/inquiry.php';
$token = is_file($cfgFile) ? (string)((include $cfgFile)['zibal_token'] ?? '') : '';
if ($token === '') {
    card_out(['ok' => false, 'error' => 'no_token', 'message' => 'سرویس استعلام فعال نیست — توکن زیبال در تنظیمات وارد نشده.']);
}

if (!abzar_is_logged_in()) {
    if (!abzar_captcha_passed()) {
        card_out(['ok' => false, 'error' => 'captcha_required', 'message' => 'اول کد امنیتی را تأیید کن.'], 403);
    }
    abzar_rate_limit('cardinq-guest', 5, 3600);
} else {
    abzar_rate_limit('cardinq', 30, 3600);
}

$in = json_decode(file_get_contents('php://input'), true);
$card = preg_replace('/\D/', '', (string)($in['card'] ?? ''));
if (strlen($card) !== 16) card_out(['ok' => false, 'message' => 'شماره کارت باید ۱۶ رقم باشد.'], 400);

// Luhn — قبل از خرجِ استعلام، خودمان رد کنیم
$sum = 0;
for ($i = 0; $i < 16; $i++) {
    $d = (int)$card[$i];
    if ($i % 2 === 0) { $d *= 2; if ($d > 9) $d -= 9; }
    $sum += $d;
}
if ($sum % 10 !== 0) card_out(['ok' => false, 'message' => 'شماره کارت نامعتبر است (رقم کنترل).'], 400);

$ch = curl_init('https://gateway.zibal.ir/v1/facility/cardInquiry');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ],
    CURLOPT_POSTFIELDS     => json_encode(['cardNumber' => $card]),
]);
$resp = curl_exec($ch);
$err  = curl_error($ch);
curl_close($ch);

if ($resp === false) card_out(['ok' => false, 'message' => 'اتصال به سرویس استعلام برقرار نشد: ' . $err]);

$data = json_decode($resp, true);
if (!is_array($data)) card_out(['ok' => false, 'message' => 'پاسخ سرویس قابل خواندن نبود.']);

// زیبال: result=1 یعنی موفق؛ نام در data.name
$result = $data['result'] ?? null;
$name = $data['data']['name'] ?? $data['name'] ?? null;
if ((int)$result === 1 && $name) {
    card_out([
        'ok'   => true,
        'name' => $name,
        'bank' => $data['data']['bankName'] ?? null,
    ]);
}
card_out(['ok' => false, 'message' => $data['message'] ?? 'استعلام ناموفق بود.']);
