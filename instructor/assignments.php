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

$cid = (int)($_GET['cid'] ?? ($myCourses[0]['id'] ?? 0));
$gradeId = (int)($_GET['grade_id'] ?? 0);

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

// Handle Form Submissions: Create Assignment, Grade Submission, Delete Assignment
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // 1. Grade a student submission
    if (isset($_POST['grade_submission'])) {
        $subId = (int)$_POST['submission_id'];
        $score = (int)$_POST['score'];
        $feedback = trim($_POST['feedback'] ?? '');

        $stmt = $db->prepare('UPDATE assignment_submissions SET score=?, feedback=?, status="graded", graded_at=NOW() WHERE id=?');
        $stmt->execute([$score, $feedback, $subId]);
        flash('success', 'Submission graded successfully.');
        redirect("assignments.php?cid=$cid#submissions");
    }

    // 2. Delete an assignment
    if (isset($_POST['delete_assignment'])) {
        $assignId = (int)$_POST['delete_assignment'];
        $db->prepare('DELETE FROM assignments WHERE id=? AND course_id=?')->execute([$assignId, $cid]);
        flash('success', 'Assignment deleted.');
        redirect("assignments.php?cid=$cid");
    }

    // 3. Create new assignment / exam
    if (isset($_POST['create_assignment'])) {
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $type = in_array($_POST['type'] ?? '', ['assignment', 'exam'], true) ? $_POST['type'] : 'assignment';
        $maxScore = max(1, (int)($_POST['max_score'] ?? 100));
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

        if ($title === '' || $desc === '') {
            $errors[] = 'Title and instructions are required.';
        } else {
            $stmt = $db->prepare('INSERT INTO assignments (course_id, title, description, type, max_score, due_date) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$cid, $title, $desc, $type, $maxScore, $dueDate]);
            flash('success', ucfirst($type) . ' created successfully.');
            redirect("assignments.php?cid=$cid");
        }
    }
}

