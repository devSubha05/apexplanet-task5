<?php
$base = '../';
require_once __DIR__ . '/../includes/functions.php';
$me = require_instructor();
$db = db();

// If admin, they can manage everything; if instructor, filter by their instructor_id or name
$isSuperAdmin = ($me['role'] === 'admin');
$instrId = (int)$me['id'];

// Course IDs owned by this educator
if ($isSuperAdmin) {
    $courseIds = $db->query('SELECT id FROM courses')->fetchAll(PDO::FETCH_COLUMN);
    $courseCount = count($courseIds);
} else {
    $s = $db->prepare('SELECT id FROM courses WHERE instructor_id = ? OR instructor = ?');
    $s->execute([$instrId, $me['name']]);
    $courseIds = $s->fetchAll(PDO::FETCH_COLUMN);
    $courseCount = count($courseIds);
}

$cIdsList = !empty($courseIds) ? implode(',', array_map('intval', $courseIds)) : '0';

// Analytics & Stats
$totalStudents = (int)$db->query("SELECT COUNT(DISTINCT user_id) FROM enrollments WHERE course_id IN ($cIdsList)")->fetchColumn();
$totalLessons = (int)$db->query("SELECT COUNT(*) FROM lessons WHERE course_id IN ($cIdsList)")->fetchColumn();
$totalMaterials = (int)$db->query("SELECT COUNT(*) FROM materials WHERE course_id IN ($cIdsList)")->fetchColumn();
$totalAssignments = (int)$db->query("SELECT COUNT(*) FROM assignments WHERE course_id IN ($cIdsList)")->fetchColumn();
$pendingGrading = (int)$db->query("SELECT COUNT(*) FROM assignment_submissions sub 
                                   JOIN assignments a ON a.id=sub.assignment_id 
                                   WHERE a.course_id IN ($cIdsList) AND sub.status='submitted'")->fetchColumn();
$activeDiscussions = (int)$db->query("SELECT COUNT(*) FROM course_discussions WHERE course_id IN ($cIdsList) AND parent_id IS NULL")->fetchColumn();

// Recent Submissions Needing Grading
$recentSubmissions = $db->query("SELECT sub.id, sub.submitted_at, sub.status, sub.score,
                                        u.name AS student_name, u.email AS student_email,
                                        a.title AS assignment_title, a.max_score, a.type AS assignment_type,
                                        c.title AS course_title
                                 FROM assignment_submissions sub
                                 JOIN users u ON u.id = sub.user_id
                                 JOIN assignments a ON a.id = sub.assignment_id
                                 JOIN courses c ON c.id = a.course_id
                                 WHERE a.course_id IN ($cIdsList) AND sub.status = 'submitted'
                                 ORDER BY sub.submitted_at DESC LIMIT 5")->fetchAll();

// Recent Student Questions
$recentQuestions = $db->query("SELECT d.id, d.title, d.message, d.created_at, d.course_id,
                                      u.name AS student_name, u.avatar AS student_avatar,
                                      c.title AS course_title,
                                      (SELECT COUNT(*) FROM course_discussions r WHERE r.parent_id=d.id) AS reply_count
                               FROM course_discussions d
                               JOIN users u ON u.id = d.user_id
                               JOIN courses c ON c.id = d.course_id
                               WHERE d.course_id IN ($cIdsList) AND d.parent_id IS NULL
                               ORDER BY d.created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Instructor Dashboard';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<div class="bar" style="margin-bottom:1.5rem;align-items:center;">
  <div>
    <h1 style="margin:0 0 .2rem;">Teacher &amp; Educator Dashboard</h1>
    <p style="margin:0;color:var(--muted);font-size:1.02rem;">
      Welcome back, <strong><?= e($me['name']) ?></strong>! Manage your learning materials, evaluate assignments, and guide your students.
    </p>
  </div>
  <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
    <a href="course_form.php" class="btn">+ Create Course</a>
    <a href="materials.php" class="btn" style="background:#2b6cb0;">+ Upload Material</a>
    <a href="assignments.php" class="btn" style="background:#7c3aed;">+ Add Assignment</a>
  </div>
</div>

<!-- Stats Counter Grid -->
<div class="stats" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.2rem;margin-bottom:2rem;">
  <div class="card" style="padding:1.2rem;border-left:4px solid var(--teal);margin:0;">
    <span style="font-size:.82rem;font-weight:700;color:var(--muted);text-transform:uppercase;">My Courses</span>
    <strong style="color:var(--teal);font-size:2.2rem;display:block;margin-top:.2rem;"><?= $courseCount ?></strong>
    <small style="color:var(--muted);"><?= $totalLessons ?> lessons published</small>
  </div>

  <div class="card" style="padding:1.2rem;border-left:4px solid #2563eb;margin:0;">
    <span style="font-size:.82rem;font-weight:700;color:var(--muted);text-transform:uppercase;">Enrolled Students</span>
    <strong style="color:#2563eb;font-size:2.2rem;display:block;margin-top:.2rem;"><?= $totalStudents ?></strong>
    <small style="color:var(--muted);">Active learners</small>
  </div>

  <div class="card" style="padding:1.2rem;border-left:4px solid #059669;margin:0;">
    <span style="font-size:.82rem;font-weight:700;color:var(--muted);text-transform:uppercase;">Study Materials</span>
    <strong style="color:#059669;font-size:2.2rem;display:block;margin-top:.2rem;"><?= $totalMaterials ?></strong>
    <small style="color:var(--muted);">PDFs, slides &amp; files</small>
  </div>

  <div class="card" style="padding:1.2rem;border-left:4px solid #d97706;margin:0;">
    <span style="font-size:.82rem;font-weight:700;color:var(--muted);text-transform:uppercase;">Pending Grading</span>
    <strong style="color:#d97706;font-size:2.2rem;display:block;margin-top:.2rem;"><?= $pendingGrading ?></strong>
    <small style="color:var(--muted);"><?= $totalAssignments ?> assignments/exams set</small>
  </div>

  <div class="card" style="padding:1.2rem;border-left:4px solid #9333ea;margin:0;">
    <span style="font-size:.82rem;font-weight:700;color:var(--muted);text-transform:uppercase;">Student Q&amp;A</span>
    <strong style="color:#9333ea;font-size:2.2rem;display:block;margin-top:.2rem;"><?= $activeDiscussions ?></strong>
    <small style="color:var(--muted);">Discussion threads</small>
  </div>
</div>

<!-- Two-Column Layout: Pending Submissions & Student Questions -->
<div class="two-col" style="gap:1.5rem;align-items:start;">
  
  <!-- Left Box: Assignments Needing Grading -->
  <div class="card" style="margin:0;padding:1.5rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;padding-bottom:.6rem;border-bottom:1px solid var(--line);">
      <h2 style="margin:0;font-size:1.2rem;">📝 Submissions Needing Grading</h2>
      <a href="assignments.php" style="font-size:.9rem;font-weight:700;">View All →</a>
    </div>

    <?php if (empty($recentSubmissions)): ?>
      <p class="empty" style="padding:1.5rem 0;text-align:center;">
        ✨ All caught up! No student submissions currently awaiting grading.
      </p>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:.8rem;">
        <?php foreach ($recentSubmissions as $sub): ?>
          <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.9rem 1.1rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;">
            <div>
              <div style="font-weight:700;color:var(--ink);font-size:1rem;"><?= e($sub['student_name']) ?></div>
              <div style="color:var(--teal);font-size:.85rem;font-weight:600;margin-top:2px;">
                <?= e($sub['assignment_title']) ?> <span style="color:var(--muted);">· <?= e($sub['course_title']) ?></span>
              </div>
              <small style="color:var(--muted);display:block;margin:3px 0 0;">Submitted: <?= date('M j, Y g:i A', strtotime($sub['submitted_at'])) ?></small>
            </div>
            <a href="assignments.php?grade_id=<?= (int)$sub['id'] ?>" class="btn btn-small" style="background:#d97706;flex-shrink:0;">Grade</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Right Box: Recent Student Q&A -->
  <div class="card" style="margin:0;padding:1.5rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;padding-bottom:.6rem;border-bottom:1px solid var(--line);">
      <h2 style="margin:0;font-size:1.2rem;">💬 Student Questions &amp; Interaction</h2>
      <a href="discussions.php" style="font-size:.9rem;font-weight:700;">Go to Q&amp;A Hub →</a>
    </div>

    <?php if (empty($recentQuestions)): ?>
      <p class="empty" style="padding:1.5rem 0;text-align:center;">
        No questions asked yet. Enrolled students will see their questions appear here.
      </p>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:.9rem;">
        <?php foreach ($recentQuestions as $q): ?>
          <div style="background:#fdfcfb;border:1px solid #e8e6e3;border-radius:10px;padding:1rem;">
            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:.5rem;">
              <strong style="color:var(--ink);font-size:.95rem;"><?= e($q['student_name']) ?></strong>
              <span style="font-size:.78rem;color:var(--muted);"><?= date('M j, g:i A', strtotime($q['created_at'])) ?></span>
            </div>
            <?php if (!empty($q['title'])): ?>
              <div style="font-weight:700;color:var(--teal-dark);font-size:.92rem;margin:.3rem 0 .2rem;"><?= e($q['title']) ?></div>
            <?php endif; ?>
            <p style="margin:.2rem 0 .6rem;font-size:.9rem;color:#334155;line-height:1.4;">
              <?= e(mb_strimwidth($q['message'], 0, 110, '...')) ?>
            </p>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:.82rem;">
              <span style="color:var(--muted);">Course: <em><?= e($q['course_title']) ?></em></span>
              <a href="discussions.php?thread_id=<?= (int)$q['id'] ?>" style="font-weight:700;color:var(--teal);">Reply (<?= (int)$q['reply_count'] ?>) →</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- Quick Teaching Tools Grid -->
<h2 style="margin:2.5rem 0 1rem;font-size:1.35rem;">Teacher Quick Actions</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.2rem;margin-bottom:2rem;">
  <a href="courses.php" class="card" style="padding:1.3rem;text-decoration:none;color:inherit;border-radius:12px;border:1px solid var(--line);transition:transform .2s ease,box-shadow .2s ease;">
    <div style="font-size:1.8rem;margin-bottom:.4rem;">📚</div>
    <h3 style="margin:0 0 .3rem;color:var(--teal);">Manage Courses</h3>
    <p style="margin:0;color:var(--muted);font-size:.9rem;">Edit course syllabus, pricing, levels, and view public course listing.</p>
  </a>

  <a href="lessons.php" class="card" style="padding:1.3rem;text-decoration:none;color:inherit;border-radius:12px;border:1px solid var(--line);transition:transform .2s ease,box-shadow .2s ease;">
    <div style="font-size:1.8rem;margin-bottom:.4rem;">🎬</div>
    <h3 style="margin:0 0 .3rem;color:var(--teal);">Videos &amp; Curriculum</h3>
    <p style="margin:0;color:var(--muted);font-size:.9rem;">Upload video lectures, embed video links, and organize lesson sequences.</p>
  </a>

  <a href="materials.php" class="card" style="padding:1.3rem;text-decoration:none;color:inherit;border-radius:12px;border:1px solid var(--line);transition:transform .2s ease,box-shadow .2s ease;">
    <div style="font-size:1.8rem;margin-bottom:.4rem;">📁</div>
    <h3 style="margin:0 0 .3rem;color:var(--teal);">Upload Study Materials</h3>
    <p style="margin:0;color:var(--muted);font-size:.9rem;">Upload PDFs, slide decks, source code files, and downloadable guides.</p>
  </a>

  <a href="assignments.php" class="card" style="padding:1.3rem;text-decoration:none;color:inherit;border-radius:12px;border:1px solid var(--line);transition:transform .2s ease,box-shadow .2s ease;">
    <div style="font-size:1.8rem;margin-bottom:.4rem;">📝</div>
    <h3 style="margin:0 0 .3rem;color:var(--teal);">Assignments &amp; Exams</h3>
    <p style="margin:0;color:var(--muted);font-size:.9rem;">Set homework, project assignments, quizzes, and grade student submissions.</p>
  </a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
