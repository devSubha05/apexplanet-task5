<?php
$base = '../'; require_once __DIR__ . '/../includes/functions.php'; $admin = require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid === (int)$admin['id']) flash('error', 'You cannot change your own account here.');
    elseif (isset($_POST['toggle'])) { db()->prepare("UPDATE users SET role = IF(role='admin','student','admin') WHERE id=?")->execute([$uid]); flash('success', 'Role updated.'); }
    elseif (isset($_POST['delete'])) { db()->prepare('DELETE FROM users WHERE id=?')->execute([$uid]); flash('success', 'User deleted.'); }
    redirect('users.php' . (isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) : ''));
}
$q = trim($_GET['q'] ?? '');
$s = db()->prepare("SELECT id,name,email,role,is_verified,created_at FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT 200");
$like = '%' . addcslashes($q, '%_\\') . '%'; $s->execute([$like, $like]); $rows = $s->fetchAll();
$pageTitle = 'Users'; require __DIR__ . '/../includes/header.php'; require __DIR__ . '/nav.php'; ?>
<div class="bar"><h1>Users</h1><form method="get"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name or email" aria-label="Search users"></form></div>
<div class="table-wrap"><table>
<thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Verified</th><th>Joined</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['name']) ?></td><td><?= e($r['email']) ?></td><td><?= e($r['role']) ?></td><td><?= $r['is_verified'] ? 'Yes' : 'No' ?></td><td><?= e(substr($r['created_at'], 0, 10)) ?></td>
<td class="actions"><?php if ($r['id'] != $admin['id']): ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>"><button class="link-btn" name="toggle" value="1"><?= $r['role'] === 'admin' ? 'Make student' : 'Make admin' ?></button></form>
<form method="post" onsubmit="return confirm('Delete this user?')"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>"><button class="link-btn danger-text" name="delete" value="1">Delete</button></form>
<?php endif; ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="6" class="empty">No users found.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
