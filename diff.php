<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'مقایسه دو متن — ابزار';
require __DIR__ . '/partials/header.php';
?>
<style>
  .dline{ padding:2px 8px; direction:rtl; text-align:right; white-space:pre-wrap; word-break:break-word; font-size:.88rem; }
  .dline.add{ background:rgba(63,185,80,.14); }
  .dline.del{ background:rgba(229,83,75,.12); text-decoration:line-through; text-decoration-color:rgba(229,83,75,.5); }
  .dline .tag{ display:inline-block; width:18px; color:var(--text-mute); user-select:none; }
</style>
<div class="wrap">
  <h1 class="page-title">مقایسه دو متن</h1>
  <p class="page-sub">تفاوت خط‌به‌خط دو متن — سبز اضافه‌شده، قرمز حذف‌شده.</p>

  <div class="two-col">
    <div class="card"><div class="field"><label>متن ۱ (قدیمی)</label><textarea id="a" style="min-height:180px"></textarea></div></div>
    <div class="card"><div class="field"><label>متن ۲ (جدید)</label><textarea id="b" style="min-height:180px"></textarea></div></div>
  </div>
  <div class="card">
    <div class="actions" style="margin:0 0 12px">
      <button class="btn btn-primary" id="go">مقایسه</button>
      <label class="chk"><input type="checkbox" id="trim" checked> نادیده گرفتن فاصله‌های ابتدا/انتها</label>
      <span id="stat" style="color:var(--text-dim);font-size:.87rem"></span>
    </div>
    <div id="res" style="border:1px solid var(--line); border-radius:4px; overflow:hidden"></div>
  </div>
</div>

<script>
/* LCS خطی ساده */
function diffLines(a, b){
  const n = a.length, m = b.length;
  if (n * m > 4e6) return null; // خیلی بزرگ
  const dp = Array.from({ length: n + 1 }, () => new Uint16Array(m + 1));
  for (let i = n - 1; i >= 0; i--)
    for (let j = m - 1; j >= 0; j--)
      dp[i][j] = a[i] === b[j] ? dp[i + 1][j + 1] + 1 : Math.max(dp[i + 1][j], dp[i][j + 1]);
  const out = [];
  let i = 0, j = 0;
  while (i < n && j < m){
    if (a[i] === b[j]){ out.push(['=', a[i]]); i++; j++; }
    else if (dp[i + 1][j] >= dp[i][j + 1]) out.push(['-', a[i++]]);
    else out.push(['+', b[j++]]);
  }
  while (i < n) out.push(['-', a[i++]]);
  while (j < m) out.push(['+', b[j++]]);
  return out;
}

const escapeHtml = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');

$('#go').onclick = () => {
  const prep = t => $('#trim').checked ? t.split('\n').map(l => l.trim()) : t.split('\n');
  const d = diffLines(prep($('#a').value), prep($('#b').value));
  if (!d){ $('#res').innerHTML = '<p class="note err" style="margin:10px">متن‌ها برای مقایسه خط‌به‌خط خیلی بزرگ‌اند.</p>'; return; }
  let add = 0, del = 0;
  $('#res').innerHTML = d.map(([op, line]) => {
    if (op === '+') add++;
    if (op === '-') del++;
    const cls = op === '+' ? 'add' : op === '-' ? 'del' : '';
    return `<div class="dline ${cls}"><span class="tag">${op === '=' ? '' : op}</span>${escapeHtml(line) || '&nbsp;'}</div>`;
  }).join('');
  $('#stat').textContent = faNum(add) + ' خط اضافه، ' + faNum(del) + ' خط حذف';
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
