<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'تبدیل PDF به عکس — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تبدیل PDF به عکس</h1>
  <p class="page-sub">هر صفحه یک تصویر PNG یا JPG می‌شود؛ تک‌تک یا همه در یک ZIP.</p>

  <div class="card">
    <div class="controls">
      <div class="field">
        <label>کیفیت / وضوح</label>
        <select id="scale">
          <option value="1.5">معمولی (~۱۱۰ DPI)</option>
          <option value="2" selected>خوب (~۱۵۰ DPI)</option>
          <option value="3">بالا (~۲۲۰ DPI)</option>
          <option value="4">خیلی بالا (~۳۰۰ DPI)</option>
        </select>
      </div>
      <div class="field">
        <label>فرمت</label>
        <select id="fmt"><option value="image/png">PNG</option><option value="image/jpeg" selected>JPG</option></select>
      </div>
      <div class="field" style="flex:1 1 200px"><label>صفحات (خالی = همه)</label><input type="text" id="range" class="ltr" placeholder="مثلاً 1-3,7"></div>
    </div>
  </div>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل PDF را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="application/pdf" hidden>
    <div class="progress" id="prog"><div class="bar"></div></div>
    <div id="list"></div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">تبدیل</button>
      <button class="btn btn-ghost hidden" id="zipAll">دانلود همه (ZIP)</button>
    </div>
    <p class="note hidden" id="msg"></p>
  </div>
</div>

<script>
let pdfFile = null, outs = [];

initDrop($('#drop'), $('#file'), fs => {
  pdfFile = fs[0]; outs = [];
  $('#drop .big').textContent = pdfFile.name + ' (' + fmtBytes(pdfFile.size) + ')';
  $('#go').classList.remove('hidden');
  $('#list').innerHTML = ''; $('#zipAll').classList.add('hidden');
});

function parseRange(str, max){
  if (!str.trim()) return [...Array(max)].map((_, i) => i + 1);
  const set = new Set();
  for (const part of enNum(str).split(',')){
    const m = part.trim().match(/^(\d+)(?:-(\d+))?$/);
    if (!m) continue;
    const a = +m[1], b = m[2] ? +m[2] : a;
    for (let i = Math.min(a, b); i <= Math.max(a, b); i++) if (i >= 1 && i <= max) set.add(i);
  }
  return [...set].sort((x, y) => x - y);
}

$('#go').onclick = async () => {
  if (!pdfFile) return;
  setBusy($('#go'), true);
  $('#msg').classList.add('hidden');
  try {
    await loadScript('assets/vendor/pdf.min.js');
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'assets/vendor/pdf.worker.min.js';
    const data = new Uint8Array(await pdfFile.arrayBuffer());
    const pdf = await pdfjsLib.getDocument({ data }).promise;
    const pages = parseRange($('#range').value, pdf.numPages);
    const scale = +$('#scale').value, mime = $('#fmt').value, ext = mime === 'image/png' ? 'png' : 'jpg';
    const prog = $('#prog'); prog.style.display = 'block';
    outs = [];
    $('#list').innerHTML = '';
    let done = 0;
    for (const pno of pages){
      const page = await pdf.getPage(pno);
      const vp = page.getViewport({ scale });
      const cv = document.createElement('canvas');
      cv.width = Math.round(vp.width); cv.height = Math.round(vp.height);
      const cx = cv.getContext('2d');
      cx.fillStyle = '#fff'; cx.fillRect(0, 0, cv.width, cv.height);
      await page.render({ canvasContext: cx, viewport: vp }).promise;
      const blob = await canvasBlob(cv, mime, .9);
      const name = baseName(pdfFile.name) + '-p' + String(pno).padStart(2, '0') + '.' + ext;
      outs.push({ name, blob });
      const d = document.createElement('div');
      d.className = 'frow';
      d.innerHTML = `<span class="nm">${name}</span><span class="sz">${cv.width}×${cv.height} — ${fmtBytes(blob.size)}</span>`;
      const a = document.createElement('a');
      a.className = 'dl'; a.textContent = 'دانلود';
      a.href = URL.createObjectURL(blob); a.download = name;
      d.appendChild(a);
      $('#list').appendChild(d);
      prog.querySelector('.bar').style.width = Math.round(++done / pages.length * 100) + '%';
    }
    prog.style.display = 'none';
    $('#zipAll').classList.toggle('hidden', outs.length < 2);
  } catch (e){
    showNote($('#msg'), 'خطا: ' + (e.message.includes('password') ? 'این PDF رمز دارد — اول با ابزار «حذف رمز PDF» بازش کن.' : e.message), 'err');
  }
  setBusy($('#go'), false);
};

$('#zipAll').onclick = () => zipDownload(outs, baseName(pdfFile.name) + '-pages.zip');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
