<?php
require __DIR__ . '/auth.php';

// اگر هنوز رمزی ست نشده، برو به مرحله‌ی تعیین رمز.
if (!abzar_password_is_set()) {
    header('Location: setup.php');
    exit;
}
// اگر از قبل وارد شده، مستقیم برو داخل.
if (abzar_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$fails = (int)($_SESSION['abzar_login_fails'] ?? 0);
$needCaptcha = $fails >= 3;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $captchaOK = true;
    if ($needCaptcha) {
        $answer = strtoupper(trim((string)($_POST['captcha'] ?? '')));
        $expected = (string)($_SESSION['abzar_captcha'] ?? '');
        unset($_SESSION['abzar_captcha']);
        $captchaOK = $expected !== '' && $answer !== '' && hash_equals($expected, $answer);
    }

    $p = (string)($_POST['password'] ?? '');
    if ($captchaOK && abzar_verify_password($p)) {
        unset($_SESSION['abzar_login_fails']);
        abzar_login();
        header('Location: index.php');
        exit;
    }

    usleep(700000); // کمی تأخیر برای کند کردن حدس رمز
    $_SESSION['abzar_login_fails'] = ++$fails;
    $needCaptcha = $fails >= 3;
    $error = $captchaOK ? 'رمز اشتباه است.' : 'کد امنیتی اشتباه است.';
}

$pageTitle = 'ورود — ابزار';
require __DIR__ . '/partials/auth_head.php';
?>
<div class="auth-box">
  <div class="auth-logo"><img src="assets/logo-64.png" alt="لوگوی ابزار" width="52" height="52" style="border-radius:14px;display:block"></div>
  <h1>ورود به ابزار</h1>
  <p class="hint">برای مدیریت ابزار، رمز را وارد کن.</p>
  <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="password" name="password" placeholder="رمز عبور" required autofocus>
    <?php if ($needCaptcha): ?>
    <div class="captcha-row">
      <img src="captcha.php?img=1&r=<?= mt_rand() ?>" alt="کد امنیتی" id="capImg"
           title="برای تصویر جدید کلیک کن" onclick="this.src='captcha.php?img=1&r='+Math.random()">
      <input type="text" name="captcha" placeholder="کد داخل تصویر" required
             style="direction:ltr; text-align:center" maxlength="5" autocomplete="off">
    </div>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary">ورود</button>
  </form>
  <?php if (abzar_public_mode()): ?>
  <p class="hint" style="margin-top:14px; margin-bottom:0"><a href="index.php">← بازگشت به ابزارها (بدون ورود)</a></p>
  <?php endif; ?>
</div>
</body>
</html>
