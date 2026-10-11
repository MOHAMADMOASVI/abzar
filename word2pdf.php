<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'تبدیل Word به PDF — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تبدیل Word به PDF</h1>
  <p class="page-sub">فایل DOCX در مرورگر باز می‌شود و از پنجره چاپ به‌صورت PDF ذخیره می‌کنی — در پنجره چاپ، مقصد را «Save as PDF» بگذار.</p>
  <p class="note">قالب‌بندی‌های ساده (تیتر، پاراگراف، جدول، عکس، لیست) حفظ می‌شود؛ صفحه‌آرایی‌های پیچیده ممکن است کمی جابه‌جا شود.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل Word (.docx) را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept=".docx" hidden>
    <div class="controls" style="margin-top:14px">
      <label class="chk"><input type="checkbox" id="rtl" checked> متن فارسی / راست‌به‌چپ</label>
    </div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">تبدیل و چاپ به PDF</button>
    </div>
    <p class="note hidden" id="msg"></p>
  </div>

  <div class="card hidden" id="pvCard">
    <h2>پیش‌نمایش</h2>
    <iframe id="pv" style="width:100%;height:60vh;border:1px solid var(--line);border-radius:4px;background:#fff"></iframe>
  </div>
</div>

<script>
let docxFile = null;

initDrop($('#drop'), $('#file'), fs => {
  docxFile = fs[0];
  $('#drop .big').textContent = docxFile.name + ' (' + fmtBytes(docxFile.size) + ')';
  $('#go').classList.remove('hidden');
});

$('#go').onclick = async () => {
  if (!docxFile) return;
  setBusy($('#go'), true);
  $('#msg').classList.add('hidden');
  try {
    await loadScript('assets/vendor/mammoth.browser.min.js');
    const res = await mammoth.convertToHtml({ arrayBuffer: await docxFile.arrayBuffer() });
    const rtl = $('#rtl').checked;
    const html = `<!DOCTYPE html><html dir="${rtl ? 'rtl' : 'ltr'}"><head><meta charset="utf-8">
<title>${docxFile.name}</title>
<style>
@page{ size:A4; margin:20mm; }
body{ font-family:'Vazirmatn','IRANYekan',Tahoma,sans-serif; line-height:1.9; color:#111; font-size:12pt; }
@font-face{ font-family:'Vazirmatn'; src:url('${location.origin + location.pathname.replace(/[^/]*$/, '')}assets/fonts/Vazirmatn-Regular.woff2') format('woff2'); }
table{ border-collapse:collapse; width:100%; }
td,th{ border:1px solid #999; padding:4pt 6pt; }
img{ max-width:100%; }
h1,h2,h3{ line-height:1.5; }
</style></head><body>${res.value}</body></html>`;
    const pv = $('#pv');
    pv.srcdoc = html;
    $('#pvCard').classList.remove('hidden');
    pv.onload = () => setTimeout(() => { pv.contentWindow.focus(); pv.contentWindow.print(); }, 300);
    if (res.messages.length)
      showNote($('#msg'), 'هشدارهای تبدیل: ' + res.messages.slice(0, 3).map(m => m.message).join(' — '), 'warn');
  } catch (e){
    showNote($('#msg'), 'خطا: ' + e.message + ' (فقط فرمت docx پشتیبانی می‌شود، نه doc قدیمی)', 'err');
  }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
