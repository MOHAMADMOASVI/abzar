<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'بزرگ‌نمایی تصویر — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">بزرگ‌نمایی تصویر (Upscale)</h1>
  <p class="page-sub">بزرگ‌نمایی مرحله‌ای با درون‌یابی باکیفیت + فیلتر شفاف‌سازی لبه (Unsharp Mask). برای عکس‌های کوچک و لوگوها نتیجه خوبی می‌دهد.</p>
  <p class="note">این ابزار آفلاین است و شبکه عصبی ندارد؛ برای بازسازی جزئیات فوق‌واقعی (مثل چهره‌های محو) به سرویس‌های AI ابری نیاز است.</p>

  <div class="card">
    <div class="controls">
      <div class="field">
        <label>بزرگ‌نمایی</label>
        <select id="scale"><option value="2">۲×</option><option value="3">۳×</option><option value="4">۴×</option></select>
      </div>
      <div class="field"><label>شفاف‌سازی: <b id="sv">60</b></label><input type="range" id="sharp" min="0" max="100" value="60" style="width:170px"></div>
      <div class="field">
        <label>فرمت خروجی</label>
        <select id="fmt"><option value="image/png">PNG</option><option value="image/jpeg">JPG</option><option value="image/webp">WebP</option></select>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="drop" id="drop"><div class="big">عکس‌ها را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="image/*" multiple hidden>
    <div id="list"></div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">بزرگ‌نمایی</button>
      <button class="btn btn-ghost hidden" id="zipAll">دانلود همه (ZIP)</button>
    </div>
  </div>
</div>

<script>
const files = [];
$('#sharp').oninput = e => $('#sv').textContent = faNum(e.target.value);
initDrop($('#drop'), $('#file'), fs => { fs.forEach(f => files.push({ f })); render(); });

function render(){
  const L = $('#list'); L.innerHTML = '';
  files.forEach((it, i) => {
    const d = document.createElement('div');
    d.className = 'frow';
    d.innerHTML = `<span class="nm">${it.f.name}</span><span class="sz">${fmtBytes(it.f.size)}</span><span class="st"></span><button class="xbtn">✕</button>`;
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

/* بزرگ‌نمایی پله‌ای ۲× برای کیفیت بهتر درون‌یابی */
function upscaleCanvas(im, scale){
  let cv = document.createElement('canvas');
  cv.width = im.naturalWidth; cv.height = im.naturalHeight;
  cv.getContext('2d').drawImage(im, 0, 0);
  let cur = scale;
  while (cur > 1){
    const s = Math.min(2, cur);
    const nc = document.createElement('canvas');
    nc.width = Math.round(cv.width * s); nc.height = Math.round(cv.height * s);
    const cx = nc.getContext('2d');
    cx.imageSmoothingEnabled = true;
    cx.imageSmoothingQuality = 'high';
    cx.drawImage(cv, 0, 0, nc.width, nc.height);
    cv = nc; cur /= s;
  }
  return cv;
}

/* Unsharp mask ساده: sharpened = orig + amount*(orig - blurred) */
function unsharp(cv, amount){
  if (amount <= 0) return cv;
  const w = cv.width, h = cv.height, cx = cv.getContext('2d');
  const src = cx.getImageData(0, 0, w, h);
  const out = cx.createImageData(w, h);
  const s = src.data, o = out.data, a = amount * 1.2;
  for (let y = 0; y < h; y++){
    for (let x = 0; x < w; x++){
      const i = (y * w + x) * 4;
      for (let c = 0; c < 3; c++){
        // میانگین ۳×۳ به‌عنوان بلور
        let sum = 0, n = 0;
        for (let dy = -1; dy <= 1; dy++){
          const yy = y + dy; if (yy < 0 || yy >= h) continue;
          for (let dx = -1; dx <= 1; dx++){
            const xx = x + dx; if (xx < 0 || xx >= w) continue;
            sum += s[(yy * w + xx) * 4 + c]; n++;
          }
        }
        const v = s[i + c] + a * (s[i + c] - sum / n);
        o[i + c] = v < 0 ? 0 : v > 255 ? 255 : v;
      }
      o[i + 3] = s[i + 3];
    }
  }
  cx.putImageData(out, 0, 0);
  return cv;
}

$('#go').onclick = async () => {
  const scale = +$('#scale').value, amount = +$('#sharp').value / 100, mime = $('#fmt').value;
  setBusy($('#go'), true);
  for (const it of files){
    try {
      const im = await loadImage(it.f);
      if (im.naturalWidth * im.naturalHeight * scale * scale > 64e6)
        throw new Error('خروجی خیلی بزرگ می‌شود (حداکثر ~۶۴ مگاپیکسل)');
      let cv = upscaleCanvas(im, scale);
      cv = unsharp(cv, amount);
      if (mime === 'image/jpeg'){
        const jc = document.createElement('canvas');
        jc.width = cv.width; jc.height = cv.height;
        const jx = jc.getContext('2d');
        jx.fillStyle = '#fff'; jx.fillRect(0, 0, jc.width, jc.height);
        jx.drawImage(cv, 0, 0);
        cv = jc;
      }
      it.out = await canvasBlob(cv, mime, .92);
      it.outName = baseName(it.f.name) + '-x' + scale + '.' + mime.split('/')[1].replace('jpeg', 'jpg');
      it.newDim = cv.width + '×' + cv.height;
      it.err = null;
    } catch (e){ it.err = e.message; it.out = null; }
    render();
    await new Promise(r => setTimeout(r)); // فرصت رندر به UI
  }
  setBusy($('#go'), false);
};

$('#zipAll').onclick = () =>
  zipDownload(files.filter(x => x.out).map(x => ({ name: x.outName, blob: x.out })), 'upscaled.zip');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
