<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$inqReady = is_file(__DIR__ . '/config/inquiry.php')
         && (string)((include __DIR__ . '/config/inquiry.php')['zibal_token'] ?? '') !== '';
$isAdmin = abzar_is_logged_in();
$pageTitle = 'اعتبارسنجی کد ملی / شبا / کارت — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">اعتبارسنجی کد ملی، شبا و کارت بانکی</h1>
  <p class="page-sub">بررسی رقم کنترل طبق الگوریتم رسمی — همه در مرورگر.</p>

  <div class="card">
    <h2>کد ملی</h2>
    <div class="controls">
      <div class="field" style="flex:1 1 200px"><input type="text" id="melli" class="ltr" placeholder="0012345678" inputmode="numeric" maxlength="10"></div>
    </div>
    <p id="melliRes" style="margin-top:8px"></p>
  </div>

  <div class="card">
    <h2>شماره شبا (IBAN)</h2>
    <div class="controls">
      <div class="field" style="flex:1 1 300px"><input type="text" id="sheba" class="ltr" placeholder="IR062960000000100324200001"></div>
    </div>
    <p id="shebaRes" style="margin-top:8px"></p>
  </div>

  <div class="card">
    <h2>شماره کارت بانکی</h2>
    <div class="controls">
      <div class="field" style="flex:1 1 260px"><input type="text" id="card" class="ltr" placeholder="6104337812345678" inputmode="numeric" maxlength="19"></div>
      <?php if ($inqReady): ?>
      <button class="btn btn-primary hidden" id="whois">استعلام نام دارنده کارت</button>
      <?php endif; ?>
    </div>
    <p id="cardRes" style="margin-top:8px"></p>
    <p id="whoisRes" class="note hidden"></p>
    <?php if (!$inqReady && $isAdmin): ?>
    <p class="note warn" style="margin-top:10px">
      برای نمایش نام صاحب کارت، توکن استعلام زیبال را در <a href="ai-settings.php">تنظیمات</a> وارد کن.
    </p>
    <?php endif; ?>
  </div>
</div>

<script>
const BANKS = {
  '603799':'ملی','589210':'سپه','627648':'توسعه صادرات','627961':'صنعت و معدن','603770':'کشاورزی',
  '628023':'مسکن','627760':'پست بانک','502908':'توسعه تعاون','627412':'اقتصاد نوین','622106':'پارسیان',
  '502229':'پاسارگاد','627488':'کارآفرین','621986':'سامان','639346':'سینا','639607':'سرمایه',
  '636214':'تات/آینده','502806':'شهر','502938':'دی','603769':'صادرات','610433':'ملت','991975':'ملت',
  '589463':'رفاه','627353':'تجارت','585983':'تجارت','627381':'انصار','636795':'مرکزی','505785':'ایران‌زمین',
  '636949':'حکمت ایرانیان','505416':'گردشگری','606373':'قرض‌الحسنه مهر','628157':'موسسه اعتباری توسعه',
  '505801':'موسسه کوثر','606256':'موسسه ملل','504172':'رسالت'
};
const ok = (el, msg) => el.innerHTML = '<span class="badge ok">معتبر ✓</span> ' + (msg || '');
const bad = (el, msg) => el.innerHTML = '<span class="badge err">نامعتبر ✗</span> ' + (msg || '');

$('#melli').oninput = () => {
  const el = $('#melliRes');
  const v = enNum($('#melli').value).replace(/\D/g, '');
  if (!v){ el.textContent = ''; return; }
  if (v.length !== 10){ el.innerHTML = '<span style="color:var(--text-mute)">۱۰ رقم لازم است…</span>'; return; }
  if (/^(\d)\1{9}$/.test(v)){ bad(el, 'همه ارقام تکراری است.'); return; }
  const check = +v[9];
  const sum = [...v.slice(0, 9)].reduce((s, d, i) => s + d * (10 - i), 0) % 11;
  (sum < 2 ? check === sum : check === 11 - sum) ? ok(el) : bad(el, 'رقم کنترل اشتباه است.');
};

$('#sheba').oninput = () => {
  const el = $('#shebaRes');
  let v = enNum($('#sheba').value).replace(/[\s-]/g, '').toUpperCase();
  if (!v){ el.textContent = ''; return; }
  if (!v.startsWith('IR')) v = 'IR' + v;
  if (v.length !== 26){ el.innerHTML = '<span style="color:var(--text-mute)">شبا باید IR + ۲۴ رقم باشد (' + faNum(v.length) + '/۲۶)…</span>'; return; }
  if (!/^IR\d{24}$/.test(v)){ bad(el, 'فقط رقم مجاز است.'); return; }
  // ISO 7064 mod 97
  const rearranged = v.slice(4) + '1827' + v.slice(2, 4); // I=18, R=27
  let rem = 0;
  for (const ch of rearranged) rem = (rem * 10 + +ch) % 97;
  rem === 1 ? ok(el, 'کد بانک: ' + v.slice(4, 7)) : bad(el, 'رقم کنترل اشتباه است.');
};

const INQ_READY = <?= json_encode($inqReady) ?>;
let validCard = null;

$('#card').oninput = () => {
  const el = $('#cardRes');
  const v = enNum($('#card').value).replace(/[\s-]/g, '');
  validCard = null;
  const wb = $('#whois');
  if (wb) wb.classList.add('hidden');
  $('#whoisRes').classList.add('hidden');
  if (!v){ el.textContent = ''; return; }
  if (v.length !== 16){ el.innerHTML = '<span style="color:var(--text-mute)">۱۶ رقم لازم است (' + faNum(v.length) + '/۱۶)…</span>'; return; }
  let sum = 0;
  for (let i = 0; i < 16; i++){
    let d = +v[i];
    if (i % 2 === 0){ d *= 2; if (d > 9) d -= 9; }
    sum += d;
  }
  const bank = BANKS[v.slice(0, 6)];
  if (sum % 10 === 0){
    ok(el, bank ? 'بانک ' + bank : '');
    validCard = v;
    if (wb && INQ_READY) wb.classList.remove('hidden');
  } else {
    bad(el, 'رقم کنترل (Luhn) اشتباه است.');
  }
};

/* استعلام نام دارنده کارت (زیبال) — برای مهمان‌ها با کپچا */
const whoisBtn = $('#whois');
if (whoisBtn) whoisBtn.onclick = async () => {
  if (!validCard) return;
  setBusy(whoisBtn, true, 'در حال استعلام…');
  try {
    for (;;){
      const r = await (await fetch('api_card.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ card: validCard }),
      })).json();
      if (r.error === 'captcha_required'){ await solveCaptcha(); continue; }
      if (!r.ok) throw new Error(r.message);
      showNote($('#whoisRes'),
        'دارنده کارت: <b>' + r.name.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</b>' +
        (r.bank ? ' — ' + String(r.bank).replace(/&/g,'&amp;').replace(/</g,'&lt;') : ''), 'ok');
      break;
    }
  } catch (e){
    showNote($('#whoisRes'), 'استعلام ناموفق: ' + e.message, 'err');
  }
  setBusy(whoisBtn, false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
