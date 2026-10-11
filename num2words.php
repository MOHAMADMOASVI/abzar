<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'عدد به حروف فارسی — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">عدد به حروف فارسی</h1>
  <p class="page-sub">برای نوشتن چک و اسناد — با تبدیل خودکار تومان/ریال.</p>

  <div class="card">
    <div class="controls">
      <div class="field" style="flex:1 1 240px"><label>عدد</label><input type="text" id="in" class="ltr" placeholder="1250000" inputmode="numeric"></div>
      <div class="field"><label>واحد</label><select id="unit"><option value="">بدون واحد</option><option value="تومان" selected>تومان</option><option value="ریال">ریال</option></select></div>
    </div>
    <div class="copy-wrap" style="margin-top:16px">
      <button class="copybtn" id="cp">کپی</button>
      <div class="out" id="out" style="direction:rtl;text-align:right;font-family:inherit;font-size:1.1rem;min-height:56px"></div>
    </div>
    <div id="extra" style="margin-top:12px"></div>
  </div>
</div>

<script>
const ONES = ['','یک','دو','سه','چهار','پنج','شش','هفت','هشت','نه'];
const TEENS = ['ده','یازده','دوازده','سیزده','چهارده','پانزده','شانزده','هفده','هجده','نوزده'];
const TENS = ['','','بیست','سی','چهل','پنجاه','شصت','هفتاد','هشتاد','نود'];
const HUNDREDS = ['','صد','دویست','سیصد','چهارصد','پانصد','ششصد','هفتصد','هشتصد','نهصد'];
const SCALE = ['','هزار','میلیون','میلیارد','هزار میلیارد','تریلیارد'];

function three(n){
  const parts = [];
  const h = Math.floor(n / 100), rest = n % 100;
  if (h) parts.push(HUNDREDS[h]);
  if (rest >= 10 && rest < 20) parts.push(TEENS[rest - 10]);
  else {
    const t = Math.floor(rest / 10), o = rest % 10;
    if (t) parts.push(TENS[t]);
    if (o) parts.push(ONES[o]);
  }
  return parts.join(' و ');
}

function toWords(numStr){
  numStr = numStr.replace(/[^\d]/g, '');
  if (!numStr) return '';
  if (/^0+$/.test(numStr)) return 'صفر';
  numStr = numStr.replace(/^0+/, '');
  if (numStr.length > 18) return 'عدد خیلی بزرگ است';
  const groups = [];
  for (let i = numStr.length; i > 0; i -= 3) groups.unshift(+numStr.slice(Math.max(0, i - 3), i));
  const parts = [];
  groups.forEach((g, i) => {
    if (!g) return;
    const scale = SCALE[groups.length - 1 - i];
    parts.push(three(g) + (scale ? ' ' + scale : ''));
  });
  return parts.join(' و ');
}

function run(){
  const raw = enNum($('#in').value);
  const words = toWords(raw);
  const unit = $('#unit').value;
  $('#out').textContent = words ? words + (unit ? ' ' + unit : '') : '—';
  const digits = raw.replace(/[^\d]/g, '');
  if (digits && unit){
    const n = BigInt(digits);
    const other = unit === 'تومان'
      ? 'معادل ' + faNum((n * 10n).toLocaleString('en-US')) + ' ریال — ' + toWords((n * 10n).toString()) + ' ریال'
      : (n % 10n === 0n ? 'معادل ' + faNum((n / 10n).toLocaleString('en-US')) + ' تومان — ' + toWords((n / 10n).toString()) + ' تومان' : '');
    $('#extra').innerHTML = (digits.length > 3 ? '<p style="color:var(--text-dim);font-size:.87rem">با جداکننده: <b dir="ltr">' + faNum(BigInt(digits).toLocaleString('en-US')) + '</b></p>' : '') +
      (other ? '<p style="color:var(--text-dim);font-size:.87rem">' + other + '</p>' : '');
  } else $('#extra').innerHTML = '';
}
$('#in').addEventListener('input', run);
$('#unit').addEventListener('input', run);
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
