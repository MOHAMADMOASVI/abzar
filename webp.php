<?php
require __DIR__ . '/auth.php';
abzar_require_page();

$pageTitle = 'کم کردن حجم تصویر — ابزار';
$active = 'webp';
require __DIR__ . '/partials/header.php';
?>
<style>
  input[type=range]{ width:200px; accent-color:var(--gold-dim); }
  select{
    background:var(--ink-2); color:var(--text); border:1px solid var(--line);
    border-radius:var(--radius-sm); padding:8px 10px; font-family:inherit; font-size:.9rem; min-width:180px;
  }
  .controls{ display:flex; flex-wrap:wrap; gap:22px; align-items:flex-end; }
  .field{ display:flex; flex-direction:column; gap:6px; }
  .field label{ font-size:.85rem; color:var(--text-dim); }
  #qval{ font-weight:800; color:var(--gold-ink); }

  #drop{
    border:2px dashed var(--line); border-radius:var(--radius); padding:44px 16px; text-align:center;
    cursor:pointer; transition:.15s; color:var(--text-mute); background:var(--ink-2);
  }
  #drop:hover, #drop.over{ border-color:var(--gold-dim); background:#F3F7FF; color:var(--text); }
  #drop .big{ font-size:1.05rem; color:var(--text); margin-bottom:6px; font-weight:700; }

  .row{
    display:flex; align-items:center; gap:10px; padding:11px 4px; border-bottom:1px solid var(--line);
    font-size:.9rem; flex-wrap:wrap;
  }
  .row:last-child{ border-bottom:0; }
  .row .nm{ flex:1 1 220px; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:500; }
  .row .sz{ color:var(--text-dim); font-variant-numeric:tabular-nums; direction:ltr; white-space:nowrap; }

  .rename-wrap{ display:flex; align-items:center; gap:5px; flex:1 1 260px; direction:ltr; }
  .rename-wrap input{
    flex:1; background:var(--ink); color:var(--text); border:1px solid var(--line);
    border-radius:var(--radius-sm); padding:8px 10px; font-family:inherit; font-size:.9rem;
    direction:ltr; text-align:left; min-width:0;
  }
  .rename-wrap input:focus{ outline:none; border-color:var(--gold-dim); background:var(--ink-2); }
  .rename-wrap .ext{ color:var(--text-mute); font-size:.82rem; }

  .badge{ font-size:.75rem; padding:3px 9px; border-radius:20px; font-weight:800; white-space:nowrap; }
  .badge.ok{ background:rgba(63,185,80,.16); color:#2E8B3D; }
  .badge.err{ background:rgba(229,83,75,.14); color:var(--danger); }

  a.dl{
    background:var(--ink); color:var(--gold-ink); border:1px solid var(--line);
    border-radius:var(--radius-sm); padding:7px 14px; font-size:.85rem; font-weight:700; text-decoration:none;
  }
  a.dl:hover{ border-color:var(--gold-dim); background:var(--gold-soft); }

  .x{ background:var(--ink-2); color:var(--text-mute); border:1px solid var(--line); border-radius:var(--radius-sm); padding:6px 10px; line-height:1; cursor:pointer; }
  .x:hover{ color:var(--danger); border-color:var(--danger); }

  .actions{ display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-top:14px; }
  #summary{ display:none; align-items:center; gap:14px; flex-wrap:wrap; margin-top:6px; }
  #summary .saved{ color:#2E8B3D; font-weight:800; }
  .hidden{ display:none; }
  .spin{ width:14px; height:14px; border:2px solid var(--line); border-top-color:var(--gold-dim); border-radius:50%; display:inline-block; animation:sp .7s linear infinite; vertical-align:-2px; }
  @keyframes sp{ to{ transform:rotate(360deg); } }
</style>

<div class="wrap">
  <h1 class="page-title">کم کردن حجم تصویر</h1>
  <p class="page-sub">هر عکسی را بکش و رها کن؛ فرمت خروجی و اسم دلخواه را انتخاب کن، بعد با حجم کمتر تبدیل کن.</p>

  <div class="card">
    <div class="controls">
      <div class="field">
        <label>فرمت خروجی</label>
        <select id="format">
          <option value="webp">WebP (کمترین حجم)</option>
          <option value="jpg">JPG</option>
          <option value="png">PNG</option>
        </select>
      </div>
      <div class="field">
        <label>کیفیت: <span id="qval">82</span></label>
        <input type="range" id="quality" min="1" max="100" value="82">
      </div>
      <div class="field">
        <label>حداکثر ابعاد (پیکسل)</label>
        <select id="maxdim">
          <option value="">بدون تغییر ابعاد</option>
          <option value="3840">۳۸۴۰ (4K)</option>
          <option value="2560">۲۵۶۰</option>
          <option value="1920">۱۹۲۰ (فول‌اچ‌دی)</option>
          <option value="1280">۱۲۸۰</option>
          <option value="800">۸۰۰ (تصویر بندانگشتی)</option>
        </select>
      </div>
    </div>
  </div>

  <div id="drop" class="card">
    <div class="big">عکس‌ها را اینجا رها کن</div>
    <div>یا برای انتخاب کلیک کن — چند فایل هم‌زمان مجاز است</div>
    <input type="file" id="file" accept="image/*,.heic,.heif,.tif,.tiff" multiple class="hidden">
  </div>

  <div class="card hidden" id="stageCard">
    <h2>اسم فایل‌ها را دلخواه تغییر بده، بعد تبدیل کن</h2>
    <div id="stageList"></div>
    <div class="actions">
      <button class="btn btn-primary" id="convertBtn">تبدیل</button>
      <button class="btn btn-ghost" id="stageClear">پاک کردن لیست</button>
    </div>
  </div>

  <div class="card hidden" id="resultCard">
    <h2>فایل‌های آماده دانلود</h2>
    <div id="list"></div>
    <div id="summary">
      <span class="saved" id="savedText"></span>
      <button class="btn btn-primary" id="dlAll">دانلود همه</button>
      <button class="btn btn-ghost" id="clearBtn">پاک کردن</button>
    </div>
  </div>
</div>

<!-- کتابخانه‌ی محلی برای تبدیل HEIC داخل مرورگر (heic2any، MIT) -->
<script src="assets/vendor/heic2any.min.js"></script>
<script>
const drop = $('#drop'), fileInput = $('#file');
const stageCard = $('#stageCard'), stageList = $('#stageList');
const resultCard = $('#resultCard'), list = $('#list'), summary = $('#summary');

let staged = [];   // {file, inputEl, rowEl}
let results = [];   // {name, blob, url, origSize}
let uid = 0;

$('#quality').addEventListener('input', e => $('#qval').textContent = e.target.value);

function currentExt(){ return '.' + $('#format').value; }
$('#format').addEventListener('change', () => {
  document.querySelectorAll('.rename-wrap .ext').forEach(e => e.textContent = currentExt());
});

drop.addEventListener('click', () => fileInput.click());
fileInput.addEventListener('change', () => { addFiles(fileInput.files); fileInput.value = ''; });

;['dragenter','dragover'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('over'); }));
;['dragleave','drop'].forEach(ev => drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('over'); }));
drop.addEventListener('drop', e => addFiles(e.dataTransfer.files));

