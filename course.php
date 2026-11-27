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

    if (isset($_POST['submit_assignment'])) {
        $assignId = (int)$_POST['assignment_id'];
        $subText = trim($_POST['submission_text'] ?? '');
        $subFileName = null;

        if (!empty($_FILES['submission_file']['tmp_name'])) {
            $sFile = $_FILES['submission_file'];
            $sExt = strtolower(pathinfo($sFile['name'], PATHINFO_EXTENSION));
            if (in_array($sExt, ['pdf', 'zip', 'doc', 'docx', 'txt', 'py', 'js', 'html', 'css', 'sql'], true)) {
                $subFileName = 'sub-' . $id . '-' . $assignId . '-' . (int)$me['id'] . '-' . time() . '.' . $sExt;
                move_uploaded_file($sFile['tmp_name'], __DIR__ . '/uploads/submissions/' . $subFileName);
            }
        }

        if ($subText === '' && !$subFileName) {
            flash('error', 'Please provide either written answers or upload a file.');
        } else {
            $stmt = db()->prepare('INSERT INTO assignment_submissions (assignment_id, user_id, submission_text, file_path, status, submitted_at)
                                  VALUES (?,?,?,?,"submitted",NOW())
                                  ON DUPLICATE KEY UPDATE submission_text=VALUES(submission_text), file_path=COALESCE(VALUES(file_path), file_path), status="submitted", submitted_at=NOW()');
            $stmt->execute([$assignId, (int)$me['id'], $subText, $subFileName]);
            flash('success', 'Your assignment submission was received by the instructor.');
        }
        redirect("course.php?id=$id#tab-assignments");
    }

    if (isset($_POST['ask_question'])) {
        $qTitle = trim($_POST['question_title'] ?? '');
        $qMsg = trim($_POST['question_message'] ?? '');
        if ($qMsg === '') {
            flash('error', 'Please write your question before submitting.');
        } else {
            $stmt = db()->prepare('INSERT INTO course_discussions (course_id, user_id, title, message) VALUES (?,?,?,?)');
            $stmt->execute([$id, (int)$me['id'], $qTitle ?: null, $qMsg]);
            flash('success', 'Your question has been posted! The instructor will reply shortly.');
        }
        redirect("course.php?id=$id#tab-discussions");
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
$isTeacher = $me && ($me['role'] === 'admin' || (int)($course['instructor_id'] ?? 0) === (int)$me['id'] || $me['name'] === $course['instructor']);

$materials = db()->prepare('SELECT * FROM materials WHERE course_id=? ORDER BY id DESC');
$materials->execute([$id]);
$materials = $materials->fetchAll();

$assignments = db()->prepare('SELECT a.*, s.status AS sub_status, s.score, s.feedback, s.submitted_at, s.submission_text, s.file_path AS sub_file
                              FROM assignments a
                              LEFT JOIN assignment_submissions s ON s.assignment_id=a.id AND s.user_id=?
                              WHERE a.course_id=?
                              ORDER BY a.id ASC');
$assignments->execute([$me ? (int)$me['id'] : 0, $id]);
$assignments = $assignments->fetchAll();

$discussions = db()->prepare('SELECT d.*, u.name AS author_name, u.role AS author_role, u.avatar AS author_avatar
                              FROM course_discussions d
                              JOIN users u ON u.id = d.user_id
                              WHERE d.course_id=? AND d.parent_id IS NULL
                              ORDER BY d.is_pinned DESC, d.created_at DESC LIMIT 25');
$discussions->execute([$id]);
$discussions = $discussions->fetchAll();

$discReplies = [];
if (!empty($discussions)) {
    $dIds = implode(',', array_map('intval', array_column($discussions, 'id')));
    $rStmt = db()->query("SELECT r.*, u.name AS author_name, u.role AS author_role
                          FROM course_discussions r
                          JOIN users u ON u.id=r.user_id
                          WHERE r.parent_id IN ($dIds)
                          ORDER BY r.created_at ASC");
    while ($row = $rStmt->fetch()) {
        $discReplies[$row['parent_id']][] = $row;
    }
}

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

  <div class="course-nav-tabs" role="tablist" style="display:flex;gap:.5rem;margin:2rem 0 1.5rem;border-bottom:2px solid var(--line);overflow-x:auto;padding-bottom:.4rem;">
    <button type="button" class="course-tab-btn active" data-tab="tab-curriculum">🎬 Lessons (<?= count($lessons) ?>)</button>
    <button type="button" class="course-tab-btn" data-tab="tab-materials">📁 Study Materials (<?= count($materials) ?>)</button>
    <button type="button" class="course-tab-btn" data-tab="tab-assignments">📝 Assignments &amp; Exams (<?= count($assignments) ?>)</button>
    <button type="button" class="course-tab-btn" data-tab="tab-discussions">💬 Q&amp;A with Teacher (<?= count($discussions) ?>)</button>
    <?php if ($isTeacher): ?>
      <a href="instructor/lessons.php?cid=<?= $id ?>" class="btn btn-small" style="background:#2563eb;margin-left:auto;align-self:center;text-decoration:none;">
        ⚙️ Edit in Teacher Panel
      </a>
    <?php endif; ?>
  </div>

  <div id="tab-curriculum" class="course-tab-panel active">
    <h2 style="margin:0 0 1rem;font-size:1.3rem;">Course Curriculum (<?= count($lessons) ?> Lessons)</h2>
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
          <?php if ($l['video_url']): ?>
            <?php if (preg_match('~^https://(www\.)?(youtube\.com|youtu\.be)/~', $l['video_url'])): ?>
              <p><a href="<?= e($l['video_url']) ?>" target="_blank" rel="noopener" style="font-weight:700;">▶ Watch YouTube Lecture Walkthrough ↗</a></p>
            <?php else: ?>
              <p><a href="<?= e($l['video_url']) ?>" target="_blank" rel="noopener" style="font-weight:700;">▶ Watch Video Lecture File ↗</a></p>
            <?php endif; ?>
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
  </div>

  <div id="tab-materials" class="course-tab-panel" style="display:none;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;flex-wrap:wrap;gap:.8rem;">
      <div>
        <h2 style="margin:0;font-size:1.3rem;">📁 Study Materials &amp; Resources</h2>
        <p style="margin:0;color:var(--muted);font-size:.92rem;">Lecture notes, cheat sheets, code repositories, and downloadable templates.</p>
      </div>
      <?php if ($isTeacher): ?>
        <a href="instructor/materials.php?cid=<?= $id ?>" class="btn btn-small" style="background:#059669;">+ Upload Material</a>
      <?php endif; ?>
    </div>

    <?php if (!$enrolled && !$isTeacher): ?>
      <div class="card" style="text-align:center;padding:2.5rem 1rem;">
        <p class="meta lock-meta" style="font-size:1rem;margin-bottom:1rem;">
          🔒 Study materials are available to enrolled students.
        </p>
        <a class="btn" href="checkout.php?course_id=<?= (int)$id ?>">Enroll to Download All Resources</a>
      </div>
    <?php elseif (empty($materials)): ?>
      <div class="card" style="text-align:center;padding:2.5rem 1rem;">
        <p class="empty">No study materials have been uploaded for this course yet.</p>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:1rem;">
        <?php foreach ($materials as $m): ?>
          <div class="card" style="padding:1.2rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;border-left:4px solid #059669;">
            <div>
              <div style="display:flex;align-items:center;gap:.5rem;">
                <span style="font-size:1.3rem;">📄</span>
                <strong style="font-size:1.05rem;color:var(--ink);"><?= e($m['title']) ?></strong>
                <span class="tag" style="text-transform:uppercase;font-size:.72rem;background:#e6f4ea;padding:2px 6px;border-radius:4px;"><?= e($m['file_type']) ?></span>
              </div>
              <?php if (!empty($m['description'])): ?>
                <p style="margin:.3rem 0;color:#475569;font-size:.88rem;"><?= e($m['description']) ?></p>
              <?php endif; ?>
              <small style="color:var(--muted);">Size: <?= round($m['file_size'] / 1024, 1) ?> KB · Added <?= date('M j, Y', strtotime($m['created_at'])) ?></small>
            </div>
            <a href="uploads/materials/<?= e($m['file_path']) ?>" download class="btn btn-small" style="background:#059669;flex-shrink:0;">
              Download ⬇
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div id="tab-assignments" class="course-tab-panel" style="display:none;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;flex-wrap:wrap;gap:.8rem;">
      <div>
        <h2 style="margin:0;font-size:1.3rem;">📝 Assignments &amp; Exams</h2>
        <p style="margin:0;color:var(--muted);font-size:.92rem;">Practical homework projects, quizzes, and midterm/final exams.</p>
      </div>
      <?php if ($isTeacher): ?>
        <a href="instructor/assignments.php?cid=<?= $id ?>" class="btn btn-small" style="background:#7c3aed;">+ Manage &amp; Grade in Panel</a>
      <?php endif; ?>
    </div>

    <?php if (!$enrolled && !$isTeacher): ?>
      <div class="card" style="text-align:center;padding:2.5rem 1rem;">
        <p class="meta lock-meta" style="font-size:1rem;margin-bottom:1rem;">
          🔒 Assignments and grading evaluations are reserved for enrolled students.
        </p>
        <a class="btn" href="checkout.php?course_id=<?= (int)$id ?>">Enroll to Access Assignments &amp; Exams</a>
      </div>
    <?php elseif (empty($assignments)): ?>
      <div class="card" style="text-align:center;padding:2.5rem 1rem;">
        <p class="empty">No assignments or exams currently scheduled for this course.</p>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <?php foreach ($assignments as $a): ?>
          <div class="card" style="padding:1.5rem;border-left:4px solid <?= $a['type'] === 'exam' ? '#dc2626' : '#7c3aed' ?>;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:.8rem;">
              <div>
                <div style="display:flex;align-items:center;gap:.6rem;">
                  <span class="tag" style="background:<?= $a['type'] === 'exam' ? '#fee2e2;color:#991b1b' : '#ede9fe;color:#5b21b6' ?>;font-weight:800;font-size:.75rem;padding:2px 8px;border-radius:4px;text-transform:uppercase;">
                    <?= e($a['type']) ?>
                  </span>
                  <strong style="font-size:1.15rem;color:var(--ink);"><?= e($a['title']) ?></strong>
                </div>
                <div style="font-size:.85rem;color:var(--muted);margin-top:4px;">
                  Max Score: <strong><?= (int)$a['max_score'] ?> points</strong> · Due Date: <strong><?= $a['due_date'] ? date('M j, Y g:i A', strtotime($a['due_date'])) : 'No strict deadline' ?></strong>
                </div>
              </div>

              <?php if (!empty($a['sub_status'])): ?>
                <div>
                  <span class="tag" style="background:<?= $a['sub_status'] === 'graded' ? '#e6f4ea;color:#0e6b63' : '#fef3c7;color:#92400e' ?>;font-weight:800;padding:3px 10px;border-radius:99px;font-size:.82rem;">
                    <?= $a['sub_status'] === 'graded' ? '✓ Graded: ' . (int)$a['score'] . ' / ' . (int)$a['max_score'] : '⏳ Submitted (Pending Grade)' ?>
                  </span>
                </div>
              <?php endif; ?>
            </div>

            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-bottom:1.2rem;">
              <strong style="font-size:.82rem;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:.3rem;">Task Instructions:</strong>
              <div style="font-size:.92rem;color:#334155;line-height:1.55;"><?= nl2br(e($a['description'])) ?></div>
            </div>

            <?php if (!empty($a['sub_status'])): ?>
              <div style="background:#fdfcf9;border:1px solid #fae8c8;border-radius:8px;padding:1rem;margin-bottom:1rem;">
                <div style="font-size:.85rem;color:var(--muted);margin-bottom:.4rem;">
                  Submitted on <?= date('M j, Y g:i A', strtotime($a['submitted_at'])) ?>:
                </div>
                <?php if (!empty($a['submission_text'])): ?>
                  <pre style="margin:0 0 .5rem;white-space:pre-wrap;font-family:inherit;font-size:.9rem;color:#1e293b;"><?= e($a['submission_text']) ?></pre>
                <?php endif; ?>
                <?php if (!empty($a['sub_file'])): ?>
                  <div><a href="uploads/submissions/<?= e($a['sub_file']) ?>" download style="font-size:.88rem;color:var(--teal);font-weight:700;">📥 Download Your Uploaded File</a></div>
                <?php endif; ?>

                <?php if (!empty($a['feedback'])): ?>
                  <div style="margin-top:.8rem;padding-top:.8rem;border-top:1px dashed #e2e8f0;color:#0e6b63;">
                    <strong>👨‍🏫 Instructor Feedback:</strong>
                    <p style="margin:.3rem 0 0;font-size:.92rem;color:#14212b;"><?= e($a['feedback']) ?></p>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div style="background:#fbfcfc;border:1px solid #e5e7eb;border-radius:8px;padding:1.2rem;">
              <h4 style="margin:0 0 .8rem;font-size:.98rem;color:var(--ink);">
                <?= !empty($a['sub_status']) ? 'Resubmit Your Work' : 'Submit Your Solution' ?>
              </h4>
              <form method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="submit_assignment" value="1">
                <input type="hidden" name="assignment_id" value="<?= (int)$a['id'] ?>">

                <label style="margin-bottom:.6rem;">Your Solution / Code / Answers
                  <textarea name="submission_text" rows="4" placeholder="Type your answers, links to repository, or notes here..."><?= e($a['submission_text'] ?? '') ?></textarea>
                </label>

                <label style="margin-bottom:.8rem;">Attach File (PDF, ZIP, Code, Document - Optional)
                  <input type="file" name="submission_file" accept=".pdf,.zip,.doc,.docx,.txt,.py,.js,.html,.css,.sql">
                </label>

                <button class="btn btn-small" type="submit" style="background:#7c3aed;">
                  <?= !empty($a['sub_status']) ? 'Update Submission' : 'Submit for Grading' ?>
                </button>
              </form>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div id="tab-discussions" class="course-tab-panel" style="display:none;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;flex-wrap:wrap;gap:.8rem;">
      <div>
        <h2 style="margin:0;font-size:1.3rem;">💬 Student Q&amp;A &amp; Teacher Discussion</h2>
        <p style="margin:0;color:var(--muted);font-size:.92rem;">Ask questions directly to the instructor and learn alongside fellow students.</p>
      </div>
      <?php if ($isTeacher): ?>
        <a href="instructor/discussions.php?cid=<?= $id ?>" class="btn btn-small" style="background:#0e6b63;">Manage Q&amp;A in Panel</a>
      <?php endif; ?>
    </div>

    <?php if ($me): ?>
      <div class="card" style="padding:1.4rem;margin-bottom:1.8rem;border:1.5px solid rgba(14,107,99,0.2);">
        <h3 style="margin:0 0 .8rem;font-size:1.1rem;color:var(--teal);">🙋 Ask the Instructor a Question</h3>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="ask_question" value="1">

          <label>Question Topic
            <input name="question_title" placeholder="e.g. Question regarding Lesson 3 Flexbox alignment" maxlength="180">
          </label>

          <label>Your Question Details
            <textarea name="question_message" rows="3" placeholder="Explain what you are trying to do, error messages, or concepts you want clarified..." required></textarea>
          </label>

          <button class="btn btn-small" type="submit" style="background:#0e6b63;">Post Question</button>
        </form>
      </div>
    <?php else: ?>
      <div class="card" style="text-align:center;padding:1.5rem;margin-bottom:1.8rem;">
        <p style="margin:0 0 .6rem;color:var(--muted);">Have a question about this course? Log in to ask the teacher.</p>
        <a href="login.php?redirect=<?= urlencode('course.php?id=' . $id . '#tab-discussions') ?>" class="btn btn-small">Log in to Ask</a>
      </div>
    <?php endif; ?>

    <?php if (empty($discussions)): ?>
      <div class="card" style="text-align:center;padding:2.5rem 1rem;">
        <p class="empty">No questions posted yet. Be the first to ask the instructor!</p>
      </div>
    <?php endif; ?>

    <div style="display:flex;flex-direction:column;gap:1.2rem;">
      <?php foreach ($discussions as $t): 
        $reps = $discReplies[$t['id']] ?? [];
      ?>
        <div class="card" style="padding:1.4rem;border-left:4px solid <?= $t['is_pinned'] ? 'var(--gold)' : 'var(--teal)' ?>;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.6rem;">
            <div style="display:flex;align-items:center;gap:.6rem;">
              <div style="width:34px;height:34px;border-radius:50%;background:<?= $t['author_role'] === 'instructor' ? 'var(--gold);color:var(--ink)' : 'var(--teal);color:#fff' ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;">
                <?= strtoupper(substr($t['author_name'], 0, 1)) ?>
              </div>
              <div>
                <strong><?= e($t['author_name']) ?></strong>
                <?php if ($t['author_role'] === 'instructor'): ?>
                  <span style="background:#fef3c7;color:#92400e;font-size:.72rem;font-weight:800;padding:2px 6px;border-radius:4px;margin-left:4px;">INSTRUCTOR</span>
                <?php endif; ?>
                <small style="color:var(--muted);display:block;"><?= date('M j, Y g:i A', strtotime($t['created_at'])) ?></small>
              </div>
            </div>

            <?php if ($t['is_pinned']): ?>
              <span style="background:#fef8eb;color:#b45309;border:1px solid #fde68a;font-size:.72rem;font-weight:800;padding:2px 7px;border-radius:99px;">📌 PINNED ANNOUNCEMENT</span>
            <?php endif; ?>
          </div>

          <?php if (!empty($t['title'])): ?>
            <h4 style="margin:.3rem 0 .4rem;font-size:1.1rem;color:var(--ink);"><?= e($t['title']) ?></h4>
          <?php endif; ?>
          <p style="margin:0 0 .8rem;color:#334155;line-height:1.5;font-size:.92rem;white-space:pre-wrap;"><?= e($t['message']) ?></p>

          <?php if (!empty($reps)): ?>
            <div style="margin:1rem 0 0 1rem;padding-left:1rem;border-left:2px solid #e2e8f0;display:flex;flex-direction:column;gap:.8rem;">
              <?php foreach ($reps as $rep): ?>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.8rem 1rem;">
                  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.2rem;">
                    <div>
                      <strong style="font-size:.85rem;color:var(--ink);"><?= e($rep['author_name']) ?></strong>
                      <?php if ($rep['author_role'] === 'instructor'): ?>
                        <span style="background:#e6f4ea;color:#0e6b63;font-size:.7rem;font-weight:800;padding:2px 6px;border-radius:4px;margin-left:4px;">TEACHER ANSWER</span>
                      <?php endif; ?>
                    </div>
                    <small style="color:var(--muted);font-size:.75rem;"><?= date('M j, g:i A', strtotime($rep['created_at'])) ?></small>
                  </div>
                  <p style="margin:0;color:#334155;font-size:.88rem;line-height:1.45;white-space:pre-wrap;"><?= e($rep['message']) ?></p>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
      <?php endforeach; ?>
    </div>
  </div>

</article>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const tabBtns = document.querySelectorAll('.course-tab-btn');
  const tabPanels = document.querySelectorAll('.course-tab-panel');

  function switchTab(targetId) {
    tabBtns.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.tab === targetId);
      if (btn.dataset.tab === targetId) {
        btn.style.color = 'var(--teal)';
        btn.style.borderBottom = '3px solid var(--teal)';
        btn.style.fontWeight = '700';
      } else {
        btn.style.color = 'var(--muted)';
        btn.style.borderBottom = 'none';
        btn.style.fontWeight = '600';
      }
    });

    tabPanels.forEach(panel => {
      if (panel.id === targetId) {
        panel.style.display = 'block';
      } else {
        panel.style.display = 'none';
      }
    });
  }

  tabBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      switchTab(this.dataset.tab);
    });
  });
L
  if (window.location.hash) {
    const hashId = window.location.hash.substring(1);
    if (document.getElementById(hashId)) {
      switchTab(hashId);
    }
  } else {
    switchTab('tab-curriculum');
  }
});
</script>
<?php
if ($enrolled) $extraScripts = '<script>window.CSRF=' . json_encode(csrf_token()) . ';window.COURSE_ID=' . $id . ';</script><script src="assets/js/progress.js"></script>';
require __DIR__ . '/includes/footer.php'; ?>
