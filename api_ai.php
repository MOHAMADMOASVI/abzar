<?php
/**
 * پروکسی سمت سرور برای Claude API (Anthropic).
 * کلید API در config/ai.php نگهداری می‌شود و هرگز به مرورگر نمی‌رسد.
 * ورودی: JSON { system?: string, messages: [{role:'user'|'assistant', content: string}], max_tokens?: int }
 * خروجی: JSON { ok: true, text, usage } یا { ok: false, error }
 */
require __DIR__ . '/auth.php';
require __DIR__ . '/ratelimit.php';
abzar_require_page();

header('Content-Type: application/json; charset=utf-8');

function ai_out($arr, $code = 200){
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

/*
 * حفاظت هزینه API:
 *  - کاربر واردشده: ۱۲۰ درخواست در ساعت.
 *  - مهمان (حالت عمومی): اول باید کپچا حل کند (captcha.php)، بعد ۱۰ درخواست در ساعت.
 */
if (!abzar_is_logged_in()) {
    if (!abzar_captcha_passed()) {
        ai_out(['ok' => false, 'error' => 'captcha_required', 'message' => 'برای استفاده از هوش مصنوعی اول کد امنیتی را تأیید کن.'], 403);
    }
    abzar_rate_limit('ai-guest', 10, 3600);
} else {
    abzar_rate_limit('ai', 120, 3600);
}

$cfgFile = __DIR__ . '/config/ai.php';
if (!is_file($cfgFile)) ai_out(['ok' => false, 'error' => 'no_key', 'message' => 'کلید API تنظیم نشده — به «تنظیمات AI» برو.']);
$cfg = require $cfgFile;
$apiKey = $cfg['api_key'] ?? '';
$model  = $cfg['model'] ?? 'claude-opus-4-8';
if ($apiKey === '') ai_out(['ok' => false, 'error' => 'no_key', 'message' => 'کلید API تنظیم نشده — به «تنظیمات AI» برو.']);

$raw = file_get_contents('php://input');
$req = json_decode($raw, true);
if (!is_array($req)) ai_out(['ok' => false, 'error' => 'bad_request', 'message' => 'درخواست نامعتبر'], 400);

$messages = [];
foreach (($req['messages'] ?? []) as $m) {
    if (!is_array($m)) continue;
    $role = $m['role'] ?? '';
    $content = (string)($m['content'] ?? '');
    if (!in_array($role, ['user', 'assistant'], true) || $content === '') continue;
    // سقف امن برای هر پیام (فایل‌های خیلی بزرگ)
    if (mb_strlen($content) > 400000) $content = mb_substr($content, 0, 400000);
    $messages[] = ['role' => $role, 'content' => $content];
}
if (!$messages) ai_out(['ok' => false, 'error' => 'bad_request', 'message' => 'پیامی ارسال نشده'], 400);

$body = [
    'model'      => $model,
    'max_tokens' => min(16000, max(256, (int)($req['max_tokens'] ?? 8192))),
    'thinking'   => ['type' => 'adaptive'],
    'messages'   => $messages,
];
if (!empty($req['system']) && is_string($req['system'])) {
    $body['system'] = mb_substr($req['system'], 0, 20000);
}

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 300,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
]);
$resp = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($resp === false) ai_out(['ok' => false, 'error' => 'network', 'message' => 'اتصال به API برقرار نشد: ' . $err]);

$data = json_decode($resp, true);
if ($code >= 400 || !is_array($data)) {
    $msg = $data['error']['message'] ?? ('HTTP ' . $code);
    $type = $data['error']['type'] ?? 'api_error';
    if ($type === 'authentication_error') $msg = 'کلید API نامعتبر است — در «تنظیمات AI» بررسی کن.';
    if ($type === 'rate_limit_error') $msg = 'سقف درخواست پر شده — کمی بعد دوباره امتحان کن.';
    ai_out(['ok' => false, 'error' => $type, 'message' => $msg]);
}

// قبل از خواندن content باید stop_reason بررسی شود
if (($data['stop_reason'] ?? '') === 'refusal') {
    ai_out(['ok' => false, 'error' => 'refusal', 'message' => 'مدل به این درخواست پاسخ نمی‌دهد.']);
}

$text = '';
foreach (($data['content'] ?? []) as $block) {
    if (($block['type'] ?? '') === 'text') $text .= $block['text'];
}

ai_out([
    'ok'    => true,
    'text'  => $text,
    'model' => $data['model'] ?? $model,
    'stop_reason' => $data['stop_reason'] ?? null,
    'usage' => [
        'input_tokens'  => $data['usage']['input_tokens'] ?? 0,
        'output_tokens' => $data['usage']['output_tokens'] ?? 0,
    ],
]);
