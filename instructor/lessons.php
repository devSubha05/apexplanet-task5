<?php
$base = '../';
require_once __DIR__ . '/../includes/functions.php';
$me = require_instructor();
$db = db();
$isSuperAdmin = ($me['role'] === 'admin');
$instrId = (int)$me['id'];

// Get all instructor's courses for selector
if ($isSuperAdmin) {
    $myCourses = $db->query('SELECT id, title FROM courses ORDER BY id DESC')->fetchAll();
} else {
    $stmt = $db->prepare('SELECT id, title FROM courses WHERE instructor_id=? OR instructor=? ORDER BY id DESC');
    $stmt->execute([$instrId, $me['name']]);
    $myCourses = $stmt->fetchAll();
}

$cid = (int)($_GET['cid'] ?? ($_GET['course_id'] ?? ($myCourses[0]['id'] ?? 0)));

// Verify ownership of selected course
$selectedCourse = null;
if ($cid) {
    if ($isSuperAdmin) {
        $s = $db->prepare('SELECT * FROM courses WHERE id=?');
        $s->execute([$cid]);
    } else {
        $s = $db->prepare('SELECT * FROM courses WHERE id=? AND (instructor_id=? OR instructor=?)');
        $s->execute([$cid, $instrId, $me['name']]);
    }
    $selectedCourse = $s->fetch() ?: null;
}

if (!$selectedCourse && !empty($myCourses)) {
    $cid = (int)$myCourses[0]['id'];
    $selectedCourse = $myCourses[0];
}

$edit = null;

