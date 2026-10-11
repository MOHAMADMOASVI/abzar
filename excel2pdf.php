<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'تبدیل Excel به PDF — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تبدیل Excel به PDF</h1>
  <p class="page-sub">شیت‌ها به جدول تبدیل می‌شوند و از پنجره چاپ به‌صورت PDF ذخیره می‌کنی — مقصد را «Save as PDF» بگذار.</p>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل Excel (.xlsx / .xls / .csv) را اینجا رها کن</div><div>یا کلیک کن</div></div>
    <input type="file" id="file" accept=".xlsx,.xls,.csv,.ods" hidden>
    <div class="controls" style="margin-top:14px">
      <div class="field"><label>شیت</label><select id="sheet"><option value="*">همه شیت‌ها</option></select></div>
      <div class="field"><label>جهت کاغذ</label><select id="orient"><option value="landscape">افقی</option><option value="portrait">عمودی</option></select></div>
      <label class="chk"><input type="checkbox" id="rtl" checked> راست‌به‌چپ</label>
    </div>
    <div class="actions"><button class="btn btn-primary hidden" id="go">تبدیل و چاپ به PDF</button></div>
    <p class="note hidden" id="msg"></p>
  </div>

  <div class="card hidden" id="pvCard">
    <h2>پیش‌نمایش</h2>
    <iframe id="pv" style="width:100%;height:60vh;border:1px solid var(--line);border-radius:4px;background:#fff"></iframe>
  </div>
</div>

<script>
let wb = null, xlFile = null;

initDrop($('#drop'), $('#file'), async fs => {
  xlFile = fs[0];
  $('#drop .big').textContent = xlFile.name + ' (' + fmtBytes(xlFile.size) + ')';
  try {
    await loadScript('assets/vendor/xlsx.full.min.js');
    wb = XLSX.read(await xlFile.arrayBuffer(), { type: 'array' });
    const sel = $('#sheet');
    sel.innerHTML = '<option value="*">همه شیت‌ها</option>' +
      wb.SheetNames.map(n => `<option>${n}</option>`).join('');
    $('#go').classList.remove('hidden');
  } catch (e){ showNote($('#msg'), 'فایل باز نشد: ' + e.message, 'err'); }
});

$('#go').onclick = () => {
  if (!wb) return;
  const names = $('#sheet').value === '*' ? wb.SheetNames : [$('#sheet').value];
  const rtl = $('#rtl').checked;
  let body = '';
  for (const n of names){
    const tbl = XLSX.utils.sheet_to_html(wb.Sheets[n], { header: '', footer: '' });
    body += (names.length > 1 ? `<h2>${n}</h2>` : '') + tbl + '<div style="page-break-after:always"></div>';
  }
  const html = `<!DOCTYPE html><html dir="${rtl ? 'rtl' : 'ltr'}"><head><meta charset="utf-8">
<title>${xlFile.name}</title>
<style>
@page{ size:A4 ${$('#orient').value}; margin:12mm; }
body{ font-family:'Vazirmatn','IRANYekan',Tahoma,sans-serif; color:#111; font-size:9.5pt; }
table{ border-collapse:collapse; width:100%; }
td,th{ border:1px solid #aaa; padding:3pt 5pt; white-space:nowrap; }
h2{ font-size:12pt; margin:6pt 0; }
</style></head><body>${body}</body></html>`;
  const pv = $('#pv');
  pv.srcdoc = html;
  $('#pvCard').classList.remove('hidden');
  pv.onload = () => setTimeout(() => { pv.contentWindow.focus(); pv.contentWindow.print(); }, 300);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
