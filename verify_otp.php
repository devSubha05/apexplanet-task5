<?php
require_once __DIR__ . '/includes/functions.php';
$uid = $_SESSION['otp_user'] ?? null;
if (!$uid) { flash('error', 'Start by creating an account or logging in.'); redirect('login.php'); }
$s = db()->prepare('SELECT id,name,email FROM users WHERE id=?'); $s->execute([$uid]); $user = $s->fetch();
if (!$user) redirect('register.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['resend'])) {
        if (time() - ($_SESSION['otp_sent_at'] ?? 0) < 60) flash('error', 'Wait a minute before asking for another code.');
        else { issue_otp($user['id'], $user['email'], $user['name']); flash('success', 'A new code is on its way.'); }
        redirect('verify_otp.php');
    }
    $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
    $s = db()->prepare('SELECT * FROM otp_codes WHERE user_id=? ORDER BY id DESC LIMIT 1'); $s->execute([$uid]); $otp = $s->fetch();
    if (!$otp || strtotime($otp['expires_at']) < time()) {
        flash('error', 'That code has expired. Request a new one.');
    } elseif ($otp['attempts'] >= 5) {
        flash('error', 'Too many wrong attempts. Request a new code.');
    } elseif (!password_verify($code, $otp['code_hash'])) {
        db()->prepare('UPDATE otp_codes SET attempts=attempts+1 WHERE id=?')->execute([$otp['id']]);
        flash('error', 'Wrong code. Check the email and try again.');
    } else {
        db()->prepare('UPDATE users SET is_verified=1 WHERE id=?')->execute([$uid]);
        db()->prepare('DELETE FROM otp_codes WHERE user_id=?')->execute([$uid]);
        unset($_SESSION['otp_user'], $_SESSION['otp_sent_at']);
        login_user((int)$uid);
        flash('success', 'Email verified. Welcome to ' . SITE_NAME . '!');
        redirect('dashboard.php');
    }
    redirect('verify_otp.php');
}
$pageTitle = 'Verify email'; require __DIR__ . '/includes/header.php'; ?>
<section class="card narrow">
  <h1>Enter your 6-digit code</h1>
  <p>We sent it to <strong><?= e($user['email']) ?></strong>. It expires in <?= (int)OTP_TTL_MINUTES ?> minutes.</p>
  <form method="post">
    <?= csrf_field() ?>
    <label>Verification code <input name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" required autofocus autocomplete="one-time-code"></label>
    <button class="btn" type="submit">Verify email</button>
  </form>
  <form method="post"><?= csrf_field() ?><button class="link-btn" name="resend" value="1">Send a new code</button></form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
