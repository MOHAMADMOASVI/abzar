<?php
require __DIR__ . '/auth.php';

// اگر رمز از قبل ست شده، اینجا کاری نیست.
if (abzar_password_is_set()) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p1 = (string) ($_POST['password'] ?? '');
    $p2 = (string) ($_POST['password2'] ?? '');
    if (strlen($p1) < 6) {
        $error = 'رمز باید حداقل ۶ کاراکتر باشد.';
    } elseif ($p1 !== $p2) {
        $error = 'دو رمز واردشده یکسان نیستند.';
    } elseif (abzar_password_is_set()) {
        $error = 'رمز قبلاً تنظیم شده است.';
    } elseif (abzar_save_password($p1)) {
        abzar_login();
        header('Location: index.php');
        exit;
    } else {
        $error = 'ذخیره‌ی رمز ناموفق بود (دسترسی نوشتن پوشه config).';
    }
}

$pageTitle = 'تعیین رمز — ابزار';
require __DIR__ . '/partials/auth_head.php';
?>
<div class="auth-box">
  <div class="auth-logo"><img src="assets/logo-64.png" alt="لوگوی ابزار" width="52" height="52" style="border-radius:14px;display:block"></div>
  <h1>تعیین رمز ورود</h1>
  <p class="hint">این اولین ورود است؛ یک رمز برای این ابزار تعیین کن. بعداً برای ورود همین رمز لازم است.</p>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="password" name="password"  placeholder="رمز جدید (حداقل ۶ کاراکتر)" required>
    <input type="password" name="password2" placeholder="تکرار رمز" required>
    <button type="submit" class="btn btn-primary">ذخیره و ورود</button>
  </form>
</div>
</body>
</html>
