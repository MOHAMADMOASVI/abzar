<?php
/**
 * بررسی سازگاری هاست با ابزار.
 * بعد از آپلود، یک‌بار این صفحه را باز کن، نتیجه را ببین، و بعد این فایل را «حذف کن».
 */
header('Content-Type: text/html; charset=utf-8');

function row($label, $ok, $note = '')
{
    $mark = $ok === true ? '✅' : ($ok === false ? '❌' : '⚠️');
    $color = $ok === true ? '#2E8B3D' : ($ok === false ? '#E5534B' : '#B5740A');
    echo "<tr><td>{$label}</td><td style='color:{$color};font-weight:bold'>{$mark}</td><td style='color:#52525B'>{$note}</td></tr>";
}

$gd = function_exists('gd_info') ? gd_info() : [];
$gdWebp = !empty($gd['WebP Support']);

$imagick = class_exists('Imagick');
$imagickHeic = false;
if ($imagick) {
    try {
        $fmts = (new Imagick())->queryFormats();
        $imagickHeic = (bool) array_intersect(['HEIC', 'HEIF'], array_map('strtoupper', $fmts));
    } catch (Throwable $e) {
    }
}

$execOk = function_exists('exec') && !in_array('exec', array_map('trim', explode(',', (string) ini_get('disable_functions'))), true);
$sips = false;
if ($execOk) {
    @exec('command -v sips 2>/dev/null', $o, $r);
    $sips = ($r === 0 && !empty($o));
}

$configDir = __DIR__ . '/config';
$configWritable = is_dir($configDir) ? is_writable($configDir) : is_writable(__DIR__);

$heicWorks = $imagickHeic || $sips;
?>
<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8">
<title>بررسی سازگاری هاست</title>
<style>body{font-family:Tahoma,sans-serif;max-width:640px;margin:40px auto;padding:0 16px;line-height:2;color:#18181B}
table{width:100%;border-collapse:collapse}td{padding:8px 6px;border-bottom:1px solid #eee}
.box{background:#FFFDF3;border:1px solid #E4E4E7;border-radius:8px;padding:14px 16px;margin-top:20px}
h1{font-size:20px}</style></head><body>
<h1>بررسی سازگاری هاست با ابزار</h1>
<table>
<?php
row('نسخه PHP: ' . PHP_VERSION, version_compare(PHP_VERSION, '8.0', '>=') ? true : 'warn', version_compare(PHP_VERSION, '8.0', '>=') ? 'مناسب' : 'بهتر است 8.0+ باشد');
row('GD نصب است', !empty($gd));
row('GD از WebP پشتیبانی می‌کند', $gdWebp, $gdWebp ? 'تبدیل JPG/PNG/WebP کار می‌کند' : 'بدون این، ابزار کار نمی‌کند!');
row('Imagick نصب است', $imagick ? true : 'warn', $imagick ? '' : 'برای HEIC لازم است');
row('Imagick از HEIC پشتیبانی می‌کند', $imagick ? ($imagickHeic ? true : false) : 'warn', $imagickHeic ? 'HEIC روی هاست کار می‌کند' : 'HEIC روی هاست کار نمی‌کند');
row('exec مجاز است', $execOk ? true : 'warn');
row('sips در دسترس است (فقط مک)', $sips ? true : 'warn', $sips ? '' : 'روی هاست لینوکسی طبیعی است که نباشد');
row('پوشه config قابل نوشتن است', $configWritable, $configWritable ? 'ذخیره‌ی رمز ممکن است' : 'باید دسترسی نوشتن بدهی (755/775)');
?>
</table>
<div class="box">
<b>جمع‌بندی:</b><br>
• JPG / PNG / WebP / GIF: <b style="color:<?= $gdWebp ? '#2E8B3D' : '#E5534B' ?>"><?= $gdWebp ? 'کار می‌کند ✅' : 'کار نمی‌کند ❌' ?></b><br>
• HEIC (عکس آیفون): <b style="color:<?= $heicWorks ? '#2E8B3D' : '#B5740A' ?>"><?= $heicWorks ? 'کار می‌کند ✅' : 'روی این هاست کار نمی‌کند ⚠️ (راه‌حل سمت مرورگر دارد)' ?></b>
</div>
<p style="color:#E5534B;margin-top:20px"><b>مهم:</b> بعد از دیدن نتیجه، این فایل (check.php) را از هاست حذف کن.</p>
</body></html>
