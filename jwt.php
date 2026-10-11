<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'JWT Decoder — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">JWT Decoder</h1>
  <p class="page-sub">هدر و Payload توکن را باز می‌کند و زمان‌های صدور/انقضا را می‌خواند. امضا اعتبارسنجی نمی‌شود.</p>

  <div class="card">
    <div class="field"><label>توکن JWT</label><textarea id="in" class="ltr" style="min-height:110px" placeholder="eyJhbGciOi..."></textarea></div>
    <div class="actions"><button class="btn btn-primary" id="go">Decode</button></div>
    <p class="note hidden" id="msg"></p>
    <div class="two-col" style="margin-top:14px">
      <div>
        <h2 style="font-size:.92rem;color:var(--text-dim);margin-bottom:8px">Header</h2>
        <div class="out" id="head" style="min-height:80px"></div>
      </div>
      <div>
        <h2 style="font-size:.92rem;color:var(--text-dim);margin-bottom:8px">Payload</h2>
        <div class="out" id="pl" style="min-height:80px"></div>
      </div>
    </div>
    <div id="times" style="margin-top:14px"></div>
  </div>
</div>

<script>
const b64uDecode = s => {
  s = s.replace(/-/g, '+').replace(/_/g, '/');
  while (s.length % 4) s += '=';
  return new TextDecoder().decode(Uint8Array.from(atob(s), c => c.charCodeAt(0)));
};

$('#go').onclick = () => {
  $('#msg').classList.add('hidden');
  $('#times').innerHTML = '';
  const parts = $('#in').value.trim().split('.');
  if (parts.length < 2){ showNote($('#msg'), 'توکن JWT باید حداقل دو بخش نقطه‌جدا داشته باشد.', 'err'); return; }
  try {
    const head = JSON.parse(b64uDecode(parts[0]));
    const pl = JSON.parse(b64uDecode(parts[1]));
    $('#head').textContent = JSON.stringify(head, null, 2);
    $('#pl').textContent = JSON.stringify(pl, null, 2);
    const rows = [];
    const fmtT = t => {
      const d = new Date(t * 1000);
      const j = toJalali(d.getFullYear(), d.getMonth() + 1, d.getDate());
      return d.toLocaleString('en-GB') + ' — ' + faNum(j[0]) + '/' + faNum(String(j[1]).padStart(2, '0')) + '/' + faNum(String(j[2]).padStart(2, '0'));
    };
    if (pl.iat) rows.push(['صدور (iat)', fmtT(pl.iat)]);
    if (pl.nbf) rows.push(['اعتبار از (nbf)', fmtT(pl.nbf)]);
    if (pl.exp){
      const expired = pl.exp * 1000 < Date.now();
      rows.push(['انقضا (exp)', fmtT(pl.exp) + (expired ? ' — ⚠️ منقضی شده' : ' — معتبر')]);
    }
    if (rows.length)
      $('#times').innerHTML = '<table class="kv">' + rows.map(r => `<tr><td>${r[0]}</td><td dir="ltr" style="text-align:left">${r[1]}</td></tr>`).join('') + '</table>';
  } catch (e){ showNote($('#msg'), 'توکن قابل خواندن نیست: ' + e.message, 'err'); }
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
