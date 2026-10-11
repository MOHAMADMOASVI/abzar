<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'متن به گفتار — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">متن به گفتار</h1>
  <p class="page-sub">با صداهای نصب‌شده روی سیستم — آفلاین و رایگان.</p>
  <p class="note">فهرست صداها به سیستم‌عامل و مرورگر بستگی دارد. اگر صدای فارسی نداری، در تنظیمات سیستم (Accessibility → Spoken Content در مک) صدای فارسی اضافه کن.</p>

  <div class="card">
    <div class="field"><label>متن</label><textarea id="in" style="min-height:140px">سلام! این یک نمونه از تبدیل متن به گفتار است.</textarea></div>
    <div class="controls" style="margin-top:12px">
      <div class="field" style="flex:1 1 260px"><label>صدا</label><select id="voice"></select></div>
      <div class="field"><label>سرعت: <b id="rv">1</b></label><input type="range" id="rate" min="0.5" max="2" step="0.1" value="1" style="width:140px"></div>
      <div class="field"><label>زیر و بمی: <b id="pv">1</b></label><input type="range" id="pitch" min="0.5" max="2" step="0.1" value="1" style="width:140px"></div>
    </div>
    <div class="actions">
      <button class="btn btn-primary" id="play">پخش</button>
      <button class="btn btn-ghost" id="stop">توقف</button>
    </div>
    <p class="note warn" style="margin-top:14px">مرورگر اجازه ذخیره مستقیم خروجی را نمی‌دهد؛ اگر فایل صوتی می‌خواهی، هنگام پخش با ابزار ضبط صدا (مثل QuickTime) ضبط کن.</p>
  </div>
</div>

<script>
let voices = [];
function loadVoices(){
  voices = speechSynthesis.getVoices();
  if (!voices.length) return;
  // فارسی‌ها اول
  voices.sort((a, b) => (b.lang.startsWith('fa') ? 1 : 0) - (a.lang.startsWith('fa') ? 1 : 0));
  $('#voice').innerHTML = voices.map((v, i) =>
    `<option value="${i}">${v.name} (${v.lang})${v.lang.startsWith('fa') ? ' ⭐' : ''}</option>`).join('');
}
loadVoices();
speechSynthesis.onvoiceschanged = loadVoices;

$('#rate').oninput = e => $('#rv').textContent = faNum(e.target.value);
$('#pitch').oninput = e => $('#pv').textContent = faNum(e.target.value);

$('#play').onclick = () => {
  speechSynthesis.cancel();
  const u = new SpeechSynthesisUtterance($('#in').value);
  const v = voices[+$('#voice').value];
  if (v) u.voice = v;
  u.rate = +$('#rate').value;
  u.pitch = +$('#pitch').value;
  speechSynthesis.speak(u);
};
$('#stop').onclick = () => speechSynthesis.cancel();
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
