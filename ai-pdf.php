<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$hasKey = is_file(__DIR__ . '/config/ai.php') && (require __DIR__ . '/config/ai.php')['api_key'] !== '';
$pageTitle = 'چت با PDF — ابزار';
require __DIR__ . '/partials/header.php';
?>
<style>
  #chat{ display:flex; flex-direction:column; gap:10px; max-height:50vh; overflow-y:auto; padding:4px; }
  .bubble{ padding:10px 14px; border-radius:10px; font-size:.92rem; line-height:1.9; white-space:pre-wrap; max-width:85%; }
  .bubble.user{ background:var(--gold-soft); color:var(--gold-ink); align-self:flex-start; }
  .bubble.ai{ background:var(--ink); border:1px solid var(--line); align-self:flex-end; }
</style>
<div class="wrap">
  <h1 class="page-title">چت با PDF</h1>
  <p class="page-sub">فایل PDF را بارگذاری کن و درباره محتوایش سؤال بپرس. متن در مرورگر استخراج و فقط برای پاسخ به Claude API ارسال می‌شود.</p>
  <?php if (!$hasKey): ?><p class="note warn">کلید API تنظیم نشده — اول به <a href="ai-settings.php">تنظیمات AI</a> برو.</p><?php endif; ?>

  <div class="card" id="pickCard">
    <div class="drop" id="drop"><div class="big">فایل PDF را اینجا رها کن</div><div>PDF متنی — فایل اسکن‌شده متن ندارد</div></div>
    <input type="file" id="file" accept="application/pdf" hidden>
    <p class="note hidden" id="msg"></p>
  </div>

  <div class="card hidden" id="chatCard">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px">
      <h2 style="margin:0" id="docInfo"></h2>
      <button class="btn btn-ghost btn-sm" id="reset">فایل دیگر</button>
    </div>
    <div id="chat"></div>
    <div class="controls" style="margin-top:14px">
      <div class="field" style="flex:1 1 300px">
        <input type="text" id="q" placeholder="سؤالت درباره این فایل…">
      </div>
      <button class="btn btn-primary" id="ask" <?= $hasKey ? '' : 'disabled' ?>>بپرس</button>
    </div>
  </div>
</div>

<script>
let docText = '', docName = '', history = [];

initDrop($('#drop'), $('#file'), async fs => {
  const f = fs[0];
  $('#msg').classList.add('hidden');
  try {
    await loadScript('assets/vendor/pdf.min.js');
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'assets/vendor/pdf.worker.min.js';
    const pdf = await pdfjsLib.getDocument({ data: new Uint8Array(await f.arrayBuffer()) }).promise;
    let text = '';
    for (let p = 1; p <= pdf.numPages; p++){
      const tc = await (await pdf.getPage(p)).getTextContent();
      text += '\n--- صفحه ' + p + ' ---\n' + tc.items.map(i => i.str).join(' ');
      if (text.length > 350000) break; // سقف امن
    }
    if (text.replace(/[-\s صفحه\d]/g, '').length < 50)
      throw new Error('متنی در این PDF پیدا نشد — احتمالاً اسکن‌شده است.');
    docText = text;
    docName = f.name;
    history = [];
    $('#chat').innerHTML = '';
    $('#docInfo').textContent = docName + ' — ' + faNum(pdf.numPages) + ' صفحه';
    $('#pickCard').classList.add('hidden');
    $('#chatCard').classList.remove('hidden');
    $('#q').focus();
  } catch (e){ showNote($('#msg'), 'خطا: ' + e.message, 'err'); }
});

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
  if (!q || !docText) return;
  $('#q').value = '';
  addBubble(q, 'user');
  const thinking = addBubble('در حال فکر کردن…', 'ai');
  setBusy($('#ask'), true);
  try {
    // تاریخچه + سند در پیام اول
    const messages = [];
    const docIntro = 'محتوای فایل «' + docName + '»:\n\n' + docText;
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
      system: 'به سؤالات فقط بر اساس محتوای فایل ارائه‌شده پاسخ بده. اگر جواب در فایل نیست، صادقانه بگو. پاسخ‌ها فارسی و دقیق باشند و در صورت امکان به شماره صفحه اشاره کن.',
      messages,
    });
    if (!r.ok) throw new Error(r.message);
    thinking.textContent = r.text.trim();
    history.push({ q, a: r.text.trim() });
    if (history.length > 8) history.splice(1, 1); // سند در پیام اول می‌ماند
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
