<?php
require_once __DIR__ . '/includes/functions.php';
$me = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    if ($action === 'details') {
        $name = trim($_POST['name'] ?? ''); $bio = trim($_POST['bio'] ?? '');
        $avatar = $me['avatar']; $err = null;
        if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $err = 'Enter your name (2 to 80 characters).';
        elseif (mb_strlen($bio) > 300) $err = 'Bio can be at most 300 characters.';
        if (!$err && !empty($_FILES['avatar']['name'])) {
            $f = $_FILES['avatar'];
            $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if ($f['error'] !== UPLOAD_ERR_OK) $err = 'The upload failed. Try a smaller image.';
            elseif ($f['size'] > 2 * 1024 * 1024) $err = 'Image must be 2 MB or smaller.';
            else {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);   // check real content, not the file name
                if (!isset($types[$mime]) || !@getimagesize($f['tmp_name'])) $err = 'Upload a JPG, PNG or WebP image.';
                else {
                    $new = bin2hex(random_bytes(12)) . '.' . $types[$mime];
                    if (move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $new)) {
                        if ($avatar && is_file(UPLOAD_DIR . $avatar)) @unlink(UPLOAD_DIR . $avatar);
                        $avatar = $new;
                    } else $err = 'Could not save the image.';
                }
            }
        }
        if ($err) flash('error', $err);
        else { db()->prepare('UPDATE users SET name=?, bio=?, avatar=? WHERE id=?')->execute([$name, $bio ?: null, $avatar, $me['id']]); flash('success', 'Profile saved.'); }
    } elseif ($action === 'password') {
        $s = db()->prepare('SELECT password FROM users WHERE id=?'); $s->execute([$me['id']]); $hash = $s->fetchColumn();
        $new = $_POST['new'] ?? '';
        if (!password_verify($_POST['current'] ?? '', $hash)) flash('error', 'Your current password is incorrect.');
        elseif (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) flash('error', 'New password needs 8+ characters with a letter and a number.');
        else { db()->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]); session_regenerate_id(true); flash('success', 'Password changed.'); }
    } elseif ($action === 'delete') {
        $s = db()->prepare('SELECT password FROM users WHERE id=?'); $s->execute([$me['id']]);
        if (!password_verify($_POST['confirm'] ?? '', $s->fetchColumn())) { flash('error', 'Wrong password, account not deleted.'); redirect('profile.php'); }
        if ($me['avatar'] && is_file(UPLOAD_DIR . $me['avatar'])) @unlink(UPLOAD_DIR . $me['avatar']);
        db()->prepare('DELETE FROM users WHERE id=?')->execute([$me['id']]);
        $_SESSION = []; session_destroy(); session_start(); flash('info', 'Your account was deleted.'); redirect('index.php');
    }
    redirect('profile.php');
}
$pageTitle = 'Profile'; require __DIR__ . '/includes/header.php'; ?>
<h1>Your profile</h1>
<div class="two-col">
  <section class="card">
    <h2>Details</h2>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="action" value="details">
      <img class="avatar-lg" src="<?= e(avatar_url($me)) ?>" alt="Your profile photo" width="96" height="96">
      <label>Photo (JPG, PNG or WebP, up to 2 MB) <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"></label>
      <label>Name <input name="name" value="<?= e($me['name']) ?>" required maxlength="80"></label>
      <label>Email <input value="<?= e($me['email']) ?>" disabled></label>
      <label>Bio <textarea name="bio" maxlength="300" rows="3"><?= e($me['bio']) ?></textarea></label>
      <button class="btn">Save profile</button>
    </form>
  </section>
  <section>
    <div class="card">
      <h2>Change password</h2>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="password">
        <label>Current password <input type="password" name="current" required autocomplete="current-password"></label>
        <label>New password <input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
        <button class="btn">Change password</button>
      </form>
    </div>
    <div class="card danger">
      <h2>Delete account</h2>
      <p>This removes your account, enrollments and progress for good.</p>
      <form method="post" onsubmit="return confirm('Delete your account permanently?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete">
        <label>Confirm with your password <input type="password" name="confirm" required></label>
        <button class="btn btn-danger">Delete my account</button>
      </form>
    </div>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
