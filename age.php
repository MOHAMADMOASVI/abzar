<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'محاسبه سن — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">محاسبه سن</h1>
  <p class="page-sub">تاریخ تولد را شمسی یا میلادی وارد کن.</p>

  <div class="card">
    <div class="tabs">
      <button class="active" data-tab="j">شمسی</button>
      <button data-tab="g">میلادی</button>
    </div>
    <div class="tabpane active" id="tab-j">
      <div class="controls">
        <div class="field"><label>روز</label><input type="number" id="jd" min="1" max="31" value="1" style="width:80px"></div>
        <div class="field"><label>ماه</label><select id="jm"></select></div>
        <div class="field"><label>سال</label><input type="number" id="jy" min="1250" max="1420" value="1370" style="width:100px"></div>
      </div>
    </div>
    <div class="tabpane" id="tab-g">
      <div class="field" style="max-width:220px"><label>تاریخ تولد</label><input type="date" id="gdate" class="ltr"></div>
    </div>
    <div class="actions"><button class="btn btn-primary" id="go">محاسبه</button></div>
    <div id="res" style="margin-top:16px"></div>
  </div>
</div>

<script>
initTabs();
let mode = 'j';
$$('.tabs button').forEach(b => b.addEventListener('click', () => mode = b.dataset.tab));
$('#jm').innerHTML = JMONTHS.map((m, i) => `<option value="${i + 1}">${m}</option>`).join('');

$('#go').onclick = () => {
  let bg; // [gy, gm, gd]
  if (mode === 'j'){
    const jy = +enNum($('#jy').value), jm = +$('#jm').value, jd = +enNum($('#jd').value);
    if (!jy || !jd || jd > jalaliMonthLen(jy, jm)){ $('#res').innerHTML = '<p class="note err">تاریخ شمسی معتبر نیست.</p>'; return; }
    bg = toGregorian(jy, jm, jd);
  } else {
    if (!$('#gdate').value){ $('#res').innerHTML = '<p class="note err">تاریخ را انتخاب کن.</p>'; return; }
    bg = $('#gdate').value.split('-').map(Number);
  }
  const birth = new Date(bg[0], bg[1] - 1, bg[2]);
  const now = new Date();
  if (birth > now){ $('#res').innerHTML = '<p class="note err">تاریخ تولد در آینده است!</p>'; return; }

  // سن دقیق: سال/ماه/روز
  let y = now.getFullYear() - birth.getFullYear();
  let m = now.getMonth() - birth.getMonth();
  let d = now.getDate() - birth.getDate();
  if (d < 0){ m--; d += new Date(now.getFullYear(), now.getMonth(), 0).getDate(); }
  if (m < 0){ y--; m += 12; }

  const totalDays = Math.floor((now - birth) / 86400000);
  const bj = toJalali(bg[0], bg[1], bg[2]);
  const nextBdayThisYear = new Date(now.getFullYear(), birth.getMonth(), birth.getDate());
  const nextBday = nextBdayThisYear >= now ? nextBdayThisYear : new Date(now.getFullYear() + 1, birth.getMonth(), birth.getDate());
  const toBday = Math.ceil((nextBday - now) / 86400000);
  const weekday = ['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه'][birth.getDay()];

  $('#res').innerHTML = `
    <div class="out" style="direction:rtl;text-align:right;font-size:1.05rem;font-family:inherit">
      سن شما: <b style="color:var(--gold-ink)">${faNum(y)} سال و ${faNum(m)} ماه و ${faNum(d)} روز</b>
    </div>
    <table class="kv" style="margin-top:12px">
      <tr><td>تولد شمسی</td><td>${weekday} ${faNum(bj[2])} ${JMONTHS[bj[1] - 1]} ${faNum(bj[0])}</td></tr>
      <tr><td>تولد میلادی</td><td dir="ltr" style="text-align:left">${bg[0]}-${String(bg[1]).padStart(2,'0')}-${String(bg[2]).padStart(2,'0')}</td></tr>
      <tr><td>کل روزهای زندگی</td><td>${faNum(totalDays.toLocaleString('en-US'))} روز (${faNum(Math.floor(totalDays / 7).toLocaleString('en-US'))} هفته)</td></tr>
      <tr><td>تا تولد بعدی</td><td>${toBday === 0 ? '🎂 تولدت مبارک!' : faNum(toBday) + ' روز'}</td></tr>
    </table>`;
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
