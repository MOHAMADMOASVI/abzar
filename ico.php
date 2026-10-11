<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'ساخت آیکون ICO / Favicon — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">ساخت آیکون (ICO / Favicon)</h1>
  <p class="page-sub">از هر عکسی، فایل ICO چندسایز به‌علاوه PNG های استاندارد سایت بساز. عکس مربعی بهترین نتیجه را می‌دهد.</p>

  <div class="card">
    <div class="controls">
      <div class="field">
        <label>سایزهای داخل ICO</label>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <label class="chk"><input type="checkbox" class="sz" value="16" checked> ۱۶</label>
          <label class="chk"><input type="checkbox" class="sz" value="32" checked> ۳۲</label>
          <label class="chk"><input type="checkbox" class="sz" value="48" checked> ۴۸</label>
          <label class="chk"><input type="checkbox" class="sz" value="64"> ۶۴</label>
          <label class="chk"><input type="checkbox" class="sz" value="128"> ۱۲۸</label>
          <label class="chk"><input type="checkbox" class="sz" value="256"> ۲۵۶</label>
        </div>
      </div>
      <label class="chk"><input type="checkbox" id="pngSet" checked> بستهٔ PNG سایت هم بساز (favicon-16/32، apple-touch-icon-180، icon-192/512)</label>
    </div>
  </div>

  <div class="card">
    <div class="drop" id="drop"><div class="big">عکس را اینجا رها کن</div><div>PNG شفاف پیشنهاد می‌شود</div></div>
    <input type="file" id="file" accept="image/*" hidden>
    <div id="pvWrap" class="hidden" style="margin-top:14px; display:flex; gap:14px; align-items:center; flex-wrap:wrap">
      <canvas id="pv" width="64" height="64" class="preview" style="width:64px;height:64px"></canvas>
      <span id="pvInfo" style="color:var(--text-dim);font-size:.85rem"></span>
    </div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">ساخت آیکون</button>
    </div>
    <div id="list"></div>
  </div>
</div>

<script>
let img = null, srcName = 'icon';

initDrop($('#drop'), $('#file'), async fs => {
  img = await loadImage(fs[0]);
  srcName = baseName(fs[0].name);
  const pv = $('#pv'), cx = pv.getContext('2d');
  cx.clearRect(0, 0, 64, 64);
  cx.imageSmoothingQuality = 'high';
  cx.drawImage(img, 0, 0, 64, 64);
  $('#pvInfo').textContent = 'ورودی: ' + img.naturalWidth + '×' + img.naturalHeight +
    (img.naturalWidth !== img.naturalHeight ? ' — مربعی نیست؛ از وسط برش می‌خورد' : '');
  $('#pvWrap').classList.remove('hidden');
  $('#go').classList.remove('hidden');
});

/* رندر مربعیِ سایز دلخواه (برش مرکزی) */
async function pngAt(size){
  const cv = document.createElement('canvas');
  cv.width = cv.height = size;
  const cx = cv.getContext('2d');
  cx.imageSmoothingQuality = 'high';
  const s = Math.min(img.naturalWidth, img.naturalHeight);
  const sx = (img.naturalWidth - s) / 2, sy = (img.naturalHeight - s) / 2;
  cx.drawImage(img, sx, sy, s, s, 0, 0, size, size);
  return new Uint8Array(await (await canvasBlob(cv, 'image/png')).arrayBuffer());
}

/* ساخت ICO با ورودی‌های PNG (پشتیبانی ویندوز ویستا به بعد) */
function buildIco(entries){
  const headerSize = 6 + entries.length * 16;
  let total = headerSize;
  for (const e of entries) total += e.png.length;
  const buf = new ArrayBuffer(total);
  const dv = new DataView(buf);
  const u8 = new Uint8Array(buf);
  dv.setUint16(0, 0, true);              // reserved
  dv.setUint16(2, 1, true);              // type: icon
  dv.setUint16(4, entries.length, true); // count
  let off = headerSize, p = 6;
  for (const e of entries){
    dv.setUint8(p, e.size >= 256 ? 0 : e.size);     // width
    dv.setUint8(p + 1, e.size >= 256 ? 0 : e.size); // height
    dv.setUint8(p + 2, 0);
    dv.setUint8(p + 3, 0);
    dv.setUint16(p + 4, 1, true);   // planes
    dv.setUint16(p + 6, 32, true);  // bpp
    dv.setUint32(p + 8, e.png.length, true);
    dv.setUint32(p + 12, off, true);
    u8.set(e.png, off);
    off += e.png.length; p += 16;
  }
  return new Blob([buf], { type: 'image/x-icon' });
}

$('#go').onclick = async () => {
  if (!img) return;
  setBusy($('#go'), true);
  try {
    const sizes = $$('.sz').filter(c => c.checked).map(c => +c.value);
    if (!sizes.length) throw new Error('حداقل یک سایز انتخاب کن');
    const entries = [];
    for (const s of sizes) entries.push({ size: s, png: await pngAt(s) });
    const ico = buildIco(entries);

    const L = $('#list'); L.innerHTML = '';
    const addRow = (name, blob) => {
      const d = document.createElement('div');
      d.className = 'frow';
      d.innerHTML = `<span class="nm">${name}</span><span class="sz">${fmtBytes(blob.size)}</span>`;
      const a = document.createElement('a');
      a.className = 'dl'; a.textContent = 'دانلود';
      a.href = URL.createObjectURL(blob); a.download = name;
      d.appendChild(a);
      L.appendChild(d);
      return { name, blob };
    };
    const outs = [addRow('favicon.ico', ico)];
    if ($('#pngSet').checked){
      for (const [n, s] of [['favicon-16x16.png', 16], ['favicon-32x32.png', 32], ['apple-touch-icon.png', 180], ['icon-192.png', 192], ['icon-512.png', 512]]){
        outs.push(addRow(n, new Blob([await pngAt(s)], { type: 'image/png' })));
      }
      const b = document.createElement('button');
      b.className = 'btn btn-ghost'; b.style.marginTop = '12px'; b.textContent = 'دانلود همه (ZIP)';
      b.onclick = () => zipDownload(outs, srcName + '-icons.zip');
      L.appendChild(b);
    }
  } catch (e){ alert(e.message); }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
