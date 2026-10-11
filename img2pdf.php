<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'تبدیل عکس به PDF — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تبدیل عکس به PDF</h1>
  <p class="page-sub">چند عکس را به یک PDF تبدیل کن؛ ترتیب را با فلش‌ها عوض کن.</p>

  <div class="card">
    <div class="controls">
      <div class="field">
        <label>اندازه صفحه</label>
        <select id="pageSize">
          <option value="fit">هم‌اندازه عکس</option>
          <option value="a4p" selected>A4 عمودی</option>
          <option value="a4l">A4 افقی</option>
        </select>
      </div>
      <div class="field"><label>حاشیه (mm)</label><input type="number" id="margin" value="10" min="0" max="50" style="width:90px"></div>
      <div class="field"><label>کیفیت عکس: <b id="qv">85</b></label><input type="range" id="q" min="40" max="100" value="85" style="width:150px"></div>
      <div class="field" style="flex:1 1 200px"><label>نام فایل خروجی</label><input type="text" id="outName" value="images.pdf" class="ltr"></div>
    </div>
  </div>

  <div class="card">
    <div class="drop" id="drop"><div class="big">عکس‌ها را اینجا رها کن</div><div>ترتیب انتخاب = ترتیب صفحات</div></div>
    <input type="file" id="file" accept="image/*" multiple hidden>
    <div id="list"></div>
    <div class="actions"><button class="btn btn-primary hidden" id="go">ساخت PDF</button></div>
  </div>
</div>

<script>
const files = [];
$('#q').oninput = e => $('#qv').textContent = faNum(e.target.value);
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
  $('#go').classList.toggle('hidden', !files.length);
}

const MM = 72 / 25.4; // pt per mm

$('#go').onclick = async () => {
  setBusy($('#go'), true);
  try {
    await loadScript('assets/vendor/pdf-lib.min.js');
    const { PDFDocument } = PDFLib;
    const doc = await PDFDocument.create();
    const q = +$('#q').value / 100, sizeMode = $('#pageSize').value, margin = (+$('#margin').value || 0) * MM;

    for (const f of files){
      const im = await loadImage(f);
      // برای سازگاری، همه را از کانواس به JPEG/PNG استاندارد می‌بریم
      const cv = document.createElement('canvas');
      cv.width = im.naturalWidth; cv.height = im.naturalHeight;
      const cx = cv.getContext('2d');
      const hasAlpha = /png|webp|gif/i.test(f.type);
      if (!hasAlpha){ cx.fillStyle = '#fff'; cx.fillRect(0, 0, cv.width, cv.height); }
      cx.drawImage(im, 0, 0);
      let bytes, img;
      if (hasAlpha){
        bytes = new Uint8Array(await (await canvasBlob(cv, 'image/png')).arrayBuffer());
        img = await doc.embedPng(bytes);
      } else {
        bytes = new Uint8Array(await (await canvasBlob(cv, 'image/jpeg', q)).arrayBuffer());
        img = await doc.embedJpg(bytes);
      }
      let pw, ph;
      if (sizeMode === 'fit'){ pw = img.width * .75 + margin * 2; ph = img.height * .75 + margin * 2; }
      else if (sizeMode === 'a4p'){ pw = 595.28; ph = 841.89; }
      else { pw = 841.89; ph = 595.28; }
      const page = doc.addPage([pw, ph]);
      const availW = pw - margin * 2, availH = ph - margin * 2;
      const sc = Math.min(availW / img.width, availH / img.height);
      const w = img.width * sc, h = img.height * sc;
      page.drawImage(img, { x: (pw - w) / 2, y: (ph - h) / 2, width: w, height: h });
    }
    const out = await doc.save();
    let name = ($('#outName').value.trim() || 'images.pdf');
    if (!/\.pdf$/i.test(name)) name += '.pdf';
    download(new Blob([out], { type: 'application/pdf' }), name);
  } catch (e){ alert('خطا: ' + e.message); }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
