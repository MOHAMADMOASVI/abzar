<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'حذف متادیتای عکس — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">نمایش و حذف متادیتای عکس</h1>
  <p class="page-sub">EXIF (دوربین، تاریخ، موقعیت GPS و…) را می‌بیند و پاک می‌کند. JPEG و PNG <b>بدون افت کیفیت</b> پاک‌سازی می‌شوند؛ بقیه فرمت‌ها با بازخوانی تصویر.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">عکس‌ها را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept="image/*" multiple hidden>
    <div id="list"></div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">حذف متادیتا</button>
      <button class="btn btn-ghost hidden" id="zipAll">دانلود همه (ZIP)</button>
    </div>
  </div>
</div>

<script>
const files = [];
initDrop($('#drop'), $('#file'), async fs => {
  for (const f of fs){
    const it = { f };
    try { it.info = await inspect(f); } catch { it.info = null; }
    files.push(it);
  }
  render();
});

/* ---- خواندن خلاصه EXIF از JPEG ---- */
async function inspect(f){
  const buf = new Uint8Array(await f.arrayBuffer());
  if (buf[0] !== 0xFF || buf[1] !== 0xD8) return null; // فقط JPEG
  let p = 2;
  const found = [];
  let exif = null;
  while (p + 4 < buf.length && buf[p] === 0xFF){
    const marker = buf[p + 1];
    if (marker === 0xDA) break; // شروع داده تصویر
    const len = (buf[p + 2] << 8) | buf[p + 3];
    if (marker >= 0xE1 && marker <= 0xEF) found.push('APP' + (marker - 0xE0) + ' (' + fmtBytes(len) + ')');
    if (marker === 0xFE) found.push('Comment');
    if (marker === 0xE1 && buf[p + 4] === 0x45 && buf[p + 5] === 0x78) // "Ex"
      exif = parseExif(buf.subarray(p + 10, p + 2 + len));
    p += 2 + len;
  }
  return { found, exif };
}

function parseExif(t){
  try {
    const le = t[0] === 0x49;
    const u16 = o => le ? t[o] | (t[o + 1] << 8) : (t[o] << 8) | t[o + 1];
    const u32 = o => le ? (t[o] | (t[o+1]<<8) | (t[o+2]<<16) | (t[o+3]<<24)) >>> 0 : ((t[o]<<24) | (t[o+1]<<16) | (t[o+2]<<8) | t[o+3]) >>> 0;
    const readAscii = (off, cnt) => new TextDecoder().decode(t.subarray(off, off + cnt - 1));
    const out = {};
    const readIFD = (off) => {
      const n = u16(off);
      const tags = {};
      for (let i = 0; i < n; i++){
        const e = off + 2 + i * 12;
        const tag = u16(e), type = u16(e + 2), cnt = u32(e + 4);
        let voff = e + 8;
        const size = (type === 2 ? 1 : type === 3 ? 2 : 4) * cnt;
        if (size > 4) voff = u32(e + 8);
        tags[tag] = { type, cnt, voff };
      }
      return tags;
    };
    const ifd0 = readIFD(u32(4));
    const get = (tags, tag) => {
      const g = tags[tag];
      if (!g) return null;
      if (g.type === 2) return readAscii(g.voff, g.cnt);
      if (g.type === 3) return u16(g.voff);
      return u32(g.voff);
    };
    if (get(ifd0, 0x010F)) out['سازنده'] = get(ifd0, 0x010F);
    if (get(ifd0, 0x0110)) out['مدل دوربین'] = get(ifd0, 0x0110);
    if (get(ifd0, 0x0132)) out['تاریخ'] = get(ifd0, 0x0132);
    if (ifd0[0x8769]){
      try {
        const sub = readIFD(get(ifd0, 0x8769));
        if (get(sub, 0x9003)) out['تاریخ عکاسی'] = get(sub, 0x9003);
      } catch {}
    }
    out['موقعیت GPS'] = ifd0[0x8825] ? '⚠️ دارد' : 'ندارد';
    return out;
  } catch { return null; }
}

