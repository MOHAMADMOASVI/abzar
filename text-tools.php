<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'ابزار متن فارسی — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">ابزار متن فارسی</h1>
  <p class="page-sub">شمارش، اصلاح حروف عربی، نیم‌فاصله، تبدیل اعداد و پاک‌سازی متن.</p>

  <div class="card">
    <div class="field"><label>متن</label><textarea id="in" style="min-height:180px"></textarea></div>
    <div id="stats" style="margin-top:10px; display:flex; gap:16px; flex-wrap:wrap; color:var(--text-dim); font-size:.88rem"></div>
    <div class="actions" style="margin-top:14px">
      <button class="btn btn-primary btn-sm" data-op="fix">اصلاح ي/ك عربی</button>
      <button class="btn btn-ghost btn-sm" data-op="half">نیم‌فاصله (می / ها)</button>
      <button class="btn btn-ghost btn-sm" data-op="fa">اعداد فارسی</button>
      <button class="btn btn-ghost btn-sm" data-op="en">اعداد انگلیسی</button>
      <button class="btn btn-ghost btn-sm" data-op="diac">حذف اعراب</button>
      <button class="btn btn-ghost btn-sm" data-op="space">حذف فاصله‌های اضافی</button>
      <button class="btn btn-ghost btn-sm" data-op="lines">حذف خطوط خالی</button>
    </div>
    <div class="copy-wrap" style="margin-top:14px">
      <button class="copybtn" id="cp">کپی</button>
      <div class="out" id="out" style="direction:rtl;text-align:right;font-family:inherit;min-height:80px"></div>
    </div>
  </div>
</div>

<script>
function stats(){
  const t = $('#in').value;
  const words = t.trim() ? t.trim().split(/\s+/).length : 0;
  const lines = t ? t.split('\n').length : 0;
  $('#stats').innerHTML =
    '<span>کلمات: <b>' + faNum(words) + '</b></span>' +
    '<span>کاراکترها: <b>' + faNum(t.length) + '</b></span>' +
    '<span>بدون فاصله: <b>' + faNum(t.replace(/\s/g, '').length) + '</b></span>' +
    '<span>خطوط: <b>' + faNum(lines) + '</b></span>' +
    '<span>زمان مطالعه: <b>~' + faNum(Math.max(1, Math.ceil(words / 200))) + ' دقیقه</b></span>';
}
$('#in').addEventListener('input', stats);
stats();

const OPS = {
  fix: t => t.replace(/ي/g, 'ی').replace(/ك/g, 'ک').replace(/ۀ/g, 'هٔ').replace(/٤/g, '۴').replace(/٥/g, '۵').replace(/٦/g, '۶'),
  half: t => t
    .replace(/\bمی (?=\S)/g, 'می‌')
    .replace(/\bنمی (?=\S)/g, 'نمی‌')
    .replace(/ (ها|های|هایی|تر|ترین|ام|ات|اش|مان|تان|شان)\b/g, '‌$1'),
  fa: t => t.replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]),
  en: t => enNum(t),
  diac: t => t.replace(/[ً-ٰٟ]/g, ''),
  space: t => t.replace(/[ \t]{2,}/g, ' ').replace(/ +([.،:؛!؟])/g, '$1').trim(),
  lines: t => t.split('\n').filter(l => l.trim()).join('\n'),
};

$$('.actions .btn').forEach(b => b.onclick = () => {
  $('#out').textContent = OPS[b.dataset.op]($('#in').value);
});
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
