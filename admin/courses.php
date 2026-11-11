<?php
$base = '../'; require_once __DIR__ . '/../includes/functions.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    csrf_check();
    db()->prepare('DELETE FROM courses WHERE id=?')->execute([(int)$_POST['delete']]);
    flash('success', 'Course deleted.'); redirect('courses.php');
}
$rows = db()->query('SELECT c.id,c.title,c.level,cat.name category,(SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) lessons,(SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) learners FROM courses c JOIN categories cat ON cat.id=c.category_id ORDER BY c.id DESC')->fetchAll();
$pageTitle = 'Manage courses'; require __DIR__ . '/../includes/header.php'; require __DIR__ . '/nav.php'; ?>
<div class="bar"><h1>Courses</h1><a class="btn" href="course_form.php">Add course</a></div>
<div class="table-wrap"><table>
<thead><tr><th>Title</th><th>Category</th><th>Level</th><th>Lessons</th><th>Enrolled</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= e($r['title']) ?></td><td><?= e($r['category']) ?></td><td><?= e($r['level']) ?></td><td><?= (int)$r['lessons'] ?></td><td><?= (int)$r['learners'] ?></td>
<td class="actions"><a href="lessons.php?course_id=<?= (int)$r['id'] ?>">Lessons</a><a href="course_form.php?id=<?= (int)$r['id'] ?>">Edit</a>
<form method="post" onsubmit="return confirm('Delete this course and all its lessons?')"><?= csrf_field() ?><button class="link-btn danger-text" name="delete" value="<?= (int)$r['id'] ?>">Delete</button></form></td></tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="empty">No courses yet. Add the first one.</td></tr><?php endif; ?>
</tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
