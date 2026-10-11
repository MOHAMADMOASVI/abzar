<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'شماره‌گذاری صفحات PDF — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">شماره‌گذاری صفحات PDF</h1>
  <p class="page-sub">شماره صفحه با موقعیت، اندازه و قالب دلخواه — اعداد فارسی هم پشتیبانی می‌شود.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل PDF را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="application/pdf" hidden>
    <div class="controls" style="margin-top:14px">
      <div class="field">
        <label>موقعیت</label>
        <select id="pos">
          <option value="bc">پایین وسط</option><option value="br">پایین راست</option><option value="bl">پایین چپ</option>
          <option value="tc">بالا وسط</option><option value="tr">بالا راست</option><option value="tl">بالا چپ</option>
        </select>
      </div>
      <div class="field">
        <label>قالب</label>
        <select id="fmt">
          <option value="n">1</option>
          <option value="n/t">1 / 20</option>
          <option value="-n-">- 1 -</option>
        </select>
      </div>
      <div class="field"><label>شروع از</label><input type="number" id="start" value="1" min="1" style="width:90px"></div>
      <div class="field"><label>اندازه فونت</label><input type="number" id="fsize" value="11" min="6" max="36" style="width:90px"></div>
      <label class="chk"><input type="checkbox" id="faDigits"> اعداد فارسی</label>
    </div>
    <div class="actions"><button class="btn btn-primary hidden" id="go">شماره‌گذاری و دانلود</button></div>
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
    await loadScript('assets/vendor/pdf-lib.min.js');
    const { PDFDocument, StandardFonts, rgb } = PDFLib;
    const doc = await PDFDocument.load(new Uint8Array(await pdfFile.arrayBuffer()));

    const useFa = $('#faDigits').checked;
    let font;
    if (useFa){
      // برای اعداد فارسی، فونت وزیرمتن embed می‌شود
      await loadScript('assets/vendor/fontkit.umd.min.js');
      doc.registerFontkit(fontkit);
      const fbytes = await (await fetch('assets/fonts/Vazirmatn-Regular.woff2')).arrayBuffer();
      font = await doc.embedFont(fbytes, { subset: true });
    } else {
      font = await doc.embedFont(StandardFonts.Helvetica);
    }

    const total = doc.getPageCount();
    const start = +$('#start').value || 1;
    const fs = +$('#fsize').value || 11;
    const pos = $('#pos').value, fmt = $('#fmt').value;
    const margin = 24;

    doc.getPages().forEach((page, i) => {
      const n = start + i;
      let label = fmt === 'n/t' ? n + ' / ' + (start + total - 1) : fmt === '-n-' ? '- ' + n + ' -' : String(n);
      if (useFa) label = label.replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
      const { width, height } = page.getSize();
      const tw = font.widthOfTextAtSize(label, fs);
      const x = pos.endsWith('c') ? (width - tw) / 2 : pos.endsWith('r') ? width - tw - margin : margin;
      const y = pos.startsWith('b') ? margin : height - margin - fs;
      page.drawText(label, { x, y, size: fs, font, color: rgb(.2, .2, .2) });
    });

    const bytes = await doc.save();
    download(new Blob([bytes], { type: 'application/pdf' }), baseName(pdfFile.name) + '-numbered.pdf');
    showNote($('#msg'), 'شماره‌گذاری ' + faNum(total) + ' صفحه انجام شد.', 'ok');
  } catch (e){
    showNote($('#msg'), 'خطا: ' + (/encrypt/i.test(e.message) ? 'فایل رمز دارد — اول با «حذف رمز PDF» بازش کن.' : e.message), 'err');
  }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
