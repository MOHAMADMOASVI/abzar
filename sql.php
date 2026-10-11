<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'SQL Formatter — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">SQL Formatter</h1>
  <p class="page-sub">کوئری را مرتب و خوانا کن — با پشتیبانی MySQL، PostgreSQL، SQLite و…</p>

  <div class="card">
    <div class="controls">
      <div class="field">
        <label>دیتابیس</label>
        <select id="dialect">
          <option value="mysql" selected>MySQL / MariaDB</option>
          <option value="postgresql">PostgreSQL</option>
          <option value="sqlite">SQLite</option>
          <option value="sql">SQL استاندارد</option>
          <option value="tsql">SQL Server</option>
        </select>
      </div>
      <div class="field">
        <label>حروف کلیدواژه‌ها</label>
        <select id="kw"><option value="upper">UPPER</option><option value="lower">lower</option><option value="preserve">دست‌نخورده</option></select>
      </div>
    </div>
    <div class="field" style="margin-top:12px"><label>کوئری</label><textarea id="in" class="ltr" style="min-height:160px" placeholder="select id,name from users where active=1 order by id desc"></textarea></div>
    <div class="actions">
      <button class="btn btn-primary" id="go">مرتب‌سازی</button>
      <button class="btn btn-ghost" id="min">یک‌خطی (فشرده)</button>
    </div>
    <p class="note hidden" id="msg"></p>
    <div class="copy-wrap" style="margin-top:12px">
      <button class="copybtn" id="cp">کپی</button>
      <div class="out" id="out" style="min-height:120px"></div>
    </div>
  </div>
</div>

<script>
$('#go').onclick = async () => {
  try {
    await loadScript('assets/vendor/sql-formatter.min.js');
    $('#out').textContent = sqlFormatter.format($('#in').value, {
      language: $('#dialect').value,
      keywordCase: $('#kw').value,
      tabWidth: 2,
    });
    $('#msg').classList.add('hidden');
  } catch (e){ showNote($('#msg'), 'خطا: ' + e.message, 'err'); }
};
$('#min').onclick = () => {
  $('#out').textContent = $('#in').value.replace(/\s+/g, ' ').trim();
};
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
