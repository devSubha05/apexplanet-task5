<?php
require_once __DIR__ . '/includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
$s = db()->prepare('SELECT c.*, cat.name AS category FROM courses c JOIN categories cat ON cat.id=c.category_id WHERE c.id=?');
$s->execute([$id]); $course = $s->fetch();
if (!$course) { http_response_code(404); $pageTitle = 'Not found'; require __DIR__ . '/includes/header.php'; echo '<p class="empty">That course does not exist. <a href="courses.php">Browse courses</a></p>'; require __DIR__ . '/includes/footer.php'; exit; }
$me = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check(); require_login();
    if (isset($_POST['unenroll'])) {
        db()->prepare('DELETE FROM enrollments WHERE user_id=? AND course_id=?')->execute([$me['id'], $id]);
        db()->prepare('DELETE p FROM lesson_progress p JOIN lessons l ON l.id=p.lesson_id WHERE p.user_id=? AND l.course_id=?')->execute([$me['id'], $id]);
        flash('info', 'You left this course and your progress was cleared.');
        redirect("course.php?id=$id");
    }
}

$lessons = db()->prepare('SELECT id,title,content,video_url,position FROM lessons WHERE course_id=? ORDER BY position,id');
$lessons->execute([$id]); $lessons = $lessons->fetchAll();
$enrolled = false; $done = []; $payment = null;
if ($me) {
    $s = db()->prepare('SELECT 1 FROM enrollments WHERE user_id=? AND course_id=?'); $s->execute([$me['id'], $id]); $enrolled = (bool)$s->fetchColumn();
    if ($enrolled) {
        $s = db()->prepare('SELECT p.lesson_id FROM lesson_progress p JOIN lessons l ON l.id=p.lesson_id WHERE p.user_id=? AND l.course_id=?');
        $s->execute([$me['id'], $id]); $done = array_column($s->fetchAll(), 'lesson_id');

        $s = db()->prepare('SELECT order_number, amount, created_at FROM payments WHERE user_id=? AND course_id=? ORDER BY id DESC LIMIT 1');
        $s->execute([$me['id'], $id]); $payment = $s->fetch() ?: null;
    }
}
$prog = $me && $enrolled ? course_progress($me['id'], $id) : null;
$price = (float)$course['price'];
$origPrice = (float)$course['original_price'];
$discountPct = $origPrice > $price ? round((($origPrice - $price) / $origPrice) * 100) : 0;

