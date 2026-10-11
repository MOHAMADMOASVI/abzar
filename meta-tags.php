<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'Meta Tag Generator — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">Meta Tag Generator</h1>
  <p class="page-sub">تگ‌های متا + Open Graph + Twitter Card آماده برای کپی در <code>&lt;head&gt;</code>.</p>

  <div class="two-col">
    <div class="card">
      <div class="field" style="margin-bottom:10px"><label>عنوان صفحه (Title) <span id="tCnt" style="color:var(--text-mute)"></span></label><input type="text" id="t" maxlength="70"></div>
      <div class="field" style="margin-bottom:10px"><label>توضیحات (Description) <span id="dCnt" style="color:var(--text-mute)"></span></label><textarea id="d" maxlength="170" style="min-height:70px"></textarea></div>
      <div class="field" style="margin-bottom:10px"><label>آدرس صفحه (Canonical URL)</label><input type="url" id="u" class="ltr" placeholder="https://example.com/page"></div>
      <div class="field" style="margin-bottom:10px"><label>آدرس تصویر (OG Image — ترجیحاً ۱۲۰۰×۶۳۰)</label><input type="url" id="img" class="ltr"></div>
      <div class="controls">
        <div class="field"><label>نام سایت</label><input type="text" id="site"></div>
        <div class="field"><label>زبان</label><select id="lang"><option value="fa_IR">فارسی</option><option value="en_US">English</option></select></div>
        <div class="field"><label>نوع</label><select id="type"><option value="website">website</option><option value="article">article</option><option value="product">product</option></select></div>
      </div>
      <div class="controls" style="margin-top:10px">
        <label class="chk"><input type="checkbox" id="noindex"> noindex (از ایندکس خارج شود)</label>
        <label class="chk"><input type="checkbox" id="tw" checked> تگ‌های توییتر</label>
      </div>
    </div>
    <div class="card">
      <h2>خروجی</h2>
      <div class="copy-wrap">
        <button class="copybtn" id="cp">کپی</button>
        <div class="out" id="out" style="min-height:260px"></div>
      </div>
    </div>
  </div>
</div>

<script>
const esc = s => s.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');

function gen(){
  const t = $('#t').value.trim(), d = $('#d').value.trim(), u = $('#u').value.trim(),
        img = $('#img').value.trim(), site = $('#site').value.trim();
  $('#tCnt').textContent = t ? '(' + faNum(t.length) + '/۶۰)' : '';
  $('#dCnt').textContent = d ? '(' + faNum(d.length) + '/۱۶۰)' : '';
  const L = [];
  if (t) L.push(`<title>${esc(t)}</title>`);
  if (d) L.push(`<meta name="description" content="${esc(d)}">`);
  if ($('#noindex').checked) L.push(`<meta name="robots" content="noindex, nofollow">`);
  if (u) L.push(`<link rel="canonical" href="${esc(u)}">`);
  L.push('');
  L.push(`<meta property="og:type" content="${$('#type').value}">`);
  if (t) L.push(`<meta property="og:title" content="${esc(t)}">`);
  if (d) L.push(`<meta property="og:description" content="${esc(d)}">`);
  if (u) L.push(`<meta property="og:url" content="${esc(u)}">`);
  if (img) L.push(`<meta property="og:image" content="${esc(img)}">`);
  if (site) L.push(`<meta property="og:site_name" content="${esc(site)}">`);
  L.push(`<meta property="og:locale" content="${$('#lang').value}">`);
  if ($('#tw').checked){
    L.push('');
    L.push(`<meta name="twitter:card" content="${img ? 'summary_large_image' : 'summary'}">`);
    if (t) L.push(`<meta name="twitter:title" content="${esc(t)}">`);
    if (d) L.push(`<meta name="twitter:description" content="${esc(d)}">`);
    if (img) L.push(`<meta name="twitter:image" content="${esc(img)}">`);
  }
  $('#out').textContent = L.join('\n');
}
$$('input, textarea, select').forEach(el => el.addEventListener('input', gen));
gen();
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
