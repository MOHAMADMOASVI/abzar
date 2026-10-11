<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'JSON Formatter — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">JSON Formatter / Validator</h1>
  <p class="page-sub">مرتب‌سازی، فشرده‌سازی و اعتبارسنجی با نمایش محل دقیق خطا.</p>

  <div class="card">
    <div class="field"><label>ورودی JSON</label><textarea id="in" class="ltr" style="min-height:200px" placeholder='{"name":"test"}'></textarea></div>
    <div class="actions">
      <button class="btn btn-primary" id="fmt">مرتب‌سازی (Pretty)</button>
      <button class="btn btn-ghost" id="min">فشرده (Minify)</button>
      <button class="btn btn-ghost" id="val">فقط اعتبارسنجی</button>
      <div class="field" style="flex-direction:row;align-items:center;gap:8px">
        <label style="margin:0">تورفتگی</label>
        <select id="indent" style="min-width:70px"><option value="2">۲</option><option value="4">۴</option><option value="tab">Tab</option></select>
      </div>
    </div>
    <p class="note hidden" id="msg"></p>
    <div class="copy-wrap" style="margin-top:12px">
      <button class="copybtn" id="cp">کپی</button>
      <div class="out" id="out" style="min-height:120px"></div>
    </div>
  </div>
</div>

<script>
function parse(){
  const src = $('#in').value;
  if (!src.trim()){ showNote($('#msg'), 'ورودی خالی است.', 'warn'); return null; }
  try {
    const v = JSON.parse(src);
    return v;
  } catch (e){
    const m = e.message.match(/position (\d+)/);
    let detail = e.message;
    if (m){
      const pos = +m[1];
      const upTo = src.slice(0, pos);
      const line = upTo.split('\n').length;
      const col = pos - upTo.lastIndexOf('\n');
      detail += ' — خط ' + faNum(line) + '، ستون ' + faNum(col) +
        '<br><code dir="ltr">' + src.slice(Math.max(0, pos - 30), pos + 30).replace(/</g, '&lt;') + '</code>';
    }
    showNote($('#msg'), 'JSON نامعتبر: ' + detail, 'err');
    $('#out').textContent = '';
    return null;
  }
}
$('#fmt').onclick = () => {
  const v = parse();
  if (v === null) return;
  const ind = $('#indent').value === 'tab' ? '\t' : +$('#indent').value;
  $('#out').textContent = JSON.stringify(v, null, ind);
  showNote($('#msg'), 'معتبر ✓', 'ok');
};
$('#min').onclick = () => {
  const v = parse();
  if (v === null) return;
  $('#out').textContent = JSON.stringify(v);
  showNote($('#msg'), 'معتبر ✓ — حجم فشرده: ' + fmtBytes(new Blob([$('#out').textContent]).size), 'ok');
};
$('#val').onclick = () => {
  const v = parse();
  if (v !== null) showNote($('#msg'), 'معتبر ✓', 'ok');
};
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
