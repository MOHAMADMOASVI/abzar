<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'تقسیم PDF — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تقسیم PDF</h1>
  <p class="page-sub">بازه‌های صفحات را با ویرگول جدا کن؛ هر بازه یک فایل می‌شود. یا هر صفحه را جدا کن.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل PDF را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="application/pdf" hidden>
    <div class="controls" style="margin-top:14px">
      <div class="field">
        <label>حالت</label>
        <select id="mode">
          <option value="ranges">بازه‌ای</option>
          <option value="each">هر صفحه جدا</option>
        </select>
      </div>
      <div class="field" style="flex:1 1 240px" id="fRanges">
        <label>بازه‌ها (مثلاً <span class="ltr" style="display:inline-block">1-3, 4-6, 7</span>)</label>
        <input type="text" id="ranges" class="ltr" placeholder="1-3, 4-10">
      </div>
      <span id="pInfo" style="color:var(--text-dim);font-size:.85rem"></span>
    </div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">تقسیم و دانلود ZIP</button>
    </div>
    <p class="note hidden" id="msg"></p>
    <div id="list"></div>
  </div>
</div>

<script>
let pdfFile = null, numPages = 0;

$('#mode').onchange = () => $('#fRanges').classList.toggle('hidden', $('#mode').value === 'each');

initDrop($('#drop'), $('#file'), async fs => {
  pdfFile = fs[0];
  $('#drop .big').textContent = pdfFile.name + ' (' + fmtBytes(pdfFile.size) + ')';
  $('#go').classList.remove('hidden');
  try {
    await loadScript('assets/vendor/pdf-lib.min.js');
    const doc = await PDFLib.PDFDocument.load(new Uint8Array(await pdfFile.arrayBuffer()));
    numPages = doc.getPageCount();
    $('#pInfo').textContent = 'تعداد صفحات: ' + faNum(numPages);
  } catch (e){ showNote($('#msg'), 'فایل باز نشد: ' + e.message, 'err'); }
});

function parseRanges(str, max){
  const out = [];
  for (const part of enNum(str).split(',')){
    const m = part.trim().match(/^(\d+)(?:-(\d+))?$/);
    if (!m) continue;
    let a = Math.max(1, +m[1]), b = Math.min(max, m[2] ? +m[2] : a);
    if (a > b) [a, b] = [b, a];
    out.push([a, b]);
  }
  return out;
}

$('#go').onclick = async () => {
  if (!pdfFile) return;
  setBusy($('#go'), true);
  $('#msg').classList.add('hidden');
  $('#list').innerHTML = '';
  try {
    await loadScript('assets/vendor/pdf-lib.min.js');
    const { PDFDocument } = PDFLib;
    const src = await PDFDocument.load(new Uint8Array(await pdfFile.arrayBuffer()));
    const max = src.getPageCount();
    let ranges;
    if ($('#mode').value === 'each') ranges = [...Array(max)].map((_, i) => [i + 1, i + 1]);
    else {
      ranges = parseRanges($('#ranges').value, max);
      if (!ranges.length) throw new Error('بازه معتبری وارد نشده');
    }
    const items = [];
    const base = baseName(pdfFile.name);
    for (const [a, b] of ranges){
      const doc = await PDFDocument.create();
      const idx = [];
      for (let i = a; i <= b; i++) idx.push(i - 1);
      const pages = await doc.copyPages(src, idx);
      pages.forEach(p => doc.addPage(p));
      const bytes = await doc.save();
      const name = base + '-' + (a === b ? 'p' + a : a + '-' + b) + '.pdf';
      const blob = new Blob([bytes], { type: 'application/pdf' });
      items.push({ name, blob });
      const d = document.createElement('div');
      d.className = 'frow';
      d.innerHTML = `<span class="nm">${name}</span><span class="sz">${fmtBytes(blob.size)}</span>`;
      const link = document.createElement('a');
      link.className = 'dl'; link.textContent = 'دانلود';
      link.href = URL.createObjectURL(blob); link.download = name;
      d.appendChild(link);
      $('#list').appendChild(d);
    }
    if (items.length > 1) await zipDownload(items, base + '-split.zip');
    else download(items[0].blob, items[0].name);
  } catch (e){
    showNote($('#msg'), 'خطا: ' + (/encrypt/i.test(e.message) ? 'فایل رمز دارد — اول با «حذف رمز PDF» بازش کن.' : e.message), 'err');
  }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
