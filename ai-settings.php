<?php
require __DIR__ . '/auth.php';
abzar_require_auth();

$cfgFile = __DIR__ . '/config/ai.php';
$cfg = is_file($cfgFile) ? require $cfgFile : ['api_key' => '', 'model' => 'claude-opus-4-8'];
$msg = '';
$msgKind = 'ok';

// --- ذخیره حالت عمومی (فرم جدا) ---
$siteFile = __DIR__ . '/config/site.php';
$siteMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_site'])) {
    $site = ['public' => !empty($_POST['site_public'])];
    $php = "<?php\nreturn " . var_export($site, true) . ";\n";
    if (@file_put_contents($siteFile, $php, LOCK_EX) === false) {
        $siteMsg = 'ذخیره نشد — پوشه config باید قابل نوشتن باشد.';
    } else {
        $siteMsg = $site['public']
            ? 'حالت عمومی فعال شد — همه ابزارها بدون ورود در دسترس‌اند.'
            : 'حالت عمومی غیرفعال شد — استفاده از ابزارها نیاز به ورود دارد.';
    }
}
$sitePublic = is_file($siteFile) && !empty((include $siteFile)['public']);

// --- ذخیره توکن استعلام بانکی (فرم جدا) ---
$inqFile = __DIR__ . '/config/inquiry.php';
$inqMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inquiry'])) {
    $t = trim($_POST['zibal_token'] ?? '');
    if (isset($_POST['clear_inquiry'])) {
        @unlink($inqFile);
        $inqMsg = 'توکن حذف شد و استعلام غیرفعال شد.';
    } else {
        // کادر خالی = حفظ توکن قبلی
        if ($t === '' && is_file($inqFile)) $t = (string)((include $inqFile)['zibal_token'] ?? '');
        if ($t === '') {
            $inqMsg = 'توکن را وارد کن.';
        } elseif (@file_put_contents($inqFile, "<?php\nreturn " . var_export(['zibal_token' => $t], true) . ";\n", LOCK_EX) === false) {
            $inqMsg = 'ذخیره نشد — پوشه config باید قابل نوشتن باشد.';
        } else {
            @chmod($inqFile, 0640);
            $inqMsg = 'توکن ذخیره شد — استعلام نام دارنده کارت فعال است.';
        }
    }
}
$hasInq = is_file($inqFile) && (string)((include $inqFile)['zibal_token'] ?? '') !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['save_site']) && !isset($_POST['save_inquiry'])) {
    $key = trim($_POST['api_key'] ?? '');
    $model = $_POST['model'] ?? 'claude-opus-4-8';
    $allowed = ['claude-opus-4-8', 'claude-sonnet-5', 'claude-haiku-4-5'];
    if (!in_array($model, $allowed, true)) $model = 'claude-opus-4-8';

    if (isset($_POST['clear'])) {
        $cfg = ['api_key' => '', 'model' => $model];
        @unlink($cfgFile);
        $msg = 'کلید حذف شد.';
    } else {
        // اگر کادر خالی ماند، کلید قبلی حفظ می‌شود (فقط مدل عوض می‌شود)
        if ($key === '') $key = $cfg['api_key'] ?? '';
        $cfg = ['api_key' => $key, 'model' => $model];
        $php = "<?php\nreturn " . var_export($cfg, true) . ";\n";
        if (@file_put_contents($cfgFile, $php, LOCK_EX) === false) {
            $msg = 'ذخیره نشد — پوشه config باید قابل نوشتن باشد.';
            $msgKind = 'err';
        } else {
            @chmod($cfgFile, 0640);
            $msg = 'ذخیره شد.';
        }
    }
}

