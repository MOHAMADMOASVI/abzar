<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'حذف نویز تصویر — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">حذف نویز تصویر</h1>
  <p class="page-sub">فیلتر Median برای نویز نمکی‌فلفلی و نرم‌کننده رنگ برای نویز رنگی (مثل عکس‌های کم‌نور موبایل).</p>

  <div class="card">
    <div class="controls">
      <div class="field">
        <label>شدت</label>
        <select id="level">
          <option value="1">ملایم</option>
          <option value="2" selected>متوسط</option>
          <option value="3">قوی</option>
        </select>
      </div>
      <label class="chk"><input type="checkbox" id="chroma" checked> کاهش نویز رنگی (پیشنهادی)</label>
      <div class="field">
        <label>فرمت خروجی</label>
        <select id="fmt"><option value="image/jpeg">JPG</option><option value="image/png">PNG</option><option value="image/webp">WebP</option></select>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="drop" id="drop"><div class="big">عکس‌ها را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="image/*" multiple hidden>
    <div id="list"></div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">حذف نویز</button>
      <button class="btn btn-ghost hidden" id="zipAll">دانلود همه (ZIP)</button>
    </div>
  </div>

  <div class="card hidden" id="cmpCard">
    <h2>مقایسه (آخرین فایل)</h2>
    <div class="two-col">
      <div><p style="font-size:.82rem;color:var(--text-dim);margin-bottom:6px">قبل</p><canvas id="cvA" class="preview"></canvas></div>
      <div><p style="font-size:.82rem;color:var(--text-dim);margin-bottom:6px">بعد</p><canvas id="cvB" class="preview"></canvas></div>
    </div>
  </div>
</div>

<script>
const files = [];
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
      a.className = 'dl'; a.textContent = 'دانلود (' + fmtBytes(it.out.size) + ')';
      a.href = URL.createObjectURL(it.out); a.download = it.outName;
      d.querySelector('.st').replaceWith(a);
    } else if (it.err) d.querySelector('.st').outerHTML = `<span class="badge err">${it.err}</span>`;
    L.appendChild(d);
  });
  $('#go').classList.toggle('hidden', !files.length);
  $('#zipAll').classList.toggle('hidden', !files.some(x => x.out));
}

/* median 3×3 روی روشنایی + میانگین‌گیری روی کانال‌های رنگ */
function denoise(imgData, w, h, passes, chroma){
  let s = imgData.data;
  const win = new Uint8ClampedArray(9);
  for (let p = 0; p < passes; p++){
    const out = new Uint8ClampedArray(s.length);
    for (let y = 0; y < h; y++){
      for (let x = 0; x < w; x++){
        const i = (y * w + x) * 4;
        for (let c = 0; c < 3; c++){
          let n = 0;
          for (let dy = -1; dy <= 1; dy++){
            const yy = Math.min(h - 1, Math.max(0, y + dy));
            for (let dx = -1; dx <= 1; dx++){
              const xx = Math.min(w - 1, Math.max(0, x + dx));
              win[n++] = s[(yy * w + xx) * 4 + c];
            }
          }
          const a = [...win.slice(0, n)].sort((u, v) => u - v);
          out[i + c] = a[4];
        }
        out[i + 3] = s[i + 3];
      }
    }
    s = out;
  }
  if (chroma){
    // تبدیل به YCbCr و بلور قوی روی کانال‌های رنگ
    const Y = new Float32Array(w * h), Cb = new Float32Array(w * h), Cr = new Float32Array(w * h);
    for (let i = 0, j = 0; i < s.length; i += 4, j++){
      const r = s[i], g = s[i + 1], b = s[i + 2];
      Y[j] = .299 * r + .587 * g + .114 * b;
      Cb[j] = -.169 * r - .331 * g + .5 * b;
      Cr[j] = .5 * r - .419 * g - .081 * b;
    }
    const blur = ch => {
      const o = new Float32Array(w * h);
      const R = 2;
      for (let y = 0; y < h; y++) for (let x = 0; x < w; x++){
        let sum = 0, n = 0;
        for (let dy = -R; dy <= R; dy++){
          const yy = Math.min(h - 1, Math.max(0, y + dy));
          for (let dx = -R; dx <= R; dx++){
            const xx = Math.min(w - 1, Math.max(0, x + dx));
            sum += ch[yy * w + xx]; n++;
          }
        }
        o[y * w + x] = sum / n;
      }
      return o;
    };
    const Cb2 = blur(Cb), Cr2 = blur(Cr);
    for (let i = 0, j = 0; i < s.length; i += 4, j++){
      s[i]     = Y[j] + 1.402 * Cr2[j];
      s[i + 1] = Y[j] - .344 * Cb2[j] - .714 * Cr2[j];
      s[i + 2] = Y[j] + 1.772 * Cb2[j];
    }
  }
  imgData.data.set(s);
  return imgData;
}

$('#go').onclick = async () => {
  const passes = +$('#level').value, chroma = $('#chroma').checked, mime = $('#fmt').value;
  setBusy($('#go'), true);
  for (const it of files){
    try {
      const im = await loadImage(it.f);
      if (im.naturalWidth * im.naturalHeight > 24e6) throw new Error('عکس خیلی بزرگ است (حداکثر ~۲۴ مگاپیکسل)');
      const cv = document.createElement('canvas');
      cv.width = im.naturalWidth; cv.height = im.naturalHeight;
      const cx = cv.getContext('2d');
      if (mime === 'image/jpeg'){ cx.fillStyle = '#fff'; cx.fillRect(0, 0, cv.width, cv.height); }
      cx.drawImage(im, 0, 0);
      const id = cx.getImageData(0, 0, cv.width, cv.height);
      cx.putImageData(denoise(id, cv.width, cv.height, passes, chroma), 0, 0);
      it.out = await canvasBlob(cv, mime, .92);
      it.outName = baseName(it.f.name) + '-denoised.' + mime.split('/')[1].replace('jpeg', 'jpg');
      it.err = null;
      // مقایسه
      const sc = Math.min(1, 420 / im.naturalWidth);
      for (const [id2, src] of [['cvA', im], ['cvB', cv]]){
        const c = $('#' + id2);
        c.width = Math.round(im.naturalWidth * sc); c.height = Math.round(im.naturalHeight * sc);
        c.getContext('2d').drawImage(src, 0, 0, c.width, c.height);
      }
      $('#cmpCard').classList.remove('hidden');
    } catch (e){ it.err = e.message; it.out = null; }
    render();
    await new Promise(r => setTimeout(r));
  }
  setBusy($('#go'), false);
};

$('#zipAll').onclick = () =>
  zipDownload(files.filter(x => x.out).map(x => ({ name: x.outName, blob: x.out })), 'denoised.zip');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
