<?php
$base = '../';
require_once __DIR__ . '/../includes/functions.php';
$me = require_instructor();
$db = db();
$isSuperAdmin = ($me['role'] === 'admin');
$instrId = (int)$me['id'];

// Get instructor courses
if ($isSuperAdmin) {
    $myCourses = $db->query('SELECT id, title FROM courses ORDER BY id DESC')->fetchAll();
} else {
    $stmt = $db->prepare('SELECT id, title FROM courses WHERE instructor_id=? OR instructor=? ORDER BY id DESC');
    $stmt->execute([$instrId, $me['name']]);
    $myCourses = $stmt->fetchAll();
}

$cIdsList = !empty($myCourses) ? implode(',', array_map('intval', array_column($myCourses, 'id'))) : '0';
$cid = (int)($_GET['cid'] ?? 0);
$threadId = (int)($_GET['thread_id'] ?? 0);

// Handle New Announcement / Reply / Pin / Delete
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // 1. Post Instructor Reply
    if (isset($_POST['post_reply'])) {
        $parent = (int)$_POST['parent_id'];
        $courseId = (int)$_POST['course_id'];
        $msg = trim($_POST['message'] ?? '');

        if ($msg === '') {
            flash('error', 'Reply message cannot be empty.');
        } else {
            $stmt = $db->prepare('INSERT INTO course_discussions (course_id, user_id, parent_id, message, is_instructor_reply) VALUES (?,?,?,?,1)');
            $stmt->execute([$courseId, (int)$me['id'], $parent, $msg]);
            flash('success', 'Your instructor reply was posted.');
            redirect("discussions.php?cid=$cid&thread_id=$parent#thread-$parent");
        }
    }

    // 2. Post Instructor Announcement / New Topic
    if (isset($_POST['post_topic'])) {
        $courseId = (int)($_POST['course_id'] ?? $cid);
        $title = trim($_POST['title'] ?? '');
        $msg = trim($_POST['message'] ?? '');
        $isPinned = isset($_POST['is_pinned']) ? 1 : 0;

        if ($title === '' || $msg === '') {
            $errors[] = 'Title and announcement content are required.';
        } else {
            $stmt = $db->prepare('INSERT INTO course_discussions (course_id, user_id, title, message, is_pinned, is_instructor_reply) VALUES (?,?,?,?,?,1)');
            $stmt->execute([$courseId, (int)$me['id'], $title, $msg, $isPinned]);
            flash('success', 'Instructor announcement posted to course.');
            redirect("discussions.php?cid=$courseId");
        }
    }

    // 3. Delete Discussion
    if (isset($_POST['delete_discussion'])) {
        $delId = (int)$_POST['delete_discussion'];
        $db->prepare('DELETE FROM course_discussions WHERE id=?')->execute([$delId]);
        flash('success', 'Thread removed.');
        redirect("discussions.php?cid=$cid");
    }
}

// Fetch questions / threads
$query = "SELECT d.*, u.name AS author_name, u.role AS author_role, u.avatar AS author_avatar,
                 c.title AS course_title,
                 (SELECT COUNT(*) FROM course_discussions r WHERE r.parent_id=d.id) AS reply_count
          FROM course_discussions d
          JOIN users u ON u.id = d.user_id
          JOIN courses c ON c.id = d.course_id
          WHERE d.parent_id IS NULL AND c.id IN ($cIdsList)";

$params = [];
if ($cid > 0) {
    $query .= " AND c.id = ?";
    $params[] = $cid;
}
if ($threadId > 0) {
    $query .= " AND d.id = ?";
    $params[] = $threadId;
}
$query .= " ORDER BY d.is_pinned DESC, d.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$threads = $stmt->fetchAll();

