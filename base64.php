<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'Base64 — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">Base64 Encode / Decode</h1>
  <p class="page-sub">متن (با پشتیبانی فارسی/UTF-8) یا فایل.</p>

  <div class="card">
    <div class="tabs">
      <button class="active" data-tab="text">متن</button>
      <button data-tab="file">فایل</button>
    </div>
    <div class="tabpane active" id="tab-text">
      <div class="field"><label>ورودی</label><textarea id="in" class="ltr"></textarea></div>
      <div class="actions">
        <button class="btn btn-primary" id="enc">Encode</button>
        <button class="btn btn-ghost" id="dec">Decode</button>
        <label class="chk"><input type="checkbox" id="urlSafe"> URL-safe</label>
      </div>
      <p class="note hidden" id="msg"></p>
      <div class="copy-wrap" style="margin-top:12px">
        <button class="copybtn" id="cp">کپی</button>
        <div class="out" id="out" style="min-height:100px"></div>
      </div>
    </div>
    <div class="tabpane" id="tab-file">
      <div class="drop" id="drop"><div class="big">فایل را اینجا رها کن</div><div>خروجی: رشته Base64 یا Data URI</div></div>
      <input type="file" id="file" hidden>
      <div class="actions">
        <label class="chk"><input type="checkbox" id="dataUri" checked> با پیشوند data:</label>
      </div>
      <div class="copy-wrap" style="margin-top:12px">
        <button class="copybtn" id="cpF">کپی</button>
        <div class="out" id="outF" style="min-height:100px;max-height:220px"></div>
      </div>
    </div>
  </div>
</div>

<script>
initTabs();
const b64encode = s => btoa(String.fromCharCode(...new TextEncoder().encode(s)));
const b64decode = s => new TextDecoder().decode(Uint8Array.from(atob(s), c => c.charCodeAt(0)));

$('#enc').onclick = () => {
  try {
    let out = b64encode($('#in').value);
    if ($('#urlSafe').checked) out = out.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    $('#out').textContent = out;
    $('#msg').classList.add('hidden');
  } catch (e){ showNote($('#msg'), e.message, 'err'); }
};
$('#dec').onclick = () => {
  try {
    let s = $('#in').value.trim().replace(/-/g, '+').replace(/_/g, '/');
    while (s.length % 4) s += '=';
    $('#out').textContent = b64decode(s);
    $('#msg').classList.add('hidden');
  } catch (e){ showNote($('#msg'), 'رشته Base64 معتبر نیست.', 'err'); }
};
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
$('#cpF').onclick = () => copyText($('#outF').textContent, $('#cpF'));

let lastFile = null;
initDrop($('#drop'), $('#file'), async fs => {
  lastFile = fs[0];
  renderFile();
});
$('#dataUri').onchange = renderFile;
async function renderFile(){
  if (!lastFile) return;
  if (lastFile.size > 5 * 1048576){ $('#outF').textContent = 'فایل بزرگ‌تر از ۵MB برای این کار مناسب نیست.'; return; }
  const buf = new Uint8Array(await lastFile.arrayBuffer());
  let bin = '';
  for (let i = 0; i < buf.length; i += 0x8000) bin += String.fromCharCode(...buf.subarray(i, i + 0x8000));
  const b64 = btoa(bin);
  $('#outF').textContent = $('#dataUri').checked
    ? 'data:' + (lastFile.type || 'application/octet-stream') + ';base64,' + b64
    : b64;
}
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
