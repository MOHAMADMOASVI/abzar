<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'رمز عبور — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">رمز عبور: تولید و سنجش قدرت</h1>
  <p class="page-sub">رمز امن بساز یا قدرت رمز فعلی‌ات را بسنج — هیچ‌چیز از مرورگر خارج نمی‌شود.</p>

  <div class="card">
    <h2>تولید رمز</h2>
    <div class="controls">
      <div class="field"><label>طول: <b id="lv">16</b></label><input type="range" id="len" min="8" max="64" value="16" style="width:180px"></div>
      <label class="chk"><input type="checkbox" id="up" checked> A-Z</label>
      <label class="chk"><input type="checkbox" id="lo" checked> a-z</label>
      <label class="chk"><input type="checkbox" id="di" checked> 0-9</label>
      <label class="chk"><input type="checkbox" id="sy" checked> !@#$</label>
      <label class="chk"><input type="checkbox" id="noAmb"> بدون کاراکترهای گیج‌کننده (l1IO0)</label>
      <button class="btn btn-primary" id="gen">تولید</button>
    </div>
    <div id="passList" style="margin-top:14px"></div>
  </div>

  <div class="card">
    <h2>سنجش قدرت رمز</h2>
    <div class="field"><label>رمز را وارد کن</label><input type="text" id="check" class="ltr" autocomplete="off"></div>
    <div class="progress" id="meter" style="display:block;margin-top:12px"><div class="bar" style="transition:width .3s, background .3s"></div></div>
    <div id="verdict" style="margin-top:6px"></div>
  </div>
</div>

<script>
$('#len').oninput = e => $('#lv').textContent = faNum(e.target.value);

function makePass(){
  let up = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', lo = 'abcdefghijklmnopqrstuvwxyz', di = '0123456789', sy = '!@#$%^&*()-_=+[]{};:,.?';
  if ($('#noAmb').checked){ up = up.replace(/[IO]/g, ''); lo = lo.replace(/[l]/g, ''); di = di.replace(/[01]/g, ''); }
  const sets = [];
  if ($('#up').checked) sets.push(up);
  if ($('#lo').checked) sets.push(lo);
  if ($('#di').checked) sets.push(di);
  if ($('#sy').checked) sets.push(sy);
  if (!sets.length) return '';
  const len = +$('#len').value;
  const all = sets.join('');
  const rnd = n => crypto.getRandomValues(new Uint32Array(1))[0] % n;
  // حداقل یکی از هر مجموعه
  const chars = sets.map(s => s[rnd(s.length)]);
  while (chars.length < len) chars.push(all[rnd(all.length)]);
  // بر زدن
  for (let i = chars.length - 1; i > 0; i--){
    const j = rnd(i + 1);
    [chars[i], chars[j]] = [chars[j], chars[i]];
  }
  return chars.join('');
}

$('#gen').onclick = () => {
  const L = $('#passList'); L.innerHTML = '';
  for (let i = 0; i < 5; i++){
    const p = makePass();
    if (!p){ L.innerHTML = '<p class="note warn">حداقل یک مجموعه کاراکتر انتخاب کن.</p>'; return; }
    const d = document.createElement('div');
    d.className = 'frow';
    d.innerHTML = `<span class="nm" style="font-family:ui-monospace,Menlo,monospace">${p.replace(/</g,'&lt;')}</span>`;
    const b = document.createElement('button');
    b.className = 'copybtn'; b.textContent = 'کپی';
    b.onclick = () => copyText(p, b);
    d.appendChild(b);
    L.appendChild(d);
  }
};
$('#gen').click();

let zx = null;
$('#check').oninput = async () => {
  const v = $('#check').value;
  const bar = $('#meter .bar'), verdict = $('#verdict');
  if (!v){ bar.style.width = '0%'; verdict.textContent = ''; return; }
  if (!zx){
    verdict.textContent = 'بارگذاری تحلیل‌گر…';
    await loadScript('assets/vendor/zxcvbn.js');
    zx = zxcvbn;
  }
  const r = zx(v);
  const colors = ['#E5534B', '#E8863D', '#E8A33D', '#8DB600', '#3FB950'];
  const labels = ['خیلی ضعیف', 'ضعیف', 'متوسط', 'خوب', 'عالی'];
  bar.style.width = ((r.score + 1) * 20) + '%';
  bar.style.background = colors[r.score];
  const crack = r.crack_times_display.offline_slow_hashing_1e4_per_second;
  verdict.innerHTML = '<b style="color:' + colors[r.score] + '">' + labels[r.score] + '</b>' +
    ' — زمان شکستن (حمله آفلاین): <b dir="ltr">' + crack + '</b>' +
    (r.feedback.warning ? '<br><span style="color:var(--warn)">' + r.feedback.warning + '</span>' : '');
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