// Fetch assignments for selected course
$assignments = [];
if ($selectedCourse) {
    $stmt = $db->prepare('SELECT a.*, 
                                 (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id=a.id) AS submission_count,
                                 (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id=a.id AND s.status="submitted") AS pending_count
                          FROM assignments a 
                          WHERE a.course_id=? 
                          ORDER BY a.id DESC');
    $stmt->execute([$cid]);
    $assignments = $stmt->fetchAll();
}

// Fetch submissions to review
$submissions = [];
if ($selectedCourse) {
    $stmt = $db->prepare('SELECT s.*, u.name AS student_name, u.email AS student_email,
                                 a.title AS assignment_title, a.type AS assignment_type, a.max_score
                          FROM assignment_submissions s
                          JOIN users u ON u.id = s.user_id
                          JOIN assignments a ON a.id = s.assignment_id
                          WHERE a.course_id=?
                          ORDER BY (s.status="submitted") DESC, s.submitted_at DESC');
    $stmt->execute([$cid]);
    $submissions = $stmt->fetchAll();
}

$pageTitle = 'Assignments & Exams · Instructor Panel';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<div class="bar" style="margin-bottom:1.5rem;">
  <div>
    <h1 style="margin:0 0 .3rem;">Assignments &amp; Exams</h1>
    <p style="margin:0;color:var(--muted);">Set homework tasks, project briefs, and midterm/final exams, then grade student submissions.</p>
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

<?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>

<?php if (!$selectedCourse): ?>
  <div class="card" style="text-align:center;padding:3rem 1rem;">
    <p class="empty" style="font-size:1.15rem;margin-bottom:1.2rem;">You haven't created any courses yet.</p>
    <a href="course_form.php" class="btn">+ Create Course First</a>
  </div>
<?php else: ?>

  <div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:10px;padding:.8rem 1.2rem;margin-bottom:1.8rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.8rem;">
    <div>
      <span style="font-size:.78rem;font-weight:700;color:#7c3aed;text-transform:uppercase;">Selected Course</span>
      <h3 style="margin:0;color:var(--ink);"><?= e($selectedCourse['title']) ?></h3>
    </div>
    <div style="display:flex;gap:.5rem;">
      <a href="#createForm" class="btn btn-small" style="background:#7c3aed;">+ Create Assignment / Exam</a>
      <a href="#submissions" class="btn btn-small" style="background:#d97706;">Review Submissions (<?= count($submissions) ?>)</a>
    </div>
  </div>

  <!-- Two Column Layout: Existing Assignments & Create Form -->
  <div class="two-col" style="gap:1.6rem;align-items:start;margin-bottom:3rem;">
    
    <!-- Left: Existing Assignments / Exams -->
    <div>
      <h2 style="margin:0 0 1rem;font-size:1.25rem;">Active Assignments &amp; Exams (<?= count($assignments) ?>)</h2>

      <?php if (empty($assignments)): ?>
        <div class="card" style="text-align:center;padding:2.5rem 1rem;">
          <p class="empty">No assignments or exams created yet. Use the form on the right to set your first task.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($assignments as $a): ?>
        <div class="card" style="margin-bottom:1rem;padding:1.2rem;border-left:4px solid <?= $a['type'] === 'exam' ? '#dc2626' : '#7c3aed' ?>;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
            <div>
              <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                <span class="tag" style="background:<?= $a['type'] === 'exam' ? '#fee2e2;color:#991b1b' : '#ede9fe;color:#5b21b6' ?>;font-weight:800;font-size:.75rem;padding:2px 8px;border-radius:4px;text-transform:uppercase;">
                  <?= e($a['type']) ?>
                </span>
                <strong style="font-size:1.1rem;color:var(--ink);"><?= e($a['title']) ?></strong>
              </div>

              <p style="margin:.5rem 0 .6rem;color:#334155;font-size:.9rem;line-height:1.5;">
                <?= nl2br(e($a['description'])) ?>
              </p>

              <div style="display:flex;gap:1.2rem;font-size:.82rem;color:var(--muted);flex-wrap:wrap;">
                <span>🎯 Max Score: <strong><?= (int)$a['max_score'] ?> pts</strong></span>
                <span>📅 Due: <?= $a['due_date'] ? date('M j, Y g:i A', strtotime($a['due_date'])) : 'No deadline' ?></span>
                <span>📥 Submissions: <strong><?= (int)$a['submission_count'] ?></strong></span>
                <?php if ((int)$a['pending_count'] > 0): ?>
                  <span style="color:#d97706;font-weight:700;">⏳ <?= (int)$a['pending_count'] ?> pending grading</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="actions" style="flex-shrink:0;">
              <form method="post" onsubmit="return confirm('Delete this assignment/exam and all student submissions?');" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="delete_assignment" value="<?= (int)$a['id'] ?>">
                <button class="btn btn-small btn-danger" type="submit">Delete</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Right: Create Assignment Form -->
    <div id="createForm" class="card" style="padding:1.6rem;position:sticky;top:90px;">
      <h2 style="margin:0 0 1.2rem;font-size:1.2rem;">+ Set New Assignment or Exam</h2>

      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="create_assignment" value="1">

        <label>Type of Task
          <select name="type">
            <option value="assignment">Homework Assignment / Project</option>
            <option value="exam">Midterm / Final Exam</option>
          </select>
        </label>

        <label>Task Title
          <input name="title" placeholder="e.g. Build a Responsive Landing Page" required maxlength="150">
        </label>

        <label>Instructions &amp; Requirements
          <textarea name="description" rows="5" placeholder="Detail the questions, technical specifications, submission guidelines..." required></textarea>
        </label>

        <div class="two-col" style="gap:1rem;">
          <label>Max Score (Points)
            <input type="number" name="max_score" min="10" max="1000" value="100" required>
          </label>

          <label>Submission Due Date (Optional)
            <input type="datetime-local" name="due_date">
          </label>
        </div>

        <div style="margin-top:1.4rem;">
          <button class="btn" type="submit" style="background:#7c3aed;width:100%;">Create Task</button>
        </div>
      </form>
    </div>

  </div>

  <!-- Section 2: Student Submissions Evaluation & Grading -->
  <div id="submissions" style="padding-top:1rem;">
    <h2 style="font-size:1.4rem;margin:0 0 .5rem;">📥 Student Submissions &amp; Grading Evaluation</h2>
    <p style="color:var(--muted);margin:0 0 1.5rem;">Review student submissions, evaluate code and answers, provide written feedback, and award points.</p>

    <?php if (empty($submissions)): ?>
      <div class="card" style="text-align:center;padding:2.5rem 1rem;">
        <p class="empty">No student submissions received for this course yet.</p>
      </div>
    <?php endif; ?>

    <?php foreach ($submissions as $sub): ?>
      <div class="card" style="margin-bottom:1.5rem;padding:1.5rem;border-left:4px solid <?= $sub['status'] === 'graded' ? '#059669' : '#d97706' ?>;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;padding-bottom:.8rem;border-bottom:1px solid var(--line);">
          <div>
            <div style="display:flex;align-items:center;gap:.6rem;">
              <strong style="font-size:1.15rem;color:var(--ink);"><?= e($sub['student_name']) ?></strong>
              <span style="font-size:.85rem;color:var(--muted);">(<?= e($sub['student_email']) ?>)</span>
              <span class="tag" style="background:<?= $sub['status'] === 'graded' ? '#e6f4ea;color:#0e6b63' : '#fef3c7;color:#92400e' ?>;font-weight:700;padding:2px 8px;border-radius:99px;font-size:.78rem;text-transform:uppercase;">
                <?= $sub['status'] === 'graded' ? '✓ Graded' : '⏳ Awaiting Grade' ?>
              </span>
            </div>
            <div style="margin-top:4px;font-size:.9rem;color:var(--teal);font-weight:600;">
              Task: <?= e($sub['assignment_title']) ?> <span style="color:var(--muted);">(<?= e($sub['assignment_type']) ?>)</span>
            </div>
          </div>

          <div style="text-align:right;">
            <small style="color:var(--muted);display:block;">Submitted: <?= date('M j, Y g:i A', strtotime($sub['submitted_at'])) ?></small>
            <?php if ($sub['status'] === 'graded'): ?>
              <span style="font-size:1.1rem;font-weight:800;color:#059669;display:block;margin-top:4px;">
                Score: <?= (int)$sub['score'] ?> / <?= (int)$sub['max_score'] ?>
              </span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Student Answer Text -->
        <?php if (!empty($sub['submission_text'])): ?>
          <div style="margin-bottom:1.2rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;">
            <strong style="font-size:.82rem;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:.4rem;">Student Response / Code Submission:</strong>
            <pre style="margin:0;white-space:pre-wrap;font-family:inherit;font-size:.92rem;line-height:1.5;color:#1e293b;"><?= e($sub['submission_text']) ?></pre>
          </div>
        <?php endif; ?>

        <!-- Student Attached File -->
        <?php if (!empty($sub['file_path'])): ?>
          <div style="margin-bottom:1.2rem;">
            <a href="../uploads/submissions/<?= e($sub['file_path']) ?>" download class="btn btn-small" style="background:#2563eb;">
              📥 Download Student Attached File (<?= e($sub['file_path']) ?>)
            </a>
          </div>
        <?php endif; ?>

        <!-- Teacher Grading Form -->
        <div style="background:#fafafa;border:1px solid #e5e5e5;border-radius:10px;padding:1.2rem;margin-top:1rem;">
          <h4 style="margin:0 0 .8rem;font-size:1rem;color:var(--ink);">👨‍🏫 Teacher Evaluation &amp; Grading</h4>
          <form method="post" style="display:flex;flex-direction:column;gap:.8rem;">
            <?= csrf_field() ?>
            <input type="hidden" name="grade_submission" value="1">
            <input type="hidden" name="submission_id" value="<?= (int)$sub['id'] ?>">

            <div class="two-col" style="gap:1rem;">
              <label style="margin:0;">Award Score (Max: <?= (int)$sub['max_score'] ?> pts)
                <input type="number" name="score" min="0" max="<?= (int)$sub['max_score'] ?>" value="<?= $sub['score'] !== null ? (int)$sub['score'] : '' ?>" placeholder="e.g. 95" required>
              </label>

              <label style="margin:0;">Teacher Feedback &amp; Suggestions
                <input name="feedback" value="<?= e($sub['feedback'] ?? '') ?>" placeholder="e.g. Great code architecture, remember to validate edge cases!">
              </label>
            </div>

            <div>
              <button class="btn btn-small" type="submit" style="background:#0e6b63;">
                <?= $sub['status'] === 'graded' ? 'Update Grade & Feedback' : 'Submit Grade & Notify Student' ?>
              </button>
            </div>
          </form>
        </div>

      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
