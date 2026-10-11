<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'UUID Generator — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">UUID Generator</h1>
  <p class="page-sub">UUID نسخه ۴ (تصادفی امن) — تکی یا انبوه.</p>

  <div class="card">
    <div class="controls">
      <div class="field"><label>تعداد</label><input type="number" id="n" value="5" min="1" max="1000" style="width:100px"></div>
      <label class="chk"><input type="checkbox" id="upper"> حروف بزرگ</label>
      <label class="chk"><input type="checkbox" id="nodash"> بدون خط تیره</label>
      <button class="btn btn-primary" id="go">تولید</button>
    </div>
    <div class="copy-wrap" style="margin-top:14px">
      <button class="copybtn" id="cp">کپی همه</button>
      <div class="out" id="out" style="min-height:120px"></div>
    </div>
  </div>
</div>

<script>
function gen(){
  let u = crypto.randomUUID ? crypto.randomUUID() : (() => {
    const b = crypto.getRandomValues(new Uint8Array(16));
    b[6] = (b[6] & 0x0f) | 0x40;
    b[8] = (b[8] & 0x3f) | 0x80;
    const h = [...b].map(x => x.toString(16).padStart(2, '0')).join('');
    return h.slice(0,8)+'-'+h.slice(8,12)+'-'+h.slice(12,16)+'-'+h.slice(16,20)+'-'+h.slice(20);
  })();
  if ($('#nodash').checked) u = u.replaceAll('-', '');
  if ($('#upper').checked) u = u.toUpperCase();
  return u;
}
$('#go').onclick = () => {
  const n = Math.min(1000, Math.max(1, +$('#n').value || 1));
  $('#out').textContent = [...Array(n)].map(gen).join('\n');
};
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
$('#go').click();
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
