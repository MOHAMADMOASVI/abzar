<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'ساخت QR Code — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">ساخت QR Code (با لوگو)</h1>
  <p class="page-sub">لینک، متن یا وای‌فای — با رنگ دلخواه و لوگوی وسط.</p>

  <div class="two-col">
    <div class="card">
      <div class="tabs">
        <button class="active" data-tab="text">متن / لینک</button>
        <button data-tab="wifi">وای‌فای</button>
        <button data-tab="tel">تماس</button>
      </div>
      <div class="tabpane active" id="tab-text">
        <div class="field"><label>متن یا آدرس</label><textarea id="txt" class="ltr" style="min-height:80px">https://example.com</textarea></div>
      </div>
      <div class="tabpane" id="tab-wifi">
        <div class="controls">
          <div class="field"><label>نام شبکه (SSID)</label><input type="text" id="ssid" class="ltr"></div>
          <div class="field"><label>رمز</label><input type="text" id="wpass" class="ltr"></div>
          <div class="field"><label>نوع</label><select id="wtype"><option>WPA</option><option>WEP</option><option value="nopass">بدون رمز</option></select></div>
        </div>
      </div>
      <div class="tabpane" id="tab-tel">
        <div class="field"><label>شماره تلفن</label><input type="text" id="tel" class="ltr" placeholder="+98912…"></div>
      </div>

      <div class="controls" style="margin-top:16px">
        <div class="field"><label>اندازه (px)</label><select id="size"><option>512</option><option selected>1024</option><option>2048</option></select></div>
        <div class="field"><label>رنگ</label><input type="color" id="fg" value="#000000"></div>
        <div class="field"><label>پس‌زمینه</label><input type="color" id="bgc" value="#ffffff"></div>
      </div>
      <div class="controls" style="margin-top:12px">
        <div class="field"><label>لوگو (اختیاری)</label><input type="file" id="logo" accept="image/*"></div>
        <div class="field"><label>اندازه لوگو: <b id="lv">20</b>٪</label><input type="range" id="lsize" min="10" max="30" value="20" style="width:130px"></div>
      </div>
      <div class="actions">
        <button class="btn btn-primary" id="dl">دانلود PNG</button>
      </div>
    </div>
    <div class="card">
      <h2>پیش‌نمایش</h2>
      <canvas id="cv" class="preview" style="width:100%;max-width:340px"></canvas>
      <p class="note" style="margin-top:14px">با لوگو، سطح تصحیح خطا خودکار روی H (بالاترین) می‌رود تا QR خوانا بماند.</p>
    </div>
  </div>
</div>

<script src="assets/vendor/qrcode.js"></script>
<script>
initTabs();
let logoImg = null, curTab = 'text';
$$('.tabs button').forEach(b => b.addEventListener('click', () => { curTab = b.dataset.tab; draw(); }));
['txt','ssid','wpass','wtype','tel','size','fg','bgc','lsize'].forEach(id => $('#' + id).addEventListener('input', draw));
$('#lsize').addEventListener('input', e => $('#lv').textContent = faNum(e.target.value));
$('#logo').onchange = async () => { logoImg = $('#logo').files[0] ? await loadImage($('#logo').files[0]) : null; draw(); };

const esc = s => s.replace(/([\\;,:"'])/g, '\\$1');

function payload(){
  if (curTab === 'wifi'){
    const t = $('#wtype').value;
    return 'WIFI:T:' + t + ';S:' + esc($('#ssid').value) + (t === 'nopass' ? '' : ';P:' + esc($('#wpass').value)) + ';;';
  }
  if (curTab === 'tel') return 'tel:' + enNum($('#tel').value.trim());
  return $('#txt').value;
}

function draw(){
  const data = payload();
  const size = +$('#size').value;
  const cv = $('#cv');
  if (!data.trim()){ cv.getContext('2d').clearRect(0, 0, cv.width, cv.height); return; }
  try {
    const qr = qrcode(0, logoImg ? 'H' : 'M');
    qr.addData(data, 'Byte');
    qr.make();
    const n = qr.getModuleCount();
    const quiet = 4;
    const cell = Math.floor(size / (n + quiet * 2));
    const real = cell * (n + quiet * 2);
    cv.width = cv.height = real;
    const cx = cv.getContext('2d');
    cx.fillStyle = $('#bgc').value;
    cx.fillRect(0, 0, real, real);
    cx.fillStyle = $('#fg').value;
    for (let r = 0; r < n; r++)
      for (let c = 0; c < n; c++)
        if (qr.isDark(r, c)) cx.fillRect((c + quiet) * cell, (r + quiet) * cell, cell, cell);
    if (logoImg){
      const lw = real * (+$('#lsize').value / 100);
      const pad = lw * .12;
      cx.fillStyle = $('#bgc').value;
      cx.beginPath();
      cx.roundRect((real - lw) / 2 - pad, (real - lw) / 2 - pad, lw + pad * 2, lw + pad * 2, lw * .12);
      cx.fill();
      const ratio = logoImg.naturalHeight / logoImg.naturalWidth;
      const lh = lw * Math.min(1, ratio), lw2 = lw * Math.min(1, 1 / ratio);
      cx.drawImage(logoImg, (real - lw2) / 2, (real - lh) / 2, lw2, lh);
    }
  } catch (e){
    // متن خیلی طولانی برای QR
  }
}
draw();

$('#dl').onclick = async () => {
  draw();
  download(await canvasBlob($('#cv'), 'image/png'), 'qrcode.png');
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
