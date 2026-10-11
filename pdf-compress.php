<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'فشرده‌سازی PDF — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">فشرده‌سازی PDF</h1>
  <p class="page-sub">صفحات به تصویر JPEG با کیفیت انتخابی تبدیل و دوباره PDF می‌شوند — برای فایل‌های اسکن‌شده و پرعکس عالی جواب می‌دهد.</p>
  <p class="note warn">بعد از فشرده‌سازی، متنِ داخل PDF دیگر قابل انتخاب/جستجو نیست (تصویر می‌شود). اگر PDF شما متنی و کم‌حجم است، این ابزار کمکی نمی‌کند.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل PDF را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="application/pdf" hidden>
    <div class="controls" style="margin-top:14px">
      <div class="field">
        <label>سطح فشرده‌سازی</label>
        <select id="preset">
          <option value="1.2|0.5">حداکثر فشرده (کیفیت پایین‌تر)</option>
          <option value="1.5|0.65" selected>متعادل</option>
          <option value="2|0.8">کیفیت بالا (فشرده‌سازی کمتر)</option>
        </select>
      </div>
    </div>
    <div class="progress" id="prog"><div class="bar"></div></div>
    <div class="actions"><button class="btn btn-primary hidden" id="go">فشرده‌سازی</button></div>
    <p class="note hidden" id="msg"></p>
  </div>
</div>

<script>
let pdfFile = null;

initDrop($('#drop'), $('#file'), fs => {
  pdfFile = fs[0];
  $('#drop .big').textContent = pdfFile.name + ' (' + fmtBytes(pdfFile.size) + ')';
  $('#go').classList.remove('hidden');
});

$('#go').onclick = async () => {
  if (!pdfFile) return;
  setBusy($('#go'), true);
  $('#msg').classList.add('hidden');
  const [scale, q] = $('#preset').value.split('|').map(Number);
  try {
    await loadScript('assets/vendor/pdf.min.js');
    await loadScript('assets/vendor/pdf-lib.min.js');
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'assets/vendor/pdf.worker.min.js';
    const data = new Uint8Array(await pdfFile.arrayBuffer());
    const srcSize = data.length;
    const pdf = await pdfjsLib.getDocument({ data }).promise;
    const out = await PDFLib.PDFDocument.create();
    const prog = $('#prog'); prog.style.display = 'block';

    for (let pno = 1; pno <= pdf.numPages; pno++){
      const page = await pdf.getPage(pno);
      const vpBase = page.getViewport({ scale: 1 });
      const vp = page.getViewport({ scale });
      const cv = document.createElement('canvas');
      cv.width = Math.round(vp.width); cv.height = Math.round(vp.height);
      const cx = cv.getContext('2d');
      cx.fillStyle = '#fff'; cx.fillRect(0, 0, cv.width, cv.height);
      await page.render({ canvasContext: cx, viewport: vp }).promise;
      const jpg = new Uint8Array(await (await canvasBlob(cv, 'image/jpeg', q)).arrayBuffer());
      const img = await out.embedJpg(jpg);
      const p = out.addPage([vpBase.width, vpBase.height]);
      p.drawImage(img, { x: 0, y: 0, width: vpBase.width, height: vpBase.height });
      prog.querySelector('.bar').style.width = Math.round(pno / pdf.numPages * 100) + '%';
    }
    prog.style.display = 'none';
    const bytes = await out.save();
    const saved = Math.round((1 - bytes.length / srcSize) * 100);
    download(new Blob([bytes], { type: 'application/pdf' }), baseName(pdfFile.name) + '-compressed.pdf');
    showNote($('#msg'),
      'حجم: ' + fmtBytes(srcSize) + ' ← ' + fmtBytes(bytes.length) +
      (saved > 0 ? ' — ' + faNum(saved) + '٪ کمتر' : ' — کوچک‌تر نشد؛ این PDF از قبل بهینه است'), saved > 0 ? 'ok' : 'warn');
  } catch (e){
    showNote($('#msg'), 'خطا: ' + (e.message.includes('password') ? 'فایل رمز دارد — اول با «حذف رمز PDF» بازش کن.' : e.message), 'err');
  }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
