<?php
require __DIR__ . '/auth.php';
abzar_require_page();

$registry = require __DIR__ . '/tools_registry.php';
$total = 0;
foreach ($registry as $tools) $total += count($tools);

$pageTitle = 'ابزار';
$active = 'home';
require __DIR__ . '/partials/header.php';

$badgeNames = ['wasm' => 'پردازش در مرورگر', 'ai' => 'نیاز به کلید AI', 'net' => 'نیاز به اینترنت'];
$catIcons = [
    'تصویر' => '🖼️', 'PDF' => '📄', 'ویدئو و صدا' => '🎬', 'هوش مصنوعی' => '✨',
    'برنامه‌نویسی' => '💻', 'سئو' => '📈', 'عمومی و ایرانی' => '🧰',
];

/* تبدیل عدد به رقم فارسی برای نمایش */
function fa_num($n){ return strtr((string)$n, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']); }
?>
<div class="wrap">
  <h1 class="page-title">ابزار</h1>
  <p class="page-sub"><?= fa_num($total) ?> ابزار کاربردی — پردازش فایل‌ها در مرورگر یا روی همین سرور انجام می‌شود و جایی آپلود نمی‌شود.</p>

  <div class="tools-search">
    <input type="text" id="q" placeholder="جستجو در ابزارها… (مثلاً: PDF، رمز، عکس)" autocomplete="off">
  </div>

  <nav class="cat-nav">
    <?php $ci = 0; foreach ($registry as $cat => $tools): ?>
    <a href="#cat-<?= $ci ?>"><?= $catIcons[$cat] ?? '' ?> <?= htmlspecialchars($cat) ?></a>
    <?php $ci++; endforeach; ?>
  </nav>

  <?php $ci = 0; foreach ($registry as $cat => $tools): ?>
  <section class="cat" data-cat="<?= htmlspecialchars($cat) ?>">
    <h2 class="cat-title" id="cat-<?= $ci ?>"><span class="ico"><?= $catIcons[$cat] ?? '🔧' ?></span> <?= htmlspecialchars($cat) ?> <span class="cnt"><?= fa_num(count($tools)) ?> ابزار</span></h2>
    <div class="grid-tools">
      <?php foreach ($tools as $t):
        [$file, $title, $desc] = $t;
        $badges = $t[3] ?? []; ?>
      <a class="tool-card" href="<?= htmlspecialchars($file) ?>" data-s="<?= htmlspecialchars(mb_strtolower($title . ' ' . $desc . ' ' . $cat)) ?>">
        <h3><?= htmlspecialchars($title) ?></h3>
        <p><?= htmlspecialchars($desc) ?></p>
        <?php if ($badges): ?>
        <span class="badges">
          <?php foreach ($badges as $b): ?><span class="badge2 <?= $b ?>"><?= $badgeNames[$b] ?? $b ?></span><?php endforeach; ?>
        </span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php $ci++; endforeach; ?>

  <p id="noRes" class="note warn hidden" style="margin-top:24px">ابزاری با این عبارت پیدا نشد.</p>
</div>

<script>
const q = $('#q');
q.addEventListener('input', () => {
  const s = q.value.trim().toLowerCase();
  let any = false;
  $$('.cat').forEach(sec => {
    let vis = 0;
    $$('.tool-card', sec).forEach(c => {
      const show = !s || c.dataset.s.includes(s);
      c.style.display = show ? '' : 'none';
      if (show) vis++;
    });
    sec.style.display = vis ? '' : 'none';
    if (vis) any = true;
  });
  $('#noRes').classList.toggle('hidden', any);
});
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
