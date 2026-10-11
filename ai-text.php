<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$hasKey = is_file(__DIR__ . '/config/ai.php') && (require __DIR__ . '/config/ai.php')['api_key'] !== '';
$pageTitle = 'دستیار متن AI — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">دستیار متن AI</h1>
  <p class="page-sub">خلاصه‌سازی، ترجمه، بازنویسی، اصلاح گرامر، تولید عنوان، کپشن، ایمیل و توضیحات محصول.</p>
  <?php if (!$hasKey): ?><p class="note warn">کلید API تنظیم نشده — اول به <a href="ai-settings.php">تنظیمات AI</a> برو.</p><?php endif; ?>

  <div class="card">
    <div class="controls">
      <div class="field">
        <label>کار</label>
        <select id="mode">
          <option value="summary">خلاصه‌سازی</option>
          <option value="translate">ترجمه</option>
          <option value="rewrite">بازنویسی</option>
          <option value="grammar">اصلاح گرامر و املا</option>
          <option value="titles">تولید عنوان مقاله</option>
          <option value="product">توضیحات محصول</option>
          <option value="caption">کپشن اینستاگرام</option>
          <option value="email">نوشتن ایمیل</option>
        </select>
      </div>
      <div class="field opt opt-summary"><label>طول خلاصه</label><select id="sumLen"><option value="کوتاه (۲-۳ جمله)">کوتاه</option><option value="یک پاراگراف" selected>متوسط</option><option value="مفصل با نکات کلیدی به‌صورت لیست">مفصل</option></select></div>
      <div class="field opt opt-translate hidden"><label>ترجمه به</label><select id="lang"><option>فارسی</option><option>انگلیسی</option><option>عربی</option><option>ترکی استانبولی</option><option>آلمانی</option><option>فرانسوی</option></select></div>
      <div class="field opt opt-rewrite hidden"><label>لحن</label><select id="tone"><option>رسمی</option><option>ساده و روان</option><option>صمیمی</option><option>حرفه‌ای و تجاری</option><option>خلاصه‌تر</option></select></div>
      <div class="field opt opt-titles hidden"><label>تعداد</label><select id="nTitles"><option>5</option><option>10</option></select></div>
      <div class="field opt opt-email hidden"><label>نوع ایمیل</label><select id="emailKind"><option>رسمی</option><option>دوستانه</option><option>پیگیری (Follow-up)</option><option>عذرخواهی</option><option>درخواست</option></select></div>
    </div>
    <div class="field" style="margin-top:14px">
      <label id="inLabel">متن</label>
      <textarea id="in" style="min-height:180px"></textarea>
    </div>
    <div class="actions">
      <button class="btn btn-primary" id="go" <?= $hasKey ? '' : 'disabled' ?>>انجام بده</button>
      <span id="usage" style="color:var(--text-mute);font-size:.8rem"></span>
    </div>
    <p class="note hidden" id="msg"></p>
    <div class="copy-wrap" style="margin-top:14px">
      <button class="copybtn" id="cp">کپی</button>
      <div class="out" id="out" style="direction:rtl;text-align:right;font-family:inherit;white-space:pre-wrap;min-height:100px"></div>
    </div>
  </div>
</div>

<script>
const HINTS = {
  summary:  'متنی که باید خلاصه شود',
  translate:'متنی که باید ترجمه شود',
  rewrite:  'متنی که باید بازنویسی شود',
  grammar:  'متنی که باید اصلاح شود',
  titles:   'موضوع مقاله یا متن آن',
  product:  'نام محصول + مشخصات/ویژگی‌ها (هرچه می‌دانی)',
  caption:  'موضوع پست + نکات مهم',
  email:    'موضوع ایمیل + گیرنده + نکاتی که باید گفته شود',
};

$('#mode').onchange = () => {
  const m = $('#mode').value;
  $$('.opt').forEach(el => el.classList.toggle('hidden', !el.classList.contains('opt-' + m)));
  $('#inLabel').textContent = HINTS[m];
};

function buildRequest(){
  const m = $('#mode').value, t = $('#in').value.trim();
  const sys = 'تو یک دستیار نویسندگی فارسی‌زبان حرفه‌ای هستی. فقط خروجی خواسته‌شده را بده، بدون مقدمه و توضیح اضافه.';
  let prompt;
  if (m === 'summary')   prompt = 'این متن را خلاصه کن. طول خلاصه: ' + $('#sumLen').value + '\n\n' + t;
  if (m === 'translate') prompt = 'این متن را به ' + $('#lang').value + ' ترجمه کن. ترجمه روان و طبیعی باشد:\n\n' + t;
  if (m === 'rewrite')   prompt = 'این متن را با لحن «' + $('#tone').value + '» بازنویسی کن. معنا حفظ شود:\n\n' + t;
  if (m === 'grammar')   prompt = 'غلط‌های املایی، نگارشی و گرامری این متن را اصلاح کن و متن اصلاح‌شده را بده. اگر فارسی است، نیم‌فاصله‌ها را هم درست کن:\n\n' + t;
  if (m === 'titles')    prompt = $('#nTitles').value + ' عنوان جذاب و سئوپسند برای مقاله‌ای با این موضوع پیشنهاد بده (لیست شماره‌دار):\n\n' + t;
  if (m === 'product')   prompt = 'برای این محصول یک توضیحات فروشگاهی جذاب و قانع‌کننده بنویس (یک پاراگراف معرفی + لیست ویژگی‌ها):\n\n' + t;
  if (m === 'caption')   prompt = 'برای این موضوع یک کپشن اینستاگرام جذاب بنویس، با ایموجی‌های مناسب و ۵ هشتگ فارسی مرتبط در انتها:\n\n' + t;
  if (m === 'email')     prompt = 'یک ایمیل ' + $('#emailKind').value + ' بر اساس این اطلاعات بنویس (با موضوع/Subject مناسب در ابتدا):\n\n' + t;
  return { system: sys, messages: [{ role: 'user', content: prompt }] };
}

$('#go').onclick = async () => {
  if (!$('#in').value.trim()){ showNote($('#msg'), 'متن را وارد کن.', 'warn'); return; }
  setBusy($('#go'), true, 'در حال پردازش…');
  $('#msg').classList.add('hidden');
  try {
    const r = await aiFetch(buildRequest());
    if (!r.ok) throw new Error(r.message);
    $('#out').textContent = r.text.trim();
    $('#usage').textContent = 'توکن: ' + faNum(r.usage.input_tokens) + ' ورودی / ' + faNum(r.usage.output_tokens) + ' خروجی — ' + r.model;
  } catch (e){ showNote($('#msg'), 'خطا: ' + e.message, 'err'); }
  setBusy($('#go'), false);
};
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
