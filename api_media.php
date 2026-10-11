<?php
/**
 * پردازش ویدئو/صدا روی سرور با ffmpeg واقعی (خیلی سریع‌تر از wasm مرورگر).
 * ورودی: multipart → file (+wm برای واترمارک) + op + پارامترها.
 * خروجی: فایل باینری، یا JSON خطا.
 * همه پارامترها whitelist/sanitize می‌شوند و ffmpeg بدون shell اجرا می‌شود (proc_open آرایه‌ای).
 */
ini_set('display_errors', '0');
set_time_limit(900);

require __DIR__ . '/auth.php';
require __DIR__ . '/ratelimit.php';
require __DIR__ . '/ffmpeg_helper.php';
abzar_require_page();

function m_fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

abzar_rate_limit('media', abzar_is_logged_in() ? 60 : 12, 3600);

$ffmpeg = abzar_ffmpeg_bin();
if ($ffmpeg === null) m_fail('ffmpeg روی این سرور موجود نیست', 501);
if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) m_fail('فایل دریافت نشد');

/* --- sanitize کمکی --- */
$intIn  = fn(string $k, int $min, int $max, int $def) => max($min, min($max, (int)($_POST[$k] ?? $def)));
$timeIn = function (string $k, ?string $def = null): ?string {
    $v = trim((string)($_POST[$k] ?? ''));
    if ($v === '') return $def;
    return preg_match('/^(\d{1,6}(\.\d{1,3})?|\d{1,2}:\d{2}(:\d{2})?(\.\d{1,3})?)$/', $v) ? $v : $def;
};
$pick = function (string $k, array $allowed, string $def): string {
    $v = (string)($_POST[$k] ?? '');
    return in_array($v, $allowed, true) ? $v : $def;
};

$op = (string)($_POST['op'] ?? '');

/* پوشه کاری موقت */
$work = sys_get_temp_dir() . '/abzar-media-' . bin2hex(random_bytes(8));
if (!@mkdir($work, 0700)) m_fail('پوشه موقت ساخته نشد', 500);
$cleanup = function () use ($work) {
    foreach ((glob($work . '/*') ?: []) as $f) @unlink($f);
    @rmdir($work);
};

$srcExt = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!preg_match('/^[a-z0-9]{1,5}$/', $srcExt)) $srcExt = 'bin';
$in = $work . '/in.' . $srcExt;
if (!move_uploaded_file($_FILES['file']['tmp_name'], $in)) { $cleanup(); m_fail('ذخیره فایل ناموفق', 500); }

/* --- ساخت آرگومان‌ها به ازای هر عملیات (بدون shell) --- */
$args = null; $outExt = 'mp4';
$AENC = [
    'mp3' => ['-c:a', 'libmp3lame', '-b:a', '192k'],
    'wav' => ['-c:a', 'pcm_s16le'],
    'aac' => ['-c:a', 'aac', '-b:a', '160k'],
    'ogg' => ['-c:a', 'libvorbis', '-q:a', '5'],
];
$AEXT = ['mp3' => 'mp3', 'wav' => 'wav', 'aac' => 'm4a', 'ogg' => 'ogg'];