function fmt(bytes){
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
  return (bytes/1024/1024).toFixed(2) + ' MB';
}
function escapeHtml(s){
  return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function baseName(name){ return name.replace(/\.[^.\/\\]+$/, ''); }

function addFiles(fileList){
  const files = Array.from(fileList).filter(f => f.size > 0);
  if (!files.length) return;
  stageCard.classList.remove('hidden');

  for (const f of files){
    const id = 'st' + (++uid);
    const row = document.createElement('div');
    row.className = 'row';
    row.dataset.id = id;
    row.innerHTML = `
      <div class="rename-wrap">
        <input type="text" value="${escapeHtml(baseName(f.name))}" placeholder="اسم فایل">
        <span class="ext">${currentExt()}</span>
      </div>
      <span class="sz">${fmt(f.size)}</span>
      <button class="x" title="حذف">حذف</button>`;
    stageList.appendChild(row);

    const item = { file: f, inputEl: row.querySelector('input'), rowEl: row };
    staged.push(item);
    row.querySelector('.x').addEventListener('click', () => removeStaged(item));
  }
}

function removeStaged(item){
  staged = staged.filter(s => s !== item);
  item.rowEl.remove();
  if (!staged.length) stageCard.classList.add('hidden');
}

function sanitizeName(raw, fallback, ext){
  let n = (raw || '').trim();
  n = n.replace(/\.(webp|png|jpe?g|avif|heic|heif|tiff?|gif|bmp)$/i, '');
  n = n.replace(/[\/\\:*?"<>|]+/g, '_').replace(/^\.+/, '');
  if (!n) n = fallback || 'image';
  return n + ext;
}

// آیا فایل HEIC/HEIF است؟ (تشخیص با پسوند یا نوع)
function isHeic(f){
  return /\.(heic|heif)$/i.test(f.name) || /image\/hei[cf]/i.test(f.type || '');
}

// HEIC را داخل مرورگر به JPEG تبدیل می‌کند تا روی هر سروری قابل پردازش باشد.
async function heicToJpeg(f){
  if (typeof heic2any !== 'function'){
    throw new Error('کتابخانه‌ی HEIC بارگذاری نشد.');
  }
  let out;
  try {
    out = await heic2any({ blob: f, toType: 'image/jpeg', quality: 0.92 });
  } catch(e){
    throw new Error('تبدیل HEIC در مرورگر ناموفق بود.');
  }
  const blob = Array.isArray(out) ? out[0] : out;
  return new File([blob], baseName(f.name) + '.jpg', { type: 'image/jpeg' });
}

$('#convertBtn').addEventListener('click', async () => {
  if (!staged.length) return;
  const btn = $('#convertBtn');
  btn.disabled = true;
  resultCard.classList.remove('hidden');

  const quality = $('#quality').value;
  const maxdim  = $('#maxdim').value;
  const format  = $('#format').value;
  const ext     = currentExt();
  const batch = staged.slice();

  for (const item of batch){
    const outName = sanitizeName(item.inputEl.value, baseName(item.file.name), ext);
    const f = item.file;

    const origSize = f.size; // اندازه‌ی واقعی فایل اصلی

    const row = document.createElement('div');
    row.className = 'row';
    row.innerHTML = `<span class="nm">${escapeHtml(outName)}</span>
      <span class="status"><span class="spin"></span> در حال تبدیل…</span>`;
    list.appendChild(row);
    const statusEl = row.querySelector('.status');

    try {
      let uploadFile = f;
      if (isHeic(f)){
        if (statusEl) statusEl.innerHTML = '<span class="spin"></span> آماده‌سازی HEIC…';
        uploadFile = await heicToJpeg(f); // تبدیل HEIC در مرورگر
        if (statusEl) statusEl.innerHTML = '<span class="spin"></span> در حال تبدیل…';
      }

      const fd = new FormData();
      fd.append('image', uploadFile);
      fd.append('quality', quality);
      fd.append('maxdim', maxdim);
      fd.append('format', format);
      fd.append('origsize', origSize); // اندازه‌ی فایل اصلی (برای محافظ «بزرگ‌تر نشدن» سرور)

      const res = await fetch('convert.php', { method:'POST', body: fd });
      if (!res.ok){
        let msg = 'خطا';
        try { msg = (await res.json()).error || msg; } catch(_){}
        throw new Error(msg);
      }
      const blob = await res.blob();
      const newSize  = blob.size;
      const url = URL.createObjectURL(blob);
      results.push({ name: outName, blob, url, origSize });

      const pct = origSize > 0 ? Math.round((1 - newSize/origSize) * 100) : 0;
      const pctTxt = pct >= 0 ? `−${pct}%` : `+${-pct}%`;
      const badgeCls = pct >= 0 ? 'ok' : 'err';
      const badgeTitle = pct >= 0 ? '' : ' title="فایل اصلی از این فشرده‌تر بود؛ برای حجم کمتر فرمت WebP یا کیفیت پایین‌تر را امتحان کن"';
      row.innerHTML = `<span class="nm">${escapeHtml(outName)}</span>
        <span class="sz">${fmt(origSize)} → ${fmt(newSize)}</span>
        <span class="badge ${badgeCls}"${badgeTitle}>${pctTxt}</span>
        <a class="dl" href="${url}" download="${escapeHtml(outName)}">دانلود</a>`;
    } catch(err){
      row.innerHTML = `<span class="nm">${escapeHtml(outName)}</span>
        <span class="badge err">ناموفق</span>
        <span class="sz">${escapeHtml(err.message || '')}</span>`;
    }

    removeStaged(item);
    updateSummary();
  }
  btn.disabled = false;
});

$('#stageClear').addEventListener('click', () => {
  staged = []; stageList.innerHTML = ''; stageCard.classList.add('hidden');
});

function updateSummary(){
  if (!results.length){ summary.style.display = 'none'; return; }
  summary.style.display = 'flex';
  const now = results.reduce((s,r)=>s + r.blob.size, 0);
  $('#savedText').textContent = `${results.length} فایل آماده شد — مجموع خروجی: ${fmt(now)}`;
}

$('#dlAll').addEventListener('click', async () => {
  for (const r of results){
    const a = document.createElement('a');
    a.href = r.url; a.download = r.name;
    document.body.appendChild(a); a.click(); a.remove();
    await new Promise(res => setTimeout(res, 250));
  }
});

$('#clearBtn').addEventListener('click', () => {
  results.forEach(r => URL.revokeObjectURL(r.url));
  results = []; list.innerHTML = '';
  resultCard.classList.add('hidden');
});
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
