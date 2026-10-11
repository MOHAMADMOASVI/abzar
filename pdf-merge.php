<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'ادغام PDF — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">ادغام PDF</h1>
  <p class="page-sub">چند فایل PDF را به ترتیب دلخواه به یک فایل تبدیل کن.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل‌های PDF را اینجا رها کن</div><div>ترتیب را بعداً با فلش‌ها عوض کن</div></div>
    <input type="file" id="file" accept="application/pdf" multiple hidden>
    <div id="list"></div>
    <div class="controls" style="margin-top:14px">
      <div class="field" style="flex:1 1 220px"><label>نام فایل خروجی</label><input type="text" id="outName" value="merged.pdf" class="ltr"></div>
    </div>
    <div class="actions"><button class="btn btn-primary hidden" id="go">ادغام</button></div>
    <p class="note hidden" id="msg"></p>
  </div>
</div>

<script>
const files = [];
initDrop($('#drop'), $('#file'), fs => { fs.forEach(f => files.push(f)); render(); });

function render(){
  const L = $('#list'); L.innerHTML = '';
  files.forEach((f, i) => {
    const d = document.createElement('div');
    d.className = 'frow';
    d.innerHTML = `<span class="sz">${faNum(i + 1)}.</span><span class="nm">${f.name}</span><span class="sz">${fmtBytes(f.size)}</span>
      <button class="xbtn" data-a="up" ${i === 0 ? 'disabled' : ''}>▲</button>
      <button class="xbtn" data-a="dn" ${i === files.length - 1 ? 'disabled' : ''}>▼</button>
      <button class="xbtn" data-a="rm">✕</button>`;
    d.onclick = e => {
      const a = e.target.dataset && e.target.dataset.a;
      if (a === 'up'){ [files[i - 1], files[i]] = [files[i], files[i - 1]]; render(); }
      if (a === 'dn'){ [files[i + 1], files[i]] = [files[i], files[i + 1]]; render(); }
      if (a === 'rm'){ files.splice(i, 1); render(); }
    };
    L.appendChild(d);
  });
  $('#go').classList.toggle('hidden', files.length < 2);
}

$('#go').onclick = async () => {
  setBusy($('#go'), true);
  $('#msg').classList.add('hidden');
  try {
    await loadScript('assets/vendor/pdf-lib.min.js');
    const { PDFDocument } = PDFLib;
    const out = await PDFDocument.create();
    for (const f of files){
      const src = await PDFDocument.load(new Uint8Array(await f.arrayBuffer()), { ignoreEncryption: false });
      const pages = await out.copyPages(src, src.getPageIndices());
      pages.forEach(p => out.addPage(p));
    }
    const bytes = await out.save();
    let name = $('#outName').value.trim() || 'merged.pdf';
    if (!/\.pdf$/i.test(name)) name += '.pdf';
    download(new Blob([bytes], { type: 'application/pdf' }), name);
    showNote($('#msg'), 'انجام شد — ' + faNum(files.length) + ' فایل ادغام شد.', 'ok');
  } catch (e){
    showNote($('#msg'), 'خطا: ' + (/encrypt/i.test(e.message) ? 'یکی از فایل‌ها رمز دارد — اول با «حذف رمز PDF» بازش کن.' : e.message), 'err');
  }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
