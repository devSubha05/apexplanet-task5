<?php
require_once __DIR__ . '/includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
$s = db()->prepare('SELECT c.*, cat.name AS category FROM courses c JOIN categories cat ON cat.id=c.category_id WHERE c.id=?');
$s->execute([$id]); $course = $s->fetch();
if (!$course) { http_response_code(404); $pageTitle = 'Not found'; require __DIR__ . '/includes/header.php'; echo '<p class="empty">That course does not exist. <a href="index.php">Browse courses</a></p>'; require __DIR__ . '/includes/footer.php'; exit; }
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); require_login();
    if (isset($_POST['enroll'])) {
        db()->prepare('INSERT IGNORE INTO enrollments (user_id,course_id) VALUES (?,?)')->execute([$me['id'], $id]);
        flash('success', 'You are enrolled. Start with lesson 1.');
    } elseif (isset($_POST['unenroll'])) {
        db()->prepare('DELETE FROM enrollments WHERE user_id=? AND course_id=?')->execute([$me['id'], $id]);
        db()->prepare('DELETE p FROM lesson_progress p JOIN lessons l ON l.id=p.lesson_id WHERE p.user_id=? AND l.course_id=?')->execute([$me['id'], $id]);
        flash('info', 'You left this course and your progress was cleared.');
    }
    redirect("course.php?id=$id");
}

$lessons = db()->prepare('SELECT id,title,content,video_url,position FROM lessons WHERE course_id=? ORDER BY position,id');
$lessons->execute([$id]); $lessons = $lessons->fetchAll();
$enrolled = false; $done = [];
if ($me) {
    $s = db()->prepare('SELECT 1 FROM enrollments WHERE user_id=? AND course_id=?'); $s->execute([$me['id'], $id]); $enrolled = (bool)$s->fetchColumn();
    if ($enrolled) {
        $s = db()->prepare('SELECT p.lesson_id FROM lesson_progress p JOIN lessons l ON l.id=p.lesson_id WHERE p.user_id=? AND l.course_id=?');
        $s->execute([$me['id'], $id]); $done = array_column($s->fetchAll(), 'lesson_id');
    }
}
$prog = $me && $enrolled ? course_progress($me['id'], $id) : null;
$pageTitle = $course['title']; require __DIR__ . '/includes/header.php'; ?>
<article>
  <span class="tag"><?= e($course['category']) ?> · <?= e($course['level']) ?></span>
  <h1><?= e($course['title']) ?></h1>
  <p class="lead"><?= nl2br(e($course['description'])) ?></p>
  <p class="meta">Taught by <?= e($course['instructor']) ?> · <?= e($course['duration_hours']) ?> hours · <?= count($lessons) ?> lessons</p>

  <?php if (!$me): ?>
    <p><a class="btn" href="login.php">Log in to enroll</a></p>
  <?php elseif (!$enrolled): ?>
    <form method="post"><?= csrf_field() ?><button class="btn" name="enroll" value="1">Enroll in this course</button></form>
  <?php else: ?>
    <div class="progress" role="progressbar" aria-valuenow="<?= $prog['pct'] ?>" aria-valuemin="0" aria-valuemax="100"><span id="bar" style="width:<?= $prog['pct'] ?>%"></span></div>
    <p id="progText"><?= $prog['done'] ?> of <?= $prog['total'] ?> lessons done (<?= $prog['pct'] ?>%)</p>
  <?php endif; ?>

  <h2>Lessons</h2>
  <?php if (!$lessons): ?><p class="empty">Lessons are coming soon.</p><?php endif; ?>
  <ol class="lessons">
  <?php foreach ($lessons as $l): $isDone = in_array($l['id'], $done); ?>
    <li class="card lesson <?= $isDone ? 'is-done' : '' ?>">
      <h3><?= e($l['title']) ?></h3>
      <?php if ($enrolled): ?>
        <p><?= nl2br(e($l['content'])) ?></p>
        <?php if ($l['video_url'] && preg_match('~^https://(www\.)?(youtube\.com|youtu\.be)/~', $l['video_url'])): ?>
          <p><a href="<?= e($l['video_url']) ?>" target="_blank" rel="noopener">Watch the video</a></p>
        <?php endif; ?>
        <button class="btn btn-small done-btn" data-id="<?= (int)$l['id'] ?>" aria-pressed="<?= $isDone ? 'true' : 'false' ?>"><?= $isDone ? 'Completed' : 'Mark as complete' ?></button>
      <?php else: ?>
        <p class="meta">Enroll to read this lesson.</p>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ol>
  <?php if ($enrolled): ?><form method="post" onsubmit="return confirm('Leave this course? Your progress will be cleared.')"><?= csrf_field() ?><button class="link-btn" name="unenroll" value="1">Leave course</button></form><?php endif; ?>
</article>
<?php
if ($enrolled) $extraScripts = '<script>window.CSRF=' . json_encode(csrf_token()) . ';window.COURSE_ID=' . $id . ';</script><script src="assets/js/progress.js"></script>';
require __DIR__ . '/includes/footer.php'; ?>
