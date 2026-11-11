<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) redirect('dashboard.php');
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $fails = $_SESSION['login_fails'] ?? 0; $until = $_SESSION['login_lock'] ?? 0;
    if ($until > time()) {
        flash('error', 'Too many attempts. Try again in ' . ceil(($until - time()) / 60) . ' minute(s).');
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $s = db()->prepare('SELECT * FROM users WHERE email=?'); $s->execute([$email]); $u = $s->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
            unset($_SESSION['login_fails'], $_SESSION['login_lock']);
            if (!$u['is_verified']) {
                issue_otp((int)$u['id'], $u['email'], $u['name']);
                flash('info', 'Verify your email to finish signing in.');
                redirect('verify_otp.php');
            }
            login_user((int)$u['id']);
            redirect($u['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
        }
        $_SESSION['login_fails'] = ++$fails;
        if ($fails >= 5) { $_SESSION['login_lock'] = time() + 300; $_SESSION['login_fails'] = 0; }
        flash('error', 'Email or password is incorrect.');
    }
}
$pageTitle = 'Log in'; require __DIR__ . '/includes/header.php'; ?>
<section class="card narrow">
  <h1>Log in</h1>
  <form method="post">
    <?= csrf_field() ?>
    <label>Email <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label>
    <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn" type="submit">Log in</button>
  </form>
  <p>New here? <a href="register.php">Create an account</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
