<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'تبدیل PDF به Word — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تبدیل PDF به Word</h1>
  <p class="page-sub">متن PDF استخراج و به فایل DOCX تبدیل می‌شود؛ در Word باز و ویرایش کن.</p>
  <p class="note warn">فقط متن منتقل می‌شود، نه صفحه‌آرایی و عکس‌ها. اگر PDF اسکن‌شده باشد (عکسِ متن)، متنی برای استخراج ندارد.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل PDF را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="application/pdf" hidden>
    <div class="actions"><button class="btn btn-primary hidden" id="go">استخراج و ساخت DOCX</button></div>
    <p class="note hidden" id="msg"></p>
    <div class="field hidden" id="pvWrap" style="margin-top:14px">
      <label>پیش‌نمایش متن استخراج‌شده</label>
      <textarea id="pv" style="min-height:220px"></textarea>
    </div>
  </div>
</div>

<script>
let pdfFile = null;

initDrop($('#drop'), $('#file'), fs => {
  pdfFile = fs[0];
  $('#drop .big').textContent = pdfFile.name + ' (' + fmtBytes(pdfFile.size) + ')';
  $('#go').classList.remove('hidden');
});

const escXml = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
const hasFa = s => /[؀-ۿ]/.test(s);

function buildDocx(paragraphs){
  const body = paragraphs.map(p => {
    if (p === null) return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    const rtl = hasFa(p);
    return '<w:p><w:pPr>' + (rtl ? '<w:bidi/><w:jc w:val="right"/>' : '') +
      '<w:spacing w:after="120"/></w:pPr><w:r>' + (rtl ? '<w:rPr><w:rtl/></w:rPr>' : '') +
      '<w:t xml:space="preserve">' + escXml(p) + '</w:t></w:r></w:p>';
  }).join('');
  const documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' +
    '<w:body>' + body + '</w:body></w:document>';
  const contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
    '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
    '<Default Extension="xml" ContentType="application/xml"/>' +
    '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>';
  const rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
    '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>';
  return { '[Content_Types].xml': contentTypes, '_rels/.rels': rels, 'word/document.xml': documentXml };
}

$('#go').onclick = async () => {
  if (!pdfFile) return;
  setBusy($('#go'), true);
  $('#msg').classList.add('hidden');
  try {
    await loadScript('assets/vendor/pdf.min.js');
    await loadScript('assets/vendor/jszip.min.js');
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'assets/vendor/pdf.worker.min.js';
    const pdf = await pdfjsLib.getDocument({ data: new Uint8Array(await pdfFile.arrayBuffer()) }).promise;
    const paragraphs = [];
    let totalChars = 0;
    for (let pno = 1; pno <= pdf.numPages; pno++){
      const tc = await (await pdf.getPage(pno)).getTextContent();
      // گروه‌بندی آیتم‌ها بر اساس خط (مختصات Y)
      let line = [], lastY = null;
      const flush = () => {
        if (!line.length) return;
        const txt = line.join(' ').replace(/\s+/g, ' ').trim();
        if (txt){ paragraphs.push(txt); totalChars += txt.length; }
        line = [];
      };
      for (const it of tc.items){
        const y = Math.round(it.transform[5]);
        if (lastY !== null && Math.abs(y - lastY) > 4) flush();
        if (it.str.trim()) line.push(it.str.trim());
        lastY = y;
      }
      flush();
      if (pno < pdf.numPages) paragraphs.push(null); // page break
    }
    if (!totalChars) throw new Error('متنی در این PDF پیدا نشد — احتمالاً اسکن‌شده است.');
    $('#pv').value = paragraphs.filter(p => p).join('\n');
    $('#pvWrap').classList.remove('hidden');

    const files = buildDocx(paragraphs);
    const z = new JSZip();
    for (const [path, content] of Object.entries(files)) z.file(path, content);
    const blob = await z.generateAsync({ type: 'blob', mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' });
    download(blob, baseName(pdfFile.name) + '.docx');
    showNote($('#msg'), faNum(paragraphs.filter(p => p).length) + ' پاراگراف استخراج شد.', 'ok');
  } catch (e){
    showNote($('#msg'), 'خطا: ' + (e.message.includes('password') ? 'فایل رمز دارد — اول با «حذف رمز PDF» بازش کن.' : e.message), 'err');
  }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
