<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'Robots.txt Generator — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">Robots.txt Generator</h1>
  <p class="page-sub">قواعد آماده + مسیرهای سفارشی + آدرس sitemap.</p>

  <div class="two-col">
    <div class="card">
      <div class="field" style="margin-bottom:12px">
        <label>حالت پایه</label>
        <select id="base">
          <option value="all">اجازه به همه ربات‌ها</option>
          <option value="none">مسدود کردن کل سایت</option>
        </select>
      </div>
      <div class="field" style="margin-bottom:12px">
        <label>مسیرهای مسدود (هر خط یکی)</label>
        <textarea id="dis" class="ltr" style="min-height:90px" placeholder="/admin/
/private/
/cart"></textarea>
      </div>
      <div class="field" style="margin-bottom:12px">
        <label>مسیرهای مجاز استثنا (هر خط یکی)</label>
        <textarea id="allow" class="ltr" style="min-height:60px" placeholder="/public/"></textarea>
      </div>
      <div class="field" style="margin-bottom:12px"><label>آدرس Sitemap</label><input type="url" id="sm" class="ltr" placeholder="https://example.com/sitemap.xml"></div>
      <div class="controls">
        <label class="chk"><input type="checkbox" id="blockAI"> مسدود کردن ربات‌های AI (GPTBot و…)</label>
        <label class="chk"><input type="checkbox" id="crawlDelay"> Crawl-delay: 10</label>
      </div>
    </div>
    <div class="card">
      <h2>خروجی robots.txt</h2>
      <div class="copy-wrap">
        <button class="copybtn" id="cp">کپی</button>
        <div class="out" id="out" style="min-height:220px"></div>
      </div>
      <div class="actions"><button class="btn btn-ghost" id="dl">دانلود robots.txt</button></div>
    </div>
  </div>
</div>

<script>
function gen(){
  const L = ['User-agent: *'];
  if ($('#base').value === 'none') L.push('Disallow: /');
  else {
    const dis = $('#dis').value.split('\n').map(x => x.trim()).filter(Boolean);
    if (dis.length) dis.forEach(d => L.push('Disallow: ' + d));
    else L.push('Disallow:');
  }
  $('#allow').value.split('\n').map(x => x.trim()).filter(Boolean).forEach(a => L.push('Allow: ' + a));
  if ($('#crawlDelay').checked) L.push('Crawl-delay: 10');
  if ($('#blockAI').checked){
    L.push('');
    for (const bot of ['GPTBot', 'ChatGPT-User', 'CCBot', 'anthropic-ai', 'Google-Extended', 'Bytespider'])
      L.push('User-agent: ' + bot + '\nDisallow: /');
  }
  const sm = $('#sm').value.trim();
  if (sm){ L.push(''); L.push('Sitemap: ' + sm); }
  $('#out').textContent = L.join('\n');
}
$$('input, textarea, select').forEach(el => el.addEventListener('input', gen));
gen();
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
$('#dl').onclick = () => download(new Blob([$('#out').textContent], { type: 'text/plain' }), 'robots.txt');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
