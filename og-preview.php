<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'پیش‌نمایش گوگل و شبکه‌ها — ابزار';
require __DIR__ . '/partials/header.php';
?>
<style>
  .g-prev{ background:#fff; border:1px solid var(--line); border-radius:8px; padding:16px; direction:ltr; text-align:left; font-family:arial,sans-serif; }
  .g-prev .url{ color:#202124; font-size:12px; }
  .g-prev .title{ color:#1a0dab; font-size:20px; margin:4px 0; line-height:1.3; }
  .g-prev .desc{ color:#4d5156; font-size:14px; line-height:1.57; }
  .og-card{ border:1px solid var(--line); border-radius:10px; overflow:hidden; max-width:500px; background:#fff; direction:ltr; }
  .og-card img{ width:100%; aspect-ratio:1.91/1; object-fit:cover; display:block; background:#eee; }
  .og-card .in{ padding:10px 14px; }
  .og-card .dom{ color:#65676b; font-size:12px; text-transform:uppercase; }
  .og-card .t{ font-weight:700; font-size:16px; color:#050505; margin:3px 0; }
  .og-card .d{ color:#65676b; font-size:14px; }
</style>
<div class="wrap">
  <h1 class="page-title">پیش‌نمایش گوگل و شبکه‌های اجتماعی</h1>
  <p class="page-sub">آدرس صفحه را بده تا متاهایش خوانده شود، یا مقادیر را دستی وارد کن.</p>

  <div class="card">
    <div class="controls">
      <div class="field" style="flex:1 1 300px"><label>آدرس صفحه</label><input type="url" id="url" class="ltr" placeholder="https://example.com"></div>
      <button class="btn btn-primary" id="fetch">دریافت متاها</button>
    </div>
    <p class="note hidden" id="msg"></p>
    <div class="controls" style="margin-top:14px">
      <div class="field" style="flex:1 1 250px"><label>عنوان</label><input type="text" id="t"></div>
      <div class="field" style="flex:1 1 300px"><label>توضیحات</label><input type="text" id="d"></div>
      <div class="field" style="flex:1 1 250px"><label>آدرس تصویر OG</label><input type="url" id="img" class="ltr"></div>
    </div>
  </div>

  <div class="card">
    <h2>نتیجه گوگل</h2>
    <div class="g-prev">
      <div class="url" id="gUrl">example.com</div>
      <div class="title" id="gT">عنوان صفحه</div>
      <div class="desc" id="gD">توضیحات صفحه اینجا نمایش داده می‌شود…</div>
    </div>
  </div>

  <div class="card">
    <h2>کارت فیسبوک / تلگرام / توییتر</h2>
    <div class="og-card">
      <img id="cImg" alt="" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1200' height='630'%3E%3Crect fill='%23e4e4e7' width='1200' height='630'/%3E%3C/svg%3E">
      <div class="in">
        <div class="dom" id="cDom">example.com</div>
        <div class="t" id="cT">عنوان صفحه</div>
        <div class="d" id="cD">توضیحات…</div>
      </div>
    </div>
  </div>
</div>

<script>
function update(){
  const t = $('#t').value || 'عنوان صفحه';
  const d = $('#d').value || 'توضیحات صفحه اینجا نمایش داده می‌شود…';
  let host = 'example.com';
  try { host = new URL($('#url').value).host; } catch {}
  $('#gT').textContent = t.length > 60 ? t.slice(0, 60) + '…' : t;
  $('#gD').textContent = d.length > 160 ? d.slice(0, 160) + '…' : d;
  $('#gUrl').textContent = host;
  $('#cT').textContent = t;
  $('#cD').textContent = d.length > 110 ? d.slice(0, 110) + '…' : d;
  $('#cDom').textContent = host;
  if ($('#img').value) $('#cImg').src = $('#img').value;
}
['t', 'd', 'img', 'url'].forEach(id => $('#' + id).addEventListener('input', update));

$('#fetch').onclick = async () => {
  const u = $('#url').value.trim();
  if (!u){ showNote($('#msg'), 'آدرس را وارد کن.', 'warn'); return; }
  setBusy($('#fetch'), true);
  $('#msg').classList.add('hidden');
  try {
    const r = await (await fetch('fetch_url.php?url=' + encodeURIComponent(u))).json();
    if (!r.ok) throw new Error(r.error);
    const doc = new DOMParser().parseFromString(r.html, 'text/html');
    const meta = (sel, attr = 'content') => {
      const el = doc.querySelector(sel);
      return el ? (el.getAttribute(attr) || el.textContent || '').trim() : '';
    };
    $('#t').value = meta('meta[property="og:title"]') || meta('title', null) || doc.title || '';
    $('#d').value = meta('meta[property="og:description"]') || meta('meta[name="description"]');
    let img = meta('meta[property="og:image"]');
    if (img && !/^https?:/i.test(img)){
      try { img = new URL(img, r.finalUrl).href; } catch {}
    }
    $('#img').value = img;
    update();
    const missing = [];
    if (!meta('meta[property="og:title"]')) missing.push('og:title');
    if (!meta('meta[property="og:description"]')) missing.push('og:description');
    if (!img) missing.push('og:image');
    if (missing.length) showNote($('#msg'), 'تگ‌های جا افتاده: ' + missing.join('، '), 'warn');
    else showNote($('#msg'), 'همه تگ‌های اصلی موجودند ✓', 'ok');
  } catch (e){ showNote($('#msg'), 'خطا: ' + e.message, 'err'); }
  setBusy($('#fetch'), false);
};
update();
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
