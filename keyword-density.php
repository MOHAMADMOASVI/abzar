<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'چگالی کلمات کلیدی — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">چگالی کلمات کلیدی</h1>
  <p class="page-sub">تحلیل تکرار عبارت‌های ۱ تا ۳ کلمه‌ای در متن — فارسی و انگلیسی.</p>

  <div class="card">
    <div class="field"><label>متن مقاله</label><textarea id="in" style="min-height:200px"></textarea></div>
    <div class="controls" style="margin-top:12px">
      <label class="chk"><input type="checkbox" id="stop" checked> حذف کلمات پرتکرار بی‌اهمیت (از، به، the و…)</label>
      <button class="btn btn-primary" id="go">تحلیل</button>
      <span id="stats" style="color:var(--text-dim);font-size:.87rem"></span>
    </div>
    <div class="two-col" style="margin-top:16px" id="results"></div>
  </div>
</div>

<script>
const STOP_FA = new Set(['و','در','به','از','که','این','آن','را','با','برای','است','تا','بر','هم','یا','اگر','هر','نیز','شد','شود','های','یک','ها','می','بی','ای','اما','چه','او','ما','شما','آنها','خود','دیگر','باید','شده','بود','دارد','کرد','کند','هست','نه','بین','پس','روی','همه'])
const STOP_EN = new Set(['the','a','an','and','or','of','to','in','on','for','with','at','by','is','are','was','were','be','it','this','that','as','from','but','not','have','has','had','you','your','we','our','they','their','can','will','would','i']);

function tokenize(txt){
  return enNum(txt)
    .replace(/[‌]/g, ' ')
    .toLowerCase()
    .split(/[^\p{L}\p{N}]+/u)
    .filter(w => w.length > 1);
}

$('#go').onclick = () => {
  const words = tokenize($('#in').value);
  if (!words.length) return;
  const useStop = $('#stop').checked;
  const isStop = w => STOP_FA.has(w) || STOP_EN.has(w);
  $('#stats').textContent = 'کل کلمات: ' + faNum(words.length) + ' — یکتا: ' + faNum(new Set(words).size);

  const tables = [];
  for (const n of [1, 2, 3]){
    const freq = new Map();
    for (let i = 0; i + n <= words.length; i++){
      const gram = words.slice(i, i + n);
      if (useStop && (isStop(gram[0]) || isStop(gram[n - 1]))) continue;
      const key = gram.join(' ');
      freq.set(key, (freq.get(key) || 0) + 1);
    }
    const top = [...freq.entries()].filter(e => e[1] > 1).sort((a, b) => b[1] - a[1]).slice(0, 15);
    if (!top.length) continue;
    tables.push('<div><h2 style="font-size:.92rem;color:var(--text-dim);margin-bottom:8px">' +
      (n === 1 ? 'تک‌کلمه' : n === 2 ? 'دوکلمه‌ای' : 'سه‌کلمه‌ای') + '</h2><table class="kv">' +
      top.map(([w, c]) => `<tr><td>${w}</td><td>${faNum(c)} بار — ${faNum((c / (words.length - n + 1) * 100).toFixed(1))}٪</td></tr>`).join('') +
      '</table></div>');
  }
  $('#results').innerHTML = tables.join('') || '<p class="note warn">عبارت تکرارشونده‌ای پیدا نشد.</p>';
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
