<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'حذف صفحات PDF — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">حذف صفحات PDF</h1>
  <p class="page-sub">شماره صفحاتی که باید حذف شوند را بده؛ بقیه در فایل جدید می‌مانند.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل PDF را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="application/pdf" hidden>
    <div class="controls" style="margin-top:14px">
      <div class="field" style="flex:1 1 260px">
        <label>صفحات حذف‌شونده (مثلاً <span class="ltr" style="display:inline-block">2, 5-7</span>)</label>
        <input type="text" id="pages" class="ltr" placeholder="2, 5-7">
      </div>
      <span id="pInfo" style="color:var(--text-dim);font-size:.85rem"></span>
    </div>
    <div class="actions"><button class="btn btn-primary hidden" id="go">حذف و دانلود</button></div>
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
    const { PDFDocument } = PDFLib;
    const src = await PDFDocument.load(new Uint8Array(await pdfFile.arrayBuffer()));
    const max = src.getPageCount();
    const del = parseSet($('#pages').value, max);
    if (!del.size) throw new Error('صفحه‌ای برای حذف مشخص نشده');
    if (del.size >= max) throw new Error('نمی‌شود همه صفحات را حذف کرد');
    const keep = [];
    for (let i = 1; i <= max; i++) if (!del.has(i)) keep.push(i - 1);
    const doc = await PDFDocument.create();
    const pages = await doc.copyPages(src, keep);
    pages.forEach(p => doc.addPage(p));
    const bytes = await doc.save();
    download(new Blob([bytes], { type: 'application/pdf' }), baseName(pdfFile.name) + '-edited.pdf');
    showNote($('#msg'), faNum(del.size) + ' صفحه حذف شد؛ ' + faNum(keep.length) + ' صفحه ماند.', 'ok');
  } catch (e){
    showNote($('#msg'), 'خطا: ' + (/encrypt/i.test(e.message) ? 'فایل رمز دارد — اول با «حذف رمز PDF» بازش کن.' : e.message), 'err');
  }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
