<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'تبدیل ارز — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تبدیل ارز</h1>
  <p class="page-sub">نرخ روز ارزهای جهانی از بانک مرکزی اروپا (رایگان، بدون کلید). برای تومان، نرخ بازار را دستی وارد کن.</p>

  <div class="card">
    <h2>ارزهای جهانی</h2>
    <div class="controls">
      <div class="field"><label>مقدار</label><input type="number" id="amt" value="1" step="any" class="ltr" style="width:130px"></div>
      <div class="field"><label>از</label><select id="from"><option>USD</option></select></div>
      <div class="field"><label>به</label><select id="to"><option>EUR</option></select></div>
      <button class="btn btn-ghost btn-sm" id="swap">⇄</button>
      <button class="btn btn-primary" id="go">تبدیل</button>
    </div>
    <p class="note hidden" id="msg"></p>
    <div class="out hidden" id="res" style="margin-top:14px; font-size:1.15rem; direction:rtl; text-align:right"></div>
    <p id="dateInfo" style="color:var(--text-mute); font-size:.8rem; margin-top:8px"></p>
  </div>

  <div class="card">
    <h2>تبدیل دلار/تومان (نرخ دستی)</h2>
    <div class="controls">
      <div class="field"><label>نرخ دلار به تومان</label><input type="number" id="rate" class="ltr" placeholder="مثلاً 95000" style="width:150px"></div>
      <div class="field"><label>مقدار</label><input type="number" id="amt2" value="100" step="any" class="ltr" style="width:130px"></div>
      <div class="field"><label>جهت</label><select id="dir"><option value="u2t">دلار → تومان</option><option value="t2u">تومان → دلار</option></select></div>
    </div>
    <div class="out" id="res2" style="margin-top:14px; font-size:1.1rem; direction:rtl; text-align:right">نرخ را وارد کن</div>
  </div>
</div>

<script>
let rates = null, rateDate = '';
const CURRENCIES = ['USD','EUR','GBP','TRY','AED','CNY','JPY','CHF','CAD','AUD','RUB','INR','SEK','NOK','KRW','SGD'];

async function loadRates(){
  try {
    const r = await (await fetch('https://api.frankfurter.app/latest?base=USD')).json();
    rates = { USD: 1, ...r.rates };
    rateDate = r.date;
    const opts = CURRENCIES.filter(c => rates[c] !== undefined).map(c => `<option>${c}</option>`).join('');
    $('#from').innerHTML = opts;
    $('#to').innerHTML = opts;
    $('#to').value = 'EUR';
    $('#dateInfo').textContent = 'نرخ‌ها به تاریخ ' + rateDate + ' (ECB)';
    $('#msg').classList.add('hidden');
  } catch (e){
    showNote($('#msg'), 'دریافت نرخ‌ها ناموفق بود — اینترنت متصل است؟', 'err');
  }
}
loadRates();

$('#go').onclick = () => {
  if (!rates){ loadRates(); return; }
  const v = parseFloat(enNum($('#amt').value)) || 0;
  const out = v * rates[$('#to').value] / rates[$('#from').value];
  $('#res').classList.remove('hidden');
  $('#res').innerHTML = faNum(v.toLocaleString('en-US')) + ' ' + $('#from').value +
    ' = <b style="color:var(--gold-ink)">' + faNum(out.toLocaleString('en-US', { maximumFractionDigits: 2 })) + '</b> ' + $('#to').value;
};
$('#swap').onclick = () => {
  const a = $('#from').value;
  $('#from').value = $('#to').value;
  $('#to').value = a;
};

function calc2(){
  const rate = parseFloat(enNum($('#rate').value)) || 0;
  const v = parseFloat(enNum($('#amt2').value)) || 0;
  if (!rate){ $('#res2').textContent = 'نرخ را وارد کن'; return; }
  if ($('#dir').value === 'u2t')
    $('#res2').innerHTML = faNum(v.toLocaleString('en-US')) + ' دلار = <b style="color:var(--gold-ink)">' +
      faNum((v * rate).toLocaleString('en-US')) + '</b> تومان';
  else
    $('#res2').innerHTML = faNum(v.toLocaleString('en-US')) + ' تومان = <b style="color:var(--gold-ink)">' +
      faNum((v / rate).toLocaleString('en-US', { maximumFractionDigits: 2 })) + '</b> دلار';
}
['rate', 'amt2', 'dir'].forEach(id => $('#' + id).addEventListener('input', calc2));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
