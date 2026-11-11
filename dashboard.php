<?php
require_once __DIR__ . '/includes/functions.php';
$me = require_login();
$s = db()->prepare('SELECT c.id,c.title,c.level,cat.name AS category FROM enrollments e JOIN courses c ON c.id=e.course_id JOIN categories cat ON cat.id=c.category_id WHERE e.user_id=? ORDER BY e.enrolled_at DESC');
$s->execute([$me['id']]); $mine = $s->fetchAll();
$pageTitle = 'My learning'; require __DIR__ . '/includes/header.php'; ?>
<h1>Hi <?= e($me['name']) ?>, here is your learning</h1>
<?php if (!$mine): ?>
  <p class="empty">You have not enrolled in a course yet. <a href="index.php">Find a course</a></p>
<?php else: ?>
<section class="grid">
<?php foreach ($mine as $c): $p = course_progress($me['id'], $c['id']); ?>
  <a class="card course-card" href="course.php?id=<?= (int)$c['id'] ?>">
    <span class="tag"><?= e($c['category']) ?> · <?= e($c['level']) ?></span>
    <h2><?= e($c['title']) ?></h2>
    <div class="progress"><span style="width:<?= $p['pct'] ?>%"></span></div>
    <p class="meta"><?= $p['done'] ?> of <?= $p['total'] ?> lessons · <?= $p['pct'] ?>%</p>
  </a>
<?php endforeach; ?>
</section>
<?php endif; require __DIR__ . '/includes/footer.php'; ?>