// Handle Lesson Add / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedCourse) {
    csrf_check();

    if (isset($_POST['delete'])) {
        $delId = (int)$_POST['delete'];
        $db->prepare('DELETE FROM lessons WHERE id=? AND course_id=?')->execute([$delId, $cid]);
        flash('success', 'Lesson deleted.');
    } else {
        $lid = (int)($_POST['lesson_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['content'] ?? '');
        $videoUrl = trim($_POST['video_url'] ?? '');
        $pos = max(1, (int)($_POST['position'] ?? 1));

        // Handle video file upload if provided
        if (!empty($_FILES['video_file']['tmp_name'])) {
            $vFile = $_FILES['video_file'];
            $vExt = strtolower(pathinfo($vFile['name'], PATHINFO_EXTENSION));
            if (in_array($vExt, ['mp4', 'webm', 'ogg'], true)) {
                $vName = 'lesson-vid-' . time() . '-' . random_int(100, 999) . '.' . $vExt;
                $vDest = __DIR__ . '/../uploads/videos/' . $vName;
                if (move_uploaded_file($vFile['tmp_name'], $vDest)) {
                    $videoUrl = 'uploads/videos/' . $vName;
                }
            }
        }

        if ($title === '' || $body === '') {
            flash('error', 'Lesson requires both a title and instructional content.');
        } else {
            if ($lid) {
                $stmt = $db->prepare('UPDATE lessons SET title=?, content=?, video_url=?, position=? WHERE id=? AND course_id=?');
                $stmt->execute([$title, $body, $videoUrl ?: null, $pos, $lid, $cid]);
                flash('success', 'Lesson updated successfully.');
            } else {
                $stmt = $db->prepare('INSERT INTO lessons (course_id, title, content, video_url, position) VALUES (?,?,?,?,?)');
                $stmt->execute([$cid, $title, $body, $videoUrl ?: null, $pos]);
                flash('success', 'New lesson published.');
            }
        }
    }
    redirect("lessons.php?cid=$cid");
}

if (isset($_GET['edit']) && $selectedCourse) {
    $s = $db->prepare('SELECT * FROM lessons WHERE id=? AND course_id=?');
    $s->execute([(int)$_GET['edit'], $cid]);
    $edit = $s->fetch() ?: null;
}

$lessons = [];
if ($selectedCourse) {
    $stmt = $db->prepare('SELECT * FROM lessons WHERE course_id=? ORDER BY position ASC, id ASC');
    $stmt->execute([$cid]);
    $lessons = $stmt->fetchAll();
}

$pageTitle = 'Manage Lessons & Videos · Instructor Panel';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<div class="bar" style="margin-bottom:1.5rem;">
  <div>
    <h1 style="margin:0 0 .3rem;">Lessons &amp; Video Lectures</h1>
    <p style="margin:0;color:var(--muted);">Upload course video lectures, format lesson notes, and organize the curriculum.</p>
  </div>
  <?php if (!empty($myCourses)): ?>
    <form method="get" style="display:flex;align-items:center;gap:.6rem;">
      <label style="margin:0;font-weight:700;">Select Course:</label>
      <select name="cid" onchange="this.form.submit()" style="margin:0;width:auto;height:42px;">
        <?php foreach ($myCourses as $mc): ?>
          <option value="<?= (int)$mc['id'] ?>" <?= $mc['id'] == $cid ? 'selected' : '' ?>><?= e($mc['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  <?php endif; ?>
</div>

<?php if (!$selectedCourse): ?>
  <div class="card" style="text-align:center;padding:3rem 1rem;">
    <p class="empty" style="font-size:1.15rem;margin-bottom:1.2rem;">You haven't created any courses yet.</p>
    <a href="course_form.php" class="btn">+ Create Course First</a>
  </div>
<?php else: ?>

  <div style="background:#e6f4f1;border:1px solid #bce6dc;border-radius:10px;padding:.8rem 1.2rem;margin-bottom:1.6rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.8rem;">
    <div>
      <span style="font-size:.78rem;font-weight:700;color:var(--teal);text-transform:uppercase;">Current Course</span>
      <h3 style="margin:0;color:var(--ink);"><?= e($selectedCourse['title']) ?></h3>
    </div>
    <div style="display:flex;gap:.5rem;">
      <a href="materials.php?cid=<?= $cid ?>" class="btn btn-small" style="background:#059669;">+ Study Materials</a>
      <a href="assignments.php?cid=<?= $cid ?>" class="btn btn-small" style="background:#7c3aed;">+ Assignments</a>
      <a href="../course.php?id=<?= $cid ?>" target="_blank" class="btn btn-small" style="background:#475569;">Preview Student View ↗</a>
    </div>
  </div>

  <div class="two-col" style="gap:1.6rem;align-items:start;">
    
    <!-- Left: Existing Lessons List -->
    <div>
      <h2 style="margin:0 0 1rem;font-size:1.25rem;">Curriculum Lessons (<?= count($lessons) ?>)</h2>
      
      <?php if (empty($lessons)): ?>
        <div class="card" style="text-align:center;padding:2rem 1rem;">
          <p class="empty">No lessons added to this course yet. Use the form on the right to add your first lecture.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($lessons as $idx => $r): ?>
        <div class="card" style="margin-bottom:1rem;border-left:4px solid var(--teal);padding:1.1rem 1.3rem;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.8rem;">
            <div>
              <div style="display:flex;align-items:center;gap:.6rem;">
                <span style="background:var(--teal);color:#fff;border-radius:50%;width:24px;height:24px;display:inline-flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:800;"><?= (int)$r['position'] ?></span>
                <strong style="font-size:1.05rem;color:var(--ink);"><?= e($r['title']) ?></strong>
              </div>
              <p style="margin:.5rem 0 .4rem;color:#475569;font-size:.9rem;line-height:1.4;">
                <?= e(mb_strimwidth($r['content'], 0, 120, '...')) ?>
              </p>
              <?php if (!empty($r['video_url'])): ?>
                <div style="font-size:.82rem;color:var(--teal);font-weight:600;display:flex;align-items:center;gap:.3rem;">
                  <span>🎬 Video:</span> <code style="font-size:.8rem;"><?= e(mb_strimwidth($r['video_url'], 0, 45, '...')) ?></code>
                </div>
              <?php else: ?>
                <span style="font-size:.8rem;color:var(--muted);">📄 Reading &amp; Code Exercise</span>
              <?php endif; ?>
            </div>

            <div class="actions" style="flex-shrink:0;">
              <a href="?cid=<?= $cid ?>&edit=<?= (int)$r['id'] ?>" class="btn btn-small" style="background:#475569;">Edit</a>
              <form method="post" onsubmit="return confirm('Delete this lesson permanently?');" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="delete" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-small btn-danger" type="submit">Delete</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Right: Add / Edit Lesson Form -->
    <div class="card" style="padding:1.6rem;position:sticky;top:90px;">
      <h2 style="margin:0 0 1.2rem;font-size:1.2rem;"><?= $edit ? 'Edit Lesson #' . (int)$edit['position'] : '+ Add New Lesson' ?></h2>
      
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="lesson_id" value="<?= (int)($edit['id'] ?? 0) ?>">

        <label>Lesson Title
          <input name="title" value="<?= e($edit['title'] ?? '') ?>" placeholder="e.g. Introduction to CSS Flexbox Architecture" required maxlength="120">
        </label>

        <label>Lesson Notes &amp; Educational Content
          <textarea name="content" rows="6" placeholder="Write lesson summary, instructions, key code examples..." required><?= e($edit['content'] ?? '') ?></textarea>
        </label>

        <label>Video Lecture URL (YouTube or external MP4)
          <input name="video_url" value="<?= e($edit['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
        </label>

        <label>Or Upload Video File (.mp4, .webm)
          <input type="file" name="video_file" accept="video/mp4,video/webm">
          <small style="color:var(--muted);margin-top:3px;">Upload your own video lecture file (up to 50MB).</small>
        </label>

        <label>Lesson Sequence Position
          <input type="number" name="position" min="1" value="<?= (int)($edit['position'] ?? count($lessons) + 1) ?>" required>
        </label>

        <div style="margin-top:1.4rem;display:flex;gap:.8rem;align-items:center;">
          <button class="btn" type="submit"><?= $edit ? 'Update Lesson' : 'Publish Lesson' ?></button>
          <?php if ($edit): ?>
            <a href="lessons.php?cid=<?= $cid ?>" style="color:var(--muted);text-decoration:none;">Cancel Edit</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

  </div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
