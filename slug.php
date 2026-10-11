<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'Slug Generator — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">Slug Generator</h1>
  <p class="page-sub">عنوان فارسی یا انگلیسی را به اسلاگ آماده برای URL تبدیل کن.</p>

  <div class="card">
    <div class="field"><label>عنوان</label><input type="text" id="in" placeholder="آموزش کامل فتوشاپ در ۱۰ روز"></div>
    <div class="controls" style="margin-top:12px">
      <div class="field">
        <label>حالت فارسی</label>
        <select id="mode">
          <option value="keep">حفظ حروف فارسی (پیشنهادی برای سایت فارسی)</option>
          <option value="translit">تبدیل به حروف انگلیسی (finglish)</option>
        </select>
      </div>
      <div class="field"><label>جداکننده</label><select id="sep"><option value="-">خط تیره -</option><option value="_">زیرخط _</option></select></div>
    </div>
    <div class="copy-wrap" style="margin-top:14px">
      <button class="copybtn" id="cp">کپی</button>
      <div class="out" id="out" style="min-height:52px;font-size:1rem"></div>
    </div>
    <p class="note">اسلاگ فارسی در مرورگر خوانا نمایش داده می‌شود ولی در لینک به‌صورت درصدی (percent-encoded) کد می‌شود — برای سئوی فارسی مشکلی ندارد.</p>
  </div>
</div>

<script>
const MAP = {
  'ا':'a','آ':'a','أ':'a','إ':'e','ب':'b','پ':'p','ت':'t','ث':'s','ج':'j','چ':'ch','ح':'h','خ':'kh',
  'د':'d','ذ':'z','ر':'r','ز':'z','ژ':'zh','س':'s','ش':'sh','ص':'s','ض':'z','ط':'t','ظ':'z','ع':'',
  'غ':'gh','ف':'f','ق':'gh','ک':'k','ك':'k','گ':'g','ل':'l','م':'m','ن':'n','و':'v','ه':'h','ی':'y',
  'ي':'y','ئ':'y','ء':'','ة':'h','ؤ':'v'
};

function slugify(s){
  const sep = $('#sep').value;
  s = enNum(s).trim().toLowerCase().replace(/[‌]/g, sep);
  if ($('#mode').value === 'translit')
    s = s.replace(/[؀-ۿ]/g, ch => MAP[ch] ?? '');
  s = s
    .replace(/[^\p{L}\p{N}]+/gu, sep)
    .replace(new RegExp('\\' + sep + '{2,}', 'g'), sep)
    .replace(new RegExp('^\\' + sep + '|\\' + sep + '$', 'g'), '');
  return s;
}

function run(){ $('#out').textContent = slugify($('#in').value); }
['in', 'mode', 'sep'].forEach(id => $('#' + id).addEventListener('input', run));
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
