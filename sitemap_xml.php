<?php
/**
 * سایت‌مپ داینامیک — با rewrite از sitemap.xml در .htaccess سرو می‌شود.
 * فقط در حالت عمومی معنا دارد؛ در حالت خصوصی هم بی‌ضرر است (صفحات پشت ورود می‌مانند).
 */
require __DIR__ . '/auth.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/';

$registry = require __DIR__ . '/tools_registry.php';
$lastmod = date('Y-m-d', max(filemtime(__FILE__), filemtime(__DIR__ . '/tools_registry.php')));

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
echo "  <url><loc>" . htmlspecialchars($base) . "</loc><lastmod>$lastmod</lastmod><priority>1.0</priority></url>\n";
foreach ($registry as $tools) {
    foreach ($tools as $t) {
        if ($t[0] === 'ai-settings.php') continue; // صفحه مدیریتی
        echo "  <url><loc>" . htmlspecialchars($base . $t[0]) . "</loc><lastmod>$lastmod</lastmod><priority>0.8</priority></url>\n";
    }
}
echo '</urlset>';
