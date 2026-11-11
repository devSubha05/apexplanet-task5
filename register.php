<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) redirect('dashboard.php');
$errors = []; $name = $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $errors[] = 'Enter your name (2 to 80 characters).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) $errors[] = 'Password needs 8+ characters with a letter and a number.';
    if (!$errors) {
        $s = db()->prepare('SELECT id,is_verified FROM users WHERE email=?'); $s->execute([$email]); $ex = $s->fetch();
        if ($ex && $ex['is_verified']) {
            $errors[] = 'That email already has an account. Log in instead.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            if ($ex) { // unverified leftover: refresh details
                db()->prepare('UPDATE users SET name=?, password=? WHERE id=?')->execute([$name, $hash, $ex['id']]);
                $uid = (int)$ex['id'];
            } else {
                db()->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?)')->execute([$name, $email, $hash]);
                $uid = (int)db()->lastInsertId();
            }
            issue_otp($uid, $email, $name);
            redirect('verify_otp.php');
        }
    }
}
$pageTitle = 'Create account'; require __DIR__ . '/includes/header.php'; ?>
<section class="card narrow">
  <h1>Create your account</h1>
  <?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>
  <form method="post" id="regForm" novalidate>
    <?= csrf_field() ?>
    <label>Name <input name="name" value="<?= e($name) ?>" required maxlength="80" autocomplete="name"></label>
    <label>Email <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email"></label>
    <label>Password <input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
    <small>8+ characters with at least one letter and one number. We email you a 6-digit code next.</small>
    <button class="btn" type="submit">Send verification code</button>
  </form>
  <p>Already registered? <a href="login.php">Log in</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
