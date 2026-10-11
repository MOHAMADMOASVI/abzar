<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$hasKey = is_file(__DIR__ . '/config/ai.php') && (require __DIR__ . '/config/ai.php')['api_key'] !== '';
$pageTitle = 'تحلیل فایل Excel با AI — ابزار';
require __DIR__ . '/partials/header.php';
?>
<style>
  #chat{ display:flex; flex-direction:column; gap:10px; max-height:50vh; overflow-y:auto; padding:4px; }
  .bubble{ padding:10px 14px; border-radius:10px; font-size:.92rem; line-height:1.9; white-space:pre-wrap; max-width:85%; }
  .bubble.user{ background:var(--gold-soft); color:var(--gold-ink); align-self:flex-start; }
  .bubble.ai{ background:var(--ink); border:1px solid var(--line); align-self:flex-end; }
  #prevWrap{ overflow-x:auto; margin-top:12px; }
  #prevWrap table{ border-collapse:collapse; font-size:.8rem; direction:ltr; }
  #prevWrap td, #prevWrap th{ border:1px solid var(--line); padding:4px 8px; white-space:nowrap; }
  #prevWrap th{ background:var(--ink); font-weight:700; }
</style>
<div class="wrap">
  <h1 class="page-title">تحلیل فایل Excel با AI</h1>
  <p class="page-sub">فایل XLSX یا CSV را بارگذاری کن و از داده‌هایش سؤال بپرس: جمع‌بندی، پیدا کردن مقادیر، الگوها و خلاصه‌سازی.</p>
  <?php if (!$hasKey): ?><p class="note warn">کلید API تنظیم نشده — اول به <a href="ai-settings.php">تنظیمات AI</a> برو.</p><?php endif; ?>

  <div class="card" id="pickCard">
    <div class="drop" id="drop"><div class="big">فایل Excel را اینجا رها کن</div><div>XLSX ،XLS یا CSV</div></div>
    <input type="file" id="file" accept=".xlsx,.xls,.csv,.ods" hidden>
    <p class="note hidden" id="msg"></p>
  </div>

  <div class="card hidden" id="chatCard">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px">
      <h2 style="margin:0" id="docInfo"></h2>
      <div style="display:flex; gap:8px; align-items:center">
        <select id="sheet" class="hidden"></select>
        <button class="btn btn-ghost btn-sm" id="reset">فایل دیگر</button>
      </div>
    </div>
    <details>
      <summary style="cursor:pointer; font-size:.85rem; color:var(--text-dim)">پیش‌نمایش داده (۱۵ ردیف اول)</summary>
      <div id="prevWrap"></div>
    </details>
    <div id="chat" style="margin-top:12px"></div>
    <div class="controls" style="margin-top:14px">
      <div class="field" style="flex:1 1 300px">
        <input type="text" id="q" placeholder="مثلاً: جمع ستون مبلغ چقدر است؟ کدام ردیف بیشترین فروش را دارد؟">
      </div>
      <button class="btn btn-primary" id="ask" <?= $hasKey ? '' : 'disabled' ?>>بپرس</button>
    </div>
  </div>
</div>

<script>
let wb = null, csvText = '', fileName = '', history = [];
const MAX_CHARS = 300000;

initDrop($('#drop'), $('#file'), async fs => {
  const f = fs[0];
  $('#msg').classList.add('hidden');
  try {
    await loadScript('assets/vendor/xlsx.full.min.js');
    wb = XLSX.read(new Uint8Array(await f.arrayBuffer()), { type: 'array' });
    fileName = f.name;
    const sel = $('#sheet');
    sel.innerHTML = wb.SheetNames.map((n, i) => `<option value="${i}">${n}</option>`).join('');
    sel.classList.toggle('hidden', wb.SheetNames.length < 2);
    pickSheet(0);
    $('#pickCard').classList.add('hidden');
    $('#chatCard').classList.remove('hidden');
    $('#q').focus();
  } catch (e){ showNote($('#msg'), 'خطا: ' + e.message, 'err'); }
});

function pickSheet(i){
  const ws = wb.Sheets[wb.SheetNames[i]];
  csvText = XLSX.utils.sheet_to_csv(ws);
  let truncated = false;
  if (csvText.length > MAX_CHARS){ csvText = csvText.slice(0, MAX_CHARS); truncated = true; }
  const rows = csvText.split('\n');
  history = [];
  $('#chat').innerHTML = '';
  $('#docInfo').textContent = fileName + ' — برگه «' + wb.SheetNames[i] + '»، ' + faNum(rows.length - 1) + ' ردیف' + (truncated ? ' (بریده شد)' : '');
  // پیش‌نمایش ۱۵ ردیف
  const prev = rows.slice(0, 16).map(r => r.split(','));
  $('#prevWrap').innerHTML = '<table>' + prev.map((r, ri) =>
    '<tr>' + r.map(c => (ri ? '<td>' : '<th>') + c.replace(/&/g,'&amp;').replace(/</g,'&lt;') + (ri ? '</td>' : '</th>')).join('') + '</tr>'
  ).join('') + '</table>';
}
$('#sheet').onchange = e => pickSheet(+e.target.value);

$('#reset').onclick = () => {
  $('#chatCard').classList.add('hidden');
  $('#pickCard').classList.remove('hidden');
};

function addBubble(text, cls){
  const d = document.createElement('div');
  d.className = 'bubble ' + cls;
  d.textContent = text;
  $('#chat').appendChild(d);
  $('#chat').scrollTop = $('#chat').scrollHeight;
  return d;
}

async function ask(){
  const q = $('#q').value.trim();
  if (!q || !csvText) return;
  $('#q').value = '';
  addBubble(q, 'user');
  const thinking = addBubble('در حال بررسی داده‌ها…', 'ai');
  setBusy($('#ask'), true);
  try {
    const docIntro = 'داده‌های فایل «' + fileName + '» به‌صورت CSV:\n\n' + csvText;
    const messages = [];
    if (!history.length){
      messages.push({ role: 'user', content: docIntro + '\n\nسؤال: ' + q });
    } else {
      messages.push({ role: 'user', content: docIntro + '\n\nسؤال: ' + history[0].q });
      messages.push({ role: 'assistant', content: history[0].a });
      for (const h of history.slice(1)){
        messages.push({ role: 'user', content: h.q });
        messages.push({ role: 'assistant', content: h.a });
      }
      messages.push({ role: 'user', content: q });
    }
    const r = await aiFetch({
      system: 'تو تحلیل‌گر داده هستی. فقط بر اساس داده CSV ارائه‌شده پاسخ بده. محاسبات را قدم‌به‌قدم و دقیق انجام بده و عدد نهایی را واضح بگو. اگر داده کافی نیست، صادقانه بگو. پاسخ فارسی باشد.',
      messages,
    });
    if (!r.ok) throw new Error(r.message);
    thinking.textContent = r.text.trim();
    history.push({ q, a: r.text.trim() });
    if (history.length > 8) history.splice(1, 1);
  } catch (e){
    thinking.textContent = '⚠️ ' + e.message;
  }
  setBusy($('#ask'), false);
  $('#q').focus();
}
$('#ask').onclick = ask;
$('#q').addEventListener('keydown', e => { if (e.key === 'Enter') ask(); });
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
