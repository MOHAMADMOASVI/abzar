<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'تبدیل فرمت تصویر — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تبدیل فرمت تصویر</h1>
  <p class="page-sub">JPG، PNG، WebP و AVIF — همه‌چیز در مرورگر خودت انجام می‌شود. HEIC هم پشتیبانی می‌شود.</p>

  <div class="card">
    <div class="controls">
      <div class="field">
        <label>فرمت خروجی</label>
        <select id="fmt">
          <option value="image/jpeg">JPG</option>
          <option value="image/png">PNG</option>
          <option value="image/webp" selected>WebP</option>
          <option value="image/avif">AVIF</option>
        </select>
      </div>
      <div class="field" id="qField">
        <label>کیفیت: <b id="qv">85</b></label>
        <input type="range" id="q" min="30" max="100" value="85" style="width:190px">
      </div>
    </div>
    <p class="note warn hidden" id="avifNote">مرورگر شما خروجی AVIF نمی‌سازد (کروم جدید این قابلیت را دارد). فرمت دیگری انتخاب کن.</p>
  </div>

  <div class="card">
    <div class="drop" id="drop">
      <div class="big">عکس‌ها را اینجا بکش و رها کن</div>
      <div>یا کلیک کن — JPG، PNG، WebP، AVIF، HEIC، BMP، GIF</div>
    </div>
    <input type="file" id="file" accept="image/*,.heic,.heif" multiple hidden>
    <div id="list"></div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">تبدیل همه</button>
      <button class="btn btn-ghost hidden" id="zipAll">دانلود همه (ZIP)</button>
    </div>
  </div>
</div>

<script>
const files = [];
let avifOK = null;

canvasBlob(document.createElement('canvas'), 'image/avif', .8)
  .then(() => avifOK = true).catch(() => avifOK = false);

$('#q').oninput = e => $('#qv').textContent = faNum(e.target.value);
$('#fmt').onchange = () => {
  $('#qField').style.display = $('#fmt').value === 'image/png' ? 'none' : '';
  $('#avifNote').classList.toggle('hidden', !($('#fmt').value === 'image/avif' && avifOK === false));
};

initDrop($('#drop'), $('#file'), fs => {
  for (const f of fs) files.push({ f, out: null });
  render();
});

const isHeic = f => /\.(heic|heif)$/i.test(f.name) || /heic|heif/.test(f.type);

async function toImage(f){
  if (isHeic(f)){
    await loadScript('assets/vendor/heic2any.min.js');
    const jb = await heic2any({ blob: f, toType: 'image/jpeg', quality: .95 });
    return loadImage(jb);
  }
  return loadImage(f);
}

function render(){
  const L = $('#list');
  L.innerHTML = '';
  files.forEach((it, i) => {
    const d = document.createElement('div');
    d.className = 'frow';
    d.innerHTML = `<span class="nm">${it.f.name}</span><span class="sz">${fmtBytes(it.f.size)}</span><span class="st"></span>
      <button class="xbtn" title="حذف">✕</button>`;
    d.querySelector('.xbtn').onclick = () => { files.splice(i, 1); render(); };
    if (it.out){
      const a = document.createElement('a');
      a.className = 'dl'; a.textContent = 'دانلود (' + fmtBytes(it.out.size) + ')';
      a.href = URL.createObjectURL(it.out); a.download = it.outName;
      d.querySelector('.st').replaceWith(a);
    }
    L.appendChild(d);
  });
  $('#go').classList.toggle('hidden', !files.length);
  $('#zipAll').classList.toggle('hidden', !files.some(x => x.out));
}

$('#go').onclick = async () => {
  const mime = $('#fmt').value, q = +$('#q').value / 100;
  if (mime === 'image/avif' && avifOK === false){ $('#avifNote').classList.remove('hidden'); return; }
  const ext = { 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp', 'image/avif': 'avif' }[mime];
  setBusy($('#go'), true);
  for (const it of files){
    try {
      const im = await toImage(it.f);
      const cv = document.createElement('canvas');
      cv.width = im.naturalWidth; cv.height = im.naturalHeight;
      const cx = cv.getContext('2d');
      if (mime === 'image/jpeg'){ cx.fillStyle = '#fff'; cx.fillRect(0, 0, cv.width, cv.height); }
      cx.drawImage(im, 0, 0);
      it.out = await canvasBlob(cv, mime, q);
      it.outName = baseName(it.f.name) + '.' + ext;
    } catch (e){ it.out = null; it.err = e.message; }
  }
  setBusy($('#go'), false);
  render();
};

$('#zipAll').onclick = () =>
  zipDownload(files.filter(x => x.out).map(x => ({ name: x.outName, blob: x.out })), 'converted.zip');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
