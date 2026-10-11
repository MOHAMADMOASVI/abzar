<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'چرخاندن صفحات PDF — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">چرخاندن صفحات PDF</h1>
  <p class="page-sub">همه صفحات یا فقط صفحات مشخص را بچرخان.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل PDF را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="application/pdf" hidden>
    <div class="controls" style="margin-top:14px">
      <div class="field">
        <label>زاویه</label>
        <select id="angle">
          <option value="90">۹۰° ساعتگرد</option>
          <option value="180">۱۸۰°</option>
          <option value="270">۹۰° پادساعتگرد</option>
        </select>
      </div>
      <div class="field" style="flex:1 1 220px">
        <label>صفحات (خالی = همه)</label>
        <input type="text" id="pages" class="ltr" placeholder="1-3, 7">
      </div>
      <span id="pInfo" style="color:var(--text-dim);font-size:.85rem"></span>
    </div>
    <div class="actions"><button class="btn btn-primary hidden" id="go">چرخاندن و دانلود</button></div>
    <p class="note hidden" id="msg"></p>
  </div>
</div>

<script>
let pdfFile = null;

initDrop($('#drop'), $('#file'), async fs => {
  pdfFile = fs[0];
  $('#drop .big').textContent = pdfFile.name + ' (' + fmtBytes(pdfFile.size) + ')';
  $('#go').classList.remove('hidden');
  try {
    await loadScript('assets/vendor/pdf-lib.min.js');
    const doc = await PDFLib.PDFDocument.load(new Uint8Array(await pdfFile.arrayBuffer()));
    $('#pInfo').textContent = 'تعداد صفحات: ' + faNum(doc.getPageCount());
  } catch {}
});

function parseSet(str, max){
  if (!str.trim()) return new Set([...Array(max)].map((_, i) => i + 1));
  const set = new Set();
  for (const part of enNum(str).split(',')){
    const m = part.trim().match(/^(\d+)(?:-(\d+))?$/);
    if (!m) continue;
    const a = +m[1], b = m[2] ? +m[2] : a;
    for (let i = Math.min(a, b); i <= Math.max(a, b); i++) if (i >= 1 && i <= max) set.add(i);
  }
  return set;
}

$('#go').onclick = async () => {
  if (!pdfFile) return;
  setBusy($('#go'), true);
  $('#msg').classList.add('hidden');
  try {
    await loadScript('assets/vendor/pdf-lib.min.js');
    const { PDFDocument, degrees } = PDFLib;
    const doc = await PDFDocument.load(new Uint8Array(await pdfFile.arrayBuffer()));
    const max = doc.getPageCount();
    const targets = parseSet($('#pages').value, max);
    const add = +$('#angle').value;
    doc.getPages().forEach((p, i) => {
      if (targets.has(i + 1)) p.setRotation(degrees(((p.getRotation().angle + add) % 360 + 360) % 360));
    });
    const bytes = await doc.save();
    download(new Blob([bytes], { type: 'application/pdf' }), baseName(pdfFile.name) + '-rotated.pdf');
    showNote($('#msg'), faNum(targets.size) + ' صفحه چرخانده شد.', 'ok');
  } catch (e){
    showNote($('#msg'), 'خطا: ' + (/encrypt/i.test(e.message) ? 'فایل رمز دارد — اول با «حذف رمز PDF» بازش کن.' : e.message), 'err');
  }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
