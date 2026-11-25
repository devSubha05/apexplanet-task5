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

// Fetch enrolled students
$query = "SELECT u.id AS user_id, u.name AS student_name, u.email AS student_email, u.avatar,
                 e.enrolled_at, c.id AS course_id, c.title AS course_title
          FROM enrollments e
          JOIN users u ON u.id = e.user_id
          JOIN courses c ON c.id = e.course_id
          WHERE c.id IN ($cIdsList)";

$params = [];
if ($cid > 0) {
    $query .= " AND c.id = ?";
    $params[] = $cid;
}
$query .= " ORDER BY e.enrolled_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle = 'Enrolled Students · Instructor Panel';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<div class="bar" style="margin-bottom:1.5rem;">
  <div>
    <h1 style="margin:0 0 .3rem;">Enrolled Students</h1>
    <p style="margin:0;color:var(--muted);">Track student engagement, course progression, and completion rates.</p>
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

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Student Name</th>
        <th>Course</th>
        <th>Enrolled Date</th>
        <th>Progress</th>
        <th style="text-align:right;">Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($students)): ?>
        <tr>
          <td colspan="5" class="empty" style="text-align:center;padding:2.5rem 1rem;">
            No student enrollments found matching the selected filter.
          </td>
        </tr>
      <?php endif; ?>

      <?php foreach ($students as $st): 
        $prog = course_progress((int)$st['user_id'], (int)$st['course_id']);
      ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:.7rem;">
              <div style="width:34px;height:34px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem;">
                <?= strtoupper(substr($st['student_name'], 0, 1)) ?>
              </div>
              <div>
                <strong><?= e($st['student_name']) ?></strong>
                <small style="color:var(--muted);display:block;"><?= e($st['student_email']) ?></small>
              </div>
            </div>
          </td>
          <td>
            <a href="../course.php?id=<?= (int)$st['course_id'] ?>" target="_blank" style="color:var(--ink);font-weight:600;text-decoration:none;">
              <?= e($st['course_title']) ?>
            </a>
          </td>
          <td>
            <span style="font-size:.88rem;color:var(--muted);"><?= date('M j, Y', strtotime($st['enrolled_at'])) ?></span>
          </td>
          <td style="min-width:180px;">
            <div style="display:flex;justify-content:space-between;font-size:.8rem;font-weight:700;margin-bottom:3px;">
              <span><?= $prog['done'] ?> / <?= $prog['total'] ?> lessons</span>
              <span style="color:var(--teal);"><?= $prog['pct'] ?>%</span>
            </div>
            <div class="progress" style="margin:0;height:7px;">
              <span style="width:<?= $prog['pct'] ?>%;"></span>
            </div>
          </td>
          <td style="text-align:right;">
            <?php if ($prog['pct'] === 100): ?>
              <span class="tag" style="background:#e6f4ea;color:#0e6b63;font-weight:700;padding:3px 8px;border-radius:99px;font-size:.78rem;">Completed 🎓</span>
            <?php elseif ($prog['pct'] > 0): ?>
              <span class="tag" style="background:#e0f2fe;color:#0369a1;font-weight:700;padding:3px 8px;border-radius:99px;font-size:.78rem;">In Progress</span>
            <?php else: ?>
              <span class="tag" style="background:#f1f5f9;color:#64748b;font-weight:700;padding:3px 8px;border-radius:99px;font-size:.78rem;">Just Started</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