// Fetch replies for active threads
$threadIds = array_column($threads, 'id');
$repliesByParent = [];
if (!empty($threadIds)) {
    $thList = implode(',', array_map('intval', $threadIds));
    $repStmt = $db->query("SELECT r.*, u.name AS author_name, u.role AS author_role, u.avatar AS author_avatar
                           FROM course_discussions r
                           JOIN users u ON u.id = r.user_id
                           WHERE r.parent_id IN ($thList)
                           ORDER BY r.created_at ASC");
    while ($row = $repStmt->fetch()) {
        $repliesByParent[$row['parent_id']][] = $row;
    }
}

$pageTitle = 'Student Q&A & Interaction · Instructor Panel';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<div class="bar" style="margin-bottom:1.5rem;">
  <div>
    <h1 style="margin:0 0 .3rem;">Student Q&amp;A &amp; Discussions</h1>
    <p style="margin:0;color:var(--muted);">Answer student inquiries, post course announcements, and maintain an active classroom dialogue.</p>
  </div>
  <?php if (!empty($myCourses)): ?>
    <form method="get" style="display:flex;align-items:center;gap:.6rem;">
      <label style="margin:0;font-weight:700;">Filter Course:</label>
      <select name="cid" onchange="this.form.submit()" style="margin:0;width:auto;height:42px;">
        <option value="0">All My Courses (<?= count($myCourses) ?>)</option>
        <?php foreach ($myCourses as $mc): ?>
          <option value="<?= (int)$mc['id'] ?>" <?= $mc['id'] == $cid ? 'selected' : '' ?>><?= e($mc['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  <?php endif; ?>
</div>

<?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>

<div class="two-col" style="gap:1.6rem;align-items:start;">

  <!-- Left: Discussion Threads & Replies -->
  <div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
      <h2 style="margin:0;font-size:1.25rem;">Discussion Threads (<?= count($threads) ?>)</h2>
      <?php if ($threadId > 0 || $cid > 0): ?>
        <a href="discussions.php" style="font-size:.85rem;font-weight:700;">View All Threads</a>
      <?php endif; ?>
    </div>

    <?php if (empty($threads)): ?>
      <div class="card" style="text-align:center;padding:2.5rem 1rem;">
        <p class="empty">No student questions or discussions found. Use the form on the right to post an announcement.</p>
      </div>
    <?php endif; ?>

    <?php foreach ($threads as $t): 
      $reps = $repliesByParent[$t['id']] ?? [];
    ?>
      <div id="thread-<?= (int)$t['id'] ?>" class="card" style="margin-bottom:1.5rem;padding:1.4rem;border-left:4px solid <?= $t['is_pinned'] ? 'var(--gold)' : 'var(--teal)' ?>;">
        
        <!-- Header -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.8rem;margin-bottom:.8rem;">
          <div style="display:flex;align-items:center;gap:.7rem;">
            <div style="width:36px;height:36px;border-radius:50%;background:<?= $t['author_role'] === 'instructor' ? 'var(--gold);color:var(--ink)' : 'var(--teal);color:#fff' ?>;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem;">
              <?= strtoupper(substr($t['author_name'], 0, 1)) ?>
            </div>
            <div>
              <strong><?= e($t['author_name']) ?></strong>
              <?php if ($t['author_role'] === 'instructor'): ?>
                <span style="background:#fef3c7;color:#92400e;font-size:.72rem;font-weight:800;padding:2px 6px;border-radius:4px;margin-left:4px;">INSTRUCTOR</span>
              <?php endif; ?>
              <small style="color:var(--muted);display:block;">
                Course: <strong style="color:var(--teal);"><?= e($t['course_title']) ?></strong> · <?= date('M j, Y g:i A', strtotime($t['created_at'])) ?>
              </small>
            </div>
          </div>

          <div style="display:flex;align-items:center;gap:.5rem;">
            <?php if ($t['is_pinned']): ?>
              <span style="background:#fef8eb;color:#b45309;border:1px solid #fde68a;font-size:.75rem;font-weight:800;padding:2px 8px;border-radius:99px;">📌 PINNED</span>
            <?php endif; ?>
            <form method="post" onsubmit="return confirm('Delete this thread?');" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="delete_discussion" value="<?= (int)$t['id'] ?>">
              <button class="link-btn danger-text" style="font-size:.85rem;">Delete</button>
            </form>
          </div>
        </div>

        <!-- Topic Content -->
        <?php if (!empty($t['title'])): ?>
          <h3 style="margin:.3rem 0 .5rem;font-size:1.15rem;color:var(--ink);"><?= e($t['title']) ?></h3>
        <?php endif; ?>
        <p style="margin:0 0 1rem;color:#334155;line-height:1.55;font-size:.95rem;white-space:pre-wrap;"><?= e($t['message']) ?></p>

        <!-- Nested Replies -->
        <?php if (!empty($reps)): ?>
          <div style="margin:1rem 0;padding-left:1rem;border-left:2px solid #e2e8f0;display:flex;flex-direction:column;gap:.8rem;">
            <?php foreach ($reps as $rep): ?>
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:.85rem 1rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem;">
                  <div>
                    <strong style="font-size:.88rem;color:var(--ink);"><?= e($rep['author_name']) ?></strong>
                    <?php if ($rep['is_instructor_reply'] || $rep['author_role'] === 'instructor'): ?>
                      <span style="background:#e6f4ea;color:#0e6b63;font-size:.7rem;font-weight:800;padding:2px 6px;border-radius:4px;margin-left:4px;">TEACHER ANSWER</span>
                    <?php endif; ?>
                  </div>
                  <small style="color:var(--muted);font-size:.78rem;"><?= date('M j, g:i A', strtotime($rep['created_at'])) ?></small>
                </div>
                <p style="margin:0;color:#334155;font-size:.9rem;line-height:1.45;white-space:pre-wrap;"><?= e($rep['message']) ?></p>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Reply Box for Instructor -->
        <div style="margin-top:1rem;padding-top:.8rem;border-top:1px dashed #e2e8f0;">
          <form method="post" style="display:flex;gap:.6rem;align-items:flex-end;">
            <?= csrf_field() ?>
            <input type="hidden" name="post_reply" value="1">
            <input type="hidden" name="parent_id" value="<?= (int)$t['id'] ?>">
            <input type="hidden" name="course_id" value="<?= (int)$t['course_id'] ?>">

            <div style="flex:1;">
              <input name="message" placeholder="Write an instructor response to this student..." required style="margin:0;height:42px;">
            </div>
            <button class="btn btn-small" type="submit" style="background:#0e6b63;height:42px;padding:0 1.2rem;flex-shrink:0;">
              Reply as Instructor
            </button>
          </form>
        </div>

      </div>
    <?php endforeach; ?>
  </div>

  <!-- Right: Post New Course Announcement -->
  <div class="card" style="padding:1.6rem;position:sticky;top:90px;">
    <h2 style="margin:0 0 1.2rem;font-size:1.2rem;">📢 Post Teacher Announcement</h2>

    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="post_topic" value="1">

      <label>Target Course
        <select name="course_id">
          <?php foreach ($myCourses as $mc): ?>
            <option value="<?= (int)$mc['id'] ?>" <?= $mc['id'] == $cid ? 'selected' : '' ?>><?= e($mc['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Announcement Title
        <input name="title" placeholder="e.g. Week 2 Live Q&amp;A Session &amp; Project Tips" required maxlength="180">
      </label>

      <label>Message Content
        <textarea name="message" rows="5" placeholder="Share updates, exam instructions, extra resources, or tips with all enrolled students..." required></textarea>
      </label>

      <label style="display:flex;align-items:center;gap:.6rem;font-weight:600;margin-top:.4rem;cursor:pointer;">
        <input type="checkbox" name="is_pinned" value="1" style="width:auto;margin:0;">
        Pin this announcement to top of course
      </label>

      <div style="margin-top:1.4rem;">
        <button class="btn" type="submit" style="width:100%;">Broadcast Announcement</button>
      </div>
    </form>
  </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
