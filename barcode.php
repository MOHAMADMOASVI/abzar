<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'Barcode Generator — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">Barcode Generator</h1>
  <p class="page-sub">EAN-13، Code128، Code39 و… با خروجی PNG.</p>

  <div class="card">
    <div class="controls">
      <div class="field">
        <label>نوع بارکد</label>
        <select id="type">
          <option value="CODE128" selected>Code 128 (متن و عدد)</option>
          <option value="EAN13">EAN-13 (۱۲ یا ۱۳ رقم)</option>
          <option value="EAN8">EAN-8</option>
          <option value="UPC">UPC-A</option>
          <option value="CODE39">Code 39</option>
          <option value="ITF14">ITF-14</option>
        </select>
      </div>
      <div class="field" style="flex:1 1 240px"><label>مقدار</label><input type="text" id="val" class="ltr" value="1234567890"></div>
      <div class="field"><label>ارتفاع</label><input type="number" id="h" value="80" min="30" max="200" style="width:90px"></div>
      <label class="chk"><input type="checkbox" id="showVal" checked> نمایش مقدار زیر بارکد</label>
    </div>
    <p class="note hidden" id="msg"></p>
    <div style="margin-top:16px; text-align:center; background:#fff; border:1px solid var(--line); border-radius:4px; padding:16px">
      <canvas id="cv"></canvas>
    </div>
    <div class="actions"><button class="btn btn-primary" id="dl">دانلود PNG</button></div>
  </div>
</div>

<script src="assets/vendor/JsBarcode.all.min.js"></script>
<script>
function draw(){
  $('#msg').classList.add('hidden');
  try {
    JsBarcode('#cv', enNum($('#val').value.trim()), {
      format: $('#type').value,
      height: +$('#h').value || 80,
      displayValue: $('#showVal').checked,
      margin: 12,
      fontSize: 15,
    });
  } catch (e){
    showNote($('#msg'), 'مقدار برای این نوع بارکد معتبر نیست (مثلاً EAN-13 دقیقاً ۱۲ یا ۱۳ رقم می‌خواهد).', 'err');
  }
}
['type', 'val', 'h', 'showVal'].forEach(id => $('#' + id).addEventListener('input', draw));
draw();
$('#dl').onclick = async () => download(await canvasBlob($('#cv'), 'image/png'), 'barcode.png');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
