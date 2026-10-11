<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'واترمارک روی عکس — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">واترمارک روی عکس</h1>
  <p class="page-sub">متن یا لوگو، با کنترل موقعیت، اندازه و شفافیت — تکی یا گروهی.</p>

  <div class="card">
    <div class="tabs">
      <button class="active" data-tab="text">واترمارک متنی</button>
      <button data-tab="logo">واترمارک لوگو</button>
    </div>
    <div class="tabpane active" id="tab-text">
      <div class="controls">
        <div class="field" style="flex:1 1 260px"><label>متن</label><input type="text" id="wmText" value="© نمونه"></div>
        <div class="field"><label>رنگ</label><input type="color" id="wmColor" value="#ffffff"></div>
        <label class="chk"><input type="checkbox" id="wmShadow" checked> سایه</label>
      </div>
    </div>
    <div class="tabpane" id="tab-logo">
      <div class="controls">
        <div class="field"><label>فایل لوگو (ترجیحاً PNG شفاف)</label><input type="file" id="logoFile" accept="image/*"></div>
      </div>
    </div>
    <div class="controls" style="margin-top:16px">
      <div class="field">
        <label>موقعیت</label>
        <select id="pos">
          <option value="br">پایین راست</option><option value="bl">پایین چپ</option>
          <option value="bc">پایین وسط</option><option value="tr">بالا راست</option>
          <option value="tl">بالا چپ</option><option value="c">وسط</option>
          <option value="tile">تکرارشونده (Tile)</option>
        </select>
      </div>
      <div class="field"><label>اندازه (٪ عرض عکس): <b id="szv">20</b></label><input type="range" id="wmSize" min="4" max="60" value="20" style="width:150px"></div>
      <div class="field"><label>شفافیت: <b id="opv">50</b></label><input type="range" id="wmOp" min="5" max="100" value="50" style="width:150px"></div>
      <div class="field">
        <label>فرمت خروجی</label>
        <select id="fmt"><option value="image/jpeg">JPG</option><option value="image/png">PNG</option><option value="image/webp">WebP</option></select>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="drop" id="drop"><div class="big">عکس‌ها را اینجا رها کن</div><div>یا کلیک کن — چند فایل هم‌زمان</div></div>
    <input type="file" id="file" accept="image/*" multiple hidden>
    <div id="list"></div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">اعمال واترمارک</button>
      <button class="btn btn-ghost hidden" id="zipAll">دانلود همه (ZIP)</button>
    </div>
  </div>

  <div class="card hidden" id="pvCard"><h2>پیش‌نمایش (اولین فایل)</h2><canvas id="pv" class="preview"></canvas></div>
</div>

<script>
initTabs();
const files = [];
let logoImg = null, mode = 'text';
$$('.tabs button').forEach(b => b.addEventListener('click', () => { mode = b.dataset.tab; preview(); }));
$('#wmSize').oninput = e => { $('#szv').textContent = faNum(e.target.value); preview(); };
$('#wmOp').oninput = e => { $('#opv').textContent = faNum(e.target.value); preview(); };
['wmText', 'wmColor', 'pos', 'wmShadow'].forEach(id => $('#' + id).addEventListener('input', preview));
$('#logoFile').onchange = async () => {
  if ($('#logoFile').files[0]) logoImg = await loadImage($('#logoFile').files[0]);
  preview();
};

initDrop($('#drop'), $('#file'), fs => { fs.forEach(f => files.push({ f })); render(); preview(); });

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
    }
    L.appendChild(d);
  });
  $('#go').classList.toggle('hidden', !files.length);
  $('#zipAll').classList.toggle('hidden', !files.some(x => x.out));
}

function drawWM(cx, W, H){
  const sizePct = +$('#wmSize').value / 100, op = +$('#wmOp').value / 100, pos = $('#pos').value;
  cx.save();
  cx.globalAlpha = op;
  const margin = Math.round(W * .025);

  const place = (w, h) => {
    switch (pos){
      case 'br': return [W - w - margin, H - h - margin];
      case 'bl': return [margin, H - h - margin];
      case 'bc': return [(W - w) / 2, H - h - margin];
      case 'tr': return [W - w - margin, margin];
      case 'tl': return [margin, margin];
      case 'c':  return [(W - w) / 2, (H - h) / 2];
    }
  };

  if (mode === 'logo' && logoImg){
    const w = W * sizePct, h = w * logoImg.naturalHeight / logoImg.naturalWidth;
    if (pos === 'tile'){
      for (let y = 0; y < H; y += h * 2.2)
        for (let x = 0; x < W; x += w * 1.8)
          cx.drawImage(logoImg, x, y, w, h);
    } else {
      const [x, y] = place(w, h);
      cx.drawImage(logoImg, x, y, w, h);
    }
  } else {
    const text = $('#wmText').value || '©';
    let fs = Math.max(10, W * sizePct * .5);
    cx.font = '700 ' + fs + 'px IRANYekan, Vazirmatn, sans-serif';
    cx.fillStyle = $('#wmColor').value;
    if ($('#wmShadow').checked){
      cx.shadowColor = 'rgba(0,0,0,.55)';
      cx.shadowBlur = fs * .12;
      cx.shadowOffsetY = fs * .04;
    }
    cx.textBaseline = 'top';
    const m = cx.measureText(text), tw = m.width, th = fs * 1.3;
    if (pos === 'tile'){
      cx.rotate(-Math.PI / 9);
      for (let y = -H; y < H * 1.6; y += th * 3.4)
        for (let x = -W * .4; x < W * 1.3; x += tw + fs * 2.4)
          cx.fillText(text, x, y);
    } else {
      const [x, y] = place(tw, th);
      cx.fillText(text, x, y);
    }
  }
  cx.restore();
}

async function stamp(f, mime, q){
  const im = await loadImage(f);
  const cv = document.createElement('canvas');
  cv.width = im.naturalWidth; cv.height = im.naturalHeight;
  const cx = cv.getContext('2d');
  if (mime === 'image/jpeg'){ cx.fillStyle = '#fff'; cx.fillRect(0, 0, cv.width, cv.height); }
  cx.drawImage(im, 0, 0);
  drawWM(cx, cv.width, cv.height);
  return { blob: await canvasBlob(cv, mime, q), cv };
}

let pvTimer = null;
function preview(){
  clearTimeout(pvTimer);
  pvTimer = setTimeout(async () => {
    if (!files.length) return;
    const im = await loadImage(files[0].f);
    const sc = Math.min(1, 700 / im.naturalWidth);
    const cv = $('#pv');
    cv.width = Math.round(im.naturalWidth * sc); cv.height = Math.round(im.naturalHeight * sc);
    const cx = cv.getContext('2d');
    cx.drawImage(im, 0, 0, cv.width, cv.height);
    drawWM(cx, cv.width, cv.height);
    $('#pvCard').classList.remove('hidden');
  }, 150);
}

$('#go').onclick = async () => {
  const mime = $('#fmt').value;
  setBusy($('#go'), true);
  for (const it of files){
    try {
      it.out = (await stamp(it.f, mime, .92)).blob;
      it.outName = baseName(it.f.name) + '-wm.' + mime.split('/')[1].replace('jpeg', 'jpg');
    } catch (e){ it.out = null; }
  }
  setBusy($('#go'), false);
  render();
};

$('#zipAll').onclick = () =>
  zipDownload(files.filter(x => x.out).map(x => ({ name: x.outName, blob: x.out })), 'watermarked.zip');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
