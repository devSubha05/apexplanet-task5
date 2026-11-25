<?php
$base = '../';
require_once __DIR__ . '/../includes/functions.php';
$me = require_instructor();
$db = db();
$isSuperAdmin = ($me['role'] === 'admin');
$instrId = (int)$me['id'];

// Handle course deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    csrf_check();
    $delId = (int)$_POST['delete'];
    
    // Check ownership
    if ($isSuperAdmin) {
        $db->prepare('DELETE FROM courses WHERE id=?')->execute([$delId]);
        flash('success', 'Course deleted successfully.');
    } else {
        $stmt = $db->prepare('DELETE FROM courses WHERE id=? AND (instructor_id=? OR instructor=?)');
        $stmt->execute([$delId, $instrId, $me['name']]);
        if ($stmt->rowCount()) {
            flash('success', 'Course deleted successfully.');
        } else {
            flash('error', 'You cannot delete this course.');
        }
    }
    redirect('courses.php');
}

// Fetch courses
if ($isSuperAdmin) {
    $rows = $db->query('SELECT c.id, c.title, c.level, c.price, c.original_price, c.instructor, cat.name AS category,
                               (SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) AS lessons,
                               (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) AS learners,
                               (SELECT COUNT(*) FROM materials m WHERE m.course_id=c.id) AS materials_count,
                               (SELECT COUNT(*) FROM assignments a WHERE a.course_id=c.id) AS assignments_count
                        FROM courses c 
                        JOIN categories cat ON cat.id=c.category_id 
                        ORDER BY c.id DESC')->fetchAll();
} else {
    $stmt = $db->prepare('SELECT c.id, c.title, c.level, c.price, c.original_price, c.instructor, cat.name AS category,
                                 (SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) AS lessons,
                                 (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) AS learners,
                                 (SELECT COUNT(*) FROM materials m WHERE m.course_id=c.id) AS materials_count,
                                 (SELECT COUNT(*) FROM assignments a WHERE a.course_id=c.id) AS assignments_count
                          FROM courses c 
                          JOIN categories cat ON cat.id=c.category_id 
                          WHERE c.instructor_id=? OR c.instructor=?
                          ORDER BY c.id DESC');
    $stmt->execute([$instrId, $me['name']]);
    $rows = $stmt->fetchAll();
}

$pageTitle = 'My Courses · Instructor Panel';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<div class="bar" style="margin-bottom:1.5rem;">
  <div>
    <h1 style="margin:0 0 .3rem;">My Courses</h1>
    <p style="margin:0;color:var(--muted);">Manage your curriculum, upload lesson videos, and attach study resources.</p>
  </div>
  <a class="btn" href="course_form.php">+ Create New Course</a>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Course Title</th>
        <th>Category</th>
        <th>Level</th>
        <th>Price</th>
        <th>Lessons</th>
        <th>Learners</th>
        <th>Materials</th>
        <th>Assignments</th>
        <th style="text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr>
          <td colspan="9" class="empty" style="text-align:center;padding:2.5rem 1rem;">
            No courses found. <a href="course_form.php">Create your first course</a> to start teaching.
          </td>
        </tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <strong><a href="../course.php?id=<?= (int)$r['id'] ?>" target="_blank" style="text-decoration:none;color:var(--ink);"><?= e($r['title']) ?></a></strong>
            <?php if ($isSuperAdmin): ?><small style="color:var(--muted);display:block;">By: <?= e($r['instructor']) ?></small><?php endif; ?>
          </td>
          <td><span class="tag"><?= e($r['category']) ?></span></td>
          <td><span style="font-size:.85rem;color:var(--muted);"><?= e($r['level']) ?></span></td>
          <td><strong>₹<?= number_format((float)$r['price'], 2) ?></strong></td>
          <td><a href="lessons.php?cid=<?= (int)$r['id'] ?>" style="font-weight:700;color:var(--teal);"><?= (int)$r['lessons'] ?> lessons</a></td>
          <td><a href="students.php?cid=<?= (int)$r['id'] ?>" style="color:#2563eb;font-weight:600;"><?= (int)$r['learners'] ?></a></td>
          <td><a href="materials.php?cid=<?= (int)$r['id'] ?>" style="color:#059669;font-weight:600;"><?= (int)$r['materials_count'] ?> files</a></td>
          <td><a href="assignments.php?cid=<?= (int)$r['id'] ?>" style="color:#7c3aed;font-weight:600;"><?= (int)$r['assignments_count'] ?> tasks</a></td>
          <td style="text-align:right;">
            <div class="actions" style="justify-content:flex-end;">
              <a href="course_form.php?id=<?= (int)$r['id'] ?>" class="btn btn-small" style="background:#475569;">Edit</a>
              <a href="lessons.php?cid=<?= (int)$r['id'] ?>" class="btn btn-small">Curriculum</a>
              <form method="post" onsubmit="return confirm('Delete this course and all its lessons, materials, and assignments?');" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="delete" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-small btn-danger" type="submit">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
