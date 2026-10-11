<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'Regex Tester — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">Regex Tester</h1>
  <p class="page-sub">تست زنده عبارت باقاعده (موتور JavaScript) با هایلایت و نمایش گروه‌ها.</p>

  <div class="card">
    <div class="controls">
      <div class="field" style="flex:1 1 300px">
        <label>الگو (Pattern)</label>
        <input type="text" id="pat" class="ltr" placeholder="\d{2,4}-\d+">
      </div>
      <div class="field"><label>فلگ‌ها</label>
        <div style="display:flex;gap:10px">
          <label class="chk"><input type="checkbox" class="flag" value="g" checked> g</label>
          <label class="chk"><input type="checkbox" class="flag" value="i"> i</label>
          <label class="chk"><input type="checkbox" class="flag" value="m"> m</label>
          <label class="chk"><input type="checkbox" class="flag" value="s"> s</label>
          <label class="chk"><input type="checkbox" class="flag" value="u"> u</label>
        </div>
      </div>
    </div>
    <div class="field" style="margin-top:12px"><label>متن آزمایشی</label><textarea id="txt" class="ltr" style="min-height:140px"></textarea></div>
    <p class="note hidden" id="msg"></p>
    <div style="margin-top:12px">
      <h2 style="font-size:.92rem;color:var(--text-dim);margin-bottom:8px">نتیجه (<span id="cnt">۰</span> تطبیق)</h2>
      <div class="out" id="hl" style="white-space:pre-wrap;word-break:break-word"></div>
    </div>
    <div id="groups" style="margin-top:12px"></div>
  </div>
</div>

<script>
const escapeHtml = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

function run(){
  const pat = $('#pat').value, txt = $('#txt').value;
  const flags = $$('.flag').filter(f => f.checked).map(f => f.value).join('');
  $('#msg').classList.add('hidden');
  if (!pat){ $('#hl').innerHTML = escapeHtml(txt); $('#cnt').textContent = '۰'; $('#groups').innerHTML = ''; return; }
  let re;
  try { re = new RegExp(pat, flags); }
  catch (e){ showNote($('#msg'), 'الگوی نامعتبر: ' + e.message, 'err'); return; }

  let html = '', last = 0, count = 0;
  const rows = [];
  const globalRe = flags.includes('g') ? re : new RegExp(pat, flags + 'g');
  for (const m of txt.matchAll(globalRe)){
    if (m[0] === '' ){ if (globalRe.lastIndex++ >= txt.length) break; continue; }
    count++;
    html += escapeHtml(txt.slice(last, m.index)) + '<mark>' + escapeHtml(m[0]) + '</mark>';
    last = m.index + m[0].length;
    if (m.length > 1 && count <= 50)
      rows.push('<tr><td>#' + count + '</td><td dir="ltr" style="text-align:left">' +
        m.slice(1).map((g, i) => '$' + (i + 1) + ' = ' + (g === undefined ? '—' : escapeHtml(g))).join('<br>') + '</td></tr>');
    if (count > 5000) break;
  }
  html += escapeHtml(txt.slice(last));
  $('#hl').innerHTML = html || '<span style="color:var(--text-mute)">متنی وارد نشده</span>';
  $('#cnt').textContent = faNum(count);
  $('#groups').innerHTML = rows.length ? '<h2 style="font-size:.92rem;color:var(--text-dim);margin-bottom:8px">گروه‌ها</h2><table class="kv">' + rows.join('') + '</table>' : '';
}
['pat', 'txt'].forEach(id => $('#' + id).addEventListener('input', run));
$$('.flag').forEach(f => f.addEventListener('change', run));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