$pageTitle = $course['title']; require __DIR__ . '/includes/header.php'; ?>
<article class="course-detail-view">
  <div class="course-detail-top-grid">
    <div class="course-detail-info">
      <div class="course-header-badge-row">
        <span class="tag"><?= e($course['category']) ?> · <?= e($course['level']) ?></span>
        <?php if ($enrolled): ?>
          <span class="badge-enrolled">✓ Enrolled Student</span>
        <?php else: ?>
          <span class="badge-paid">👑 Premium Course</span>
        <?php endif; ?>
      </div>

      <h1><?= e($course['title']) ?></h1>
      <p class="lead"><?= nl2br(e($course['description'])) ?></p>
      <p class="meta">Taught by <strong><?= e($course['instructor'] !== 'Admin' ? $course['instructor'] : 'LearnLoop Academy') ?></strong> · <?= e($course['duration_hours']) ?> hours · <?= count($lessons) ?> lessons · ⭐ <?= e($course['rating'] ?? '4.8') ?> (<?= number_format((int)($course['rating_count'] ?? 2400)) ?> ratings)</p>
    </div>
    <div class="course-detail-media">
      <img src="<?= e($course['image'] ?: 'assets/img/courses/course-' . $id . '.jpg') ?>" alt="<?= e($course['title']) ?>" class="course-detail-cover-img" onerror="this.src='assets/img/courses/course-1.jpg'">
    </div>
  </div>

  <?php if (!$enrolled): ?>
    <!-- Premium Paid Course Purchase Box -->
    <div class="course-purchase-box">
      <div class="cpb-price-col">
        <div class="cpb-price-tag">
          <span class="cpb-current-price">₹<?= number_format($price) ?></span>
          <?php if ($origPrice > $price): ?>
            <span class="cpb-orig-price">₹<?= number_format($origPrice) ?></span>
            <span class="cpb-discount-badge"><?= $discountPct ?>% OFF</span>
          <?php endif; ?>
        </div>
        <p class="cpb-guarantee">🔒 30-Day Money-Back Guarantee · Lifetime Access</p>
      </div>

      <div class="cpb-action-col">
        <?php if ($me): ?>
          <a class="btn btn-buy-now" href="checkout.php?course_id=<?= (int)$id ?>">
            Enroll &amp; Pay ₹<?= number_format($price) ?> →
          </a>
        <?php else: ?>
          <a class="btn btn-buy-now" href="login.php?redirect=<?= urlencode('checkout.php?course_id=' . $id) ?>">
            Log In to Enroll &amp; Pay ₹<?= number_format($price) ?> →
          </a>
        <?php endif; ?>
      </div>

      <div class="cpb-perks">
        <span>⚡ Instant Access</span>
        <span>📜 Certificate of Completion</span>
        <span>💻 Interactive Lessons</span>
        <span>📱 Mobile &amp; Desktop</span>
      </div>
    </div>
  <?php else: ?>
    <!-- Enrolled User Progress Bar & Receipt Link -->
    <div class="course-enrolled-banner">
      <div class="progress" role="progressbar" aria-valuenow="<?= $prog['pct'] ?>" aria-valuemin="0" aria-valuemax="100">
        <span id="bar" style="width:<?= $prog['pct'] ?>%"></span>
      </div>
      <div class="enrolled-controls-row">
        <p id="progText" style="margin:0; font-weight:600; color:var(--ink);">
          <?= $prog['done'] ?> of <?= $prog['total'] ?> lessons done (<?= $prog['pct'] ?>%)
        </p>
        <?php if ($payment): ?>
          <a class="btn btn-small btn-invoice" href="payment_success.php?order=<?= urlencode($payment['order_number']) ?>">
            📄 View Tax Invoice / Receipt
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <h2 style="margin-top:2rem;">Course Curriculum (<?= count($lessons) ?> Lessons)</h2>
  <?php if (!$lessons): ?><p class="empty">Lessons are coming soon.</p><?php endif; ?>
  <ol class="lessons">
  <?php foreach ($lessons as $idx => $l): $isDone = in_array($l['id'], $done); ?>
    <li class="card lesson <?= $isDone ? 'is-done' : '' ?> <?= !$enrolled ? 'lesson-locked' : '' ?>">
      <div class="lesson-header-row">
        <h3><span class="lesson-num"><?= $idx + 1 ?>.</span> <?= e($l['title']) ?></h3>
        <?php if (!$enrolled): ?>
          <span class="lock-indicator" title="Course enrollment required">🔒 Locked</span>
        <?php endif; ?>
      </div>

      <?php if ($enrolled): ?>
        <p><?= nl2br(e($l['content'])) ?></p>
        <?php if ($l['video_url'] && preg_match('~^https://(www\.)?(youtube\.com|youtu\.be)/~', $l['video_url'])): ?>
          <p><a href="<?= e($l['video_url']) ?>" target="_blank" rel="noopener">▶ Watch the video walkthrough</a></p>
        <?php endif; ?>
        <button class="btn btn-small done-btn" data-id="<?= (int)$l['id'] ?>" aria-pressed="<?= $isDone ? 'true' : 'false' ?>">
          <?= $isDone ? '✓ Completed' : 'Mark as complete' ?>
        </button>
      <?php else: ?>
        <p class="meta lock-meta">
          🔒 Premium Lesson Content. <a href="checkout.php?course_id=<?= (int)$id ?>">Enroll now for ₹<?= number_format($price) ?></a> to unlock full text, code exercises, and videos.
        </p>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ol>

  <?php if ($enrolled): ?>
    <div style="margin-top:2.5rem; padding-top:1rem; border-top:1px solid var(--line);">
      <form method="post" onsubmit="return confirm('Leave this course? Your progress will be cleared.')">
        <?= csrf_field() ?>
        <button class="link-btn danger-text" name="unenroll" value="1">Leave course &amp; reset progress</button>
      </form>
    </div>
  <?php endif; ?>
</article>
<?php
if ($enrolled) $extraScripts = '<script>window.CSRF=' . json_encode(csrf_token()) . ';window.COURSE_ID=' . $id . ';</script><script src="assets/js/progress.js"></script>';
require __DIR__ . '/includes/footer.php'; ?>
