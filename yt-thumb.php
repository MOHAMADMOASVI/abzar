<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'ساخت Thumbnail یوتیوب — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">ساخت Thumbnail یوتیوب</h1>
  <p class="page-sub">بوم استاندارد ۱۲۸۰×۷۲۰ — عکس پس‌زمینه، تیرگی، تیتر درشت با دورخط و بَج گوشه.</p>

  <div class="two-col">
    <div class="card">
      <div class="field" style="margin-bottom:12px">
        <label>عکس پس‌زمینه</label>
        <input type="file" id="bg" accept="image/*">
      </div>
      <div class="controls">
        <div class="field"><label>تیرگی پس‌زمینه: <b id="dv">35</b></label><input type="range" id="dark" min="0" max="80" value="35" style="width:160px"></div>
        <div class="field"><label>رنگ زمینه (بدون عکس)</label><input type="color" id="bgColor" value="#1a1a2e"></div>
      </div>
      <div class="field" style="margin:12px 0"><label>تیتر (با Enter چندخطی)</label><textarea id="title" style="min-height:70px">آموزش کامل
در ۱۰ دقیقه!</textarea></div>
      <div class="controls">
        <div class="field"><label>اندازه تیتر: <b id="fv">96</b></label><input type="range" id="fsize" min="40" max="180" value="96" style="width:150px"></div>
        <div class="field"><label>رنگ تیتر</label><input type="color" id="tColor" value="#ffffff"></div>
        <div class="field"><label>رنگ دورخط</label><input type="color" id="sColor" value="#000000"></div>
        <div class="field">
          <label>چیدمان</label>
          <select id="pos"><option value="c">وسط</option><option value="b" selected>پایین</option><option value="t">بالا</option></select>
        </div>
      </div>
      <div class="controls" style="margin-top:12px">
        <div class="field"><label>بَج گوشه (اختیاری)</label><input type="text" id="badge" placeholder="مثلاً: قسمت ۳"></div>
        <div class="field"><label>رنگ بَج</label><input type="color" id="bColor" value="#2563EB"></div>
      </div>
      <div class="actions">
        <button class="btn btn-primary" id="dlJpg">دانلود JPG</button>
        <button class="btn btn-ghost" id="dlPng">دانلود PNG</button>
      </div>
    </div>
    <div class="card">
      <h2>پیش‌نمایش</h2>
      <canvas id="cv" width="1280" height="720" class="preview" style="width:100%"></canvas>
    </div>
  </div>
</div>

<script>
let bgImg = null;
const cv = $('#cv'), cx = cv.getContext('2d');

$('#bg').onchange = async () => {
  if ($('#bg').files[0]) bgImg = await loadImage($('#bg').files[0]);
  draw();
};
['dark', 'bgColor', 'title', 'fsize', 'tColor', 'sColor', 'pos', 'badge', 'bColor'].forEach(id =>
  $('#' + id).addEventListener('input', draw));
$('#dark').addEventListener('input', e => $('#dv').textContent = faNum(e.target.value));
$('#fsize').addEventListener('input', e => $('#fv').textContent = faNum(e.target.value));

function draw(){
  const W = 1280, H = 720;
  // پس‌زمینه
  if (bgImg){
    const sc = Math.max(W / bgImg.naturalWidth, H / bgImg.naturalHeight);
    const w = bgImg.naturalWidth * sc, h = bgImg.naturalHeight * sc;
    cx.drawImage(bgImg, (W - w) / 2, (H - h) / 2, w, h);
  } else {
    const g = cx.createLinearGradient(0, 0, W, H);
    g.addColorStop(0, $('#bgColor').value);
    g.addColorStop(1, '#000');
    cx.fillStyle = g;
    cx.fillRect(0, 0, W, H);
  }
  // تیرگی
  cx.fillStyle = 'rgba(0,0,0,' + (+$('#dark').value / 100) + ')';
  cx.fillRect(0, 0, W, H);

  // تیتر
  const lines = $('#title').value.split('\n').filter(l => l.trim());
  const fs = +$('#fsize').value;
  cx.font = '800 ' + fs + 'px IRANYekan, Vazirmatn, sans-serif';
  cx.textAlign = 'center';
  cx.textBaseline = 'middle';
  cx.lineJoin = 'round';
  const lh = fs * 1.25, blockH = lines.length * lh;
  const pos = $('#pos').value;
  let y0 = pos === 't' ? 90 + fs / 2 : pos === 'b' ? H - 70 - blockH + lh / 2 : (H - blockH) / 2 + lh / 2;
  lines.forEach((ln, i) => {
    const y = y0 + i * lh;
    cx.strokeStyle = $('#sColor').value;
    cx.lineWidth = fs * .16;
    cx.strokeText(ln, W / 2, y);
    cx.fillStyle = $('#tColor').value;
    cx.fillText(ln, W / 2, y);
  });

  // بج
  const b = $('#badge').value.trim();
  if (b){
    cx.font = '800 44px IRANYekan, Vazirmatn, sans-serif';
    const tw = cx.measureText(b).width, pad = 26;
    const bw = tw + pad * 2, bh = 74, bx = W - bw - 34, by = 34;
    cx.fillStyle = $('#bColor').value;
    cx.beginPath();
    cx.roundRect(bx, by, bw, bh, 14);
    cx.fill();
    cx.fillStyle = '#111';
    cx.fillText(b, bx + bw / 2, by + bh / 2 + 3);
  }
}

document.fonts.ready.then(draw);
draw();

$('#dlJpg').onclick = async () => download(await canvasBlob(cv, 'image/jpeg', .92), 'thumbnail.jpg');
$('#dlPng').onclick = async () => download(await canvasBlob(cv, 'image/png'), 'thumbnail.png');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
