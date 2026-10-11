<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'تبدیل تاریخ شمسی و میلادی — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تبدیل تاریخ شمسی ↔ میلادی</h1>
  <p class="page-sub">با روز هفته و فاصله تا امروز.</p>

  <div class="two-col">
    <div class="card">
      <h2>شمسی → میلادی</h2>
      <div class="controls">
        <div class="field"><label>روز</label><input type="number" id="jd" min="1" max="31" style="width:80px"></div>
        <div class="field"><label>ماه</label><select id="jm"></select></div>
        <div class="field"><label>سال</label><input type="number" id="jy" min="1200" max="1500" style="width:100px"></div>
      </div>
      <div class="out" id="jres" style="margin-top:14px; direction:rtl; text-align:right; font-family:inherit">—</div>
    </div>
    <div class="card">
      <h2>میلادی → شمسی</h2>
      <div class="field" style="max-width:220px"><label>تاریخ میلادی</label><input type="date" id="gdate" class="ltr"></div>
      <div class="out" id="gres" style="margin-top:14px; direction:rtl; text-align:right; font-family:inherit">—</div>
    </div>
  </div>
  <div class="card">
    <h2>امروز</h2>
    <div class="out" id="today" style="direction:rtl; text-align:right; font-family:inherit"></div>
  </div>
</div>

<script>
$('#jm').innerHTML = JMONTHS.map((m, i) => `<option value="${i + 1}">${m}</option>`).join('');
const WD = ['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه'];

function diffDays(gy, gm, gd){
  const d = new Date(gy, gm - 1, gd);
  const now = new Date(); now.setHours(0, 0, 0, 0);
  return Math.round((d - now) / 86400000);
}
const rel = n => n === 0 ? '(امروز)' : n > 0 ? '(' + faNum(n) + ' روز دیگر)' : '(' + faNum(-n) + ' روز پیش)';

function j2g(){
  const jy = +enNum($('#jy').value), jm = +$('#jm').value, jd = +enNum($('#jd').value);
  if (!jy || !jd){ $('#jres').textContent = '—'; return; }
  if (jd > jalaliMonthLen(jy, jm)){ $('#jres').textContent = 'تاریخ نامعتبر'; return; }
  const [gy, gm, gd] = toGregorian(jy, jm, jd);
  const w = WD[new Date(gy, gm - 1, gd).getDay()];
  $('#jres').innerHTML = `<b>${w}</b> — <span dir="ltr">${gy}-${String(gm).padStart(2,'0')}-${String(gd).padStart(2,'0')}</span> ${rel(diffDays(gy, gm, gd))}`;
}
function g2j(){
  if (!$('#gdate').value){ $('#gres').textContent = '—'; return; }
  const [gy, gm, gd] = $('#gdate').value.split('-').map(Number);
  const [jy, jm, jd] = toJalali(gy, gm, gd);
  const w = WD[new Date(gy, gm - 1, gd).getDay()];
  $('#gres').innerHTML = `<b>${w}</b> ${faNum(jd)} ${JMONTHS[jm - 1]} ${faNum(jy)} ${rel(diffDays(gy, gm, gd))}`;
}
['jd', 'jm', 'jy'].forEach(id => $('#' + id).addEventListener('input', j2g));
$('#gdate').addEventListener('input', g2j);

// امروز
const n = new Date();
const tj = toJalali(n.getFullYear(), n.getMonth() + 1, n.getDate());
$('#today').innerHTML = `<b>${WD[n.getDay()]}</b> ${faNum(tj[2])} ${JMONTHS[tj[1] - 1]} ${faNum(tj[0])} — <span dir="ltr">${n.toISOString().slice(0, 10)}</span>`;
// مقدار اولیه
$('#jy').value = tj[0]; $('#jm').value = tj[1]; $('#jd').value = tj[2];
j2g();
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
