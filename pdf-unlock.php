<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'حذف رمز PDF — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">حذف رمز PDF</h1>
  <p class="page-sub">رمز فایل را وارد کن تا نسخه بدون رمز بگیری. فقط برای فایل‌هایی که رمزشان را داری و مجاز به باز کردنشان هستی.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل PDF رمزدار را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="application/pdf" hidden>
    <div class="controls" style="margin-top:14px">
      <div class="field"><label>رمز فایل</label><input type="password" id="pass" class="ltr" autocomplete="off"></div>
    </div>
    <div class="actions"><button class="btn btn-primary hidden" id="go">حذف رمز و دانلود</button></div>
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
  try {
    const data = new Uint8Array(await pdfFile.arrayBuffer());
    await loadScript('assets/vendor/pdf-lib.min.js');
    const { PDFDocument } = PDFLib;
    let doc;
    try {
      doc = await PDFDocument.load(data, { password: $('#pass').value });
    } catch (e){
      if (/password/i.test(e.message)) throw new Error('رمز اشتباه است.');
      // شاید اصلاً رمز نداشته باشد
      doc = await PDFDocument.load(data, { ignoreEncryption: true });
    }
    // ذخیره بدون فراخوانی encrypt یعنی خروجی بدون رمز
    const out = await PDFDocument.create();
    const pages = await out.copyPages(doc, doc.getPageIndices());
    pages.forEach(p => out.addPage(p));
    const bytes = await out.save();
    download(new Blob([bytes], { type: 'application/pdf' }), baseName(pdfFile.name) + '-unlocked.pdf');
    showNote($('#msg'), 'رمز برداشته شد.', 'ok');
  } catch (e){
    showNote($('#msg'), 'خطا: ' + e.message, 'err');
  }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