switch ($op) {
    /* ---------- ویدئو ---------- */
    case 'compress':
        $args = ['-i', $in, '-c:v', 'libx264', '-crf', (string)$intIn('crf', 18, 40, 28), '-preset', 'fast', '-c:a', 'aac', '-b:a', '96k'];
        break;
    case 'convert':
        $f = $pick('fmt', ['mp4', 'mkv', 'avi', 'webm'], 'mp4');
        $outExt = $f;
        if ($f === 'avi')       $args = ['-i', $in, '-c:v', 'mpeg4', '-q:v', '5', '-c:a', 'libmp3lame'];
        elseif ($f === 'webm')  $args = ['-i', $in, '-c:v', 'libvpx-vp9', '-crf', '32', '-b:v', '0', '-c:a', 'libopus'];
        else                    $args = ['-i', $in, '-c:v', 'libx264', '-crf', '23', '-preset', 'fast', '-c:a', 'aac'];
        break;
    case 'trim': {
        $s = $timeIn('start', '0'); $e = $timeIn('end');
        if ($e === null) { $cleanup(); m_fail('زمان پایان معتبر نیست'); }
        $args = ['-ss', $s, '-to', $e, '-i', $in];
        if ($pick('mode', ['copy', 'enc'], 'copy') === 'copy') array_push($args, '-c', 'copy');
        else array_push($args, '-c:v', 'libx264', '-crf', '22', '-preset', 'fast', '-c:a', 'aac');
        break;
    }
    case 'audio': {
        $f = $pick('afmt', ['mp3', 'aac', 'wav'], 'mp3');
        $outExt = $AEXT[$f];
        $args = array_merge(['-i', $in, '-vn'], $AENC[$f]);
        break;
    }
    case 'gif': {
        $s = $timeIn('start', '0'); $e = $timeIn('end');
        $fps = $intIn('fps', 5, 20, 10); $w = $intIn('w', 240, 960, 480);
        $args = ['-ss', $s];
        if ($e !== null) array_push($args, '-to', $e);
        array_push($args, '-i', $in, '-vf', "fps=$fps,scale=$w:-1:flags=lanczos", '-loop', '0');
        $outExt = 'gif';
        break;
    }
    case 'thumb':
        $args = ['-ss', $timeIn('at', '1'), '-i', $in, '-frames:v', '1', '-q:v', '2'];
        $outExt = 'jpg';
        break;
    case 'scale':
        $h = $pick('h', ['2160', '1440', '1080', '720', '480', '360', '240'], '720');
        $args = ['-i', $in, '-vf', 'scale=-2:' . $h, '-c:v', 'libx264', '-crf', '23', '-preset', 'fast', '-c:a', 'copy'];
        break;
    case 'fps':
        $args = ['-i', $in, '-r', (string)$intIn('fps', 5, 120, 30), '-c:v', 'libx264', '-crf', '23', '-preset', 'fast', '-c:a', 'copy'];
        break;
    case 'wm': {
        if (empty($_FILES['wm']['tmp_name']) || $_FILES['wm']['error'] !== UPLOAD_ERR_OK) { $cleanup(); m_fail('فایل لوگو دریافت نشد'); }
        $wmPath = $work . '/wm.png';
        if (!move_uploaded_file($_FILES['wm']['tmp_name'], $wmPath)) { $cleanup(); m_fail('ذخیره لوگو ناموفق', 500); }
        $pos = $pick('pos', ['10:10', 'W-w-10:10', '10:H-h-10', 'W-w-10:H-h-10'], 'W-w-10:H-h-10');
        $args = ['-i', $in, '-i', $wmPath, '-filter_complex', 'overlay=' . $pos, '-c:v', 'libx264', '-crf', '23', '-preset', 'fast', '-c:a', 'copy'];
        break;
    }
    /* ---------- صدا ---------- */
    case 'a_convert': {
        $f = $pick('fmt', ['mp3', 'wav', 'aac', 'ogg'], 'mp3');
        $outExt = $AEXT[$f];
        $args = array_merge(['-i', $in, '-vn'], $AENC[$f]);
        break;
    }
    case 'a_compress':
        $args = ['-i', $in, '-vn', '-c:a', 'libmp3lame', '-b:a', $intIn('br', 32, 320, 96) . 'k'];
        $outExt = 'mp3';
        break;
    case 'a_denoise':
        $args = array_merge(['-i', $in, '-vn', '-af', 'afftdn=nr=' . $intIn('nr', 6, 40, 20) . ':nf=-25,highpass=f=80'], $AENC['mp3']);
        $outExt = 'mp3';
        break;
    case 'a_volume': {
        $filter = !empty($_POST['norm']) ? 'loudnorm' : 'volume=' . $intIn('db', -30, 30, 6) . 'dB';
        $args = array_merge(['-i', $in, '-vn', '-af', $filter], $AENC['mp3']);
        $outExt = 'mp3';
        break;
    }
    case 'a_trim': {
        $s = $timeIn('start', '0'); $e = $timeIn('end');
        if ($e === null) { $cleanup(); m_fail('زمان پایان معتبر نیست'); }
        $args = array_merge(['-ss', $s, '-to', $e, '-i', $in], $AENC['mp3']);
        $outExt = 'mp3';
        break;
    }
    default:
        $cleanup();
        m_fail('عملیات ناشناخته');
}

$out = $work . '/out.' . $outExt;
$cmd = array_merge([$ffmpeg, '-hide_banner', '-y', '-nostdin'], $args, [$out]);

$proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $work);
if (!is_resource($proc)) { $cleanup(); m_fail('اجرای ffmpeg ناموفق', 500); }
stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]); fclose($pipes[2]);
$exit = proc_close($proc);

if ($exit !== 0 || !is_file($out) || filesize($out) === 0) {
    $tail = mb_substr(trim($stderr), -400);
    $cleanup();
    m_fail('پردازش ناموفق: ' . $tail, 422);
}

$mime = [
    'mp4' => 'video/mp4', 'mkv' => 'video/x-matroska', 'avi' => 'video/x-msvideo', 'webm' => 'video/webm',
    'gif' => 'image/gif', 'jpg' => 'image/jpeg',
    'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'm4a' => 'audio/mp4', 'ogg' => 'audio/ogg',
][$outExt] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($out));
header('Cache-Control: no-store');
readfile($out);
$cleanup();
