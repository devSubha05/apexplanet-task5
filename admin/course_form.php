<?php
$base = '../'; require_once __DIR__ . '/../includes/functions.php'; require_admin();
$id = (int)($_GET['id'] ?? 0);
$c = ['title' => '', 'description' => '', 'category_id' => 0, 'level' => 'Beginner', 'instructor' => '', 'duration_hours' => '1.0'];
if ($id) { $s = db()->prepare('SELECT * FROM courses WHERE id=?'); $s->execute([$id]); $c = $s->fetch() ?: redirect('courses.php'); }
$cats = db()->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $c = ['title' => trim($_POST['title'] ?? ''), 'description' => trim($_POST['description'] ?? ''), 'category_id' => (int)($_POST['category_id'] ?? 0),
          'level' => $_POST['level'] ?? '', 'instructor' => trim($_POST['instructor'] ?? ''), 'duration_hours' => $_POST['duration_hours'] ?? ''];
    if ($c['title'] === '' || mb_strlen($c['title']) > 120) $errors[] = 'Title is required (up to 120 characters).';
    if ($c['description'] === '') $errors[] = 'Add a description.';
    if (!in_array($c['category_id'], array_column($cats, 'id'))) $errors[] = 'Pick a category.';
    if (!in_array($c['level'], ['Beginner', 'Intermediate', 'Advanced'], true)) $errors[] = 'Pick a level.';
    if ($c['instructor'] === '') $errors[] = 'Add an instructor name.';
    if (!is_numeric($c['duration_hours']) || $c['duration_hours'] <= 0 || $c['duration_hours'] > 999) $errors[] = 'Duration must be a positive number of hours.';
    if (!$errors) {
        $v = [$c['title'], $c['description'], $c['category_id'], $c['level'], $c['instructor'], $c['duration_hours']];
        if ($id) { db()->prepare('UPDATE courses SET title=?,description=?,category_id=?,level=?,instructor=?,duration_hours=? WHERE id=?')->execute([...$v, $id]); }
        else { db()->prepare('INSERT INTO courses (title,description,category_id,level,instructor,duration_hours) VALUES (?,?,?,?,?,?)')->execute($v); $id = (int)db()->lastInsertId(); }
        flash('success', 'Course saved. Now add its lessons.'); redirect("lessons.php?course_id=$id");
    }
}
$pageTitle = $id ? 'Edit course' : 'Add course'; require __DIR__ . '/../includes/header.php'; require __DIR__ . '/nav.php'; ?>
<section class="card narrow">
<h1><?= $id ? 'Edit course' : 'Add course' ?></h1>
<?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>
<form method="post"><?= csrf_field() ?>
  <label>Title <input name="title" value="<?= e($c['title']) ?>" required maxlength="120"></label>
  <label>Description <textarea name="description" rows="5" required><?= e($c['description']) ?></textarea></label>
  <label>Category <select name="category_id"><?php foreach ($cats as $k): ?><option value="<?= (int)$k['id'] ?>" <?= $k['id'] == $c['category_id'] ? 'selected' : '' ?>><?= e($k['name']) ?></option><?php endforeach; ?></select></label>
  <label>Level <select name="level"><?php foreach (['Beginner', 'Intermediate', 'Advanced'] as $lv): ?><option <?= $lv === $c['level'] ? 'selected' : '' ?>><?= $lv ?></option><?php endforeach; ?></select></label>
  <label>Instructor <input name="instructor" value="<?= e($c['instructor']) ?>" required maxlength="80"></label>
  <label>Duration in hours <input type="number" name="duration_hours" step="0.5" min="0.5" value="<?= e($c['duration_hours']) ?>" required></label>
  <button class="btn">Save course</button>
</form></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