/* ---- پاک‌سازی بدون افت کیفیت ---- */
function stripJpeg(buf){
  const out = [buf.subarray(0, 2)];
  let p = 2;
  while (p + 4 < buf.length && buf[p] === 0xFF){
    const marker = buf[p + 1];
    if (marker === 0xDA){ out.push(buf.subarray(p)); break; }
    const len = (buf[p + 2] << 8) | buf[p + 3];
    const isMeta = (marker >= 0xE1 && marker <= 0xEF) || marker === 0xFE;
    if (!isMeta) out.push(buf.subarray(p, p + 2 + len));
    p += 2 + len;
  }
  return new Blob(out, { type: 'image/jpeg' });
}

function stripPng(buf){
  const keep = ['IHDR','PLTE','IDAT','IEND','tRNS','gAMA','sRGB','sBIT','bKGD','pHYs','acTL','fcTL','fdAT'];
  const out = [buf.subarray(0, 8)];
  let p = 8;
  const td = new TextDecoder();
  while (p + 8 <= buf.length){
    const len = (buf[p]<<24 | buf[p+1]<<16 | buf[p+2]<<8 | buf[p+3]) >>> 0;
    const type = td.decode(buf.subarray(p + 4, p + 8));
    const chunk = buf.subarray(p, p + 12 + len);
    if (keep.includes(type)) out.push(chunk);
    p += 12 + len;
    if (type === 'IEND') break;
  }
  return new Blob(out, { type: 'image/png' });
}

function render(){
  const L = $('#list'); L.innerHTML = '';
  files.forEach((it, i) => {
    const d = document.createElement('div');
    d.className = 'frow';
    let meta = '';
    if (it.info && it.info.exif && Object.keys(it.info.exif).length){
      meta = Object.entries(it.info.exif).map(([k, v]) => k + ': ' + v).join(' | ');
    } else if (it.info && it.info.found.length){
      meta = 'متادیتا: ' + it.info.found.join('، ');
    } else meta = 'متادیتای قابل توجهی ندارد';
    d.innerHTML = `<span class="nm">${it.f.name}</span><span class="sz">${fmtBytes(it.f.size)}</span>
      <span style="flex-basis:100%;font-size:.78rem;color:var(--text-dim)">${meta}</span><span class="st"></span>
      <button class="xbtn">✕</button>`;
    d.querySelector('.xbtn').onclick = () => { files.splice(i, 1); render(); };
    if (it.out){
      const a = document.createElement('a');
      a.className = 'dl';
      a.textContent = 'دانلود پاک‌شده (' + fmtBytes(it.out.size) + ')';
      a.href = URL.createObjectURL(it.out); a.download = it.outName;
      d.querySelector('.st').replaceWith(a);
    }
    L.appendChild(d);
  });
  $('#go').classList.toggle('hidden', !files.length);
  $('#zipAll').classList.toggle('hidden', !files.some(x => x.out));
}

$('#go').onclick = async () => {
  setBusy($('#go'), true);
  for (const it of files){
    try {
      const buf = new Uint8Array(await it.f.arrayBuffer());
      const ext = it.f.name.split('.').pop().toLowerCase();
      if (buf[0] === 0xFF && buf[1] === 0xD8){
        it.out = stripJpeg(buf);
        it.outName = baseName(it.f.name) + '-clean.jpg';
      } else if (buf[0] === 0x89 && buf[1] === 0x50){
        it.out = stripPng(buf);
        it.outName = baseName(it.f.name) + '-clean.png';
      } else {
        // بازخوانی از طریق کانواس (متادیتا حفظ نمی‌شود)
        const im = await loadImage(it.f);
        const cv = document.createElement('canvas');
        cv.width = im.naturalWidth; cv.height = im.naturalHeight;
        cv.getContext('2d').drawImage(im, 0, 0);
        it.out = await canvasBlob(cv, 'image/png');
        it.outName = baseName(it.f.name) + '-clean.png';
      }
    } catch (e){ it.out = null; }
  }
  setBusy($('#go'), false);
  render();
};

$('#zipAll').onclick = () =>
  zipDownload(files.filter(x => x.out).map(x => ({ name: x.outName, blob: x.out })), 'cleaned.zip');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
