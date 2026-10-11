<?php
/**
 * فوتر مشترک: بلوک محتوای سئو (درباره + FAQ با اسکیمای FAQPage) +
 * ابزارهای مرتبط همان دسته + فوتر سایت.
 * با include قبل از </body> در هر صفحه بارگذاری می‌شود.
 */
$ft_self = basename($_SERVER['SCRIPT_NAME'] ?? '');
$ft_reg  = require __DIR__ . '/../tools_registry.php';
$ft_seo  = require __DIR__ . '/../seo_content.php';

$ft_tool = null; $ft_cat = null;
foreach ($ft_reg as $ft_c => $ft_tools) {
    foreach ($ft_tools as $ft_t) {
        if ($ft_t[0] === $ft_self) { $ft_tool = $ft_t; $ft_cat = $ft_c; break 2; }
    }
}
$ft_content = $ft_seo[$ft_self] ?? null;
?>
<?php if ($ft_tool && $ft_content): ?>
<div class="wrap seo-block">
  <div class="card">
    <h2>درباره ابزار <?= htmlspecialchars($ft_tool[1]) ?></h2>
    <p><?= htmlspecialchars($ft_content['about']) ?></p>
    <?php if (!empty($ft_content['faq'])): ?>
    <div class="faq" style="margin-top:16px">
      <?php foreach ($ft_content['faq'] as $ft_qa): ?>
      <details>
        <summary><?= htmlspecialchars($ft_qa[0]) ?></summary>
        <p><?= htmlspecialchars($ft_qa[1]) ?></p>
      </details>
      <?php endforeach; ?>
    </div>
    <script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(fn($qa) => [
        '@type' => 'Question',
        'name' => $qa[0],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
    ], $ft_content['faq']),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>

    </script>
    <?php endif; ?>
  </div>

  <?php
  // ابزارهای مرتبط همان دسته (حداکثر ۶ تا)
  $ft_related = array_values(array_filter($ft_reg[$ft_cat], fn($t) => $t[0] !== $ft_self));
  $ft_related = array_slice($ft_related, 0, 6);
  if ($ft_related): ?>
  <div class="related">
    <h2>ابزارهای مرتبط</h2>
    <div class="grid-tools">
      <?php foreach ($ft_related as $ft_r): ?>
      <a class="tool-card" href="<?= htmlspecialchars($ft_r[0]) ?>">
        <h3><?= htmlspecialchars($ft_r[1]) ?></h3>
        <p><?= htmlspecialchars($ft_r[2]) ?></p>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<footer class="site-footer">
  <div class="site-footer-in">
    <a class="f-brand" href="index.php">
      <img src="assets/logo-64.png" alt="لوگوی ابزار" width="28" height="28">
      ابزار
    </a>
    <nav class="f-links">
      <a href="index.php">همه ابزارها</a>
      <a href="webp.php">فشرده‌سازی عکس</a>
      <a href="bg-remove.php">حذف پس‌زمینه</a>
      <a href="pdf-merge.php">ادغام PDF</a>
      <a href="video.php">تبدیل ویدئو</a>
      <a href="qr.php">QR Code</a>
    </nav>
    <p class="f-note">پردازش فایل‌ها در مرورگر شما یا روی همین سرور انجام می‌شود و چیزی ذخیره نمی‌شود.</p>
  </div>
</footer>
