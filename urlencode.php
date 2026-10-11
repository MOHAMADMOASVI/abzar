<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'URL Encode / Decode — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">URL Encode / Decode</h1>
  <p class="page-sub">برای پارامترها از Component و برای آدرس کامل از URL استفاده کن.</p>

  <div class="card">
    <div class="field"><label>ورودی</label><textarea id="in" class="ltr" placeholder="سلام دنیا یا https://example.com/?q=تست"></textarea></div>
    <div class="actions">
      <button class="btn btn-primary" data-op="encC">Encode (Component)</button>
      <button class="btn btn-ghost" data-op="encU">Encode (URL)</button>
      <button class="btn btn-ghost" data-op="dec">Decode</button>
    </div>
    <p class="note hidden" id="msg"></p>
    <div class="copy-wrap" style="margin-top:12px">
      <button class="copybtn" id="cp">کپی</button>
      <div class="out" id="out" style="min-height:100px"></div>
    </div>
  </div>
</div>

<script>
$$('.actions .btn').forEach(b => b.onclick = () => {
  const v = $('#in').value;
  try {
    $('#out').textContent =
      b.dataset.op === 'encC' ? encodeURIComponent(v) :
      b.dataset.op === 'encU' ? encodeURI(v) :
      decodeURIComponent(v.replace(/\+/g, ' '));
    $('#msg').classList.add('hidden');
  } catch (e){ showNote($('#msg'), 'ورودی معتبر نیست: ' + e.message, 'err'); }
});
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
