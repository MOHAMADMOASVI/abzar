<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'گفتار به متن — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">گفتار به متن</h1>
  <p class="page-sub">با میکروفن حرف بزن، متن زنده تایپ می‌شود — فارسی و انگلیسی.</p>
  <p class="note">این قابلیت از موتور تشخیص گفتار مرورگر استفاده می‌کند (کروم/اج، با اینترنت). برای تبدیل «فایل صوتی» به متن، سرویس ابری لازم است — در ابزار «دستیار متن AI» می‌توانی متن پیاده‌شده را ویرایش و خلاصه کنی.</p>

  <div class="card">
    <div class="controls">
      <div class="field"><label>زبان</label><select id="lang"><option value="fa-IR">فارسی</option><option value="en-US">English</option><option value="ar-SA">العربية</option><option value="tr-TR">Türkçe</option></select></div>
      <button class="btn btn-primary" id="start">🎙 شروع</button>
      <button class="btn btn-ghost" id="stop" disabled>توقف</button>
      <span id="state" style="color:var(--text-dim);font-size:.87rem"></span>
    </div>
    <p class="note err hidden" id="msg"></p>
    <div class="field" style="margin-top:14px">
      <label>متن</label>
      <textarea id="out" style="min-height:220px" placeholder="اینجا تایپ می‌شود…"></textarea>
    </div>
    <div class="actions">
      <button class="copybtn" id="cp">کپی</button>
      <button class="copybtn" id="clear">پاک کردن</button>
      <button class="copybtn" id="dl">دانلود TXT</button>
    </div>
  </div>
</div>

<script>
const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
let rec = null, finalText = '';

if (!SR){
  showNote($('#msg'), 'مرورگر شما از تشخیص گفتار پشتیبانی نمی‌کند — از کروم یا اج استفاده کن.', 'err');
  $('#start').disabled = true;
}

$('#start').onclick = () => {
  finalText = $('#out').value ? $('#out').value + ' ' : '';
  rec = new SR();
  rec.lang = $('#lang').value;
  rec.continuous = true;
  rec.interimResults = true;
  rec.onresult = e => {
    let interim = '';
    for (let i = e.resultIndex; i < e.results.length; i++){
      const t = e.results[i][0].transcript;
      if (e.results[i].isFinal) finalText += t + ' ';
      else interim += t;
    }
    $('#out').value = finalText + interim;
  };
  rec.onerror = e => {
    const map = { 'not-allowed': 'دسترسی میکروفن رد شد.', 'network': 'خطای شبکه — اینترنت لازم است.', 'no-speech': 'صدایی شنیده نشد.' };
    showNote($('#msg'), map[e.error] || 'خطا: ' + e.error, 'err');
  };
  rec.onend = () => {
    $('#state').textContent = '';
    $('#start').disabled = false;
    $('#stop').disabled = true;
  };
  rec.start();
  $('#msg').classList.add('hidden');
  $('#state').innerHTML = '<span class="spin"></span> در حال شنیدن…';
  $('#start').disabled = true;
  $('#stop').disabled = false;
};

$('#stop').onclick = () => rec && rec.stop();
$('#cp').onclick = () => copyText($('#out').value, $('#cp'));
$('#clear').onclick = () => { $('#out').value = ''; finalText = ''; };
$('#dl').onclick = () => download(new Blob([$('#out').value], { type: 'text/plain;charset=utf-8' }), 'transcript.txt');
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
