<?php
/**
 * هدر مشترک ابزار + متاتگ‌های سئو.
 * ورودی قبل از include:
 *   $pageTitle (لازم) — $active ('home'|…) — $pageDesc (اختیاری، وگرنه از رجیستری)
 *   $noIndex (اختیاری، true برای صفحات مدیریتی)
 */
$active    = $active ?? '';
$pageTitle = $pageTitle ?? 'ابزار';
$noIndex   = $noIndex ?? false;

$self = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');

/* توضیح متا + عنوان ابزار جاری از رجیستری (منبع واحد) */
$toolTitle = null;
if (!isset($pageDesc)) {
    $pageDesc = '';
    $reg = require __DIR__ . '/../tools_registry.php';
    foreach ($reg as $tools) {
        foreach ($tools as $t) {
            if ($t[0] === $self) { $toolTitle = $t[1]; $pageDesc = $t[1] . ' آنلاین و رایگان — ' . $t[2] . '. بدون نصب برنامه، سریع و امن.'; break 2; }
        }
    }
    if ($pageDesc === '' && $self === 'index.php') {
        $pageDesc = 'مجموعه ابزارهای آنلاین رایگان: فشرده‌سازی و تبدیل عکس، ادغام و تقسیم PDF، تبدیل ویدئو و صدا، هوش مصنوعی، ابزار برنامه‌نویسی و سئو — همه در مرورگر شما، بدون آپلود.';
    }
}

/* آدرس پایه برای og/canonical (به دامنه وابسته نیست) */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/';
$canonical = $baseUrl . ($self === 'index.php' ? '' : $self);
?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
<?php if ($noIndex): ?><meta name="robots" content="noindex, nofollow">
<?php endif; ?><meta name="theme-color" content="#2563EB">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
<link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16.png">
<link rel="apple-touch-icon" href="assets/apple-touch-icon.png">
<meta property="og:type" content="website">
<meta property="og:site_name" content="ابزار">
<meta property="og:locale" content="fa_IR">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDesc) ?>">
<meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:image" content="<?= htmlspecialchars($baseUrl) ?>assets/og-image.jpg">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($pageDesc) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($baseUrl) ?>assets/og-image.jpg">
<?php if (!$noIndex): ?>
<script type="application/ld+json">
<?= json_encode(
    $toolTitle !== null
    ? [
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => $toolTitle,
        'url' => $canonical,
        'description' => $pageDesc,
        'applicationCategory' => 'UtilitiesApplication',
        'operatingSystem' => 'Any',
        'inLanguage' => 'fa',
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
        'isPartOf' => ['@type' => 'WebSite', 'name' => 'ابزار', 'url' => $baseUrl],
      ]
    : [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'ابزار',
        'url' => $baseUrl,
        'description' => $pageDesc,
        'inLanguage' => 'fa',
      ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>

</script>
<?php endif; ?>
<link rel="stylesheet" href="assets/theme.css">
<link rel="stylesheet" href="assets/tools.css">
<script src="assets/tools.js"></script>
</head>
<body>
<header class="topbar" id="topbar">
  <div class="topbar-in">
    <a class="brand" href="index.php">
      <img class="logo-img" src="assets/logo-64.png" alt="لوگوی ابزار" width="36" height="36">
      <span>ابزار</span>
    </a>
    <nav class="nav">
      <a href="index.php" class="<?= $active === 'home' ? 'active' : '' ?>">خانه</a>
      <?php if (function_exists('abzar_is_logged_in') && abzar_is_logged_in()): ?>
        <a href="ai-settings.php">تنظیمات</a>
        <a href="logout.php">خروج</a>
      <?php else: ?>
        <a href="login.php" rel="nofollow">ورود مدیر</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
