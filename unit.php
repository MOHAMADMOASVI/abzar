<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'تبدیل واحد — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تبدیل واحد</h1>
  <p class="page-sub">طول، وزن، دما، حجم، مساحت، سرعت و داده.</p>

  <div class="card">
    <div class="controls">
      <div class="field">
        <label>دسته</label>
        <select id="cat"></select>
      </div>
      <div class="field"><label>مقدار</label><input type="number" id="val" value="1" step="any" style="width:140px" class="ltr"></div>
      <div class="field"><label>از</label><select id="from"></select></div>
      <div class="field"><label>به</label><select id="to"></select></div>
      <button class="btn btn-ghost btn-sm" id="swap" title="جابه‌جا">⇄</button>
    </div>
    <div class="out" style="margin-top:16px; font-size:1.15rem; direction:rtl; text-align:right" id="res">—</div>
    <div id="all" style="margin-top:14px"></div>
  </div>
</div>

<script>
/* هر واحد: ضریب نسبت به واحد پایه (برای دما تابع جدا) */
const UNITS = {
  'طول': { 'میلی‌متر': .001, 'سانتی‌متر': .01, 'متر': 1, 'کیلومتر': 1000, 'اینچ': .0254, 'فوت': .3048, 'یارد': .9144, 'مایل': 1609.344 },
  'وزن': { 'گرم': 1, 'کیلوگرم': 1000, 'تن': 1e6, 'مثقال': 4.6875, 'سیر': 75, 'پوند': 453.592, 'اونس': 28.3495 },
  'دما': { 'سانتی‌گراد': 'c', 'فارنهایت': 'f', 'کلوین': 'k' },
  'حجم': { 'میلی‌لیتر': .001, 'لیتر': 1, 'متر مکعب': 1000, 'گالن (آمریکا)': 3.78541, 'پیمانه (Cup)': .24 },
  'مساحت': { 'متر مربع': 1, 'هکتار': 10000, 'کیلومتر مربع': 1e6, 'فوت مربع': .092903, 'جریب': 4046.86 },
  'سرعت': { 'متر بر ثانیه': 1, 'کیلومتر بر ساعت': 1 / 3.6, 'مایل بر ساعت': .44704, 'گره دریایی': .514444 },
  'داده': { 'بایت': 1, 'کیلوبایت (KB)': 1024, 'مگابایت (MB)': 1048576, 'گیگابایت (GB)': 1073741824, 'ترابایت (TB)': 1099511627776 },
};

$('#cat').innerHTML = Object.keys(UNITS).map(c => `<option>${c}</option>`).join('');

function fillUnits(){
  const us = Object.keys(UNITS[$('#cat').value]);
  $('#from').innerHTML = us.map(u => `<option>${u}</option>`).join('');
  $('#to').innerHTML = us.map(u => `<option>${u}</option>`).join('');
  $('#to').selectedIndex = Math.min(1, us.length - 1);
  calc();
}

function tempConv(v, from, to){
  let c = from === 'c' ? v : from === 'f' ? (v - 32) * 5 / 9 : v - 273.15;
  return to === 'c' ? c : to === 'f' ? c * 9 / 5 + 32 : c + 273.15;
}

const nice = n => {
  if (!isFinite(n)) return '—';
  const abs = Math.abs(n);
  const s = abs !== 0 && (abs < 1e-4 || abs >= 1e12) ? n.toExponential(4) : (+n.toFixed(6)).toLocaleString('en-US', { maximumFractionDigits: 6 });
  return faNum(s);
};

function calc(){
  const cat = $('#cat').value, v = parseFloat(enNum($('#val').value));
  if (isNaN(v)){ $('#res').textContent = '—'; return; }
  const U = UNITS[cat];
  let out;
  if (cat === 'دما') out = tempConv(v, U[$('#from').value], U[$('#to').value]);
  else out = v * U[$('#from').value] / U[$('#to').value];
  $('#res').innerHTML = nice(v) + ' ' + $('#from').value + ' = <b style="color:var(--gold-ink)">' + nice(out) + '</b> ' + $('#to').value;

  // جدول همه واحدها
  const rows = Object.keys(U).filter(u => u !== $('#from').value).map(u => {
    const o = cat === 'دما' ? tempConv(v, U[$('#from').value], U[u]) : v * U[$('#from').value] / U[u];
    return `<tr><td>${u}</td><td>${nice(o)}</td></tr>`;
  });
  $('#all').innerHTML = '<table class="kv">' + rows.join('') + '</table>';
}

$('#cat').onchange = fillUnits;
['val', 'from', 'to'].forEach(id => $('#' + id).addEventListener('input', calc));
$('#swap').onclick = () => {
  const a = $('#from').selectedIndex;
  $('#from').selectedIndex = $('#to').selectedIndex;
  $('#to').selectedIndex = a;
  calc();
};
fillUnits();
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
