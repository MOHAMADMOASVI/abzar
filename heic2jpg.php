<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'تبدیل HEIC به JPG — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تبدیل HEIC به JPG</h1>
  <p class="page-sub">عکس‌های آیفون (HEIC/HEIF) در خود مرورگر به JPG تبدیل می‌شوند — هیچ‌چیز آپلود نمی‌شود.</p>

  <div class="card">
    <div class="controls">
      <div class="field"><label>کیفیت: <b id="qv">90</b></label><input type="range" id="q" min="50" max="100" value="90" style="width:190px"></div>
    </div>
  </div>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل‌های HEIC را اینجا رها کن</div><div>یا کلیک کن — چند فایل هم‌زمان</div></div>
    <input type="file" id="file" accept=".heic,.heif,image/heic,image/heif" multiple hidden>
    <div id="list"></div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">تبدیل به JPG</button>
      <button class="btn btn-ghost hidden" id="zipAll">دانلود همه (ZIP)</button>
    </div>
  </div>
</div>

<script>
const files = [];
$('#q').oninput = e => $('#qv').textContent = faNum(e.target.value);
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

$('#go').onclick = async () => {
  const q = +$('#q').value / 100;
  setBusy($('#go'), true);
  await loadScript('assets/vendor/heic2any.min.js');
  for (const it of files){
    try {
      let blob;
      try {
        blob = await heic2any({ blob: it.f, toType: 'image/jpeg', quality: q });
      } catch (e){
        // شاید مرورگر خودش HEIC بخواند (سافاری)
        const im = await loadImage(it.f);
        const cv = document.createElement('canvas');
        cv.width = im.naturalWidth; cv.height = im.naturalHeight;
        const cx = cv.getContext('2d');
        cx.fillStyle = '#fff'; cx.fillRect(0, 0, cv.width, cv.height);
        cx.drawImage(im, 0, 0);
        blob = await canvasBlob(cv, 'image/jpeg', q);
      }
      it.out = Array.isArray(blob) ? blob[0] : blob;
      it.outName = baseName(it.f.name) + '.jpg';
      it.err = null;
    } catch (e){ it.err = 'تبدیل نشد'; it.out = null; }
    render();
  }
  setBusy($('#go'), false);
};

$('#zipAll').onclick = () =>
  zipDownload(files.filter(x => x.out).map(x => ({ name: x.outName, blob: x.out })), 'jpg.zip');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
