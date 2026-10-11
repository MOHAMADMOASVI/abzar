<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'ZIP — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">ZIP: فشرده‌سازی و استخراج</h1>
  <p class="page-sub">چند فایل را ZIP کن یا محتوای یک ZIP را ببین و دانلود کن — همه در مرورگر.</p>

  <div class="card">
    <div class="tabs">
      <button class="active" data-tab="make">ساخت ZIP</button>
      <button data-tab="extract">استخراج ZIP</button>
    </div>

    <div class="tabpane active" id="tab-make">
      <div class="drop" id="dropM"><div class="big">فایل‌ها را اینجا رها کن</div><div>یا کلیک کن — چند فایل</div></div>
      <input type="file" id="fileM" multiple hidden>
      <div id="listM"></div>
      <div class="controls" style="margin-top:12px">
        <div class="field"><label>نام فایل</label><input type="text" id="zname" value="archive.zip" class="ltr"></div>
        <div class="field"><label>سطح فشرده‌سازی</label><select id="lvl"><option value="6">معمولی</option><option value="9">حداکثر</option><option value="1">سریع</option></select></div>
      </div>
      <div class="actions"><button class="btn btn-primary hidden" id="goM">ساخت ZIP</button><span id="statM" style="color:var(--text-dim);font-size:.87rem"></span></div>
    </div>

    <div class="tabpane" id="tab-extract">
      <div class="drop" id="dropE"><div class="big">فایل ZIP را اینجا رها کن</div><div>یا کلیک کن</div></div>
      <input type="file" id="fileE" accept=".zip" hidden>
      <p class="note hidden" id="msgE"></p>
      <div id="listE"></div>
      <div class="actions"><button class="btn btn-ghost hidden" id="allE">دانلود همه فایل‌ها (تکی)</button></div>
    </div>
  </div>
</div>

<script>
initTabs();

/* --- ساخت --- */
const files = [];
initDrop($('#dropM'), $('#fileM'), fs => { fs.forEach(f => files.push(f)); renderM(); });
function renderM(){
  const L = $('#listM'); L.innerHTML = '';
  files.forEach((f, i) => {
    const d = document.createElement('div');
    d.className = 'frow';
    d.innerHTML = `<span class="nm">${f.name}</span><span class="sz">${fmtBytes(f.size)}</span><button class="xbtn">✕</button>`;
    d.querySelector('.xbtn').onclick = () => { files.splice(i, 1); renderM(); };
    L.appendChild(d);
  });
  $('#goM').classList.toggle('hidden', !files.length);
}
$('#goM').onclick = async () => {
  setBusy($('#goM'), true);
  try {
    await loadScript('assets/vendor/jszip.min.js');
    const z = new JSZip();
    for (const f of files) z.file(f.name, f);
    const blob = await z.generateAsync({ type: 'blob', compression: 'DEFLATE', compressionOptions: { level: +$('#lvl').value } });
    let name = $('#zname').value.trim() || 'archive.zip';
    if (!/\.zip$/i.test(name)) name += '.zip';
    download(blob, name);
    const orig = files.reduce((s, f) => s + f.size, 0);
    $('#statM').textContent = fmtBytes(orig) + ' ← ' + fmtBytes(blob.size);
  } catch (e){ alert('خطا: ' + e.message); }
  setBusy($('#goM'), false);
};

/* --- استخراج --- */
let zipObj = null;
initDrop($('#dropE'), $('#fileE'), async fs => {
  $('#msgE').classList.add('hidden');
  try {
    await loadScript('assets/vendor/jszip.min.js');
    zipObj = await JSZip.loadAsync(fs[0]);
    const L = $('#listE'); L.innerHTML = '';
    const entries = Object.values(zipObj.files).filter(e => !e.dir);
    for (const e of entries){
      const d = document.createElement('div');
      d.className = 'frow';
      d.innerHTML = `<span class="nm">${e.name}</span>`;
      const a = document.createElement('button');
      a.className = 'copybtn'; a.textContent = 'دانلود';
      a.onclick = async () => download(await e.async('blob'), e.name.split('/').pop());
      d.appendChild(a);
      L.appendChild(d);
    }
    $('#allE').classList.toggle('hidden', entries.length < 2);
    if (!entries.length) showNote($('#msgE'), 'این ZIP خالی است.', 'warn');
  } catch (e){ showNote($('#msgE'), 'فایل ZIP معتبر نیست' + (/encrypted/i.test(e.message) ? ' (رمزدار پشتیبانی نمی‌شود)' : '') + '.', 'err'); }
});
$('#allE').onclick = async () => {
  for (const e of Object.values(zipObj.files).filter(e => !e.dir))
    download(await e.async('blob'), e.name.split('/').pop());
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
