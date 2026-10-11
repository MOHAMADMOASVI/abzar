<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'رمزگذاری PDF — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">رمزگذاری PDF</h1>
  <p class="page-sub">رمز باز کردن فایل + محدودیت چاپ و کپی — رمزنگاری AES-256 در خود مرورگر.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل PDF را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="application/pdf" hidden>
    <div class="controls" style="margin-top:14px">
      <div class="field"><label>رمز باز کردن فایل</label><input type="password" id="upass" class="ltr" autocomplete="new-password"></div>
      <div class="field"><label>رمز مالک (اختیاری)</label><input type="password" id="opass" class="ltr" autocomplete="new-password"></div>
    </div>
    <div class="controls" style="margin-top:12px">
      <label class="chk"><input type="checkbox" id="pPrint" checked> اجازه چاپ</label>
      <label class="chk"><input type="checkbox" id="pCopy"> اجازه کپی متن</label>
      <label class="chk"><input type="checkbox" id="pEdit"> اجازه ویرایش</label>
    </div>
    <div class="actions"><button class="btn btn-primary hidden" id="go">رمزگذاری و دانلود</button></div>
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
  const upass = $('#upass').value, opass = $('#opass').value;
  if (!upass && !opass){ showNote($('#msg'), 'حداقل یک رمز وارد کن.', 'warn'); return; }
  setBusy($('#go'), true);
  $('#msg').classList.add('hidden');
  try {
    await loadScript('assets/vendor/pdf-lib.min.js');
    const { PDFDocument } = PDFLib;
    const doc = await PDFDocument.load(new Uint8Array(await pdfFile.arrayBuffer()));
    doc.encrypt({
      userPassword: upass || undefined,
      ownerPassword: opass || upass,
      permissions: {
        printing: $('#pPrint').checked ? 'highResolution' : false,
        copying: $('#pCopy').checked,
        modifying: $('#pEdit').checked,
        annotating: $('#pEdit').checked,
        fillingForms: true,
        contentAccessibility: true,
        documentAssembly: $('#pEdit').checked,
      },
    });
    const bytes = await doc.save({ useObjectStreams: false });
    download(new Blob([bytes], { type: 'application/pdf' }), baseName(pdfFile.name) + '-protected.pdf');
    showNote($('#msg'), 'فایل رمزگذاری شد. رمز را جایی امن نگه دار — قابل بازیابی نیست.', 'ok');
  } catch (e){
    showNote($('#msg'), 'خطا: ' + (/encrypt/i.test(e.message) ? 'فایل از قبل رمز دارد.' : e.message), 'err');
  }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
