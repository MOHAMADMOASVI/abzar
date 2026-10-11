<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'Minifier — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">Minifier</h1>
  <p class="page-sub">فشرده‌سازی HTML، CSS (با csso) و JavaScript (با Terser).</p>

  <div class="card">
    <div class="tabs">
      <button class="active" data-tab="js">JavaScript</button>
      <button data-tab="css">CSS</button>
      <button data-tab="html">HTML</button>
    </div>
    <div class="tabpane active" id="tab-js"></div>
    <div class="tabpane" id="tab-css"></div>
    <div class="tabpane" id="tab-html"></div>

    <div class="field"><label>ورودی</label><textarea id="in" class="ltr" style="min-height:200px"></textarea></div>
    <div class="actions">
      <button class="btn btn-primary" id="go">فشرده‌سازی</button>
      <span id="stat" style="color:var(--text-dim);font-size:.87rem"></span>
    </div>
    <p class="note hidden" id="msg"></p>
    <div class="copy-wrap" style="margin-top:12px">
      <button class="copybtn" id="cp">کپی</button>
      <div class="out" id="out" style="min-height:100px"></div>
    </div>
  </div>
</div>

<script>
initTabs();
let mode = 'js';
$$('.tabs button').forEach(b => b.addEventListener('click', () => mode = b.dataset.tab));

function minifyHtml(src){
  return src
    .replace(/<!--(?!\[if)[\s\S]*?-->/g, '')
    .replace(/>\s+</g, '><')
    .replace(/\s{2,}/g, ' ')
    .trim();
}

$('#go').onclick = async () => {
  const src = $('#in').value;
  if (!src.trim()) return;
  $('#msg').classList.add('hidden');
  setBusy($('#go'), true);
  try {
    let out;
    if (mode === 'js'){
      await loadScript('assets/vendor/terser.bundle.min.js');
      const r = await Terser.minify(src, { compress: true, mangle: true });
      if (r.error) throw r.error;
      out = r.code;
    } else if (mode === 'css'){
      await loadScript('assets/vendor/csso.js');
      out = csso.minify(src).css;
    } else {
      out = minifyHtml(src);
    }
    $('#out').textContent = out;
    const a = new Blob([src]).size, b = new Blob([out]).size;
    $('#stat').textContent = fmtBytes(a) + ' ← ' + fmtBytes(b) + ' (' + faNum(Math.round((1 - b / a) * 100)) + '٪ کمتر)';
  } catch (e){ showNote($('#msg'), 'خطا: ' + (e.message || e), 'err'); }
  setBusy($('#go'), false);
};
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