$hasKey = ($cfg['api_key'] ?? '') !== '';
$pageTitle = 'تنظیمات AI — ابزار';
$noIndex = true; // صفحه مدیریتی نباید ایندکس شود
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">تنظیمات</h1>
  <p class="page-sub">کلید هوش مصنوعی و نحوه دسترسی به ابزارها.</p>

  <div class="card">
    <h2>دسترسی عمومی</h2>
    <p style="color:var(--text-dim); font-size:.88rem; margin-bottom:14px">
      با فعال‌کردن این گزینه، همه ابزارها <b>بدون ورود</b> در دسترس بازدیدکننده‌ها قرار می‌گیرند (مناسب انتشار عمومی و کسب درآمد).
      حفاظت‌ها خودکار اعمال می‌شود: مهمان‌ها برای هوش مصنوعی باید کد امنیتی حل کنند و سقف ساعتی دارند
      (AI: ۱۰، تبدیل تصویر سروری: ۶۰، ویدئو/صدا سروری: ۱۲ درخواست در ساعت به‌ازای هر IP).
      صفحه تنظیمات همیشه پشت رمز می‌ماند.
    </p>
    <form method="post">
      <label class="chk"><input type="checkbox" name="site_public" value="1" <?= $sitePublic ? 'checked' : '' ?>> حالت عمومی فعال باشد</label>
      <div class="actions">
        <button class="btn btn-primary" type="submit" name="save_site" value="1">ذخیره</button>
      </div>
    </form>
    <?php if ($siteMsg): ?><p class="note ok" style="margin-top:12px"><?= htmlspecialchars($siteMsg) ?></p><?php endif; ?>
  </div>

  <div class="card">
    <h2>استعلام بانکی (نام دارنده کارت)</h2>
    <p style="color:var(--text-dim); font-size:.88rem; margin-bottom:14px">
      برای اینکه ابزار «اعتبارسنجی کارت» نام صاحب کارت را هم نشان بدهد، از سرویس زیبال استفاده می‌شود.
      از <span dir="ltr">zibal.ir</span> ثبت‌نام کن، سرویس «استعلام کارت» (Facility) را فعال کن و توکن دسترسی را اینجا بگذار.
      هر استعلام طبق تعرفه زیبال هزینه دارد و از کیف پول زیبال خودت کم می‌شود. مهمان‌ها با کد امنیتی و سقف ۵ استعلام در ساعت محدود می‌شوند.
    </p>
    <form method="post">
      <div class="controls">
        <div class="field" style="flex:1 1 340px">
          <label>توکن زیبال <?= $hasInq ? '<span class="badge ok">فعال</span>' : '<span class="badge err">غیرفعال</span>' ?></label>
          <input type="password" name="zibal_token" class="ltr" placeholder="<?= $hasInq ? 'برای تغییر، توکن جدید را وارد کن' : 'توکن دسترسی زیبال' ?>" autocomplete="off">
        </div>
      </div>
      <div class="actions">
        <button class="btn btn-primary" type="submit" name="save_inquiry" value="1">ذخیره</button>
        <?php if ($hasInq): ?><button class="btn btn-ghost" type="submit" name="save_inquiry" value="1" onclick="this.form.insertAdjacentHTML('beforeend','<input type=hidden name=clear_inquiry value=1>')">حذف توکن</button><?php endif; ?>
      </div>
    </form>
    <?php if ($inqMsg): ?><p class="note ok" style="margin-top:12px"><?= htmlspecialchars($inqMsg) ?></p><?php endif; ?>
  </div>

  <div class="note">
    کلید را از <span dir="ltr">platform.claude.com</span> بگیر (بخش API Keys). کلید فقط روی همین سرور و در فایل محافظت‌شده
    <code>config/ai.php</code> ذخیره می‌شود و هرگز به مرورگر ارسال نمی‌شود. استفاده از API هزینه دارد و از حساب خودت کم می‌شود.
  </div>

  <div class="card">
    <form method="post">
      <div class="controls">
        <div class="field" style="flex:1 1 340px">
          <label>کلید API <?= $hasKey ? '<span class="badge ok">تنظیم شده</span>' : '<span class="badge err">تنظیم نشده</span>' ?></label>
          <input type="password" name="api_key" class="ltr" placeholder="<?= $hasKey ? 'برای تغییر، کلید جدید را وارد کن' : 'sk-ant-…' ?>" autocomplete="off">
        </div>
        <div class="field">
          <label>مدل</label>
          <select name="model">
            <option value="claude-opus-4-8" <?= ($cfg['model'] ?? '') === 'claude-opus-4-8' ? 'selected' : '' ?>>Claude Opus 4.8 (بهترین کیفیت)</option>
            <option value="claude-sonnet-5" <?= ($cfg['model'] ?? '') === 'claude-sonnet-5' ? 'selected' : '' ?>>Claude Sonnet 5 (سریع و اقتصادی‌تر)</option>
            <option value="claude-haiku-4-5" <?= ($cfg['model'] ?? '') === 'claude-haiku-4-5' ? 'selected' : '' ?>>Claude Haiku 4.5 (ارزان‌ترین)</option>
          </select>
        </div>
      </div>
      <div class="actions">
        <button class="btn btn-primary" type="submit">ذخیره</button>
        <?php if ($hasKey): ?><button class="btn btn-ghost" type="submit" name="clear" value="1">حذف کلید</button><?php endif; ?>
        <button class="btn btn-ghost" type="button" id="test" <?= $hasKey ? '' : 'disabled' ?>>تست اتصال</button>
      </div>
    </form>
    <?php if ($msg): ?><p class="note <?= $msgKind ?>" style="margin-top:14px"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
    <p class="note hidden" id="testRes"></p>
  </div>
</div>

<script>
$('#test') && ($('#test').onclick = async () => {
  setBusy($('#test'), true, 'در حال تست…');
  try {
    const r = await (await fetch('api_ai.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ messages: [{ role: 'user', content: 'فقط بنویس: سلام' }], max_tokens: 256 }),
    })).json();
    if (r.ok) showNote($('#testRes'), 'اتصال برقرار است ✓ — پاسخ مدل: ' + r.text, 'ok');
    else showNote($('#testRes'), 'خطا: ' + r.message, 'err');
  } catch (e) { showNote($('#testRes'), 'خطا: ' + e.message, 'err'); }
  setBusy($('#test'), false);
});
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
