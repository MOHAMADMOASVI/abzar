<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'برش تصویر — ابزار';
require __DIR__ . '/partials/header.php';
?>
<link rel="stylesheet" href="assets/vendor/cropper.min.css">
<style>
  #stage{ max-height:60vh; }
  #stage img{ max-width:100%; display:block; }
</style>
<div class="wrap">
  <h1 class="page-title">برش تصویر (Crop)</h1>
  <p class="page-sub">عکس را بارگذاری کن، ناحیه را انتخاب کن و خروجی بگیر. چرخش هم داری.</p>

  <div class="card" id="pickCard">
    <div class="drop" id="drop"><div class="big">عکس را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="image/*" hidden>
  </div>

  <div class="card hidden" id="edCard">
    <div class="controls" style="margin-bottom:14px">
      <div class="field">
        <label>نسبت برش</label>
        <select id="ratio">
          <option value="free">آزاد</option>
          <option value="1">۱:۱ (مربع)</option>
          <option value="1.3333">۴:۳</option>
          <option value="1.7778">۱۶:۹</option>
          <option value="0.5625">۹:۱۶ (استوری)</option>
          <option value="1.5">۳:۲</option>
        </select>
      </div>
      <div class="field">
        <label>فرمت</label>
        <select id="fmt">
          <option value="image/jpeg">JPG</option>
          <option value="image/png">PNG</option>
          <option value="image/webp">WebP</option>
        </select>
      </div>
      <div class="field"><label>کیفیت: <b id="qv">90</b></label><input type="range" id="q" min="30" max="100" value="90" style="width:140px"></div>
      <button class="btn btn-ghost btn-sm" id="rotL">⟲ چرخش</button>
      <button class="btn btn-ghost btn-sm" id="rotR">⟳ چرخش</button>
      <button class="btn btn-ghost btn-sm" id="reset">از نو</button>
    </div>
    <div id="stage"><img id="img"></div>
    <div class="actions">
      <button class="btn btn-primary" id="go">برش و دانلود</button>
      <button class="btn btn-ghost" id="change">عکس دیگر</button>
      <span id="info" style="color:var(--text-dim); font-size:.85rem"></span>
    </div>
  </div>
</div>

<script>
let cropper = null, curName = 'image';
$('#q').oninput = e => $('#qv').textContent = faNum(e.target.value);

initDrop($('#drop'), $('#file'), async fs => {
  const f = fs[0];
  curName = baseName(f.name);
  $('#img').src = URL.createObjectURL(f);
  $('#pickCard').classList.add('hidden');
  $('#edCard').classList.remove('hidden');
  await loadScript('assets/vendor/cropper.min.js');
  if (cropper) cropper.destroy();
  cropper = new Cropper($('#img'), {
    viewMode: 1,
    autoCropArea: .9,
    crop(e){
      $('#info').textContent = 'ناحیه: ' + faNum(Math.round(e.detail.width)) + '×' + faNum(Math.round(e.detail.height)) + ' پیکسل';
    }
  });
});

$('#ratio').onchange = () => {
  if (!cropper) return;
  const v = $('#ratio').value;
  cropper.setAspectRatio(v === 'free' ? NaN : parseFloat(v));
};
$('#rotL').onclick = () => cropper && cropper.rotate(-90);
$('#rotR').onclick = () => cropper && cropper.rotate(90);
$('#reset').onclick = () => cropper && cropper.reset();
$('#change').onclick = () => {
  $('#edCard').classList.add('hidden');
  $('#pickCard').classList.remove('hidden');
};

$('#go').onclick = async () => {
  if (!cropper) return;
  const mime = $('#fmt').value;
  const cv = cropper.getCroppedCanvas({ imageSmoothingQuality: 'high', fillColor: mime === 'image/jpeg' ? '#fff' : undefined });
  const blob = await canvasBlob(cv, mime, +$('#q').value / 100);
  download(blob, curName + '-crop.' + mime.split('/')[1].replace('jpeg', 'jpg'));
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
