<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'حذف پس‌زمینه با AI — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">حذف پس‌زمینه با AI</h1>
  <p class="page-sub">شبکه عصبی U²-Net کاملاً داخل مرورگر اجرا می‌شود — عکس جایی آپلود نمی‌شود.</p>
  <p class="note">بار اول مدل (~۴٫۵MB) از همین سرور بارگذاری می‌شود. برای سوژه‌های مشخص (آدم، محصول، حیوان) بهترین نتیجه را می‌دهد؛ برای لبه‌های خیلی ظریف (مو) نتیجه متوسط است.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">عکس را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="image/*" hidden>
    <div class="controls" style="margin-top:14px">
      <div class="field"><label>نرمی لبه: <b id="fv">1</b></label><input type="range" id="feather" min="0" max="4" value="1" style="width:150px"></div>
      <div class="field"><label>پس‌زمینه جدید</label>
        <select id="bg">
          <option value="transparent">شفاف (PNG)</option>
          <option value="#ffffff">سفید</option>
          <option value="#000000">مشکی</option>
          <option value="custom">رنگ دلخواه…</option>
        </select>
      </div>
      <div class="field hidden" id="customBgF"><label>رنگ</label><input type="color" id="customBg" value="#e8f4fd"></div>
    </div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">حذف پس‌زمینه</button>
      <span id="stat" style="color:var(--text-dim);font-size:.87rem"></span>
    </div>
    <p class="note hidden" id="msg"></p>
  </div>

  <div class="card hidden" id="resCard">
    <div class="two-col">
      <div><p style="font-size:.82rem;color:var(--text-dim);margin-bottom:6px">قبل</p><canvas id="cvA" class="preview"></canvas></div>
      <div><p style="font-size:.82rem;color:var(--text-dim);margin-bottom:6px">بعد</p><canvas id="cvB" class="preview"></canvas></div>
    </div>
    <div class="actions"><button class="btn btn-primary" id="dl">دانلود PNG</button></div>
  </div>
</div>

<script>
let img = null, srcName = 'image', session = null, outBlob = null;
const SIZE = 320;

initDrop($('#drop'), $('#file'), async fs => {
  img = await loadImage(fs[0]);
  srcName = baseName(fs[0].name);
  $('#drop .big').textContent = fs[0].name;
  $('#go').classList.remove('hidden');
});

$('#feather').oninput = e => $('#fv').textContent = faNum(e.target.value);
$('#bg').onchange = () => $('#customBgF').classList.toggle('hidden', $('#bg').value !== 'custom');

async function getSession(){
  if (session) return session;
  await loadScript('assets/vendor/ort.min.js');
  ort.env.wasm.wasmPaths = 'assets/vendor/';
  ort.env.wasm.numThreads = 1;
  $('#stat').textContent = 'بارگذاری مدل…';
  session = await ort.InferenceSession.create('assets/vendor/u2netp.onnx', { executionProviders: ['wasm'] });
  $('#stat').textContent = '';
  return session;
}

/* بلور ساده روی ماسک برای نرمی لبه */
function blurMask(mask, w, h, r){
  if (!r) return mask;
  const out = new Float32Array(mask.length);
  for (let y = 0; y < h; y++)
    for (let x = 0; x < w; x++){
      let s = 0, n = 0;
      for (let dy = -r; dy <= r; dy++){
        const yy = Math.min(h - 1, Math.max(0, y + dy));
        for (let dx = -r; dx <= r; dx++){
          const xx = Math.min(w - 1, Math.max(0, x + dx));
          s += mask[yy * w + xx]; n++;
        }
      }
      out[y * w + x] = s / n;
    }
  return out;
}

