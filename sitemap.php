<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'Sitemap Generator — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">Sitemap Generator</h1>
  <p class="page-sub">هر آدرس در یک خط؛ خروجی sitemap.xml استاندارد.</p>

  <div class="card">
    <div class="field"><label>آدرس‌ها (هر خط یکی)</label><textarea id="in" class="ltr" style="min-height:180px" placeholder="https://example.com/
https://example.com/about
https://example.com/blog/post-1"></textarea></div>
    <div class="controls" style="margin-top:12px">
      <div class="field">
        <label>changefreq</label>
        <select id="freq"><option value="">— نگذار —</option><option>daily</option><option>weekly</option><option>monthly</option><option>yearly</option></select>
      </div>
      <div class="field"><label>priority صفحه اول</label><select id="prio"><option value="">— نگذار —</option><option>1.0</option><option>0.8</option></select></div>
      <label class="chk"><input type="checkbox" id="lastmod" checked> lastmod = امروز</label>
    </div>
    <div class="actions">
      <button class="btn btn-primary" id="go">ساخت Sitemap</button>
      <button class="btn btn-ghost" id="dl">دانلود sitemap.xml</button>
    </div>
    <p class="note hidden" id="msg"></p>
    <div class="copy-wrap" style="margin-top:12px">
      <button class="copybtn" id="cp">کپی</button>
      <div class="out" id="out" style="min-height:120px"></div>
    </div>
  </div>
</div>

<script>
const escX = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');

function gen(){
  const lines = $('#in').value.split('\n').map(l => l.trim()).filter(Boolean);
  const urls = [], bad = [];
  for (const l of lines){
    try { urls.push(new URL(l).href); } catch { bad.push(l); }
  }
  if (bad.length) showNote($('#msg'), 'آدرس‌های نامعتبر (حذف شدند): ' + bad.slice(0, 5).join('، '), 'warn');
  else $('#msg').classList.add('hidden');
  if (!urls.length){ $('#out').textContent = ''; return ''; }
  const today = new Date().toISOString().slice(0, 10);
  const freq = $('#freq').value, prio = $('#prio').value, lm = $('#lastmod').checked;
  const items = urls.map((u, i) => {
    let s = '  <url>\n    <loc>' + escX(u) + '</loc>\n';
    if (lm) s += '    <lastmod>' + today + '</lastmod>\n';
    if (freq) s += '    <changefreq>' + freq + '</changefreq>\n';
    if (prio) s += '    <priority>' + (i === 0 ? prio : Math.max(.1, prio - .2).toFixed(1)) + '</priority>\n';
    return s + '  </url>';
  });
  const xml = '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' + items.join('\n') + '\n</urlset>';
  $('#out').textContent = xml;
  return xml;
}
$('#go').onclick = gen;
$('#dl').onclick = () => {
  const xml = gen();
  if (xml) download(new Blob([xml], { type: 'application/xml' }), 'sitemap.xml');
};
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
