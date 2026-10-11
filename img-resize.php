<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'تغییر اندازه تصویر — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تغییر اندازه تصویر</h1>
  <p class="page-sub">بر اساس درصد یا ابعاد مشخص؛ نسبت تصویر حفظ می‌شود مگر هر دو بُعد را بدهی.</p>

  <div class="card">
    <div class="controls">
      <div class="field">
        <label>روش</label>
        <select id="mode">
          <option value="percent">درصدی</option>
          <option value="dims">ابعاد (پیکسل)</option>
        </select>
      </div>
      <div class="field" id="fPercent">
        <label>درصد: <b id="pv">50</b>٪</label>
        <input type="range" id="percent" min="5" max="200" value="50" style="width:190px">
      </div>
      <div class="field hidden" id="fW"><label>عرض (px)</label><input type="number" id="w" min="1" placeholder="خودکار" style="width:110px"></div>
      <div class="field hidden" id="fH"><label>ارتفاع (px)</label><input type="number" id="h" min="1" placeholder="خودکار" style="width:110px"></div>
      <div class="field">
        <label>فرمت خروجی</label>
        <select id="fmt">
          <option value="">مثل ورودی</option>
          <option value="image/jpeg">JPG</option>
          <option value="image/png">PNG</option>
          <option value="image/webp">WebP</option>
        </select>
      </div>
      <div class="field"><label>کیفیت: <b id="qv">88</b></label><input type="range" id="q" min="30" max="100" value="88" style="width:150px"></div>
    </div>
  </div>

  <div class="card">
    <div class="drop" id="drop"><div class="big">عکس‌ها را اینجا رها کن</div><div>یا کلیک کن — چند فایل هم‌زمان</div></div>
    <input type="file" id="file" accept="image/*" multiple hidden>
    <div id="list"></div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">تغییر اندازه</button>
      <button class="btn btn-ghost hidden" id="zipAll">دانلود همه (ZIP)</button>
    </div>
  </div>
</div>

<script>
const files = [];
$('#pv') && ($('#percent').oninput = e => $('#pv').textContent = faNum(e.target.value));
$('#q').oninput = e => $('#qv').textContent = faNum(e.target.value);
$('#mode').onchange = () => {
  const dims = $('#mode').value === 'dims';
  $('#fPercent').classList.toggle('hidden', dims);
  $('#fW').classList.toggle('hidden', !dims);
  $('#fH').classList.toggle('hidden', !dims);
};

initDrop($('#drop'), $('#file'), fs => { fs.forEach(f => files.push({ f })); render(); });

function render(){
  const L = $('#list'); L.innerHTML = '';
  files.forEach((it, i) => {
    const d = document.createElement('div');
    d.className = 'frow';
    d.innerHTML = `<span class="nm">${it.f.name}</span><span class="sz">${fmtBytes(it.f.size)}</span>
      <span class="dim sz">${it.dim || ''}</span><span class="st"></span><button class="xbtn">✕</button>`;
    d.querySelector('.xbtn').onclick = () => { files.splice(i, 1); render(); };
    if (it.out){
      const a = document.createElement('a');
      a.className = 'dl'; a.textContent = `دانلود ${it.newDim} (${fmtBytes(it.out.size)})`;
      a.href = URL.createObjectURL(it.out); a.download = it.outName;
      d.querySelector('.st').replaceWith(a);
    } else if (it.err) d.querySelector('.st').outerHTML = `<span class="badge err">${it.err}</span>`;
    L.appendChild(d);
  });
  $('#go').classList.toggle('hidden', !files.length);
  $('#zipAll').classList.toggle('hidden', !files.some(x => x.out));
}

$('#go').onclick = async () => {
  const mode = $('#mode').value, q = +$('#q').value / 100;
  setBusy($('#go'), true);
  for (const it of files){
    try {
      const im = await loadImage(it.f);
      let W, H;
      if (mode === 'percent'){
        const p = +$('#percent').value / 100;
        W = Math.max(1, Math.round(im.naturalWidth * p));
        H = Math.max(1, Math.round(im.naturalHeight * p));
      } else {
        const w = +$('#w').value || 0, h = +$('#h').value || 0;
        if (!w && !h) throw new Error('عرض یا ارتفاع را وارد کن');
        W = w || Math.round(h * im.naturalWidth / im.naturalHeight);
        H = h || Math.round(w * im.naturalHeight / im.naturalWidth);
      }
      const mime = $('#fmt').value || (/png$/i.test(it.f.name) ? 'image/png' : /webp$/i.test(it.f.name) ? 'image/webp' : 'image/jpeg');
      const cv = document.createElement('canvas');
      cv.width = W; cv.height = H;
      const cx = cv.getContext('2d');
      cx.imageSmoothingQuality = 'high';
      if (mime === 'image/jpeg'){ cx.fillStyle = '#fff'; cx.fillRect(0, 0, W, H); }
      cx.drawImage(im, 0, 0, W, H);
      it.out = await canvasBlob(cv, mime, q);
      it.outName = baseName(it.f.name) + '-' + W + 'x' + H + '.' + mime.split('/')[1].replace('jpeg', 'jpg');
      it.newDim = W + '×' + H;
      it.err = null;
    } catch (e){ it.err = e.message; it.out = null; }
  }
  setBusy($('#go'), false);
  render();
};

$('#zipAll').onclick = () =>
  zipDownload(files.filter(x => x.out).map(x => ({ name: x.outName, blob: x.out })), 'resized.zip');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