$('#go').onclick = async () => {
  if (!img) return;
  setBusy($('#go'), true, 'در حال پردازش AI…');
  $('#msg').classList.add('hidden');
  try {
    const sess = await getSession();

    // پیش‌پردازش: 320×320، نرمال‌سازی ImageNet
    const cv = document.createElement('canvas');
    cv.width = cv.height = SIZE;
    const cx = cv.getContext('2d');
    cx.drawImage(img, 0, 0, SIZE, SIZE);
    const { data } = cx.getImageData(0, 0, SIZE, SIZE);
    const input = new Float32Array(3 * SIZE * SIZE);
    const mean = [0.485, 0.456, 0.406], std = [0.229, 0.224, 0.225];
    let maxV = 0;
    for (let i = 0; i < data.length; i += 4) maxV = Math.max(maxV, data[i], data[i + 1], data[i + 2]);
    maxV = maxV || 255;
    for (let i = 0, p = 0; i < data.length; i += 4, p++){
      for (let c = 0; c < 3; c++)
        input[c * SIZE * SIZE + p] = (data[i + c] / maxV - mean[c]) / std[c];
    }
    const feeds = {};
    feeds[sess.inputNames[0]] = new ort.Tensor('float32', input, [1, 3, SIZE, SIZE]);
    const results = await sess.run(feeds);
    const out = results[sess.outputNames[0]].data;

    // نرمال‌سازی min-max ماسک
    let mn = Infinity, mx = -Infinity;
    for (const v of out){ if (v < mn) mn = v; if (v > mx) mx = v; }
    let mask = new Float32Array(SIZE * SIZE);
    for (let i = 0; i < mask.length; i++) mask[i] = (out[i] - mn) / (mx - mn || 1);
    mask = blurMask(mask, SIZE, SIZE, +$('#feather').value);

    // اعمال ماسک روی تصویر اصلی
    const W = img.naturalWidth, H = img.naturalHeight;
    if (W * H > 25e6) throw new Error('عکس خیلی بزرگ است (حداکثر ~۲۵ مگاپیکسل)');
    // ماسک را به کانواس با ابعاد اصلی می‌بریم
    const mCv = document.createElement('canvas');
    mCv.width = mCv.height = SIZE;
    const mId = mCv.getContext('2d').createImageData(SIZE, SIZE);
    for (let i = 0; i < mask.length; i++){
      const v = Math.round(mask[i] * 255);
      mId.data[i * 4] = mId.data[i * 4 + 1] = mId.data[i * 4 + 2] = 255;
      mId.data[i * 4 + 3] = v;
    }
    mCv.getContext('2d').putImageData(mId, 0, 0);

    const oCv = document.createElement('canvas');
    oCv.width = W; oCv.height = H;
    const ox = oCv.getContext('2d');
    ox.drawImage(img, 0, 0);
    ox.globalCompositeOperation = 'destination-in';
    ox.imageSmoothingEnabled = true;
    ox.imageSmoothingQuality = 'high';
    ox.drawImage(mCv, 0, 0, W, H);
    ox.globalCompositeOperation = 'source-over';

    // پس‌زمینه جدید
    let finalCv = oCv;
    const bgSel = $('#bg').value;
    if (bgSel !== 'transparent'){
      const bCv = document.createElement('canvas');
      bCv.width = W; bCv.height = H;
      const bx = bCv.getContext('2d');
      bx.fillStyle = bgSel === 'custom' ? $('#customBg').value : bgSel;
      bx.fillRect(0, 0, W, H);
      bx.drawImage(oCv, 0, 0);
      finalCv = bCv;
    }

    // نمایش
    const sc = Math.min(1, 460 / W);
    for (const [id, src] of [['cvA', img], ['cvB', finalCv]]){
      const c = $('#' + id);
      c.width = Math.round(W * sc); c.height = Math.round(H * sc);
      c.getContext('2d').drawImage(src, 0, 0, c.width, c.height);
    }
    outBlob = await canvasBlob(finalCv, 'image/png');
    $('#resCard').classList.remove('hidden');
  } catch (e){
    showNote($('#msg'), 'خطا: ' + (e.message || e), 'err');
  }
  setBusy($('#go'), false);
};

$('#dl').onclick = () => outBlob && download(outBlob, srcName + '-nobg.png');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
